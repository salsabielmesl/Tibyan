<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Modelversions extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('ModelVersions_model');
    }

    public function index($id = null) {
        $result = $this->ModelVersions_model->get_versions($id);
        return $this->_json_response(200, $result);
    }

    public function create() {
        // High-level system actions should ideally be admin-only
        $this->require_role('admin');

        $input = $this->json_body;

        if (empty($input['id']) || empty($input['model_type'])) {
            return $this->_json_response(400, ['error' => 'ID and Model Type are required']);
        }

        $data = [
            'id'                   => $input['id'],
            'model_type'           => $input['model_type'],
            'algorithm_used'       => $input['algorithm_used'] ?? null,
            'training_data_source' => $input['training_data_source'] ?? null,
            'training_end_date'    => $input['training_end_date'] ?? null,
            'created_at'           => date('Y-m-d H:i:s'),
            'updated_at'           => date('Y-m-d H:i:s')
        ];

        if ($this->ModelVersions_model->insert_version($data)) {
            return $this->_json_response(201, ['message' => 'Model version created']);
        } else {
            return $this->_json_response(500, ['error' => 'Database failure']);
        }
    }
}