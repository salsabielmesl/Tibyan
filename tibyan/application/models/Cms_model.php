<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cms_model extends CI_Model {

    private $db_analytical;

    public function __construct() {
        parent::__construct();
        // Force loading of the analytical database configuration block
        $this->db_analytical = $this->load->database('analytical', TRUE);
    }

    /**
     * Updates any specific section table by ID using explicit parameters
     */
    public function update_section_data($table, $id, $data) {
        $this->db_analytical->where('id', $id);
        return $this->db_analytical->update($table, $data);
    }

    /**
     * Optional utility to fetch current row data for verification
     */
    public function get_section_data($table, $id = null) {
        if ($id) {
            $this->db_analytical->where('id', $id);
            return $this->db_analytical->get($table)->row_array();
        }
        return $this->db_analytical->get($table)->result_array();
    }
}