<?php
// handlers/register.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../common/api.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['status' => 'error', 'message' => 'No data received']);
    exit;
}

$response = api_call('auth/register', 'POST', $input);
$body     = $response['body'];
$status   = $response['status'];

if ($status === 201 || ($body['status'] ?? '') === 'success') {
    // Store upid in session immediately so it's available after login
    if (!empty($body['upid'])) {
        $_SESSION['pending_upid'] = $body['upid'];
    }
    echo json_encode([
        'status'  => 'success',
        'message' => 'Account created successfully',
        'upid'    => $body['upid'] ?? ''
    ]);
} else {
    $errors  = $body['errors']  ?? null;
    $message = $body['message'] ?? 'Registration failed';
    echo json_encode([
        'status'  => 'error',
        'message' => $message,
        'errors'  => $errors
    ]);
}