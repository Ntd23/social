<?php 
if ($f == 'decline_call') {
    $data = array(
        'status' => 404
    );
    if (!empty($_GET['id']) && !empty($_GET['type'])) {
        $id = Wo_Secure($_GET['id']);
        $user_id = Wo_Secure($wo['user']['user_id']);
        $claim_id = Wo_GetCallSessionClaim($user_id);
        if ($_GET['type'] == 'video') {
            $provider = Wo_GetActiveCallProvider('video');
            $query = mysqli_query($sqlConnect, "UPDATE " . T_VIDEOS_CALLES . " SET `declined` = '1', `status` = 'declined', `called` = '{$claim_id}' WHERE `id` = '$id' AND `to_id` = '$user_id' AND `active` = '0' AND (`declined` = '0' OR `declined` IS NULL) AND (`status` = '' OR `status` = 'calling')");
            if (mysqli_affected_rows($sqlConnect) <= 0) {
                $query = mysqli_query($sqlConnect, "UPDATE " . T_AGORA . " SET `declined` = '1', `status` = 'declined', `called` = '{$claim_id}' WHERE `id` = '$id' AND `to_id` = '$user_id' AND `active` = '0' AND (`declined` = '0' OR `declined` IS NULL) AND `status` = 'calling' AND (`type` = 'video' OR `type` = '' OR `type` IS NULL)");
                if (mysqli_affected_rows($sqlConnect) > 0) {
                    $provider = 'agora';
                }
            }
            if (mysqli_affected_rows($sqlConnect) > 0) {
                Wo_UpdateCallLog($id, 'video', 'declined', array(
                    'provider' => $provider,
                    'status_by' => $wo['user']['user_id']
                ));
            }
        } else {
            $provider = Wo_GetActiveCallProvider('audio');
            $query = mysqli_query($sqlConnect, "UPDATE " . T_AUDIO_CALLES . " SET `declined` = '1', `status` = 'declined', `called` = '{$claim_id}' WHERE `id` = '$id' AND `to_id` = '$user_id' AND `active` = '0' AND (`declined` = '0' OR `declined` IS NULL) AND (`status` = '' OR `status` = 'calling')");
            if (mysqli_affected_rows($sqlConnect) <= 0) {
                $query = mysqli_query($sqlConnect, "UPDATE " . T_AGORA . " SET `declined` = '1', `status` = 'declined', `called` = '{$claim_id}' WHERE `id` = '$id' AND `to_id` = '$user_id' AND `active` = '0' AND (`declined` = '0' OR `declined` IS NULL) AND `status` = 'calling' AND `type` = 'audio'");
                if (mysqli_affected_rows($sqlConnect) > 0) {
                    $provider = 'agora';
                }
            }
            if (mysqli_affected_rows($sqlConnect) > 0) {
                Wo_UpdateCallLog($id, 'audio', 'declined', array(
                    'provider' => $provider,
                    'status_by' => $wo['user']['user_id']
                ));
            }
        }
        if (!empty($query) && mysqli_affected_rows($sqlConnect) > 0) {
            $data = array(
                'status' => 200
            );
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
