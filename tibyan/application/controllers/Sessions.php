<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sessions Controller
 * Handles Login and Token Generation.
 * Extends MY_Controller to use the professional _json_response helper.
 */
class Sessions extends MY_Controller {

    public function __construct() {
        /**
         * IMPORTANT: We call CI_Controller's constructor directly here.
         * This bypasses the parent::construct() in MY_Controller so 
         * you don't need a token to login (which would be impossible!).
         */
        CI_Controller::__construct();
        
        $this->load->model('Sessions_model');
        $this->load->model('User_model');
    }

    /**
     * POST: Create a new session (Login)
     * Endpoint: POST /sessions/create
     */
    public function create() {
        $input = json_decode($this->input->raw_input_stream, true);

        // 1. VALIDATE INPUT
        if (!$input || empty($input['email']) || empty($input['password'])) {
            return $this->_json_response(400, [
                'status' => 'error', 
                'message' => 'Email and password required'
            ]);
        }

        // 2. VERIFY CREDENTIALS
        $user = $this->User_model->authenticate($input['email'], $input['password']);

        if (!$user) {
            return $this->_json_response(401, [
                'status' => 'error', 
                'message' => 'Invalid email or password'
            ]);
        }

        // 3. GENERATE SECURE TOKEN
        $token = bin2hex(random_bytes(16)); 

        // 4. PREPARE SESSION DATA
        // Note: Using ['id'] because $user is an array
        $data = [
            'id'            => $token,
            'user_id'       => $user['id'], 
            'ip_address'    => $this->input->ip_address(), 
            'user_agent'    => $this->input->user_agent(), 
            'payload'       => $input['payload'] ?? 'web_login',
            'last_activity' => time() 
        ];

        // 5. INSERT & RETURN
        if ($this->db->insert('sessions', $data)) {
    
    // ADD THIS LINE: Update the user's last login timestamp
    $this->db->where('id', $user['id']);
    $this->db->update('user', ['last_login_at' => date('Y-m-d H:i:s')]);

    return $this->_json_response(201, [
        'status'  => 'success', 
        'message' => 'Login successful',
        'token'   => $token,
        'role'    => $user['role'],
        'upid'    => $user['upid']
    ]);
        } else {
            return $this->_json_response(500, [
                'status'  => 'error', 
                'message' => 'Failed to create session'
            ]);
        }
    }
}