<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        // Load the default database (tibyan_pii)
        $this->load->database();
    }

    /**
     * Authenticate a user by email and password
     * Points to 'user' table
     */
    public function authenticate($email, $password) {
    // 1. Find the user by email
    $user = $this->db->get_where('user', ['email' => $email])->row();

    if ($user) {
        // 2. USE password_verify to check the BCRYPT hash
        // This is the secure way to handle passwords in Senior 2 projects.
        if (password_verify($password, $user->password_hashed)) {
            // Return as an array so Auth.php can access it like $user['id']
            return (array) $user; 
        }
    }

    return FALSE; 
}

    /**
     * Fetch profile for a specific logged-in user
     * Added 'role' to support Step 2: Authorization
     */
    public function get_user_profile($user_id) {
        // We include 'role' here so Auth_Controller can check permissions
        $this->db->select('id, upid, email, full_name, role, date_of_birth, contact_info, mfa_enabled, last_login_at');
        return $this->db->get_where('user', ['id' => $user_id])->row();
    }

    // GET: Retrieve user(s) from 'user' table
    public function get_users($id = null) {
        if ($id) {
            return $this->db->get_where('user', ['id' => $id])->row();
        }
        return $this->db->get('user')->result();
    }

    // POST: Insert a new user profile into 'user'
    public function insert_user($data) {
        return $this->db->insert('user', $data);
    }

    // PUT: Update profile in 'user'
    public function update_user($id, $data) {
        $this->db->where('id', $id)->update('user', $data);
        return ($this->db->affected_rows() >= 0); 
    }

    // DELETE: Remove from 'user'
    public function delete_user($id) {
        $this->db->where('id', $id)->delete('user');
        return ($this->db->affected_rows() > 0);
    }
}