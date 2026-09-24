<?php
// handlers/withdraw_consent.php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['logged_in'])) { header('Location: ../signin.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../profile.php'); exit; }
require_once '../common/api.php';

// Re-save consent with today's withdrawal date by calling consent/create
// The backend's replace() will update the existing record
$data = [
    'policy_agreed_version'  => 1,
    'research_opt_in'        => 0,
    'withdrawn_requested_at' => date('Y-m-d'),
];

$response = api_call('consent/create', 'POST', $data);
$status   = $response['body']['payload']['status'] ?? $response['body']['status'] ?? '';
$code     = $response['status'] ?? 0;

if ($status === 'success' || in_array($code, [200, 201])) {
    header('Location: ../profile.php?success=' . urlencode('Consent withdrawn successfully')); exit;
} else {
    $msg = $response['body']['payload']['message'] ?? $response['body']['message'] ?? 'Failed to withdraw consent';
    header('Location: ../profile.php?error=' . urlencode($msg)); exit;
}