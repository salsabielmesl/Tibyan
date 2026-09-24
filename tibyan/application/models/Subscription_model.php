<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Subscription_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        // Load default PII database
        $this->load->database();
    }

    // Insert new record
    public function insert_subscription($data) {
        return $this->db->insert('subscription', $data);
    }

    // Get records (all or single)
    public function get_subscriptions($id = null) {
        if ($id) {
            return $this->db->get_where('subscription', ['id' => $id])->row();
        }
        return $this->db->get('subscription')->result();
    }
    public function get_user_subscriptions($user_id) {

        return $this->db->get_where('subscription', ['user_id' => $user_id])->result_array();
    }

}