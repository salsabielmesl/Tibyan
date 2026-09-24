<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Predictions_model extends CI_Model {

    private $db_analyt;

    public function __construct() {
        parent::__construct();
        // Securely load the analytical database connection
        // This ensures prediction results are kept in the research-focused environment
        $this->db_analyt = $this->load->database('analytical', TRUE);
    }

    /**
     * Insert a new ML prediction result
     * Records risk levels and model confidence scores
     */
    public function insert_prediction($data) {
        if (empty($data)) {
            return false;
        }
        return $this->db_analyt->insert('prediction_records', $data);
    }

    /**
     * Get predictions (all or by UPID for patient history)
     * Ordered by newest results first to show the most relevant data
     */
    public function get_predictions($upid = null) {
        // We order by 'created_at' so the newest ML outputs appear first
        $this->db_analyt->order_by('created_at', 'DESC');

        if ($upid) {
            return $this->db_analyt->get_where('prediction_records', ['upid' => $upid])->result();
        }
        
        return $this->db_analyt->get('prediction_records')->result();
    }

    /**
     * Helper: Retrieve only high-risk predictions
     * Useful for medical intervention dashboards
     */
    public function get_high_risk_predictions($threshold = 'High') {
        $this->db_analyt->where('risk_level_dm', $threshold);
        $this->db_analyt->order_by('confidence_score', 'DESC');
        return $this->db_analyt->get('prediction_records')->result();
    }

    /**
     * Helper: Get predictions by specific model version
     * Useful for comparing how different models performed
     */
    public function get_by_model_version($version_id) {
        return $this->db_analyt->get_where('prediction_records', ['model_version_id' => $version_id])->result();
    }
    public function get_last_id() {
    return $this->db_analyt->insert_id();
    }
}