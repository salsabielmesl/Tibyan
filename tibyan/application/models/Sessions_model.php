<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sessions_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        // Load default PII database
        $this->load->database();
    }

    // Insert a new session record
    public function insert_session($data) {
        return $this->db->insert('sessions', $data);
    }

    // Get session records
    public function get_sessions($id = null) {
        if ($id) {
            return $this->db->get_where('sessions', ['id' => $id])->row();
        }
        return $this->db->get('sessions')->result();
    }
}