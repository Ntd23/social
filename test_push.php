<?php
require_once('assets/init.php');
header("Content-Type: text/plain");

echo "=== ONE SIGNAL MULTI-USER PUSH TEST ===\n";

$app_id  = !empty($wo['config']['android_m_push_id'])  ? $wo['config']['android_m_push_id']  : '';
$api_key = !empty($wo['config']['android_m_push_key']) ? $wo['config']['android_m_push_key'] : '';

if (empty($app_id) || empty($api_key)) {
    die("ERROR: Missing App ID or API Key in website settings!\n");
}

// Lấy 5 user có token thiết bị mới nhất
$res = mysqli_query($sqlConnect, "SELECT user_id, username, android_m_device_id FROM " . T_USERS . " WHERE android_m_device_id != '' ORDER BY user_id DESC LIMIT 5");

if (!$res || mysqli_num_rows($res) == 0) {
    die("ERROR: No users with token found in database! Please make sure you are logged in on the app.\n");
}

while ($row = mysqli_fetch_assoc($res)) {
    $user_id = $row['user_id'];
    $username = $row['username'];
    $target_token = $row['android_m_device_id'];
    
    echo "\n----------------------------------------\n";
    echo "USER: $username (ID: $user_id)\n";
    echo "Token: $target_token\n";
    
    // 1. Kiểm tra trạng thái trên OneSignal Cloud
    $status_url = "https://onesignal.com/api/v1/players/{$target_token}?app_id={$app_id}";
    $ch_status = curl_init();
    curl_setopt($ch_status, CURLOPT_URL, $status_url);
    curl_setopt($ch_status, CURLOPT_HTTPHEADER, array(
        'Authorization: Basic ' . $api_key,
    ));
    curl_setopt($ch_status, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch_status, CURLOPT_SSL_VERIFYPEER, false);
    $status_response = curl_exec($ch_status);
    $status_http_code = curl_getinfo($ch_status, CURLINFO_HTTP_CODE);
    curl_close($ch_status);
    
    $status_data = json_decode($status_response, true);
    $identifier = isset($status_data['identifier']) ? $status_data['identifier'] : 'NULL';
    $invalid = isset($status_data['invalid_identifier']) && $status_data['invalid_identifier'] ? 'YES' : 'NO';
    
    echo "OneSignal Cloud Info -> HTTP: $status_http_code | Identifier (FCM Token): $identifier | Is Invalid?: $invalid\n";
    
    // 2. Gửi test push cuộc gọi
    $payload = array(
        'app_id'             => $app_id,
        'include_player_ids' => array($target_token),
        'headings'           => array('en' => 'Test Caller'),
        'contents'           => array('en' => 'Test incoming call push notification'),
        'priority'           => 10,
        'content_available'  => true,
        'ttl'                => 30,
        'data'               => array(
            'type'          => 'incoming_call',
            'caller_name'   => 'Test Caller',
            'caller_avatar' => '',
            'caller_id'     => '999',
            'call_id'       => '123',
            'call_type'     => 'video',
            'room_name'     => 'test_room',
            'call_url'      => $wo['config']['site_url'] . '/call_livekit.php?room=test_room&type=video&id=123',
        )
    );
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://onesignal.com/api/v1/notifications');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json; charset=utf-8',
        'Authorization: Basic ' . $api_key,
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);
    
    echo "Push Response: $response\n";
}
?>
