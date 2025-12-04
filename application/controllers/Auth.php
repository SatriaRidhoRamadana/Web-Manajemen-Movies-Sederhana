<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Controller `Auth` menangani autentikasi: login, logout, refresh token.
// Menghasilkan JWT pada saat login dan mencabut (revoke) token pada logout/refresh.
class Auth extends CI_Controller {

    public function __construct() {
        // Panggil constructor parent
        parent::__construct();
        // Muat model dan helper/library yang diperlukan untuk autentikasi
        $this->load->model('User_model');
        $this->load->helper(array('form', 'url'));
        $this->load->library(array('form_validation', 'session'));
    }

    public function login() {
        // Jika request POST maka proses login API
        if ($this->input->method() === 'post') {
            try {
            // Validasi input
            $this->form_validation->set_rules('name','Name','required');
            $this->form_validation->set_rules('password','Password','required');

            if ($this->form_validation->run() === false) {
                // Kembalikan error validasi sebagai JSON
                $this->output->set_content_type('application/json')->set_output(json_encode([
                    'status' => false, 'error' => validation_errors()
                ]));
                return;
            }

            // Ambil credential dari request
            $name = $this->input->post('name', true);
            $password = $this->input->post('password', true);
            // Cari user berdasarkan kolom username/name
            $user = $this->User_model->get_by_username($name);

            // Verifikasi password (coba hashed kemudian fallback plaintext legacy)
            $password_ok = false;
            if ($user && isset($user['password'])) {
                $stored = $user['password'];
                if (function_exists('password_verify') && @password_verify($password, $stored)) {
                    $password_ok = true;
                } elseif ($password === $stored) {
                    $password_ok = true;
                }
            }

            if (!$user || !$password_ok) {
                // Login gagal — catat debug dan kembalikan 401
                log_message('debug', 'Auth login failed for name=' . (is_string($name) ? $name : ''));
                $this->output->set_status_header(401)->set_content_type('application/json')->set_output(json_encode([
                    'status' => false,
                    'error' => 'Invalid credentials'
                ]));
                return;
            }

            // Buat JWT untuk user yang valid
            $this->load->helper('jwt');
            $jwt_key = $this->config->item('jwt_key');
            $payload = array(
                'sub' => (int)$user['id'],
                'name' => isset($user['name']) ? $user['name'] : null,
                'email' => isset($user['email']) ? $user['email'] : null
            );
            $expires = 3600; // 1 jam
            $token = jwt_encode($payload, $jwt_key, $expires);

            // Simpan session minimal untuk UI web (tidak menyimpan token server-side)
            $this->session->set_userdata('user_id', $user['id']);

            // Sertakan token juga di header respon (berguna untuk debugging)
            $this->output->set_header('X-Auth-Token: ' . $token);
            $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate');

            // Set cookie HttpOnly untuk klien browser (lebih aman untuk UI)
            @setcookie('api_token', $token, time() + $expires, '/', '', false, true);

            // Kembalikan JSON berisi token dan informasi user
            $this->output->set_content_type('application/json')->set_output(json_encode([
                'status' => true,
                'message' => 'Login successful',
                'token' => $token,
                'expires_in' => $expires,
                'user' => array('id' => $user['id'], 'name' => isset($user['name']) ? $user['name'] : null, 'email' => isset($user['email']) ? $user['email'] : null)
            ]));
            return;
            } catch (Throwable $e) {
                // Tangani exception dengan aman dan catat log
                log_message('error', 'Auth::login exception: ' . $e->getMessage());
                $this->output->set_status_header(500)->set_content_type('application/json')->set_output(json_encode([
                    'status' => false,
                    'error' => 'Server error'
                ]));
                return;
            }
        }

        // Jika bukan POST, tampilkan form login sederhana untuk browser
        $this->load->view('auth/login');
    }

    public function logout() {
        // Revoke token saat logout jika token disediakan
        $this->load->model('Token_model');
        $auth_header = $this->input->get_request_header('Authorization', TRUE);
        $token = null;
        if (!empty($auth_header) && stripos($auth_header, 'Bearer ') === 0) {
            $token = substr($auth_header, 7);
        } elseif (!empty($_COOKIE['api_token'])) {
            // Fallback cookie (digunakan untuk UI web saja)
            $token = $_COOKIE['api_token'];
        }

        if (!empty($token)) {
            $this->load->helper('jwt');
            $jwt_key = $this->config->item('jwt_key');
            $payload = jwt_decode($token, $jwt_key);
            if ($payload && isset($payload['jti'])) {
                // Masukkan jti ke tabel revoked_tokens
                $this->Token_model->revoke_jti($payload['jti'], isset($payload['exp']) ? $payload['exp'] : null);
            }
        }

        // Hapus cookie dan session, lalu redirect ke halaman login
        @setcookie('api_token', '', time() - 3600, '/', '', false, true);
        $this->session->unset_userdata(array('user_id'));
        redirect('auth/login');
    }

    // Refresh JWT: revoke old token and issue new one
    // Refresh token: cabut token lama dan buat token baru
    public function refresh() {
        $this->load->helper('jwt');
        $this->load->model('Token_model');
        $jwt_key = $this->config->item('jwt_key');

        // Ambil token dari header atau cookie (cookie untuk UI)
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

        // Decode dan verifikasi token lama
        $payload = jwt_decode($token, $jwt_key);
        if (!$payload || !isset($payload['sub'])) {
            $this->output->set_status_header(401)->set_content_type('application/json')->set_output(json_encode(['status' => false, 'error' => 'Invalid token']));
            return;
        }

        // Revoke token lama jika memiliki jti
        if (isset($payload['jti'])) {
            $this->Token_model->revoke_jti($payload['jti'], isset($payload['exp']) ? $payload['exp'] : null);
        }

        // Buat token baru untuk user yang sama
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
