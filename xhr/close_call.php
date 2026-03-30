<?php 
if ($f == 'close_call') {
    if (!empty($_GET['id'])) {
        $id         = Wo_Secure($_GET['id']);
        $status     = !empty($_GET['status']) ? Wo_Secure($_GET['status']) : '';
        $duration   = !empty($_GET['duration']) ? intval($_GET['duration']) : 0;
        $call_type  = !empty($_GET['call_type']) ? Wo_Secure($_GET['call_type']) : '';
        $provider   = !empty($_GET['provider']) ? Wo_Secure($_GET['provider']) : 'twilio';
        $table_type = '';
        $final_status = 'cancelled';
        if ($status == 'ended') {
            $final_status = 'ended';
        }
        else if ($status == 'no_answer' || $status == 'missed') {
            $final_status = 'no_answer';
        }
        if ($call_type == 'video') {
            $table_type = 'video';
            $query = mysqli_query($sqlConnect, "UPDATE " . T_VIDEOS_CALLES . " SET `active` = '0', `status` = '$final_status' WHERE `id` = '$id'");
        }
        else if ($call_type == 'audio') {
            $table_type = 'audio';
            $query = mysqli_query($sqlConnect, "UPDATE " . T_AUDIO_CALLES . " SET `active` = '0', `status` = '$final_status' WHERE `id` = '$id'");
        }
        else {
            $video_q = mysqli_query($sqlConnect, "SELECT `id`, `active` FROM " . T_VIDEOS_CALLES . " WHERE `id` = '$id'");
            if (mysqli_num_rows($video_q) > 0) {
                $table_type = 'video';
                $query = mysqli_query($sqlConnect, "UPDATE " . T_VIDEOS_CALLES . " SET `active` = '0', `status` = '$final_status' WHERE `id` = '$id'");
            }
            else {
                $audio_q = mysqli_query($sqlConnect, "SELECT `id`, `active` FROM " . T_AUDIO_CALLES . " WHERE `id` = '$id'");
                if (mysqli_num_rows($audio_q) > 0) {
                    $table_type = 'audio';
                    $query = mysqli_query($sqlConnect, "UPDATE " . T_AUDIO_CALLES . " SET `active` = '0', `status` = '$final_status' WHERE `id` = '$id'");
                }
                else {
                    $agora_q = mysqli_query($sqlConnect, "SELECT `id`, `type` FROM " . T_AGORA . " WHERE `id` = '$id'");
                    if (mysqli_num_rows($agora_q) > 0) {
                        $agora_row = mysqli_fetch_assoc($agora_q);
                        $table_type = (!empty($agora_row['type']) ? $agora_row['type'] : 'audio');
                        $provider = 'agora';
                        $query = mysqli_query($sqlConnect, "UPDATE " . T_AGORA . " SET `active` = '0', `status` = '$final_status' WHERE `id` = '$id'");
                    }
                }
            }
        }
        if ($query) {
            if ($status == 'ended') {
                Wo_UpdateCallLog($id, (!empty($table_type) ? $table_type : 'audio'), 'ended', array(
                    'provider' => $provider,
                    'duration' => $duration,
                    'ended_at' => time(),
                    'status_by' => $wo['user']['user_id']
                ));
            }
            else if ($status == 'no_answer' || $status == 'missed') {
                Wo_UpdateCallLog($id, (!empty($table_type) ? $table_type : 'audio'), 'no_answer', array(
                    'provider' => $provider,
                    'status_by' => $wo['user']['user_id']
                ));
            }
            else {
                Wo_UpdateCallLog($id, (!empty($table_type) ? $table_type : 'audio'), 'cancelled', array(
                    'provider' => $provider,
                    'status_by' => $wo['user']['user_id']
                ));
            }
            $data = array(
                'status' => 200
            );
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
