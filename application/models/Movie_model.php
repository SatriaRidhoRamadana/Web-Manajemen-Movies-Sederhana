<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Movie_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    public function get_all_movies() {
        $this->db->select('*');
        $this->db->from('movies');
        $this->db->order_by('id', 'ASC');
        $query = $this->db->get();
        return $query->result_array();
    }

    public function get_movie($id) {
        $query = $this->db->get_where('movies', array('id' => $id));
        return $query->row_array();
    }

    public function insert_movie($data) {
        // Basic validation
        if (empty($data['title']) || empty($data['duration'])) {
            return false;
        }
        return $this->db->insert('movies', $data);
    }

    public function update_movie($id, $data) {
        // Basic validation
        if (empty($id) || !is_numeric($id)) {
            return false;
        }
        $this->db->where('id', $id);
        return $this->db->update('movies', $data);
    }

    public function delete_movie($id) {
        // Basic validation
        if (empty($id) || !is_numeric($id)) {
            return false;
        }
        return $this->db->delete('movies', array('id' => $id));
    }

    public function search_movies($query) {
        $this->db->like('title', $query);
        $this->db->or_like('genre', $query);
        $this->db->or_like('description', $query);
        $query = $this->db->get('movies');
        return $query->result_array();
    }

    public function get_movies_by_genre($genre) {
        $this->db->where('genre', $genre);
        $query = $this->db->get('movies');
        return $query->result_array();
    }

    public function get_recent_movies($limit = 10) {
        $this->db->order_by('release_date', 'DESC');
        $this->db->limit($limit);
        $query = $this->db->get('movies');
        return $query->result_array();
    }
}