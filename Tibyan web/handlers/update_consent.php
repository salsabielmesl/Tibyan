<?php
// handlers/update_consent.php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['logged_in'])) { header('Location: ../signin.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../profile.php'); exit; }
require_once '../common/api.php';

$data = [
    'policy_agreed_version' => (int)($_POST['policy_agreed_version'] ?? 1),
    'research_opt_in'       => isset($_POST['research_opt_in']) ? (int)$_POST['research_opt_in'] : 0,
];

$response = api_call('consent/create', 'POST', $data);
$status   = $response['body']['payload']['status'] ?? $response['body']['status'] ?? '';
$code     = $response['status'] ?? 0;

if ($status === 'success' || in_array($code, [200, 201])) {
    header('Location: ../profile.php?success=' . urlencode('Consent updated successfully')); exit;
} else {
    $msg = $response['body']['payload']['message'] ?? $response['body']['message'] ?? 'Failed to update consent';
    header('Location: ../profile.php?error=' . urlencode($msg)); exit;
}