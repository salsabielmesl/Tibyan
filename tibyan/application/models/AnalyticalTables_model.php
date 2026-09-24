<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class AnalyticalTables_model extends CI_Model {

    private $db_analytical;

    public function __construct() {
        parent::__construct();
        // Manually load the analytical group defined in database.php
        // This keeps ML-critical data separate from the primary user database
        $this->db_analytical = $this->load->database('analytical', TRUE);
    }

    /**
     * Insert new medical metrics into the analytical database
     */
    public function insert_metrics($data) {
        if (empty($data)) {
            return false;
        }

        // We use $this->db_analytical to target the correct database connection
        return $this->db_analytical->insert('analytical_tables', $data);
    }

    /**
     * Retrieve all metrics for a specific user
     * Standardized to return newest records first
     */
    public function get_user_metrics($user_id) {
        if (!$user_id) {
            return [];
        }

        // Order by the primary key or a timestamp to show progress over time
        $this->db_analytical->order_by('id', 'DESC'); 
        
        $query = $this->db_analytical->get_where('analytical_tables', ['user_id' => $user_id]);
        return $query->result_array();
    }

    /**
     * Optional: Get the most recent single entry for a user
     * Useful for the "Current Status" section of a dashboard
     */
    public function get_latest_metrics($user_id) {
        $this->db_analytical->where('user_id', $user_id);
        $this->db_analytical->order_by('id', 'DESC');
        $this->db_analytical->limit(1);
        return $this->db_analytical->get('analytical_tables')->row_array();
    }
}