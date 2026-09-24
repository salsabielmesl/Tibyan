<?php
class Audit_model extends CI_Model {

    public function get_logs($limit = 100, $offset = 0) {
        $this->db->select('activity_logs.*, user.full_name, user.email');
        $this->db->from('activity_logs');
        // Join with user table to see WHO did the action
        $this->db->join('user', 'user.id = activity_logs.user_id', 'left');
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($limit, $offset);
        return $this->db->get()->result_array();
    }

    public function get_logs_by_user($user_id) {
        return $this->db->get_where('activity_logs', ['user_id' => $user_id])->result_array();
    }
    
}