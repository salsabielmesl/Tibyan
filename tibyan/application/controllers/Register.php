<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Register extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('User_model');
        $this->load->library('form_validation');
        header("Content-Type: application/json");
    }

    private function _json_response($code, $data) {
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header($code)
            ->set_output(json_encode($data));
    }

    public function index() {
        $input = json_decode($this->input->raw_input_stream, true);

        if (!$input) {
            return $this->_json_response(400, ['status' => 'error', 'message' => 'No input provided']);
        }

        // Auto-generate UPID if not provided by frontend
        if (empty($input['upid'])) {
            $input['upid'] = 'PAT' . time() . rand(10, 99);
        }

        // 1. Validation
        $this->form_validation->set_data($input);
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email|is_unique[user.email]');
        $this->form_validation->set_rules('password', 'Password', 'required|min_length[8]');
        $this->form_validation->set_rules('full_name', 'Full Name', 'required|min_length[3]');

        if ($this->form_validation->run() == FALSE) {
            return $this->_json_response(400, [
                'status' => 'error',
                'errors' => $this->form_validation->error_array()
            ]);
        }

        // 2. Prepare User Data (PII Database)
        $data = [
            'upid'            => $input['upid'],
            'email'           => $input['email'],
            'password_hashed' => password_hash($input['password'], PASSWORD_BCRYPT), 
            'full_name'       => $input['full_name'],
            'role'            => 'patient', 
            'date_of_birth'   => $input['date_of_birth'] ?? '2000-01-01',
            'contact_info'    => $input['contact_info'] ?? '',
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s')
        ];

        // 3. Insert User
        if ($this->User_model->insert_user($data)) {
            $new_user_id = $this->db->insert_id();

            // --- CROSS-DATABASE AUTOMATION ---
            // Explicitly load the analytical database
            $analytical_db = $this->load->database('analytical', TRUE); 

            $link_data = [
                'user_id'       => $new_user_id,        // Column we added in Step 1
                'upid'          => $data['upid'],       // Your bridge ID
                'creation_date' => date('Y-m-d H:i:s'), // Column #3 in image_fec2bf.jpg
                'created_at'    => date('Y-m-d H:i:s'), // Column #6 in image_fec2bf.jpg
                'updated_at'    => date('Y-m-d H:i:s')  // Column #7 in image_fec2bf.jpg
            ];

            $analytical_db->insert('upid_links', $link_data);
            // ---------------------------------

            return $this->_json_response(201, [
                'status'  => 'success',
                'message' => 'Registration successful! UPID ' . $data['upid'] . ' is now linked.',
                'user_id' => $new_user_id,
                'upid'    => $data['upid'],
            ]);
        } else {
            return $this->_json_response(500, ['status' => 'error', 'message' => 'Database error.']);
        }
    }
}