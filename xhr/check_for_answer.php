<?php 
if ($f == 'check_for_answer') {
    if (!empty($_GET['id'])) {
        $selectData = Wo_CheckCallAnswer($_GET['id']);
        if ($selectData !== false) {
            $data = array(
                'status' => 200,
                'url' => $selectData['url'],
                'text_answered' => $wo['lang']['answered'],
                'text_please_wait' => $wo['lang']['please_wait']
            );
        } else {
            $check_declined = Wo_CheckCallAnswerDeclined($_GET['id']);
            if ($check_declined) {
                $data = array(
                    'status' => 400,
                    'text_call_declined' => $wo['lang']['call_declined'],
                    'text_call_declined_desc' => $wo['lang']['call_declined_desc']
                );
            }
            else {
                $call_id = intval($_GET['id']);
                $source_data = Wo_GetCallLogSourceData($call_id, 'video', 'twilio');
                if (empty($source_data)) {
                    $source_data = Wo_GetCallLogSourceData($call_id, 'video', 'agora');
                }
                $source_status = !empty($source_data['status']) ? $source_data['status'] : '';
                $is_still_ringing = ($source_status === '' || $source_status === 'calling');
                if (!empty($source_data) && $is_still_ringing && intval($source_data['active']) === 0 && intval($source_data['declined']) === 0 && !empty($source_data['time']) && (time() - intval($source_data['time'])) >= 43) {
                    $provider = !empty($source_data['provider']) ? $source_data['provider'] : 'twilio';
                    $table = ($provider == 'agora') ? T_AGORA : T_VIDEOS_CALLES;
                    if ($provider == 'agora') {
                        mysqli_query($sqlConnect, "UPDATE " . $table . " SET `active` = '0', `status` = 'no_answer' WHERE `id` = '{$call_id}'");
                    }
                    else {
                        mysqli_query($sqlConnect, "UPDATE " . $table . " SET `active` = '0', `status` = 'no_answer' WHERE `id` = '{$call_id}'");
                    }
                    Wo_UpdateCallLog($call_id, 'video', 'no_answer', array(
                        'provider' => $provider,
                        'status_by' => $wo['user']['user_id']
                    ));
                }
            }
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
