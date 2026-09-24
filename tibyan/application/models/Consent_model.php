<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Consent_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        // Load default PII database
        $this->load->database();
    }

    /**
     * Records or updates a user's agreement. 
     * Renamed to insert_consent to match the call in Consent.php controller.
     * Using replace() ensures each user only has ONE active consent record.
     */
    public function insert_consent($data) {
        return $this->db->replace('consent_policy', $data);
    }

    /**
     * Records or updates a user's agreement (Alias for insert_consent).
     */
    public function save_consent($data) {
        return $this->insert_consent($data);
    }

    /**
     * Get consent records for a specific user or all users
     */
    public function get_consent($user_id = null) {
        if ($user_id) {
            return $this->db->get_where('consent_policy', ['user_id' => $user_id])->row();
        }
        return $this->db->get('consent_policy')->result();
    }

    /**
     * Specific check for Research Opt-In.
     * Useful for your Data Science scripts to filter valid data.
     */
    public function is_eligible_for_research($user_id) {
        $query = $this->db->get_where('consent_policy', [
            'user_id' => $user_id,
            'research_opt_in' => 1
        ]);
        return $query->num_rows() > 0;
    }

    /**
     * Join Query: Gets user data ONLY for those who consented to research.
     * This is perfect for exporting to your Python/Scikit-learn environment.
     */
    public function get_consented_user_data() {
        $this->db->select('user.*, consent_policy.agreed_at, consent_policy.research_opt_in');
        $this->db->from('user');
        $this->db->join('consent_policy', 'consent_policy.user_id = user.id');
        $this->db->where('consent_policy.research_opt_in', 1);
        return $this->db->get()->result_array();
    }
}