<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CMS Content Controller
 * Coordinates admin panel dashboard content updates via Cms_model.
 */
class Cms extends MY_Controller {

    public function __construct() {
        parent::__construct();
        // Enforce structural security validation filters
        $this->require_role('admin'); 
        
        // Load the content manager database model
        $this->load->model('Cms_model');
    }

    /**
     * POST/PUT Dynamic Routing Handler for sections
     * Endpoint: POST /admin/cms/update_section/{table_name}
     */
    public function update_section($table_name = null) {
        $valid_tables = ['carousel', 'about_us', 'services', 'features', 'about_page', 'contact_page'];

        if (!$table_name || !in_array($table_name, $valid_tables)) {
            return $this->_json_response(400, ['status' => 'error', 'message' => 'Invalid landing section context requested']);
        }

        // Intercept incoming payload structure forms
        $input = $this->json_body;
        if (empty($input)) {
            $input = $this->input->post();
        }

        $id = isset($input['id']) ? $input['id'] : null;
        if (!$id) {
            return $this->_json_response(400, ['status' => 'error', 'message' => 'Missing target content record key (id)']);
        }

        unset($input['id']); // Restrict primary database key manipulation changes

        // Intercept and cleanly route form attachment data
        if (!empty($_FILES)) {
            $uploaded_files = $this->_handle_file_uploads($table_name);
            foreach ($uploaded_files as $field_name => $file_path) {
                $input[$field_name] = $file_path;
            }
        }

        // Safeguard transaction execution using an error interception block
        try {
            // Commit modifications utilizing our model layer data context structures
            $execution_status = $this->Cms_model->update_section_data($table_name, $id, $input);

            if ($execution_status) {
                return $this->_json_response(200, [
                    'status' => 'success', 
                    'message' => ucfirst(str_replace('_', ' ', $table_name)) . ' updated successfully'
                ]);
            } else {
                return $this->_json_response(400, [
                    'status' => 'error', 
                    'message' => 'Database rejected the modification query. Verify row record ID exists.'
                ]);
            }
        } catch (Exception $e) {
            // Gracefully catch engine faults and hand back organized diagnostics without throwing a 500
            return $this->_json_response(500, [
                'status' => 'error', 
                'message' => 'An internal query driver exception was encountered: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Native File Upload Processor Utility
     */
    private function _handle_file_uploads($section) {
        $saved_paths = [];
        $config['upload_path']   = './uploads/' . $section . '/';
        $config['allowed_types'] = 'gif|jpg|png|jpeg|webp|svg';
        $config['max_size']      = 2048; 
        $config['encrypt_name']  = TRUE; 

        if (!is_dir($config['upload_path'])) {
            mkdir($config['upload_path'], 0777, TRUE);
        }

        $this->load->library('upload', $config);

        foreach ($_FILES as $field_key => $file_data) {
            if (!empty($file_data['name'])) {
                $this->upload->initialize($config);
                if ($this->upload->do_upload($field_key)) {
                    $upload_metadata = $this->upload->data();
                    $saved_paths[$field_key] = $section . '/' . $upload_metadata['file_name'];
                }
            }
        }
        return $saved_paths;
    }
}