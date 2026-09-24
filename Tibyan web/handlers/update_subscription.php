<?php
// handlers/update_subscription.php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['logged_in'])) { header('Location: ../signin.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../profile.php'); exit; }
require_once '../common/api.php';

$data = [
    'status'       => $_POST['status']       ?? 'active',
    'renewal_date' => $_POST['renewal_date'] ?? null,
];

$response = api_call('subscription/create', 'POST', $data);
$code     = $response['status'] ?? 0;

if (in_array($code, [200, 201])) {
    header('Location: ../profile.php?success=' . urlencode('Subscription updated successfully')); exit;
} else {
    $msg = $response['body']['payload']['message'] ?? $response['body']['message'] ?? 'Failed to update subscription';
    header('Location: ../profile.php?error=' . urlencode($msg)); exit;
}