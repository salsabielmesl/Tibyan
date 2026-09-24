<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->model('PasswordResets_model');
        $this->load->library('form_validation');
        
        $this->output->set_header("X-Content-Type-Options: nosniff");
        $this->output->set_header("X-Frame-Options: DENY");
        header("Content-Type: application/json");
    }

    /**
     * Endpoint: Standard Registration
     */
    public function register() {
        $json_input = json_decode(file_get_contents('php://input'), true);
        $input = !empty($json_input) ? $json_input : $this->input->post();

        $this->form_validation->set_data($input);
        $this->form_validation->set_rules('full_name',    'Full Name',    'required|min_length[3]');
        $this->form_validation->set_rules('email',        'Email',        'required|valid_email');
        $this->form_validation->set_rules('password',     'Password',     'required|min_length[8]');
        $this->form_validation->set_rules('date_of_birth','Date of Birth','required');
        $this->form_validation->set_rules('contact_info', 'Contact Info', 'required');

        if ($this->form_validation->run() == FALSE) {
            return $this->_json_response(400, [
                'status' => 'error',
                'errors' => $this->form_validation->error_array()
            ]);
        }

        // Check duplicate email
        $email_exists = $this->db->get_where('user', ['email' => $input['email']])->num_rows();
        if ($email_exists > 0) {
            return $this->_json_response(400, [
                'status'  => 'error',
                'message' => 'An account already exists with this email address'
            ]);
        }

        // Auto-generate unique UPID (e.g. PAT1748123456)
        $upid = 'PAT' . time();

        $user_payload = [
            'upid'            => $upid,
            'email'           => $input['email'],
            'password_hashed' => password_hash($input['password'], PASSWORD_BCRYPT),
            'full_name'       => htmlspecialchars($input['full_name']),
            'role'            => 'patient',
            'date_of_birth'   => $input['date_of_birth'],
            'contact_info'    => $input['contact_info'],
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ];

        if ($this->db->insert('user', $user_payload)) {
            $new_user_id = $this->db->insert_id();
            log_activity($new_user_id, 'registration_success', 'New account registered.');

            // Bridge the UPID into the analytical database so health_logs FK is satisfied
            $db_analyt = $this->load->database('analytical', TRUE);
            $db_analyt->insert('upid_links', [
                'user_id'       => $new_user_id,
                'upid'          => $upid,
                'creation_date' => date('Y-m-d H:i:s'),
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);

            return $this->_json_response(200, [
                'status'  => 'success',
                'message' => 'Account created successfully! You can now log in.',
                'upid'    => $upid
            ]);
        } else {
            return $this->_json_response(500, [
                'status'  => 'error',
                'message' => 'Database error: could not create account'
            ]);
        }
        // In Auth.php register(), after inserting user:
        $ul_result = $db_analyt->insert('upid_links', [
            'user_id'       => $new_user_id,
            'upid'          => $upid,
            'creation_date' => date('Y-m-d H:i:s'),
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        if (!$ul_result) {
        // Log the error - this is why health_logs inserts fail later
            log_message('error', 'upid_links insert failed for upid: ' . $upid);
        }
    }

    /**
     * Endpoint: Standard Login
     */
    public function login() {
        $json_input = json_decode(file_get_contents('php://input'), true);
        $input = !empty($json_input) ? $json_input : $this->input->post();

        $this->form_validation->set_data($input);
        $this->form_validation->set_rules('email',    'Email',    'required|valid_email');
        $this->form_validation->set_rules('password', 'Password', 'required');

        if ($this->form_validation->run() == FALSE) {
            return $this->_json_response(400, ['status' => 'error', 'errors' => $this->form_validation->error_array()]);
        }

        $this->load->model('User_model');
        $user = $this->User_model->authenticate($input['email'], $input['password']);

        if ($user) {
            $session_data = [
                'user_id'   => $user['id'],
                'upid'      => $user['upid'],
                'role'      => $user['role'],
                'logged_in' => TRUE
            ];
            $this->session->set_userdata($session_data);
            log_activity($user['id'], 'login_success', 'User logged in successfully');
            return $this->_json_response(200, [
                'status'  => 'success',
                'message' => 'Login successful',
                'role'    => $user['role'],
                'upid'    => $user['upid'],
                'user_id' => $user['id'],
            ]);
        } else {
            log_activity(0, 'login_failed', ['attempted_email' => $input['email']]);
            return $this->_json_response(401, ['status' => 'error', 'message' => 'Invalid email or password']);
        }
    }

    /**
     * Endpoint: Password Reset
     */
    public function reset_password() {
        $json_input = json_decode(file_get_contents('php://input'), true);
        $input = !empty($json_input) ? $json_input : $this->input->post();

        $this->form_validation->set_data($input);
        $this->form_validation->set_rules('email',    'Email',    'required|valid_email');
        $this->form_validation->set_rules('password', 'Password', 'required|min_length[8]');

        if ($this->form_validation->run() == FALSE) {
            return $this->_json_response(400, ['status' => 'error', 'errors' => $this->form_validation->error_array()]);
        }

        $hashed_password = password_hash($input['password'], PASSWORD_BCRYPT);
        $success = $this->PasswordResets_model->update_user_password($input['email'], $hashed_password);

        if ($success) {
            $this->PasswordResets_model->delete_token($input['email']);
            $user    = $this->db->get_where('user', ['email' => $input['email']])->row();
            $user_id = $user ? $user->id : 0;
            log_activity($user_id, 'password_reset_success', 'Password reset successfully.');
            return $this->_json_response(200, ['status' => 'success', 'message' => 'Password updated successfully']);
        } else {
            return $this->_json_response(500, ['status' => 'error', 'message' => 'Failed to update password']);
        }
    }

    /**
     * Endpoint: Update Consent
     */
    public function update_consent() {
        $this->load->model('Consent_model');
        $json_input = json_decode(file_get_contents('php://input'), true);
        $input = !empty($json_input) ? $json_input : $this->input->post();

        $this->form_validation->set_data($input);
        $this->form_validation->set_rules('user_id',         'User ID',        'required|integer');
        $this->form_validation->set_rules('research_opt_in', 'Opt-in',         'required|in_list[0,1]');
        $this->form_validation->set_rules('version',         'Policy Version', 'numeric');

        if ($this->form_validation->run() == FALSE) {
            return $this->_json_response(400, ['status' => 'error', 'errors' => $this->form_validation->error_array()]);
        }

        $user_id           = $input['user_id'];
        $logged_in_user_id = $this->session->userdata('user_id');
        if ($logged_in_user_id && $logged_in_user_id != $user_id) {
            return $this->_json_response(403, ['status' => 'error', 'message' => 'Unauthorized']);
        }

        $data = [
            'user_id'               => (int)$user_id,
            'policy_agreed_version' => $input['version'] ?? 1,
            'agreed_at'             => date('Y-m-d'),
            'research_opt_in'       => (int)$input['research_opt_in'],
            'updated_at'            => date('Y-m-d H:i:s')
        ];

        if ($this->Consent_model->save_consent($data)) {
            log_activity($user_id, 'consent_updated', $data);
            return $this->_json_response(200, ['status' => 'success', 'message' => 'Consent recorded successfully']);
        } else {
            return $this->_json_response(500, ['status' => 'error', 'message' => 'Failed to save consent']);
        }
    }

    /**
     * API status check
     */
    public function index() {
        echo json_encode(['status' => 'ok', 'message' => 'Tibyan API is running']);
    }

    private function _json_response($status, $data) {
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header($status)
            ->set_output(json_encode($data));
    }

    public function test_all_dbs() {
        $db1 = $this->load->database('default',    TRUE);
        $db2 = $this->load->database('analytical', TRUE);
        echo "Default DB: "    . ($db1->conn_id ? "Connected" : "Failed") . " (" . $db1->database . ")<br>";
        echo "Analytical DB: " . ($db2->conn_id ? "Connected" : "Failed") . " (" . $db2->database . ")";
    }
}