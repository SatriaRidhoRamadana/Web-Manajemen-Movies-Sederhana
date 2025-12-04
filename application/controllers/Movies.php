<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Movies extends CI_Controller {

    private $upload_path;

    public function __construct() {
        parent::__construct();
        $this->load->model('Movie_model');
        $this->load->model('User_model');
        $this->load->helper(array('form', 'url'));
        $this->load->library(array('form_validation', 'session'));

        // Token-based auth: accept Authorization: Bearer <token> or session user_id for web UI
        $this->load->helper('jwt');
        $jwt_key = $this->config->item('jwt_key');

        $auth_header = $this->input->get_request_header('Authorization', TRUE);
        $token = null;
        if (!empty($auth_header) && stripos($auth_header, 'Bearer ') === 0) {
            $token = substr($auth_header, 7);
        }

        $user = null;
            if (!empty($token)) {
                $payload = jwt_decode($token, $jwt_key);
                if ($payload && isset($payload['sub'])) {
                    $this->load->model('Token_model');
                    if (isset($payload['jti']) && $this->Token_model->is_revoked($payload['jti'])) {
                        $user = null;
                    } else {
                        $user = $this->User_model->get_user((int)$payload['sub']);
                    }
                }
            }

        // fallback to session (for web UI)
        if (!$user && $this->session->userdata('user_id')) {
            $user = $this->User_model->get_user((int)$this->session->userdata('user_id'));
        }

        // Only admin may use Movies management
        if ($user && empty($user['is_admin'])) {
            $user = null;
        }

        if (!$user) {
            if (!empty($auth_header)) {
                // API client provided Authorization header -> return JSON 401/403
                $this->output->set_content_type('application/json')->set_status_header(403)->set_output(json_encode([
                    'status' => false,
                    'error'  => 'Forbidden'
                ]));
                exit;
            } else {
                // No header or not admin -> redirect to login for web UI
                redirect('auth/login');
                exit;
            }
        }

        // store current user for controller use
        $this->current_user = $user;

        $this->upload_path = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'posters' . DIRECTORY_SEPARATOR;
        if (!is_dir($this->upload_path)) {
            mkdir($this->upload_path, 0775, true);
        }
    }

    public function index() {
        $data['movies'] = $this->Movie_model->get_all_movies();
        $data['messages'] = $this->session->flashdata('messages');
        $this->load->view('movies/index', $data);
    }

    public function create() {
        $this->set_validation_rules();

        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('messages', array(
                'type'    => 'error',
                'content' => validation_errors()
            ));
            redirect('movies');
            return;
        }

        $movie_data = $this->collect_movie_data();

        if ($movie_data === false) {
            redirect('movies');
            return;
        }

        if ($this->Movie_model->insert_movie($movie_data)) {
            $this->session->set_flashdata('messages', array(
                'type'    => 'success',
                'content' => 'Movie created successfully.'
            ));
        } else {
            $this->session->set_flashdata('messages', array(
                'type'    => 'error',
                'content' => 'Failed to create movie.'
            ));
        }

        redirect('movies');
    }

    public function edit($id = null) {
        if ($id === null || !is_numeric($id)) {
            show_404();
        }

        $movie = $this->Movie_model->get_movie($id);
        if (!$movie) {
            show_404();
        }

        $this->set_validation_rules(true);

        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('messages', array(
                'type'    => 'error',
                'content' => validation_errors()
            ));
            redirect('movies');
            return;
        }

        $movie_data = $this->collect_movie_data(true, $movie);

        if ($movie_data === false) {
            redirect('movies');
            return;
        }

        if ($this->Movie_model->update_movie($id, $movie_data)) {
            $this->session->set_flashdata('messages', array(
                'type'    => 'success',
                'content' => 'Movie updated successfully.'
            ));
        } else {
            $this->session->set_flashdata('messages', array(
                'type'    => 'error',
                'content' => 'Failed to update movie.'
            ));
        }

        redirect('movies');
    }

    public function delete($id = null) {
        if ($id === null || !is_numeric($id)) {
            show_404();
        }

        $movie = $this->Movie_model->get_movie($id);
        if (!$movie) {
            show_404();
        }

        if (!empty($movie['poster_url'])) {
            $this->delete_poster($movie['poster_url']);
        }

        if ($this->Movie_model->delete_movie($id)) {
            $this->session->set_flashdata('messages', array(
                'type'    => 'success',
                'content' => 'Movie deleted successfully.'
            ));
        } else {
            $this->session->set_flashdata('messages', array(
                'type'    => 'error',
                'content' => 'Failed to delete movie.'
            ));
        }

        redirect('movies');
    }

    private function set_validation_rules($is_update = false) {
        $this->form_validation->set_rules('title', 'Title', 'required');
        $this->form_validation->set_rules('duration', 'Duration', 'required|integer|greater_than[0]');
        $this->form_validation->set_rules('genre', 'Genre', 'required');
        $this->form_validation->set_rules('release_date', 'Release Date', 'required');
        $this->form_validation->set_rules('description', 'Description', 'trim');
    }

    private function collect_movie_data($is_update = false, $existing_movie = null) {
        $data = array(
            'title'        => $this->input->post('title', true),
            'description'  => $this->input->post('description', true),
            'duration'     => (int) $this->input->post('duration', true),
            'genre'        => $this->input->post('genre', true),
            'release_date' => $this->input->post('release_date', true),
            'poster_url'   => $existing_movie ? $existing_movie['poster_url'] : null
        );

        if (!empty($_FILES['poster']['name'])) {
            $upload = $this->handle_upload();
            if ($upload['status']) {
                if ($existing_movie && !empty($existing_movie['poster_url'])) {
                    $this->delete_poster($existing_movie['poster_url']);
                }
                $data['poster_url'] = $upload['path'];
            } else {
                $this->session->set_flashdata('messages', array(
                    'type'    => 'error',
                    'content' => $upload['message']
                ));
                return false;
            }
        }

        return $data;
    }

    private function handle_upload() {
        $config = array(
            'upload_path'   => $this->upload_path,
            'allowed_types' => 'jpg|jpeg|png|gif',
            'max_size'      => 2048,
            'encrypt_name'  => true
        );

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('poster')) {
            return array(
                'status'  => false,
                'message' => $this->upload->display_errors('', '')
            );
        }

        $data = $this->upload->data();
        $relative_path = 'uploads/posters/' . $data['file_name'];

        return array(
            'status' => true,
            'path'   => $relative_path
        );
    }

    private function delete_poster($relative_path) {
        $full_path = FCPATH . $relative_path;
        if (is_file($full_path)) {
            @unlink($full_path);
        }
    }
}