<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ModelPerformance extends MY_Controller {

    public function __construct() {
        parent::__construct();
        // Load the model that tracks ML accuracy and metrics
        $this->load->model('ModelPerformance_model');
    }

    /**
     * GET: View performance logs
     * Standardized to Tibyan project response format
     */
    public function index($model_version_id = null) {
        $result = $this->ModelPerformance_model->get_performance_history($model_version_id);
        return $this->_json_response(200, $result);
    }

    /**
     * POST: Record new accuracy metrics
     */
    public function log_accuracy() {
        $input = $this->json_body;

        if (empty($input['model_version_id']) || !isset($input['accuracy'])) {
            return $this->_json_response(400, [
                'status' => 'error',
                'message' => 'model_version_id and accuracy are required'
            ]);
        }

        $data = [
            'model_version_id' => $input['model_version_id'],
            'performance_date' => $input['performance_date'] ?? date('Y-m-d'),
            'accuracy'         => $input['accuracy'],
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s')
        ];

        if ($this->ModelPerformance_model->insert_performance($data)) {
            // Explicitly use the analytical database connection to get the last ID
            $db_analyt = $this->load->database('analytical', TRUE);
            
            return $this->_json_response(201, [
                'status' => 'success',
                'log_id' => $db_analyt->insert_id(),
                'message' => 'Performance metrics recorded'
            ]);
        } else {
            // Retrieve detailed error from the analytical connection
            $err = $this->load->database('analytical', TRUE)->error();
            return $this->_json_response(500, [
                'status' => 'error', 
                'db_msg' => $err['message']
            ]);
        }
    }
}