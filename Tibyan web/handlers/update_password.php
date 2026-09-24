<?php
// handlers/update_password.php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['logged_in'])) { header('Location: ../signin.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../profile.php'); exit; }
require_once '../common/api.php';

$old = trim($_POST['old_password']    ?? '');
$new = trim($_POST['new_password']    ?? '');
$con = trim($_POST['confirm_password'] ?? '');

if (empty($old) || empty($new) || empty($con)) {
    header('Location: ../profile.php?error=' . urlencode('All password fields are required')); exit;
}
if ($new !== $con) {
    header('Location: ../profile.php?error=' . urlencode('New passwords do not match')); exit;
}
if (strlen($new) < 8) {
    header('Location: ../profile.php?error=' . urlencode('Password must be at least 8 characters')); exit;
}

// Use POST instead of PUT — App Engine strips PUT request bodies
$response = api_call('user/update', 'POST', [
    'old_password' => $old,
    'password'     => $new,
]);

$payload = $response['body']['payload'] ?? $response['body'] ?? [];
$status  = $payload['status'] ?? '';
$code    = $response['status'] ?? 0;

if ($status === 'success' || $code === 200) {
    header('Location: ../profile.php?success=' . urlencode('Password updated successfully')); exit;
} else {
    $errors = isset($payload['errors']) ? ' (' . implode(', ', $payload['errors']) . ')' : '';
    $msg    = ($payload['message'] ?? 'Failed to update password') . $errors;
    header('Location: ../profile.php?error=' . urlencode($msg)); exit;
}