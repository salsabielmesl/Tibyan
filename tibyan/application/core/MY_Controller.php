<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller {

    protected $current_user_id   = null;
    protected $current_user_role = null;
    protected $version           = "1.0.4";
    protected $json_body         = [];

    public function __construct() {
        parent::__construct();

        $this->_sanitize_global_input();

        // DEBUG: log what json_body contains right after sanitize
        log_message('error', '[MY_Controller] json_body after sanitize: ' . json_encode($this->json_body));

        $auth_header  = $this->input->get_request_header('Authorization', TRUE);
        $session_data = $this->validate_token($auth_header);

        // DEBUG: log token result
        log_message('error', '[MY_Controller] validate_token result: ' . json_encode($session_data));

        if ($session_data === FALSE) {
            $this->_json_response(401, [
                'status'  => 'error',
                'message' => 'Unauthorized: Invalid or missing token'
            ]);
            exit;
        }

        if (is_object($session_data)) {
            $this->current_user_id = $session_data->user_id;

            $user = $this->db->select('role')->get_where('user', ['id' => $this->current_user_id])->row();

            if (!$user) {
                $this->_json_response(403, [
                    'status'  => 'error',
                    'message' => 'Forbidden: Associated user account not found'
                ]);
                exit;
            }

            $this->current_user_role = $user->role;
        }

        // DEBUG: log json_body at end of MY_Controller constructor
        log_message('error', '[MY_Controller] json_body at end of __construct: ' . json_encode($this->json_body));
    }

    private function _sanitize_global_input() {
        if ($this->input->post()) {
            $_POST = array_map(function($value) {
                return is_string($value) ? trim($value) : $value;
            }, $this->input->post());
        }

        $raw_input = file_get_contents('php://input');
        log_message('error', '[sanitize] raw_input: ' . $raw_input);

        if (!empty($raw_input)) {
            $json_data = json_decode($raw_input, true);
            if (is_array($json_data)) {
                array_walk_recursive($json_data, function(&$item) {
                    if (is_string($item)) $item = trim($item);
                });
                $this->json_body = $json_data;
            }
        }

        log_message('error', '[sanitize] json_body set to: ' . json_encode($this->json_body));
    }

    protected function validate_token($header) {
        $token = str_replace('Bearer ', '', trim((string)$header));

        if ($token === 'tibyan_secret_key_2026') {
            $user_id = $this->session->userdata('user_id');
            if (!$user_id) {
                $user_id = $this->input->get_request_header('X-User-Id', TRUE);
            }
            if (!$user_id) return FALSE;
            return (object)['user_id' => (int)$user_id];
        }

        $session = $this->db->get_where('sessions', ['id' => $token])->row();
        return $session ?: FALSE;
    }

    protected function require_role($required_role) {
        if ($this->current_user_role !== $required_role) {
            $this->_json_response(403, [
                'status'  => 'error',
                'message' => 'Forbidden: This action requires ' . $required_role . ' privileges.'
            ]);
            exit;
        }
    }

    protected function _json_response($status_code, $data) {
        $sensitive_keys = ['password', 'password_hashed', 'secret_key', 'salt', 'auth_key'];

        if (!is_array($data)) {
            $data = ['result' => $data];
        }

        $clean_data = json_decode(json_encode($data), true);
        array_walk_recursive($clean_data, function(&$item, $key) use ($sensitive_keys) {
            if (in_array(strtolower($key), $sensitive_keys)) {
                $item = "********";
            }
        });

        $response = [
            'meta' => [
                'version'    => $this->version,
                'timestamp'  => date('c'),
                'request_id' => uniqid('req_'),
            ],
            'payload' => $clean_data
        ];

        if (ob_get_level()) ob_end_clean();

        http_response_code($status_code);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($response);
        exit;
    }

    private function log_security_event($action, $details) {
        $data = [
            'user_id'    => $this->current_user_id,
            'action'     => $action,
            'details'    => is_array($details) ? json_encode($details) : $details,
            'ip_address' => $this->input->ip_address(),
            'created_at' => date('Y-m-d H:i:s')
        ];
        $this->db->insert('activity_logs', $data);
    }
}