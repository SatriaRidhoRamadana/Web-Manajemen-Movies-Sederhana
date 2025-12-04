<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('User_model');
        $this->load->helper(array('form', 'url'));
        $this->load->library(array('form_validation', 'session'));
    }

    public function login() {
        if ($this->input->method() === 'post') {
            try {
            $this->form_validation->set_rules('name','Name','required');
            $this->form_validation->set_rules('password','Password','required');

            if ($this->form_validation->run() === false) {
                $this->output->set_content_type('application/json')->set_output(json_encode([
                    'status' => false, 'error' => validation_errors()
                ]));
                return;
            }

            $name = $this->input->post('name', true);
            $password = $this->input->post('password', true);
            // lookup by `name` (display/login name)
            $user = $this->User_model->get_by_username($name);

            $password_ok = false;
            if ($user && isset($user['password'])) {
                $stored = $user['password'];
                // First, try password_verify for hashed passwords
                if (function_exists('password_verify') && @password_verify($password, $stored)) {
                    $password_ok = true;
                // Fallback: if stored password is plaintext (legacy), compare directly
                } elseif ($password === $stored) {
                    $password_ok = true;
                }
            }

            if (!$user || !$password_ok) {
                // log debug info (no sensitive full password logging)
                log_message('debug', 'Auth login failed for name=' . (is_string($name) ? $name : ''));
                $this->output->set_status_header(401)->set_content_type('application/json')->set_output(json_encode([
                    'status' => false,
                    'error' => 'Invalid credentials'
                ]));
                return;
            }

            // create JWT for valid user
            $this->load->helper('jwt');
            $jwt_key = $this->config->item('jwt_key');
            $payload = array(
                'sub' => (int)$user['id'],
                'name' => isset($user['name']) ? $user['name'] : null,
                'email' => isset($user['email']) ? $user['email'] : null
            );
            $expires = 3600; // 1 hour
            $token = jwt_encode($payload, $jwt_key, $expires);

            // store minimal session data for web UI, but don't store token server-side
            $this->session->set_userdata('user_id', $user['id']);

            // set token also in response header so it is visible in Network -> Response Headers
            $this->output->set_header('X-Auth-Token: ' . $token);
            $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate');

            // set HttpOnly cookie for browser clients (more secure)
            @setcookie('api_token', $token, time() + $expires, '/', '', false, true);

            $this->output->set_content_type('application/json')->set_output(json_encode([
                'status' => true,
                'message' => 'Login successful',
                'token' => $token,
                'expires_in' => $expires,
                'user' => array('id' => $user['id'], 'name' => isset($user['name']) ? $user['name'] : null, 'email' => isset($user['email']) ? $user['email'] : null)
            ]));
            return;
            } catch (Throwable $e) {
                // return safe error message and log details
                log_message('error', 'Auth::login exception: ' . $e->getMessage());
                $this->output->set_status_header(500)->set_content_type('application/json')->set_output(json_encode([
                    'status' => false,
                    'error' => 'Server error'
                ]));
                return;
            }
        }

        // GET -> show simple login form (for browser)
        $this->load->view('auth/login');
    }

    public function logout() {
        // revoke current token (if provided)
        $this->load->model('Token_model');
        $auth_header = $this->input->get_request_header('Authorization', TRUE);
        $token = null;
        if (!empty($auth_header) && stripos($auth_header, 'Bearer ') === 0) {
            $token = substr($auth_header, 7);
        } elseif (!empty($_COOKIE['api_token'])) {
            $token = $_COOKIE['api_token'];
        }

        if (!empty($token)) {
            $this->load->helper('jwt');
            $jwt_key = $this->config->item('jwt_key');
            $payload = jwt_decode($token, $jwt_key);
            if ($payload && isset($payload['jti'])) {
                $this->Token_model->revoke_jti($payload['jti'], isset($payload['exp']) ? $payload['exp'] : null);
            }
        }

        // clear cookie and session
        @setcookie('api_token', '', time() - 3600, '/', '', false, true);
        $this->session->unset_userdata(array('user_id'));
        redirect('auth/login');
    }

    // Refresh JWT: revoke old token and issue new one
    public function refresh() {
        $this->load->helper('jwt');
        $this->load->model('Token_model');
        $jwt_key = $this->config->item('jwt_key');

        $auth_header = $this->input->get_request_header('Authorization', TRUE);
        $token = null;
        if (!empty($auth_header) && stripos($auth_header, 'Bearer ') === 0) {
            $token = substr($auth_header, 7);
        } elseif (!empty($_COOKIE['api_token'])) {
            $token = $_COOKIE['api_token'];
        }

        if (empty($token)) {
            $this->output->set_status_header(401)->set_content_type('application/json')->set_output(json_encode(['status' => false, 'error' => 'Unauthorized']));
            return;
        }

        $payload = jwt_decode($token, $jwt_key);
        if (!$payload || !isset($payload['sub'])) {
            $this->output->set_status_header(401)->set_content_type('application/json')->set_output(json_encode(['status' => false, 'error' => 'Invalid token']));
            return;
        }

        // revoke old
        if (isset($payload['jti'])) {
            $this->Token_model->revoke_jti($payload['jti'], isset($payload['exp']) ? $payload['exp'] : null);
        }

        // issue new token
        $this->load->model('User_model');
        $user = $this->User_model->get_user((int)$payload['sub']);
        if (!$user) {
            $this->output->set_status_header(404)->set_content_type('application/json')->set_output(json_encode(['status' => false, 'error' => 'User not found']));
            return;
        }

        $new_payload = array('sub' => (int)$user['id'], 'name' => isset($user['name']) ? $user['name'] : null, 'email' => isset($user['email']) ? $user['email'] : null);
        $new_token = jwt_encode($new_payload, $jwt_key, 3600);
        @setcookie('api_token', $new_token, time() + 3600, '/', '', false, true);

        $this->output->set_content_type('application/json')->set_output(json_encode(['status' => true, 'token' => $new_token, 'expires_in' => 3600]));
    }
}
