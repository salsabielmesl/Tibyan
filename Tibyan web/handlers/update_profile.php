<?php
// handlers/update_profile.php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['logged_in'])) { header('Location: ../signin.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../profile.php'); exit; }
require_once '../common/api.php';

$data = array_filter([
    'full_name'     => trim($_POST['full_name']     ?? ''),
    'email'         => trim($_POST['email']         ?? ''),
    'date_of_birth' => trim($_POST['date_of_birth'] ?? ''),
    'contact_info'  => trim($_POST['contact_info']  ?? ''),
]);

if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    header('Location: ../profile.php?error=' . urlencode('Invalid email address')); exit;
}

if (empty($data)) {
    header('Location: ../profile.php?error=' . urlencode('No changes provided')); exit;
}

$response = api_call('user/update', 'POST', $data);
$payload  = $response['body']['payload'] ?? $response['body'] ?? [];
$status   = $payload['status'] ?? '';
$code     = $response['status'] ?? 0;

if ($status === 'success' || $code === 200) {
    // Update session so profile page reflects changes immediately
    if (!empty($data['full_name'])) $_SESSION['user_name']  = $data['full_name'];
    if (!empty($data['email']))     $_SESSION['user_email'] = $data['email'];
    if (!empty($data['contact_info'])) $_SESSION['contact_info'] = $data['contact_info'];
    if (!empty($data['date_of_birth'])) $_SESSION['date_of_birth'] = $data['date_of_birth'];
    header('Location: ../profile.php?success=' . urlencode('Profile updated successfully')); exit;
} else {
    $msg = $payload['message'] ?? 'Failed to update profile';
    header('Location: ../profile.php?error=' . urlencode($msg)); exit;
}