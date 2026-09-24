<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Upidlinks extends MY_Controller {

    public function __construct() {
        parent::__construct(); 
        $this->load->model('UpidLinks_model');
    }

    /**
     * GET: View existing UPID links, only viewed by admin
     */
    public function index($upid = null) {
        // Add this line to restrict the list to admins
        $this->require_role('admin');

        $result = $this->UpidLinks_model->get_links($upid);
        return $this->_json_response(200, $result);
    }

    /**
     * POST: Create a new UPID link
     */
    public function create() {
        // Enforce admin-only access for creating patient identifiers
        $this->require_role('admin');

        $input = $this->json_body;

        if (empty($input['upid'])) {
            return $this->_json_response(400, ['error' => 'UPID is required']);
        }

        $data = [
            'upid'               => $input['upid'],
            'creation_date'      => date('Y-m-d'),
            'last_activity_date' => date('Y-m-d H:i:s'),
            'created_at'         => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s')
        ];

        if ($this->UpidLinks_model->insert_link($data)) {
            // Keeping your multi-database logic
            $analytical_db = $this->load->database('analytical', TRUE);
            $id = $analytical_db->insert_id(); 
            
            return $this->_json_response(201, [
                'status' => 'success', 
                'id' => $id,
                'message' => 'UPID link created in analytical storage'
            ]);
        } else {
            return $this->_json_response(500, ['status' => 'error', 'msg' => 'Database insert failed']);
        }
    }
}