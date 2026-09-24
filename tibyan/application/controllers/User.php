<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User extends MY_Controller {

    public function __construct() {
        $current_url = $_SERVER['REQUEST_URI'];
        $is_registration = (strpos($current_url, 'user/create') !== false);

        if ($is_registration) {
            CI_Controller::__construct();
        } else {
            parent::__construct();
        }

        $this->load->model('User_model');

        // Save json_body before loading form_validation — CI3's form_validation
        // library re-reads input internally and resets $this->json_body to [].
        $saved_json_body = $this->json_body;
        $this->load->library('form_validation');
        $this->json_body = $saved_json_body;
    }

    // TEMPORARY DEBUG - remove after fix
    public function debug_input() {
        $raw = file_get_contents('php://input');
        return $this->_json_response(200, [
            'json_body'      => $this->json_body,
            'raw_input'      => $raw,
            'raw_length'     => strlen($raw),
            'post'           => $_POST,
            'content_type'   => $_SERVER['CONTENT_TYPE'] ?? 'not set',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'not set',
        ]);
    }

    public function profile() {
        $result = $this->User_model->get_user_profile($this->current_user_id);
        if ($result) {
            return $this->_json_response(200, $result);
        } else {
            return $this->_json_response(404, ['status' => 'error', 'message' => 'Profile not found']);
        }
    }

    public function index($id = null) {
        $this->require_role('admin');
        $result = $this->User_model->get_users($id);
        return $this->_json_response(200, $result);
    }

    public function update($id = null) {
        if ($this->current_user_role !== 'admin') {
            $id = $this->current_user_id;
        }

        $input = $this->json_body;

        // DEBUG: log what we actually got
        log_message('error', 'UPDATE json_body: ' . json_encode($input));
        log_message('error', 'UPDATE raw: ' . file_get_contents('php://input'));

        if (empty($input)) {
            return $this->_json_response(400, [
                'status'  => 'error',
                'message' => 'No input provided - json_body was empty',
                'debug'   => [
                    'json_body' => $this->json_body,
                    'method'    => $_SERVER['REQUEST_METHOD'] ?? '',
                    'ct'        => $_SERVER['CONTENT_TYPE'] ?? '',
                ]
            ]);
        }

        // Manual validation — avoids CI3 form_validation caching issues
        if (isset($input['email']) && !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->_json_response(400, [
                'status'  => 'error',
                'message' => 'Invalid email address'
            ]);
        }

        $fields = ['email', 'full_name', 'date_of_birth', 'contact_info', 'mfa_enabled'];
        $data = [];
        foreach ($fields as $f) {
            if (isset($input[$f])) $data[$f] = $input[$f];
        }

        if (!empty($input['password'])) {
            if (empty($input['old_password'])) {
                return $this->_json_response(400, [
                    'status'  => 'error',
                    'message' => 'Current password is required to set a new one'
                ]);
            }
            $current = $this->db
                ->select('password_hashed')
                ->get_where('user', ['id' => $id])
                ->row();
            if (!$current || !password_verify($input['old_password'], $current->password_hashed)) {
                return $this->_json_response(401, [
                    'status'  => 'error',
                    'message' => 'Current password is incorrect'
                ]);
            }
            $data['password_hashed'] = password_hash($input['password'], PASSWORD_BCRYPT);
        }

        $data['updated_at'] = date('Y-m-d H:i:s');

        if ($this->User_model->update_user($id, $data)) {
            return $this->_json_response(200, ['status' => 'success', 'message' => 'User updated']);
        } else {
            return $this->_json_response(400, ['status' => 'error', 'message' => 'No changes made or invalid ID']);
        }
    }

    public function create() {
        $json_input = $this->json_body;
        $input = !empty($json_input) ? $json_input : $this->input->post();

        $this->form_validation->set_data($input);
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email|is_unique[user.email]');
        $this->form_validation->set_rules('password', 'Password', 'required|min_length[4]');
        $this->form_validation->set_rules('upid', 'UPID', 'required|is_unique[user.upid]');

        if ($this->form_validation->run() == FALSE) {
            return $this->_json_response(400, [
                'status' => 'error',
                'errors' => $this->form_validation->error_array()
            ]);
        }

        $data = [
            'upid'            => $input['upid'],
            'email'           => $input['email'],
            'password_hashed' => password_hash($input['password'], PASSWORD_BCRYPT),
            'full_name'       => $input['full_name'] ?? 'New User',
            'role'            => $input['role'] ?? 'patient',
            'date_of_birth'   => $input['date_of_birth'] ?? '2000-01-01',
            'contact_info'    => $input['contact_info'] ?? '',
            'created_at'      => date('Y-m-d H:i:s')
        ];

        if ($this->User_model->insert_user($data)) {
            $new_user_id = $this->db->insert_id();
            $analytical_db = $this->load->database('analytical', TRUE);
            $link_data = [
                'user_id'    => $new_user_id,
                'upid'       => $data['upid'],
                'created_at' => date('Y-m-d H:i:s')
            ];
            $analytical_db->insert('upid_links', $link_data);
            return $this->_json_response(201, [
                'status'  => 'success',
                'message' => 'User created and medical ID linked successfully'
            ]);
        } else {
            return $this->_json_response(500, [
                'status'  => 'error',
                'message' => 'Database error during user creation'
            ]);
        }
    }

    public function delete($id) {
        $this->require_role('admin');
        if ($this->User_model->delete_user($id)) {
            return $this->_json_response(200, ['status' => 'success', 'message' => 'User deleted']);
        } else {
            return $this->_json_response(404, ['status' => 'error', 'message' => 'Invalid ID or already deleted']);
        }
    }
}