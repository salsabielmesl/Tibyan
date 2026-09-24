<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class FeatureImportance_model extends CI_Model {

    private $db_analyt;

    public function __construct() {
        parent::__construct();
        // Maintain the isolated connection to the analytical database
        // This ensures the ML interpretability data remains separate from user data
        $this->db_analyt = $this->load->database('analytical', TRUE);
    }

    /**
     * Insert feature importance metrics
     * Records how much weight a specific medical feature (e.g., Glucose) had on the model
     */
    public function insert_importance($data) {
        if (empty($data)) {
            return false;
        }
        return $this->db_analyt->insert('feature_importance', $data);
    }

    /**
     * Retrieve importance scores for a specific model version
     * Automatically orders by importance_score (highest impact first)
     */
    public function get_importance_by_model($model_version_id = null) {
        $this->db_analyt->order_by('importance_score', 'DESC');

        if ($model_version_id) {
            return $this->db_analyt->get_where('feature_importance', ['model_version_id' => $model_version_id])->result();
        }
        
        return $this->db_analyt->get('feature_importance')->result();
    }

    /**
     * Helper: Get the Top X features for a model
     * Excellent for creating feature impact visualizations in the frontend
     */
    public function get_top_features($model_version_id, $limit = 5) {
        $this->db_analyt->where('model_version_id', $model_version_id);
        $this->db_analyt->order_by('importance_score', 'DESC');
        $this->db_analyt->limit($limit);
        return $this->db_analyt->get('feature_importance')->result();
    }

    /**
     * Clear old importance records for a version
     * Useful if you are re-training a model and want to refresh the scores
     */
    public function delete_importance_by_model($model_version_id) {
        return $this->db_analyt->delete('feature_importance', ['model_version_id' => $model_version_id]);
    }
}