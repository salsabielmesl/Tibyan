<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Predictions extends MY_Controller {

    public function __construct() {
        parent::__construct();
        // Load the new predictions model
        $this->load->model('Predictions_model');
    }

    /**
     * GET: List all predictions or filter by UPID
     */
    public function index($upid = null) {
        $result = $this->Predictions_model->get_predictions($upid);
        return $this->_json_response(200, $result);
    }

    /**
     * POST: Create a new prediction record
     */
    public function create() {
        $input = $this->json_body;

        if (empty($input['upid']) || empty($input['model_version_id'])) {
            return $this->_json_response(400, [
                'status' => 'error',
                'message' => 'UPID and Model Version ID are required'
            ]);
        }

        $data = [
            'upid'                => $input['upid'],
            'model_version_id'    => $input['model_version_id'],
            'risk_level_dm'       => $input['risk_level_dm'] ?? null,
            'safe_fail_triggered' => isset($input['safe_fail_triggered']) ? (int)$input['safe_fail_triggered'] : 0,
            'feature_input_type'  => $input['feature_input_type'] ?? null,
            'confidence_score'    => $input['confidence_score'] ?? 0.00,
            'created_at'          => date('Y-m-d H:i:s'),
            'updated_at'          => date('Y-m-d H:i:s')
        ];

        if ($this->Predictions_model->insert_prediction($data)) {
            // Retrieve ID from the model's specific analytical DB connection
            $db_analyt = $this->load->database('analytical', TRUE);
            
            return $this->_json_response(201, [
                'status' => 'success',
                'prediction_id'  => $this->Predictions_model->get_last_id(),
                'message' => 'Prediction record created successfully'
            ]);
        } else {
            $err = $this->load->database('analytical', TRUE)->error();
            return $this->_json_response(500, [
                'status' => 'error',
                'db_msg' => $err['message']
            ]);
        }
    }
}