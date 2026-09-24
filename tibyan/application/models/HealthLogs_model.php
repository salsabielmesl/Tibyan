<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class HealthLogs_model extends CI_Model {

    private $db_analyt;

    public function __construct() {
        parent::__construct();
        // Load the analytical database connection specifically
        // This keeps patient health logs isolated from primary account data
        $this->db_analyt = $this->load->database('analytical', TRUE);
    }

    /**
     * Insert a new health log entry (BP, Cholesterol, etc.)
     */
    public function insert_log($data) {
        if (empty($data)) {
            return false;
        }
        return $this->db_analyt->insert('health_logs', $data);
    }

    /**
     * Get health logs by UPID for longitudinal tracking
     * Results are ordered by date (newest first) to show the latest status
     */
    public function get_logs_by_upid($upid = null) {
        // We order by 'created_at' so the timeline makes sense in your frontend/Postman
        $this->db_analyt->order_by('created_at', 'DESC');

        if ($upid) {
            return $this->db_analyt->get_where('health_logs', ['upid' => $upid])->result();
        }
        
        return $this->db_analyt->get('health_logs')->result();
    }

    /**
     * Helper: Get only the most recent log for a specific patient
     * Useful for showing "Last Recorded BP" in a UI
     */
    public function get_latest_log($upid) {
        $this->db_analyt->where('upid', $upid);
        $this->db_analyt->order_by('created_at', 'DESC');
        $this->db_analyt->limit(1);
        return $this->db_analyt->get('health_logs')->row();
    }

    public function get_last_id() {
        return $this->db_analyt->insert_id();
    }

    public function get_db_error() {
        return $this->db_analyt->error();
    }
}