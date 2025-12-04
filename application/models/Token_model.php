<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Token_model extends CI_Model {
    public function __construct() {
        parent::__construct();
    }

    private function ensure_table() {
        // Create table if not exists (best-effort)
        $sql = "CREATE TABLE IF NOT EXISTS `revoked_tokens` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `jti` VARCHAR(64) NOT NULL,
            `expires_at` INT(11) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY (`jti`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        try {
            $this->db->query($sql);
        } catch (Exception $e) {
            log_message('error', 'Token_model::ensure_table failed: ' . $e->getMessage());
        }
    }

    public function revoke_jti($jti, $expires_at = null) {
        if (empty($jti)) return false;
        $this->ensure_table();
        try {
            return (bool)$this->db->query("INSERT INTO `revoked_tokens` (`jti`,`expires_at`) VALUES (?,?) ON DUPLICATE KEY UPDATE `expires_at` = ?", array($jti, $expires_at, $expires_at));
        } catch (Exception $e) {
            log_message('error', 'Token_model::revoke_jti error: ' . $e->getMessage());
            return false;
        }
    }

    public function is_revoked($jti) {
        if (empty($jti)) return true;
        $this->ensure_table();
        $row = $this->db->get_where('revoked_tokens', array('jti' => $jti))->row_array();
        if (!$row) return false;
        if (!empty($row['expires_at']) && time() > (int)$row['expires_at']) {
            // cleanup expired revocation
            $this->db->delete('revoked_tokens', array('jti' => $jti));
            return false;
        }
        return true;
    }
}
