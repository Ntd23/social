<?php
if ($wo['loggedin'] == false) {
    header("Location: " . Wo_SeoLink('index.php?link1=welcome'));
    exit();
}
if (empty($_GET['call_id'])) {
    header("Location: " . Wo_SeoLink('index.php?link1=welcome'));
    exit();
}
if ($wo['config']['video_chat'] == 0) {
    header("Location: " . Wo_SeoLink('index.php?link1=welcome'));
    exit();
}
$id = Wo_Secure($_GET['call_id']);
$return_url = $wo['config']['site_url'];
if (!empty($_GET['return_url'])) {
    $return_request = urldecode($_GET['return_url']);
    if (strpos($return_request, $wo['config']['site_url']) === 0) {
        $return_url = $return_request;
    }
}
if ($return_url == $wo['config']['site_url'] && !empty($_SERVER['HTTP_REFERER'])) {
    $referer_request = $_SERVER['HTTP_REFERER'];
    if (strpos($referer_request, $wo['config']['site_url']) === 0) {
        $return_url = $referer_request;
    }
}
$data2 = Wo_GetAllDataFromCallID($id);
if (!$data2) {
    header("Location: " . Wo_SeoLink('index.php?link1=welcome'));
    exit();
}
$wo['video_call'] = $data2;
if (in_array($wo['video_call']['provider'], array('jitsi', 'livekit'))) {
    $redirect = Wo_BuildCallJoinUrl($wo['video_call']['room_name'], (!empty($wo['video_call']['call_type']) ? $wo['video_call']['call_type'] : 'video'), array(
        'id' => $wo['video_call']['id'],
        'provider' => $wo['video_call']['provider'],
        'return_url' => $return_url
    ));
    header("Location: " . $redirect);
    exit();
}
if ($wo['video_call']['provider'] == 'agora') {
    $wo['video_call']['call_id'] = $wo['video_call']['id'];
    $wo['video_call']['room'] = $wo['video_call']['room_name'];
}
else {
    if ($wo['video_call']['to_id'] == $wo['user']['user_id']) {
        $wo['video_call']['user']         = 1;
        $wo['video_call']['access_token'] = $wo['video_call']['access_token'];
        $wo['video_call']['call_id']      = $wo['video_call']['id'];
    } else if ($wo['video_call']['from_id'] == $wo['user']['user_id']) {
        $wo['video_call']['user']         = 2;
        $wo['video_call']['access_token'] = $wo['video_call']['access_token_2'];
        $wo['video_call']['call_id']      = $wo['video_call']['id'];
    } else {
        header("Location: " . Wo_SeoLink('index.php?link1=welcome'));
        exit();
    }
    $wo['video_call']['room'] = $wo['video_call']['room_name'];
}
$wo['video_call']['return_url'] = $return_url;
$wo['description'] = $wo['config']['siteDesc'];
$wo['keywords']    = $wo['config']['siteKeywords'];
$wo['page']        = 'video';
$wo['title']       = $wo['config']['siteTitle'];
$wo['content']     = Wo_LoadPage('video/content');
?>
