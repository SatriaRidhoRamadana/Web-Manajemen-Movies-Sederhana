<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Model Booking_model menangani operasi pada tabel `bookings`.
class Booking_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    // Ambil semua booking dengan informasi user, showtime, dan judul film
    public function get_all_bookings() {
        $this->db->select('bookings.*, users.name as user_name, users.email, showtimes.show_date, showtimes.show_time, showtimes.theater, movies.title as movie_title');
        $this->db->from('bookings');
        $this->db->join('users', 'bookings.user_id = users.id');
        $this->db->join('showtimes', 'bookings.showtime_id = showtimes.id');
        $this->db->join('movies', 'showtimes.movie_id = movies.id');
        $query = $this->db->get();
        return $query->result_array();
    }

    public function get_bookings_by_user($user_id) {
        // Ambil booking berdasarkan user id
        $this->db->select('bookings.*, showtimes.show_date, showtimes.show_time, showtimes.theater, movies.title as movie_title');
        $this->db->from('bookings');
        $this->db->join('showtimes', 'bookings.showtime_id = showtimes.id');
        $this->db->join('movies', 'showtimes.movie_id = movies.id');
        $this->db->where('bookings.user_id', $user_id);
        $query = $this->db->get();
        return $query->result_array();
    }

    public function get_booking($id) {
        // Ambil detail booking berdasarkan id
        $this->db->select('bookings.*, users.name as user_name, users.email, showtimes.show_date, showtimes.show_time, showtimes.theater, movies.title as movie_title');
        $this->db->from('bookings');
        $this->db->join('users', 'bookings.user_id = users.id');
        $this->db->join('showtimes', 'bookings.showtime_id = showtimes.id');
        $this->db->join('movies', 'showtimes.movie_id = movies.id');
        $this->db->where('bookings.id', $id);
        $query = $this->db->get();
        return $query->row_array();
    }

    public function insert_booking($data) {
        // Tambah booking baru
        return $this->db->insert('bookings', $data);
    }

    public function update_booking($id, $data) {
        // Update booking
        $this->db->where('id', $id);
        return $this->db->update('bookings', $data);
    }

    public function delete_booking($id) {
        // Hapus booking
        return $this->db->delete('bookings', array('id' => $id));
    }

    public function cancel_booking($id) {
        // Batalkan booking dengan mengubah status
        $this->db->where('id', $id);
        return $this->db->update('bookings', array('status' => 'cancelled'));
    }
}