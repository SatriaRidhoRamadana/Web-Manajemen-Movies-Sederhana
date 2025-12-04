<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Model `User_model` menangani operasi terkait tabel `users`.
class User_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    // Ambil user berdasarkan username (digunakan untuk login).
    // Cek keberadaan kolom `username` terlebih dahulu untuk menghindari error pada skema lama.
    public function get_by_username($username) {
        if ($this->db->field_exists('username', 'users')) {
            return $this->db->get_where('users', array('username' => $username))->row_array();
        }
        // Jika kolom username tidak ada, fallback ke kolom `name`.
        return $this->db->get_where('users', array('name' => $username))->row_array();
    }

    // Simpan token API dan expiry dalam tabel users (legacy support).
    // Catatan: fitur ini bersifat best-effort dan membutuhkan hak ALTER TABLE jika kolom belum ada.
    public function save_token($user_id, $token, $expires) {
        try {
            if (!$this->db->field_exists('api_token', 'users')) {
                // Tambahkan kolom api_token jika belum ada
                $this->db->query("ALTER TABLE `users` ADD COLUMN `api_token` VARCHAR(128) NULL");
            }
            if (!$this->db->field_exists('token_expires', 'users')) {
                $this->db->query("ALTER TABLE `users` ADD COLUMN `token_expires` INT(11) NULL");
            }

            return $this->db->update('users', array('api_token' => $token, 'token_expires' => $expires), array('id' => $user_id));
        } catch (Throwable $e) {
            // Log error dan kembalikan false
            log_message('error', 'User_model::save_token error: ' . $e->getMessage());
            return false;
        }
    }

    // Ambil user berdasarkan token (legacy token stored in DB)
    public function get_user_by_token($token) {
        if (empty($token)) return null;
        $this->db->where('api_token', $token);
        $this->db->where('token_expires >', time());
        return $this->db->get('users')->row_array();
    }

    public function get_all_users() {
        // Ambil semua pengguna
        $query = $this->db->get('users');
        return $query->result_array();
    }

    public function get_user($id) {
        // Ambil user berdasarkan id
        $query = $this->db->get_where('users', array('id' => $id));
        return $query->row_array();
    }

    public function get_user_by_email($email) {
        // Ambil user berdasarkan email
        $query = $this->db->get_where('users', array('email' => $email));
        return $query->row_array();
    }

    public function insert_user($data) {
        // Tambah user baru
        return $this->db->insert('users', $data);
    }

    public function update_user($id, $data) {
        // Update data user
        $this->db->where('id', $id);
        return $this->db->update('users', $data);
    }

    public function delete_user($id) {
        // Hapus user
        return $this->db->delete('users', array('id' => $id));
    }
}