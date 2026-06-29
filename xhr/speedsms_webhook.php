<?php
// Handles SpeedSMS delivery webhooks for SMS status tracking.
if ($f == 'speedsms_webhook') {
    header("Content-type: application/json");

    $configured_secret = !empty($wo['config']['speedsms_webhook_secret']) ? (string) $wo['config']['speedsms_webhook_secret'] : '';
    $provided_secret = '';
    if (!empty($_GET['secret'])) {
        $provided_secret = (string) $_GET['secret'];
    } elseif (!empty($_POST['secret'])) {
        $provided_secret = (string) $_POST['secret'];
    } elseif (!empty($_SERVER['HTTP_X_SPEEDSMS_SECRET'])) {
        $provided_secret = (string) $_SERVER['HTTP_X_SPEEDSMS_SECRET'];
    }

    if (empty($configured_secret) || !hash_equals($configured_secret, $provided_secret)) {
        http_response_code(403);
        echo json_encode(array(
            'status' => 403,
            'message' => 'Invalid webhook secret'
        ));
        exit();
    }

    $raw_payload = file_get_contents('php://input');
    $payload = array();
    if (!empty($raw_payload)) {
        $decoded_payload = json_decode($raw_payload, true);
        if (is_array($decoded_payload)) {
            $payload = $decoded_payload;
        }
    }
    if (empty($payload) && !empty($_POST)) {
        $payload = $_POST;
    }

    error_log('SpeedSMS webhook: ' . json_encode(array(
        'time' => time(),
        'payload' => $payload
    )));

    echo json_encode(array(
        'status' => 200
    ));
    exit();
}
