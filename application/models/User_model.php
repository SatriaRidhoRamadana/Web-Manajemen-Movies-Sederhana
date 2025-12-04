<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    // Get user by username (for login)
    // Check if `username` column exists first to avoid SQL errors on older schemas.
    public function get_by_username($username) {
        if ($this->db->field_exists('username', 'users')) {
            return $this->db->get_where('users', array('username' => $username))->row_array();
        }
        // fallback to `name` column
        return $this->db->get_where('users', array('name' => $username))->row_array();
    }

    // Save API token and expiry for a user
    // Note: ensure your `users` table has `api_token` (VARCHAR) and `token_expires` (INT) columns
    public function save_token($user_id, $token, $expires) {
        // If columns don't exist, attempt to add them (best-effort).
        // Note: ALTER TABLE requires DB user privileges; if this fails, run SQL manually.
        try {
            if (!$this->db->field_exists('api_token', 'users')) {
                // add nullable varchar column for token
                $this->db->query("ALTER TABLE `users` ADD COLUMN `api_token` VARCHAR(128) NULL");
            }
            if (!$this->db->field_exists('token_expires', 'users')) {
                $this->db->query("ALTER TABLE `users` ADD COLUMN `token_expires` INT(11) NULL");
            }

            return $this->db->update('users', array('api_token' => $token, 'token_expires' => $expires), array('id' => $user_id));
        } catch (Throwable $e) {
            // log and return false; caller will handle response
            log_message('error', 'User_model::save_token error: ' . $e->getMessage());
            return false;
        }
    }

    // Retrieve user by valid token
    public function get_user_by_token($token) {
        if (empty($token)) return null;
        $this->db->where('api_token', $token);
        $this->db->where('token_expires >', time());
        return $this->db->get('users')->row_array();
    }

    public function get_all_users() {
        $query = $this->db->get('users');
        return $query->result_array();
    }

    public function get_user($id) {
        $query = $this->db->get_where('users', array('id' => $id));
        return $query->row_array();
    }

    public function get_user_by_email($email) {
        $query = $this->db->get_where('users', array('email' => $email));
        return $query->row_array();
    }

    public function insert_user($data) {
        return $this->db->insert('users', $data);
    }

    public function update_user($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update('users', $data);
    }

    public function delete_user($id) {
        return $this->db->delete('users', array('id' => $id));
    }
}