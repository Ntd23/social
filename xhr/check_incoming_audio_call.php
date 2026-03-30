<?php
if ($f == 'check_incoming_audio_call') {
    $data = array(
        'status' => 204
    );

    if (Wo_CheckMainSession($hash_id) === true) {
        $call_type = (!empty($_GET['call_type']) && $_GET['call_type'] == 'video') ? 'video' : 'audio';
        $expected_call_id = (!empty($_GET['call_id']) ? intval($_GET['call_id']) : 0);
        $check_calles = Wo_CheckFroInCalls($call_type);
        if ($check_calles !== false && is_array($check_calles)) {
            if ($expected_call_id > 0 && intval($check_calles['id']) !== $expected_call_id) {
                $data = array(
                    'status' => 204
                );
            }
            else {
                $wo['incall'] = $check_calles;
                $wo['incall']['in_call_user'] = Wo_UserData($check_calles['from_id']);
                $data = array(
                    'status' => 200,
                    'call_id' => $wo['incall']['id'],
                    'call_type' => $call_type,
                    'html' => Wo_LoadPage($call_type == 'video' ? 'modals/in_call' : 'modals/in_audio_call')
                );
            }
        }
    }

    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Content-type: application/json');
    echo json_encode($data);
    exit();
}
