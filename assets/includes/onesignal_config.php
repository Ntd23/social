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
    curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
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
 * Gửi FCM data-only call notification đến thiết bị Android/iOS.
 * Dùng cho cuộc gọi đến (incoming call) để Flutter hiển thị màn hình
 * incoming call khi app đang ở background hoặc bị kill.
 *
 * @param string $fcm_token  FCM registration token của thiết bị người nhận
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
function Wo_SendFcmCallNotification($fcm_token = '', $call_data = array()) {
    global $wo;
    if (empty($fcm_token) || empty($call_data)) {
        return false;
    }
    $server_key = !empty($wo['config']['android_m_push_key']) ? $wo['config']['android_m_push_key'] : '';
    if (empty($server_key)) {
        return false;
    }
    // Data-only message: KHÔNG có 'notification' block
    // → Flutter background handler sẽ nhận và hiển thị incoming call UI
    $payload = array(
        'to'           => $fcm_token,
        'priority'     => 'high',      // bắt buộc để wakeup app khi bị kill/background
        'time_to_live' => 30,          // hết hạn sau 30 giây (cuộc gọi không còn ý nghĩa)
        'data'         => array(
            'type'          => 'incoming_call',
            'caller_name'   => !empty($call_data['caller_name'])   ? (string) $call_data['caller_name']   : '',
            'caller_avatar' => !empty($call_data['caller_avatar']) ? (string) $call_data['caller_avatar'] : '',
            'caller_id'     => !empty($call_data['caller_id'])     ? (string) $call_data['caller_id']     : '',
            'call_id'       => !empty($call_data['call_id'])       ? (string) $call_data['call_id']       : '',
            'call_type'     => !empty($call_data['call_type'])     ? (string) $call_data['call_type']     : 'video',
            'room_name'     => !empty($call_data['room_name'])     ? (string) $call_data['room_name']     : '',
            'call_url'      => !empty($call_data['call_url'])      ? (string) $call_data['call_url']      : '',
        ),
        'android' => array(
            'priority' => 'high',
        ),
        'apns' => array(
            'headers' => array(
                'apns-priority' => '10',
                'apns-push-type' => 'background',
            ),
            'payload' => array(
                'aps' => array(
                    'content-available' => 1,
                ),
            ),
        ),
    );
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: key=' . $server_key,
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    curl_close($ch);
    $decoded = json_decode($response, true);
    return !empty($decoded['success']) && $decoded['success'] > 0;
}
?>
