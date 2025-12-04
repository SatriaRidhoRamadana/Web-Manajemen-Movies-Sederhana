<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Showtime_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    public function get_all_showtimes() {
        $this->db->select('showtimes.*, movies.title as movie_title');
        $this->db->from('showtimes');
        $this->db->join('movies', 'showtimes.movie_id = movies.id');
        $query = $this->db->get();
        return $query->result_array();
    }

    public function get_showtimes_by_movie($movie_id) {
        $this->db->select('showtimes.*, movies.title as movie_title');
        $this->db->from('showtimes');
        $this->db->join('movies', 'showtimes.movie_id = movies.id');
        $this->db->where('showtimes.movie_id', $movie_id);
        $query = $this->db->get();
        return $query->result_array();
    }

    public function get_showtime($id) {
        $this->db->select('showtimes.*, movies.title as movie_title');
        $this->db->from('showtimes');
        $this->db->join('movies', 'showtimes.movie_id = movies.id');
        $this->db->where('showtimes.id', $id);
        $query = $this->db->get();
        return $query->row_array();
    }

    public function insert_showtime($data) {
        return $this->db->insert('showtimes', $data);
    }

    public function update_showtime($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update('showtimes', $data);
    }

    public function delete_showtime($id) {
        return $this->db->delete('showtimes', array('id' => $id));
    }

    public function update_available_seats($id, $seats_booked) {
        $this->db->set('available_seats', 'available_seats - ' . (int)$seats_booked, FALSE);
        $this->db->where('id', $id);
        $this->db->where('available_seats >=', $seats_booked);
        return $this->db->update('showtimes');
    }
}