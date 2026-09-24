<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class AnalyticalTables extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('AnalyticalTables_model');
        // Load the validation library
        $saved = $this->json_body;
        $this->load->library('form_validation');
        $this->json_body = $saved;
    }

    /**
     * Helper for JSON responses (Standardized for Tibyan Project)
     */
    protected function _json_response($code, $data) {
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header($code)
            ->set_output(json_encode($data));
    }

    public function add_metrics() {
        // 1. Handle JSON or Form-Data input
        $json_input = json_decode(file_get_contents('php://input'), true);
        $input = !empty($json_input) ? $json_input : $this->input->post();

        // 2. DATA VALIDATION
        // Manually pointing validation to our $input array
        $this->form_validation->set_data($input);

        // Strict rules for ML-critical fields
        $this->form_validation->set_rules('user_id', 'User ID', 'required|integer');
        $this->form_validation->set_rules('lbxglu', 'Glucose', 'required|numeric|greater_than[0]|less_than[1000]');
        $this->form_validation->set_rules('lbxin', 'Insulin', 'required|numeric|greater_than_equal_to[0]');
        $this->form_validation->set_rules('bmxwaist', 'Waist Circumference', 'required|numeric');
        $this->form_validation->set_rules('bmxbmi', 'BMI', 'required|numeric|greater_than[10]|less_than[100]');
        
        // Optional fields - ensure they are numeric if provided
        $optional_fields = ['lbxcp', 'lbdhdd', 'lbdldl', 'lbxtr', 'lbxsal', 'lbxsc3si', 'lbxsatsi', 'urxuma', 'lbdsbusi', 'vnavebpxsy', 'vnlbavebpxdi', 'bmpwhr', 'bmxsad1'];
        foreach ($optional_fields as $field) {
            $this->form_validation->set_rules($field, $field, 'numeric');
        }

        if ($this->form_validation->run() == FALSE) {
            return $this->_json_response(400, [
                'status' => 'error',
                'message' => 'Validation Failed',
                'errors' => $this->form_validation->error_array()
            ]);
        }

        // 3. SECURITY: Ownership Check
        // UPDATED: Using the authenticated current_user_id from your Bearer Token
        $logged_in_user_id = $this->current_user_id; 
        $requested_user_id = $input['user_id'];

        if ($logged_in_user_id != $requested_user_id) {
            // Log the unauthorized attempt
            log_activity($logged_in_user_id, 'unauthorized_data_submission', ['target_user_id' => $requested_user_id]);
            
            return $this->_json_response(403, [
                'status' => 'error', 
                'message' => 'Unauthorized: You can only submit data for your own account.'
            ]);
        }

        // 4. Map the data for insertion
        $data = [
            'user_id'      => $requested_user_id,
            'lbxglu'       => $input['lbxglu'],
            'lbxin'        => $input['lbxin'],
            'bmxwaist'     => $input['bmxwaist'],
            'bmxbmi'       => $input['bmxbmi'],
            'lbxcp'        => $input['lbxcp'] ?? null,
            'lbdhdd'       => $input['lbdhdd'] ?? null,
            'lbdldl'       => $input['lbdldl'] ?? null,
            'lbxtr'        => $input['lbxtr'] ?? null,
            'lbxsal'       => $input['lbxsal'] ?? null,
            'lbxsc3si'     => $input['lbxsc3si'] ?? null,
            'lbxsatsi'     => $input['lbxsatsi'] ?? null,
            'urxuma'       => $input['urxuma'] ?? null,
            'lbdsbusi'     => $input['lbdsbusi'] ?? null,
            'vnavebpxsy'   => $input['vnavebpxsy'] ?? null,
            'vnlbavebpxdi' => $input['vnlbavebpxdi'] ?? null,
            'bmpwhr'       => $input['bmpwhr'] ?? null,
            'bmxsad1'      => $input['bmxsad1'] ?? null
        ];

        // 5. Cross-DB Check: Ensure user exists in primary system
        $user_exists = $this->db->get_where('user', ['id' => $data['user_id']])->row();
        if (!$user_exists) {
            return $this->_json_response(404, ['status' => 'error', 'message' => 'User ID does not exist in the primary system.']);
        }

        // 6. Save and Log
        if ($this->AnalyticalTables_model->insert_metrics($data)) {
            
            log_activity(
                $data['user_id'], 
                'added_medical_metrics', 
                ['fields_recorded' => count(array_filter($data))]
            );

            return $this->_json_response(200, ['status' => 'success', 'message' => 'Metrics recorded successfully.']);
        } else {
            return $this->_json_response(500, ['status' => 'error', 'message' => 'Database failure.']);
        }
    }
}