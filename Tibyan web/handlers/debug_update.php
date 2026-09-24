<?php
// handlers/debug_update.php
if (session_status() === PHP_SESSION_NONE) session_start();

$user_id    = $_SESSION['user_id']    ?? null;
$ci_session = $_SESSION['ci_session'] ?? null;
$payload    = json_encode(['full_name' => 'Debug Test']);

// Hit the debug_input endpoint which returns json_body directly
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,            'https://tibyan-backend-fqjmrgkofa-ew.a.run.app/index.php/user/debug_input');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST,           true);
curl_setopt($ch, CURLOPT_POSTFIELDS,     $payload);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HEADER,         false);
curl_setopt($ch, CURLOPT_TIMEOUT,        30);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($payload),
    'Authorization: Bearer tibyan_secret_key_2026',
    'X-User-Id: ' . $user_id,
]);
if ($ci_session) curl_setopt($ch, CURLOPT_COOKIE, 'ci_session=' . $ci_session);

$body1 = curl_exec($ch);
$code1 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Now hit user/update and capture what update() sees inside json_body
$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL,            'https://tibyan-backend-fqjmrgkofa-ew.a.run.app/index.php/user/update');
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_POST,           true);
curl_setopt($ch2, CURLOPT_POSTFIELDS,     $payload);
curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch2, CURLOPT_HEADER,         false);
curl_setopt($ch2, CURLOPT_TIMEOUT,        30);
curl_setopt($ch2, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($payload),
    'Authorization: Bearer tibyan_secret_key_2026',
    'X-User-Id: ' . $user_id,
]);
if ($ci_session) curl_setopt($ch2, CURLOPT_COOKIE, 'ci_session=' . $ci_session);

$body2 = curl_exec($ch2);
$code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

echo '<pre>';
echo "=== debug_input (HTTP $code1) ===\n";
print_r(json_decode($body1, true)['payload'] ?? $body1);

echo "\n=== user/update (HTTP $code2) ===\n";
print_r(json_decode($body2, true)['payload'] ?? $body2);
echo '</pre>';