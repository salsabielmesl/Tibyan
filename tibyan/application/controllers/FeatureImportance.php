<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class FeatureImportance extends MY_Controller {

    public function __construct() {
        parent::__construct();
        // Load the model dedicated to ML feature analysis
        $this->load->model('FeatureImportance_model');
    }

    /**
     * GET: View importance scores (ordered by highest impact)
     * Standardized to Tibyan API response format
     */
    public function index($model_version_id = null) {
        $result = $this->FeatureImportance_model->get_importance_by_model($model_version_id);
        return $this->_json_response(200, $result);
    }

    /**
     * POST: Log a feature's importance score
     * This endpoint records which medical features had the most impact on ML results
     */
    public function create() {
        $input = $this->json_body;

        if (empty($input['model_version_id']) || empty($input['feature_name'])) {
            return $this->_json_response(400, [
                'status' => 'error',
                'message' => 'model_version_id and feature_name are required'
            ]);
        }

        $data = [
            'model_version_id' => $input['model_version_id'],
            'feature_name'     => $input['feature_name'],
            'importance_score' => $input['importance_score'] ?? 0,
            'calculation_date' => $input['calculation_date'] ?? date('Y-m-d')
        ];

        if ($this->FeatureImportance_model->insert_importance($data)) {
            return $this->_json_response(201, [
                'status' => 'success',
                'message' => 'Feature importance recorded'
            ]);
        } else {
            // Fetch error from the analytical database connection
            $err = $this->load->database('analytical', TRUE)->error();
            return $this->_json_response(500, [
                'status' => 'error', 
                'db_msg' => $err['message']
            ]);
        }
    }
}