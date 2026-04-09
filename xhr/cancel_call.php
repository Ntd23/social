<?php 
if ($f == 'cancel_call') {
    $user_id = Wo_Secure($wo['user']['user_id']);
    $video_calls = mysqli_query($sqlConnect, "SELECT `id` FROM " . T_VIDEOS_CALLES . " WHERE `from_id` = '$user_id' OR `to_id` = '$user_id'");
    while ($video_call = mysqli_fetch_assoc($video_calls)) {
        Wo_UpdateCallLog($video_call['id'], 'video', 'cancelled', array(
            'provider' => Wo_GetActiveCallProvider('video'),
            'status_by' => $wo['user']['user_id']
        ));
    }
    $audio_calls = mysqli_query($sqlConnect, "SELECT `id` FROM " . T_AUDIO_CALLES . " WHERE `from_id` = '$user_id' OR `to_id` = '$user_id'");
    while ($audio_call = mysqli_fetch_assoc($audio_calls)) {
        Wo_UpdateCallLog($audio_call['id'], 'audio', 'cancelled', array(
            'provider' => Wo_GetActiveCallProvider('audio'),
            'status_by' => $wo['user']['user_id']
        ));
    }
    $agora_calls = mysqli_query($sqlConnect, "SELECT `id`, `type` FROM " . T_AGORA . " WHERE `from_id` = '$user_id' OR `to_id` = '$user_id'");
    while ($agora_call = mysqli_fetch_assoc($agora_calls)) {
        Wo_UpdateCallLog($agora_call['id'], (!empty($agora_call['type']) ? $agora_call['type'] : 'audio'), 'cancelled', array(
            'provider' => 'agora',
            'status_by' => $wo['user']['user_id']
        ));
    }
    $query   = mysqli_query($sqlConnect, "DELETE FROM " . T_VIDEOS_CALLES . " WHERE `from_id` = '$user_id' OR `to_id` = '$user_id'");
    $query   = mysqli_query($sqlConnect, "DELETE FROM " . T_AUDIO_CALLES . " WHERE `from_id` = '$user_id' OR `to_id` = '$user_id'");
    $query   = mysqli_query($sqlConnect, "DELETE FROM " . T_AGORA . " WHERE `from_id` = '$user_id' OR `to_id` = '$user_id'");
    if ($query) {
        $data = array(
            'status' => 200
        );
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
