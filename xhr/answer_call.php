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
            $provider = Wo_GetActiveCallProvider('audio');
            $query = mysqli_query($sqlConnect, "UPDATE " . T_AUDIO_CALLES . " SET `active` = 1, `status` = 'answered', `called` = '{$claim_id}' WHERE `id` = '$id' AND `to_id` = '$user_id' AND `active` = '0' AND (`declined` = '0' OR `declined` IS NULL) AND (`status` = '' OR `status` = 'calling')");
            if (mysqli_affected_rows($sqlConnect) <= 0) {
                $query = mysqli_query($sqlConnect, "UPDATE " . T_AGORA . " SET `active` = 1, `status` = 'answered', `called` = '{$claim_id}' WHERE `id` = '$id' AND `to_id` = '$user_id' AND `active` = '0' AND (`declined` = '0' OR `declined` IS NULL) AND `status` = 'calling' AND `type` = 'audio'");
                if (mysqli_affected_rows($sqlConnect) > 0) {
                    $provider = 'agora';
                }
            }
            if (mysqli_affected_rows($sqlConnect) > 0) {
                Wo_UpdateCallLog($id, 'audio', 'answered', array(
                    'provider' => $provider,
                    'started_at' => time(),
                    'status_by' => $wo['user']['user_id']
                ));
            }
        } else {
            $provider = Wo_GetActiveCallProvider('video');
            $query = mysqli_query($sqlConnect, "UPDATE " . T_VIDEOS_CALLES . " SET `active` = 1, `status` = 'answered', `called` = '{$claim_id}' WHERE `id` = '$id' AND `to_id` = '$user_id' AND `active` = '0' AND (`declined` = '0' OR `declined` IS NULL) AND (`status` = '' OR `status` = 'calling')");
            if (mysqli_affected_rows($sqlConnect) <= 0) {
                $query = mysqli_query($sqlConnect, "UPDATE " . T_AGORA . " SET `active` = 1, `status` = 'answered', `called` = '{$claim_id}' WHERE `id` = '$id' AND `to_id` = '$user_id' AND `active` = '0' AND (`declined` = '0' OR `declined` IS NULL) AND `status` = 'calling' AND (`type` = 'video' OR `type` = '' OR `type` IS NULL)");
                if (mysqli_affected_rows($sqlConnect) > 0) {
                    $provider = 'agora';
                }
            }
            if (mysqli_affected_rows($sqlConnect) > 0) {
                Wo_UpdateCallLog($id, 'video', 'answered', array(
                    'provider' => $provider,
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
                $sql = Wo_GetCallSourceById($id, 'audio');
                if (!empty($sql) && is_array($sql)) {
                    $wo['incall']                 = $sql;
                    $wo['incall']['in_call_user'] = Wo_UserData($sql['from_id']);
                    if ($wo['incall']['provider'] != 'agora' && $wo['incall']['to_id'] == $wo['user']['user_id']) {
                        $wo['incall']['user']         = 1;
                        $wo['incall']['access_token'] = $wo['incall']['access_token'];
                    } else if ($wo['incall']['provider'] != 'agora' && $wo['incall']['from_id'] == $wo['user']['user_id']) {
                        $wo['incall']['user']         = 2;
                        $wo['incall']['access_token'] = $wo['incall']['access_token_2'];
                    }
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
