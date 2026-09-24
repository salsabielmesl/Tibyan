<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['logged_in'])) { header('Location: signin.php'); exit; }

require_once 'common/api.php';

$upid      = $_SESSION['upid'] ?? null;
$date_from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to   = $_GET['to']   ?? date('Y-m-d');

if (empty($upid)) {
    die('No UPID in session. Please log in again.');
}

// Call predict/report — now streams PDF directly from backend
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, rtrim(API_BASE_URL, '/') . '/predict/report');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'upid'      => $upid,
    'date_from' => $date_from,
    'date_to'   => $date_to,
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer tibyan_secret_key_2026',
    'X-User-Id: ' . ($_SESSION['user_id'] ?? ''),
]);
$response  = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$ct        = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($http_code === 200 && strpos($ct, 'application/pdf') !== false) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="tibyan_report_' . $date_from . '_' . $date_to . '.pdf"');
    header('Content-Length: ' . strlen($response));
    echo $response;
    exit;
}

// Fallback error
$error = json_decode($response, true);
?>
<!DOCTYPE html>
<html><head><?php require "common/head.php"; ?></head>
<body>
<?php require "common/navbar2.php"; ?>
<div class="container my-5 text-center">
  <i class="fa-solid fa-triangle-exclamation text-warning fs-1 mb-3"></i>
  <h4>PDF Could Not Be Generated</h4>
  <p class="text-muted"><?= htmlspecialchars($error['payload']['hint'] ?? $error['payload']['error'] ?? 'No predictions found for this date range.') ?></p>
  <a href="javascript:history.back()" class="btn btn-primary">Go Back</a>
</div>
</body></html>