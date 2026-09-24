<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../common/api.php';

$upid = $_SESSION['upid'] ?? 'NOT SET';

echo '<pre>';
echo "UPID in session: $upid\n\n";

// Step 1: just auth token
$ML_BASE = 'https://tibyan-api-tfm64sq5hq-uc.a.run.app';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $ML_BASE . '/auth/token');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['upid' => $upid]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$body = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err  = curl_error($ch);
curl_close($ch);

echo "=== ML /auth/token ===\n";
echo "HTTP: $code\n";
echo "Error: $err\n";
echo "Body: $body\n\n";

// Step 2: call predict/run via our backend
echo "=== Backend predict/run ===\n";
$r = api_call('predict/run', 'POST', ['upid' => $upid]);
echo "HTTP: " . $r['status'] . "\n";
print_r($r['body']);
echo '</pre>';