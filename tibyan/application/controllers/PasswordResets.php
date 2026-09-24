<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class PasswordResets extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Load the new model
        $this->load->model('PasswordResets_model');
        header("Content-Type: application/json");
    }
public function index() {
    // 1. Tell the Model to get the tokens
    $this->db->select('email, token, created_at');
    $result = $this->db->get('password_reset_tokens')->result();
    
    // 2. Return the data as JSON
    echo json_encode($result);
}
    // POST: Create a reset token
    public function create() {
        $input = json_decode($this->input->raw_input_stream, true);

        if (!$input || empty($input['email'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Email is required']);
            return;
        }

        $data = [
            'email'      => $input['email'],
            'token'      => bin2hex(random_bytes(32)), // Secure random token
            'created_at' => date('Y-m-d H:i:s')
        ];

        if ($this->PasswordResets_model->upsert_token($data)) {
            http_response_code(201);
            echo json_encode(['status' => 'success', 'token' => $data['token']]);
        } else {
            $error = $this->db->error();
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $error['message']]);
        }
    }
}