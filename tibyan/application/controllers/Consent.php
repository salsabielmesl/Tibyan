<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Consent extends MY_Controller {

    public function __construct() {
        parent::__construct();
        // Load the model
        $this->load->model('Consent_model');
    }

    protected function _json_response($code, $data) {
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header($code)
            ->set_output(json_encode($data));
    }

    // GET: Check consent history
    public function index($user_id = null) {
        // Use current_user_id from Auth_Controller for token-based consistency
        $logged_in_user_id = $this->current_user_id;

        // SECURITY: Users can only view their own consent unless they are an admin
        if ($user_id !== null && $logged_in_user_id != $user_id && $this->current_user_role !== 'admin') {
            return $this->_json_response(403, ['status' => 'error', 'message' => 'Unauthorized access to consent records']);
        }

        $target_id = ($user_id !== null) ? $user_id : $logged_in_user_id;
        $result = $this->Consent_model->get_consent($target_id);
        return $this->_json_response(200, $result);
    }

    // POST: Record a user's consent
    public function create() {
        $input = $this->json_body;

        if (!$input || empty($input['policy_agreed_version'])) {
            return $this->_json_response(400, ['status' => 'error', 'message' => 'Policy Version is required']);
        }

        // SECURITY: Automatically use the ID of the person logged in via Token
        $user_id = $this->current_user_id;

        $data = [
           'user_id'                => $user_id,
           'policy_agreed_version'  => $input['policy_agreed_version'],
           'agreed_at'              => date('Y-m-d H:i:s'),
           'research_opt_in'        => isset($input['research_opt_in']) ? (int)$input['research_opt_in'] : 0,
           'withdrawn_requested_at' => $input['withdrawn_requested_at'] ?? NULL,
           'created_at'             => date('Y-m-d H:i:s'),
           'updated_at'             => date('Y-m-d H:i:s'),
       ];

        if ($this->Consent_model->insert_consent($data)) {
            // Audit Logging (Assumes your helper function log_activity is loaded)
            if (function_exists('log_activity')) {
                log_activity(
                    $user_id, 
                    'signed_consent', 
                    [
                        'policy_version' => $data['policy_agreed_version'],
                        'research_opt_in' => $data['research_opt_in']
                    ]
                );
            }

            return $this->_json_response(201, [
                'status' => 'success', 
                'message' => 'Consent recorded successfully',
                'id' => $this->db->insert_id()
            ]);
        } else {
            return $this->_json_response(500, ['status' => 'error', 'message' => 'Database error']);
        }
    }
}