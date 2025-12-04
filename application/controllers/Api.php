<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Controller `Api` bertanggung jawab untuk endpoint API (movies, users).
// Setiap method publik di controller ini dipetakan dari route `api/*`.
// Controller ini memvalidasi JWT, memeriksa revocation, dan memastikan user adalah admin.
class Api extends CI_Controller {

    public function __construct() {
        // Panggil constructor parent
        parent::__construct();

        // Load model dan library yang diperlukan
        $this->load->model('Movie_model');
        $this->load->model('User_model');
        $this->load->library('form_validation');
        // Semua response API dikembalikan sebagai JSON
        $this->output->set_content_type('application/json');
        $this->load->database();

        // Cek token untuk request API (format: Authorization: Bearer <token>)
        // Beberapa server (terutama konfigurasi Apache) dapat menghapus header Authorization,
        // sehingga kita mencoba beberapa sumber untuk menemukan token.
        $token = null;

        // 1) Ambil header Authorization standar via CI
        $auth_header = $this->input->get_request_header('Authorization', TRUE);
        if (!empty($auth_header)) {
            if (stripos($auth_header, 'Bearer ') === 0) {
                // Jika berformat 'Bearer <token>' maka ambil token tanpa kata 'Bearer '
                $token = substr($auth_header, 7);
            } else {
                // Jika header berisi langsung token, gunakan apa adanya
                $token = $auth_header;
            }
        }

        // 2) Fallback ke variabel server umum (HTTP_AUTHORIZATION / REDIRECT_HTTP_AUTHORIZATION)
        if (empty($token) && !empty($_SERVER['HTTP_AUTHORIZATION'])) {
            $h = $_SERVER['HTTP_AUTHORIZATION'];
            if (stripos($h, 'Bearer ') === 0) $token = substr($h, 7); else $token = $h;
        }
        if (empty($token) && !empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $h = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            if (stripos($h, 'Bearer ') === 0) $token = substr($h, 7); else $token = $h;
        }

        // 3) Header alternatif yang kadang digunakan untuk debugging
        if (empty($token)) {
            $x = $this->input->get_request_header('X-Auth-Token', TRUE);
            if (!empty($x)) $token = $x;
        }

        // 4) Query parameter fallback (TIDAK direkomendasikan untuk production)
        if (empty($token)) {
            $q = $this->input->get('token', TRUE);
            if (!empty($q)) $token = $q;
        }

        // Catatan: cookie fallback sengaja dihapus; API memerlukan header Authorization eksplisit.

        // Normalisasi token ke string atau null
        $token = is_string($token) ? trim($token) : null;

        // Decode JWT stateless dan muat data user berdasarkan klaim sub
        $this->load->helper('jwt');
        $jwt_key = $this->config->item('jwt_key');
        $user = null;
        if (!empty($token)) {
            log_message('debug', 'Api token length=' . strlen($token));
            $payload = jwt_decode($token, $jwt_key);
            if (!$payload) {
                log_message('debug', 'Api jwt_decode returned null or invalid');
            }
            if ($payload && isset($payload['sub'])) {
                // Periksa apakah token sudah dicabut (revoked)
                $this->load->model('Token_model');
                if (isset($payload['jti']) && $this->Token_model->is_revoked($payload['jti'])) {
                    log_message('debug', 'Api auth failed: token revoked jti=' . $payload['jti']);
                } else {
                    // Muat user dari DB
                    $user = $this->User_model->get_user((int)$payload['sub']);
                    // Untuk API ini hanya admin yang diizinkan mengakses
                    if ($user && empty($user['is_admin'])) {
                        log_message('debug', 'Api auth failed: user not admin id=' . (int)$payload['sub']);
                        $user = null; // perlakukan sebagai unauthorized
                    }
                }
            }
        }

        if (!$user) {
            log_message('debug', 'Api auth failed; token present=' . (!empty($token) ? 'yes' : 'no'));
            $this->output->set_status_header(401)->set_output(json_encode([
                'status' => 'error',
                'message' => 'Unauthorized'
            ]));
            exit;
        }

        // Simpan user saat ini agar bisa diakses oleh method lain
        $this->current_user = $user;
    }

    // Movies endpoints
    public function movies() {
        $method = $this->input->method();

        switch ($method) {
            case 'get':
                $this->get_movies();
                break;
            case 'post':
                $this->create_movie();
                break;
            default:
                $this->output->set_status_header(405);
                echo json_encode(['status' => 'error', 'message' => 'Metode tidak diizinkan']);
        }
    }

    public function movies_detail($id) {
        $method = $this->input->method();

        switch ($method) {
            case 'get':
                $this->get_movie($id);
                break;
            case 'put':
                $this->update_movie($id);
                break;
            case 'delete':
                $this->delete_movie($id);
                break;
            default:
                $this->output->set_status_header(405);
                echo json_encode(['status' => 'error', 'message' => 'Metode tidak diizinkan']);
        }
    }

    

    // Users endpoints
    public function users() {
        $method = $this->input->method();

        switch ($method) {
            case 'get':
                $this->get_users();
                break;
            case 'post':
                $this->create_user();
                break;
            default:
                $this->output->set_status_header(405);
                echo json_encode(['status' => 'error', 'message' => 'Metode tidak diizinkan']);
        }
    }

