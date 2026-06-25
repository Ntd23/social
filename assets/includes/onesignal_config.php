<?php
function Wo_SendPushNotification($data = array(), $push_type = 'chat') {
    global $sqlConnect, $wo;
    if (empty($data)) {
        return false;
    }
    if (empty($data['notification']['notification_content'])) {
        return false;
    }
    if (empty($data['send_to'])) {
        return false;
    }
    if ($wo['config']['push'] == 0) {
        return false;
    }
    $app_id  = '';
    $app_key = '';
    if ($push_type == 'android_messenger') {
        $app_id  = $wo['config']['android_m_push_id'];
        $app_key = $wo['config']['android_m_push_key'];
    } else if ($push_type == 'ios_messenger') {
        $app_id  = $wo['config']['ios_m_push_id'];
        $app_key = $wo['config']['ios_m_push_key'];
    } else if ($push_type == 'android_native') {
        $app_id  = $wo['config']['android_n_push_id'];
        $app_key = $wo['config']['android_n_push_key'];
    } else if ($push_type == 'ios_native') {
        $app_id  = $wo['config']['ios_n_push_id'];
        $app_key = $wo['config']['ios_n_push_key'];
    } else if ($push_type == 'web') {
        $app_id  = $wo['config']['web_push_id'];
        $app_key = $wo['config']['web_push_key'];
    }
    $data['notification']['notification_content'] = Wo_EmoPhone($data['notification']['notification_content']);
    $data['notification']['notification_content'] = Wo_EditMarkup($data['notification']['notification_content']);
    $final_request_data                           = array(
        'app_id' => $app_id,
        'include_player_ids' => $data['send_to'],
        'send_after' => new \DateTime('1 second'),
        'isChrome' => false,
        'contents' => array(
            'en' => $data['notification']['notification_content']
        ),
        'headings' => array(
            'en' => $data['notification']['notification_title']
        ),
        'android_led_color' => 'FF0000FF',
        'priority' => 10
    );
    if (!empty($data['notification']['notification_data'])) {
        $final_request_data['data'] = $data['notification']['notification_data'];
    }
    if (!empty($data['notification']['notification_image'])) {
        $final_request_data['large_icon'] = $data['notification']['notification_image'];
    }
    $fields = json_encode($final_request_data);
    $ch     = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.onesignal.com/api/v1/notifications");
    curl_setopt($ch, CURLOPT_RESOLVE, array("api.onesignal.com:443:104.17.111.223", "api.onesignal.com:443:104.17.112.223"));
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json; charset=utf-8',
        'Authorization: Basic ' . $app_key
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    $response = curl_exec($ch);
    curl_close($ch);
    $response = json_decode($response);
    if ($response->id) {
        return $response->id;
    }
    return false;
}

/**
 * Gửi call notification đến thiết bị Android/iOS qua OneSignal REST API.
 * Dùng cho cuộc gọi đến (incoming call) để Flutter app hiển thị
 * thông báo cuộc gọi khi app đang ở foreground, background hoặc bị kill
 *
 * LƯU Ý: android_m_push_key chứa OneSignal REST API key (os_v2_app_...),
 * KHÔNG phải Firebase Server Key. Nên phải gửi qua OneSignal API,
 * không gửi trực tiếp qua fcm.googleapis.com.
 *
 * @param string $device_id  OneSignal Subscription ID (Player ID) của người nhận
 * @param array  $call_data  Thông tin cuộc gọi:
 *   - caller_name    : tên người gọi
 *   - caller_avatar  : URL avatar người gọi
 *   - caller_id      : user_id người gọi
 *   - call_id        : ID bản ghi cuộc gọi trong DB
 *   - call_type      : 'audio' | 'video'
 *   - room_name      : tên phòng LiveKit
 *   - call_url       : URL đầy đủ để join cuộc gọi
 * @return bool  true nếu gửi thành công
 */
