<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Subscription extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Subscription_model');
        header("Content-Type: application/json");
    }

    /**
     * POST: Create a new subscription
     * Only allow creating a subscription for the LOGGED-IN user.
     */
    public function create() {
        $input = $this->json_body;

        if (!$input || empty($input['status'])) {
            $this->output->set_status_header(400);
            echo json_encode(['status' => 'error', 'message' => 'Missing status']);
            return;
        }

        $data = [
            // Use current_user_id from token, don't trust the JSON input for user_id
            'user_id'      => $this->current_user_id,
            'status'       => $input['status'], 
            'renewal_date' => $input['renewal_date'] ?? NULL,
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s')
        ];

        if ($this->Subscription_model->insert_subscription($data)) {
            $this->output->set_status_header(201);
            echo json_encode(['status' => 'success', 'id' => $this->db->insert_id()]);
        } else {
            $this->output->set_status_header(500);
            echo json_encode(['status' => 'error', 'message' => 'Database insert failed']);
        }
    }

    /**
     * GET: Fetch subscriptions
     * AUTHORIZATION: Patients see only their own. Admins can see all.
     */
    public function index($id = null) {
        // If the user is a patient, they can only see their own subscriptions
        if ($this->current_user_role === 'patient') {
            $result = $this->Subscription_model->get_user_subscriptions($this->current_user_id);
        } else {
            // Admins/Researchers can see everything
            $result = $this->Subscription_model->get_subscriptions($id);
        }
        
        echo json_encode($result);
    }
}