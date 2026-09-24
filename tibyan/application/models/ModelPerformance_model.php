<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ModelPerformance_model extends CI_Model {

    private $db_analyt;

    public function __construct() {
        parent::__construct();
        // Maintain the isolated connection to the analytical database
        // This keeps system evaluation logs separate from sensitive user data
        $this->db_analyt = $this->load->database('analytical', TRUE);
    }

    /**
     * Insert performance metrics
     * Records accuracy and loss metrics for specific model iterations
     */
    public function insert_performance($data) {
        if (empty($data)) {
            return false;
        }
        return $this->db_analyt->insert('model_performance_logs', $data);
    }

    /**
     * Retrieve performance history for a specific model version
     * Ordered by date to allow for trend analysis (e.g., accuracy over time)
     */
    public function get_performance_history($model_version_id = null) {
        // We order by performance_date so you can track if the model is getting better
        $this->db_analyt->order_by('performance_date', 'ASC');

        if ($model_version_id) {
            return $this->db_analyt->get_where('model_performance_logs', ['model_version_id' => $model_version_id])->result();
        }
        
        return $this->db_analyt->get('model_performance_logs')->result();
    }

    /**
     * Helper: Get the latest accuracy score for a specific model
     * Useful for showing the "Current System Accuracy" on a dashboard
     */
    public function get_latest_accuracy($model_version_id) {
        $this->db_analyt->where('model_version_id', $model_version_id);
        $this->db_analyt->order_by('performance_date', 'DESC');
        $this->db_analyt->limit(1);
        $result = $this->db_analyt->get('model_performance_logs')->row();
        
        return $result ? $result->accuracy : null;
    }

    /**
     * Helper: Get average accuracy for a specific model version
     * Good for showing overall stability of the algorithm
     */
    public function get_average_accuracy($model_version_id) {
        $this->db_analyt->select_avg('accuracy');
        $this->db_analyt->where('model_version_id', $model_version_id);
        $query = $this->db_analyt->get('model_performance_logs');
        return $query->row()->accuracy;
    }
}