function Wo_SendFcmCallNotification($calling_user, $call_data = array()) {
    global $wo;
    $log_file = dirname(dirname(__DIR__)) . '/fcm_debug.log';
    $log_msg = "[" . date('Y-m-d H:i:s') . "] Calling Wo_SendFcmCallNotification (OneSignal mode)\n";
    
    $device_ids = array();
    if (is_array($calling_user)) {
        if (!empty($calling_user['android_m_device_id'])) $device_ids[] = $calling_user['android_m_device_id'];
        if (!empty($calling_user['ios_m_device_id'])) $device_ids[] = $calling_user['ios_m_device_id'];
        if (!empty($calling_user['android_n_device_id'])) $device_ids[] = $calling_user['android_n_device_id'];
        if (!empty($calling_user['ios_n_device_id'])) $device_ids[] = $calling_user['ios_n_device_id'];
        if (!empty($calling_user['android_device_id'])) $device_ids[] = $calling_user['android_device_id'];
        if (!empty($calling_user['ios_device_id'])) $device_ids[] = $calling_user['ios_device_id'];
    } elseif (is_string($calling_user)) {
        $device_ids[] = $calling_user;
    }
    
    $device_ids = array_unique(array_filter($device_ids));
    
    $log_msg .= "Device IDs (OneSignal): " . (!empty($device_ids) ? implode(',', $device_ids) : 'EMPTY') . "\n";
    $log_msg .= "Call Data: " . json_encode($call_data) . "\n";

    if (empty($device_ids) || empty($call_data)) {
        $log_msg .= "ERROR: Empty device ID or call data. Exiting.\n\n";
        file_put_contents($log_file, $log_msg, FILE_APPEND);
        return false;
    }

    // OneSignal App ID & REST API Key (cả hai đều là OneSignal, không phải Firebase)
    $app_id  = !empty($wo['config']['android_m_push_id'])  ? $wo['config']['android_m_push_id']  : '';
    $api_key = !empty($wo['config']['android_m_push_key']) ? $wo['config']['android_m_push_key'] : '';

    $log_msg .= "OneSignal App ID: " . ($app_id ? substr($app_id, 0, 12) . '...' : 'EMPTY') . "\n";
    $log_msg .= "OneSignal API Key: " . ($api_key ? substr($api_key, 0, 15) . '...' : 'EMPTY') . "\n";

    if (empty($app_id) || empty($api_key)) {
        $log_msg .= "ERROR: OneSignal App ID or API Key is empty. Exiting.\n\n";
        file_put_contents($log_file, $log_msg, FILE_APPEND);
        return false;
    }

    $caller_name = !empty($call_data['caller_name']) ? (string) $call_data['caller_name'] : 'Cuộc gọi đến';
    $call_type   = !empty($call_data['call_type'])   ? (string) $call_data['call_type']   : 'video';

    // Gửi qua OneSignal REST API v1 — notification kèm data payload
    // OneSignal sẽ chuyển thành FCM message gửi đến thiết bị
    $payload = array(
        'app_id'             => $app_id,
        'include_player_ids' => array_values($device_ids),
        'headings'           => array('en' => $caller_name),
        'contents'           => array('en' => ($call_type == 'audio' ? 'Cuộc gọi thoại đến' : 'Cuộc gọi video đến')),
        'priority'           => 10,
        'ttl'                => 30,
        'buttons'            => array(
            array('id' => 'accept', 'text' => 'Nghe'),
            array('id' => 'decline', 'text' => 'Từ chối')
        ),
        'data'               => array(
            'type'          => 'incoming_call',
            'caller_name'   => $caller_name,
            'caller_avatar' => !empty($call_data['caller_avatar']) ? (string) $call_data['caller_avatar'] : '',
            'caller_id'     => !empty($call_data['caller_id'])     ? (string) $call_data['caller_id']     : '',
            'call_id'       => !empty($call_data['call_id'])       ? (string) $call_data['call_id']       : '',
            'call_type'     => $call_type,
            'room_name'     => !empty($call_data['room_name'])     ? (string) $call_data['room_name']     : '',
            'call_url'      => !empty($call_data['call_url'])      ? (string) $call_data['call_url']      : '',
        ),
    );

    if (!empty($call_data['caller_avatar'])) {
        $payload['large_icon'] = (string) $call_data['caller_avatar'];
    }

    $log_msg .= "OneSignal Payload: " . json_encode($payload) . "\n";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.onesignal.com/api/v1/notifications');
    curl_setopt($ch, CURLOPT_RESOLVE, array("api.onesignal.com:443:104.17.111.223", "api.onesignal.com:443:104.17.112.223"));
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json; charset=utf-8',
        'Authorization: Basic ' . $api_key,
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);

    $log_msg .= "Curl Error: " . ($curl_error ? $curl_error : 'None') . "\n";
    $log_msg .= "Response: " . ($response ? $response : 'EMPTY') . "\n\n";
    file_put_contents($log_file, $log_msg, FILE_APPEND);

    $decoded = json_decode($response, true);
    return !empty($decoded['id']);
}
?>
