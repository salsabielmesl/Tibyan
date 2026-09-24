<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ModelVersions_model extends CI_Model {

    private $db_analyt;

    public function __construct() {
        parent::__construct();
        // Connect specifically to the analytical database
        // This ensures ML data stays separate from primary user data
        $this->db_analyt = $this->load->database('analytical', TRUE);
    }

    /**
     * Insert a new model version
     * Returns the boolean result of the operation
     */
    public function insert_version($data) {
        if (empty($data)) {
            return false;
        }
        return $this->db_analyt->insert('model_versions', $data);
    }

    /**
     * Get all versions or a specific one by ID
     * Results are ordered by the most recently updated first
     */
    public function get_versions($id = null) {
        if ($id) {
            // Retrieve a single specific model version
            return $this->db_analyt->get_where('model_versions', ['id' => $id])->row();
        }

        // Return all versions, ordered by newest first for better UI/Demo display
        $this->db_analyt->order_by('updated_at', 'DESC');
        return $this->db_analyt->get('model_versions')->result();
    }

    /**
     * Helper to check if a version exists before attempting related inserts
     * (Useful for Predictions and Performance logs)
     */
    public function version_exists($id) {
        return $this->db_analyt->where('id', $id)->count_all_results('model_versions') > 0;
    }
}