<?php

define('API_BASE_URL', 'https://tibyan-backend-tfm64sq5hq-ew.a.run.app/index.php');

function api_call($endpoint, $method = 'GET', $data = null) {
    $ci_session = $_SESSION['ci_session'] ?? null;
    $user_id    = $_SESSION['user_id']    ?? null;

    $ch  = curl_init();
    $url = API_BASE_URL . '/' . ltrim($endpoint, '/');

    curl_setopt($ch, CURLOPT_URL,            $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER,         true);
    curl_setopt($ch, CURLOPT_TIMEOUT,        30);

    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer tibyan_secret_key_2026',
    ];

    // Send user_id so the backend can resolve it from the static token path
    if ($user_id) {
        $headers[] = 'X-User-Id: ' . $user_id;
    }

    if ($ci_session) {
        curl_setopt($ch, CURLOPT_COOKIE, 'ci_session=' . $ci_session);
    }

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif (in_array($method, ['PUT', 'DELETE'])) {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($data !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response   = curl_exec($ch);
    $header_sz  = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $http_code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        return ['status' => 0, 'body' => ['status' => 'error', 'message' => $curl_error], 'ci_session' => null];
    }

    $resp_headers = substr($response, 0, $header_sz);
    $body         = substr($response, $header_sz);

    $new_session = null;
    if (preg_match('/Set-Cookie:\s*ci_session=([^;]+)/i', $resp_headers, $m)) {
        $new_session = $m[1];
    }

    return [
        'status'     => $http_code,
        'body'       => json_decode($body, true) ?? [],
        'ci_session' => $new_session,
    ];
}