<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class HealthLogs extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('HealthLogs_model');
    }

    /**
     * GET: Retrieve logs for a specific UPID or all logs
     */
    public function index($upid = null) {
        $result = $this->HealthLogs_model->get_logs_by_upid($upid);
        return $this->_json_response(200, $result);
    }

    /**
     * POST: Create a new health log entry with all NHANES features
     */
    public function create() {
        $input = $this->json_body;

        if (empty($input['upid'])) {
            return $this->_json_response(400, ['error' => 'UPID is required']);
        }

        $data = [
            // REQUIRED features
            'upid'            => $input['upid'],
            'glucose'         => $input['glucose']         ?? null, // LBXGLU
            'insulin'         => $input['insulin']         ?? null, // LBXIN
            'circumference'   => $input['circumference']   ?? null, // BMXWAIST
            'bmi'             => $input['bmi']             ?? null, // BMXBMI

            // RECOMMENDED features
            'cpeptide'        => $input['cpeptide']        ?? null, // LBXCP
            'cholesterol_hdl' => $input['cholesterol_hdl'] ?? null, // LBDHDD
            'ldl'             => $input['ldl']             ?? null, // LBDLDL
            'triglycerides'   => $input['triglycerides']   ?? null, // LBXTR
            'albumin'         => $input['albumin']         ?? null, // LBXSAL
            'bicarbonate'     => $input['bicarbonate']     ?? null, // LBXSC3SI
            'alt'             => $input['alt']             ?? null, // LBXSATSI
            'ualbumin'        => $input['ualbumin']        ?? null, // URXUMA
            'nitrogen'        => $input['nitrogen']        ?? null, // LBDSBUSI
            'systolic_bp'     => $input['systolic_bp']     ?? null, // VNAVEBPXSY
            'diastolic_bp'    => $input['diastolic_bp']    ?? null, // VNLBAVEBPXDI

            // OPTIONAL features
            'waist_hip_ratio' => $input['waist_hip_ratio'] ?? null, // BMPWHR
            'sagittal'        => $input['sagittal']        ?? null, // BMXSAD1

            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ];

        if ($this->HealthLogs_model->insert_log($data)) {
            return $this->_json_response(201, [
                'status'  => 'success',
                'log_id'  => $this->HealthLogs_model->get_last_id(),
                'message' => 'Health log entry created successfully'
            ]);
        } else {
            $err = $this->HealthLogs_model->get_db_error();
            return $this->_json_response(500, [
                'status'   => 'error',
                'message'  => 'Database insert failed',
                'db_code'  => $err['code']    ?? null,
                'db_msg'   => $err['message'] ?? 'Unknown error'
            ]);
        }
    }
}