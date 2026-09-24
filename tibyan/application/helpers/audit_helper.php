<?php
function log_activity($user_id, $action, $details = null) {
    $CI =& get_instance();
    $CI->db->insert('activity_logs', [
        'user_id'    => $user_id,
        'action'     => $action,
        'details'    => is_array($details) ? json_encode($details) : $details,
        'ip_address' => $CI->input->ip_address()
    ]);
}