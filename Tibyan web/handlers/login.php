<?php
// handlers/login.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../common/api.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../signin.php'); exit;
}

$email    = trim($_POST['email']    ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($email) || empty($password)) {
    header('Location: ../signin.php?error=' . urlencode('Email and password are required')); exit;
}

$response = api_call('auth/login', 'POST', [
    'email'    => $email,
    'password' => $password,
]);

$body   = $response['body'];
$status = $response['status'];

if ($status === 200 && ($body['status'] ?? '') === 'success') {
    if (!empty($response['ci_session'])) {
        $_SESSION['ci_session'] = $response['ci_session'];
    }

    $_SESSION['logged_in']  = true;
    $_SESSION['user_email'] = $email;
    $_SESSION['role']       = $body['role']    ?? 'patient';
    $_SESSION['user_id']    = $body['user_id'] ?? null;
    $_SESSION['upid']       = $body['upid']    ?? $_SESSION['pending_upid'] ?? '';

    // If upid still missing, fetch from profile
    if (empty($_SESSION['upid'])) {
        $pr = api_call('user/profile', 'GET');
        $_SESSION['upid']      = $pr['body']['payload']['upid']      ?? '';
        $_SESSION['user_name'] = $pr['body']['payload']['full_name'] ?? '';
    }

    // Clean up pending upid
    unset($_SESSION['pending_upid']);

    header('Location: ../labtests.php'); exit;
} else {
    $error = $body['message'] ?? 'Invalid email or password';
    header('Location: ../signin.php?error=' . urlencode($error)); exit;
}