    public function users_detail($id) {
        $method = $this->input->method();

        switch ($method) {
            case 'get':
                $this->get_user($id);
                break;
            case 'put':
                $this->update_user($id);
                break;
            case 'delete':
                $this->delete_user($id);
                break;
            default:
                $this->output->set_status_header(405);
                echo json_encode(['status' => 'error', 'message' => 'Metode tidak diizinkan']);
        }
    }

    // Implementation of methods
    private function get_movies() {
        try {
            $movies = $this->Movie_model->get_all_movies();
            echo json_encode(['status' => 'success', 'data' => $movies]);
        } catch (Exception $e) {
            $this->output->set_status_header(500);
            echo json_encode(['status' => 'error', 'message' => 'Kesalahan database: ' . $e->getMessage()]);
        }
    }

    private function get_movie($id) {
        try {
            $movie = $this->Movie_model->get_movie($id);
            if ($movie) {
                echo json_encode(['status' => 'success', 'data' => $movie]);
            } else {
                $this->output->set_status_header(404);
                echo json_encode(['status' => 'error', 'message' => 'Film tidak ditemukan']);
            }
        } catch (Exception $e) {
            $this->output->set_status_header(500);
            echo json_encode(['status' => 'error', 'message' => 'Kesalahan database: ' . $e->getMessage()]);
        }
    }

    private function create_movie() {
        $data = json_decode(file_get_contents('php://input'), true);

        if ($data === null) {
            $this->output->set_status_header(400);
            echo json_encode(['status' => 'error', 'message' => 'Data JSON tidak valid']);
            return;
        }

        $this->form_validation->set_data($data);
        $this->form_validation->set_rules('title', 'Title', 'required');
        $this->form_validation->set_rules('duration', 'Duration', 'required|integer');

        if ($this->form_validation->run() == FALSE) {
            $this->output->set_status_header(400);
            echo json_encode(['status' => 'error', 'message' => validation_errors()]);
            return;
        }

        if ($this->Movie_model->insert_movie($data)) {
            echo json_encode(['status' => 'success', 'message' => 'Film berhasil dibuat']);
        } else {
            $this->output->set_status_header(500);
            echo json_encode(['status' => 'error', 'message' => 'Gagal membuat film']);
        }
    }

    private function update_movie($id) {
        $data = json_decode(file_get_contents('php://input'), true);

        if ($this->Movie_model->update_movie($id, $data)) {
            echo json_encode(['status' => 'success', 'message' => 'Film berhasil diperbarui']);
        } else {
            $this->output->set_status_header(500);
            echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui film']);
        }
    }

    private function delete_movie($id) {
        if ($this->Movie_model->delete_movie($id)) {
            echo json_encode(['status' => 'success', 'message' => 'Film berhasil dihapus']);
        } else {
            $this->output->set_status_header(500);
            echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus film']);
        }
    }
    

    private function get_users() {
        $req_id = $this->input->get('id', TRUE);
        if (!empty($req_id)) {
            $req_id = (int)$req_id;
            // Only allow fetching a single user if it's the current authenticated user
            if (isset($this->current_user['id']) && (int)$this->current_user['id'] === $req_id) {
                $user = $this->User_model->get_user($req_id);
                if ($user) {
                    echo json_encode(['status' => 'success', 'data' => $user]);
                } else {
                    $this->output->set_status_header(404)->set_output(json_encode(['status' => 'error', 'message' => 'Pengguna tidak ditemukan']));
                }
            } else {
                $this->output->set_status_header(403)->set_output(json_encode(['status' => 'error', 'message' => 'Forbidden']));
            }
            return;
        }

        $users = $this->User_model->get_all_users();
        echo json_encode(['status' => 'success', 'data' => $users]);
    }

    private function get_user($id) {
        try {
            $user = $this->User_model->get_user($id);
            if ($user) {
                echo json_encode(['status' => 'success', 'data' => $user]);
            } else {
                $this->output->set_status_header(404);
                echo json_encode(['status' => 'error', 'message' => 'Pengguna tidak ditemukan']);
            }
        } catch (Exception $e) {
            $this->output->set_status_header(500);
            echo json_encode(['status' => 'error', 'message' => 'Kesalahan database: ' . $e->getMessage()]);
        }
    }

    private function create_user() {
        $data = json_decode(file_get_contents('php://input'), true);

        $this->form_validation->set_data($data);
        $this->form_validation->set_rules('name', 'Name', 'required');
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email|is_unique[users.email]');

        if ($this->form_validation->run() == FALSE) {
            $this->output->set_status_header(400);
            echo json_encode(['status' => 'error', 'message' => validation_errors()]);
            return;
        }

        if ($this->User_model->insert_user($data)) {
            echo json_encode(['status' => 'success', 'message' => 'Pengguna berhasil dibuat']);
        } else {
            $this->output->set_status_header(500);
            echo json_encode(['status' => 'error', 'message' => 'Gagal membuat pengguna']);
        }
    }

    private function update_user($id) {
        $data = json_decode(file_get_contents('php://input'), true);

        if ($this->User_model->update_user($id, $data)) {
            echo json_encode(['status' => 'success', 'message' => 'Pengguna berhasil diperbarui']);
        } else {
            $this->output->set_status_header(500);
            echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui pengguna']);
        }
    }

    private function delete_user($id) {
        if ($this->User_model->delete_user($id)) {
            echo json_encode(['status' => 'success', 'message' => 'Pengguna berhasil dihapus']);
        } else {
            $this->output->set_status_header(500);
            echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus pengguna']);
        }
    }
}