<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class PasswordResets_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Step 1: Save or Update a reset token for a user
     */
    public function upsert_token($data) {
        return $this->db->replace('password_reset_tokens', $data);
    }

    /**
     * Step 2: Verify if a token is valid for a specific email
     */
    public function verify_token($email, $token) {
        return $this->db->get_where('password_reset_tokens', [
            'email' => $email,
            'token' => $token
        ])->row();
    }

    /**
     * Step 3: The actual password update in the 'user' table
     */
    public function update_user_password($email, $new_password_hashed) {
        $this->db->where('email', $email);
        return $this->db->update('user', [
            'password_hashed' => $new_password_hashed,
            'updated_at'      => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Step 4: Cleanup - Delete the token after use
     */
    public function delete_token($email) {
        return $this->db->delete('password_reset_tokens', ['email' => $email]);
    }
}