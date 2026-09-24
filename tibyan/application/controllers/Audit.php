<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Audit extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Audit_model');
        // SECURITY: Only Admins/Researchers should see logs
        $this->require_role('admin'); 
    }

    public function index() {
        $logs = $this->Audit_model->get_logs();
        
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(200)
            ->set_output(json_encode([
                'status' => 'success',
                'total_logs' => count($logs),
                'data' => $logs
            ]));
    }
}