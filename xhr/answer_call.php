<?php 
if ($f == 'answer_call') {
    $data = array(
        'status' => 404
    );
    if (!empty($_GET['id']) && !empty($_GET['type'])) {
        $id = Wo_Secure($_GET['id']);
        $user_id = Wo_Secure($wo['user']['user_id']);
        $claim_id = Wo_GetCallSessionClaim($user_id);
        if ($_GET['type'] == 'audio') {
            if ($wo['config']['agora_chat_video'] == 1) {
                $query = mysqli_query($sqlConnect, "UPDATE " . T_AGORA . " SET `active` = 1, `status` = 'answered', `called` = '{$claim_id}' WHERE `id` = '$id' AND `to_id` = '$user_id' AND `active` = '0' AND (`declined` = '0' OR `declined` IS NULL) AND `status` = 'calling'");
            } else {
                $query = mysqli_query($sqlConnect, "UPDATE " . T_AUDIO_CALLES . " SET `active` = 1, `status` = 'answered', `called` = '{$claim_id}' WHERE `id` = '$id' AND `to_id` = '$user_id' AND `active` = '0' AND (`declined` = '0' OR `declined` IS NULL) AND (`status` = '' OR `status` = 'calling')");
            }
            if (mysqli_affected_rows($sqlConnect) > 0) {
                Wo_UpdateCallLog($id, 'audio', 'answered', array(
                    'provider' => ($wo['config']['agora_chat_video'] == 1 ? 'agora' : 'twilio'),
                    'started_at' => time(),
                    'status_by' => $wo['user']['user_id']
                ));
            }
        } else {
            if ($wo['config']['agora_chat_video'] == 1) {
                $query = mysqli_query($sqlConnect, "UPDATE " . T_AGORA . " SET `active` = 1, `status` = 'answered', `called` = '{$claim_id}' WHERE `id` = '$id' AND `to_id` = '$user_id' AND `active` = '0' AND (`declined` = '0' OR `declined` IS NULL) AND `status` = 'calling'");
            } else {
                $query = mysqli_query($sqlConnect, "UPDATE " . T_VIDEOS_CALLES . " SET `active` = 1, `status` = 'answered', `called` = '{$claim_id}' WHERE `id` = '$id' AND `to_id` = '$user_id' AND `active` = '0' AND (`declined` = '0' OR `declined` IS NULL) AND (`status` = '' OR `status` = 'calling')");
            }
            if (mysqli_affected_rows($sqlConnect) > 0) {
                Wo_UpdateCallLog($id, 'video', 'answered', array(
                    'provider' => ($wo['config']['agora_chat_video'] == 1 ? 'agora' : 'twilio'),
                    'started_at' => time(),
                    'status_by' => $wo['user']['user_id']
                ));
            }
        }
        if (!empty($query) && mysqli_affected_rows($sqlConnect) > 0) {
            $data = array(
                'status' => 200
            );
            if ($_GET['type'] == 'audio') {
                if ($wo['config']['agora_chat_video'] == 1) {
                    $query = mysqli_query($sqlConnect, "SELECT * FROM " . T_AGORA . " WHERE `id` = '{$id}'");
                }
                else{
                    $query = mysqli_query($sqlConnect, "SELECT * FROM " . T_AUDIO_CALLES . " WHERE `id` = '{$id}'");
                }
                
                $sql   = mysqli_fetch_assoc($query);
                if (!empty($sql) && is_array($sql)) {
                    $wo['incall']                 = $sql;
                    $wo['incall']['in_call_user'] = Wo_UserData($sql['from_id']);
                    if ($wo['incall']['to_id'] == $wo['user']['user_id']) {
                        $wo['incall']['user']         = 1;
                        $wo['incall']['access_token'] = $wo['incall']['access_token'];
                    } else if ($wo['incall']['from_id'] == $wo['user']['user_id']) {
                        $wo['incall']['user']         = 2;
                        $wo['incall']['access_token'] = $wo['incall']['access_token_2'];
                    }
                    $user_1               = Wo_UserData($wo['incall']['from_id']);
                    $user_2               = Wo_UserData($wo['incall']['to_id']);
                    $wo['incall']['room'] = $wo['incall']['room_name'];
                    $data['calls_html']   = Wo_LoadPage('modals/talking');
                }
            }
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
