<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class UpidLinks_model extends CI_Model {

    // Custom property to hold the analytical database connection
    private $db_analyt;

    public function __construct() {
        parent::__construct();
        
        // Load the analytical database specifically. 
        // This ensures PII and research data remain separated.
        $this->db_analyt = $this->load->database('analytical', TRUE);
    }

    /**
     * Insert a new UPID link into the analytical database
     */
    public function insert_link($data) {
        // We use the $db_analyt connection instead of the default $this->db
        return $this->db_analyt->insert('upid_links', $data);
    }

    /**
     * Get UPID records from the analytical database
     * @param string|null $upid Optional specific UPID to filter by
     */
    public function get_links($upid = null) {
        if ($upid) {
            // Returns a single object if a specific UPID is requested
            return $this->db_analyt->get_where('upid_links', ['upid' => $upid])->row();
        }
        
        // Returns an array of all UPID records
        return $this->db_analyt->get('upid_links')->result();
    }
}