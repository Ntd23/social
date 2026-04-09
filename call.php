<?php
require_once('assets/init.php');

if ($wo['loggedin'] == false) {
    header("Location: " . $wo['config']['site_url']);
    exit();
}

$roomRequest = isset($_GET['room']) ? $_GET['room'] : '';
$roomRequest = is_string($roomRequest) ? trim($roomRequest) : $roomRequest;
$callType = (isset($_GET['type']) && $_GET['type'] == 'audio') ? 'audio' : 'video';
$callId = !empty($_GET['id']) ? intval($_GET['id']) : 0;
$provider = !empty($_GET['provider']) ? Wo_NormalizeCallProvider($_GET['provider'], $callType) : '';
$callSource = false;

if ($callId > 0) {
    $callSource = Wo_GetCallSourceById($callId, $callType);
}
if (empty($callSource) && !empty($roomRequest)) {
    $callSource = Wo_GetCallSourceByRoomName($roomRequest, intval($wo['user']['user_id']), $callType);
}

if (!empty($callSource)) {
    $provider = !empty($provider) ? $provider : $callSource['provider'];
    $callType = !empty($callSource['call_type']) ? $callSource['call_type'] : $callType;
    if ($callId <= 0 && !empty($callSource['id'])) {
        $callId = intval($callSource['id']);
    }
}

$provider = Wo_NormalizeCallProvider($provider, $callType);

if ($provider === 'livekit') {
    $target = 'call_livekit.php';
}
else if ($provider === 'jitsi') {
    $target = 'call_jitsi.php';
}
else if (!empty($callSource) && $callType === 'video') {
    if ($provider === 'agora') {
        header("Location: " . $wo['config']['site_url'] . '/video-call/' . urlencode($callSource['room_name']));
        exit();
    }
    if ($provider === 'twilio') {
        header("Location: " . $wo['config']['site_url'] . '/video-call/' . intval($callSource['id']));
        exit();
    }
    $target = 'call_jitsi.php';
}
else {
    $target = 'call_jitsi.php';
}

$params = $_GET;
$params['provider'] = $provider;
if ($callId > 0) {
    $params['id'] = $callId;
}
if ($callType === 'audio') {
    $params['type'] = 'audio';
}
if (is_string($roomRequest) && $roomRequest !== '') {
    $params['room'] = $roomRequest;
}

header("Location: " . $wo['config']['site_url'] . '/' . $target . '?' . http_build_query($params));
exit();
