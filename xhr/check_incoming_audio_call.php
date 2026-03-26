<?php
if ($f == 'check_incoming_audio_call') {
    $data = array(
        'status' => 204
    );

    if (Wo_CheckMainSession($hash_id) === true) {
        $check_calles = Wo_CheckFroInCalls('audio');
        if ($check_calles !== false && is_array($check_calles)) {
            $wo['incall'] = $check_calles;
            $wo['incall']['in_call_user'] = Wo_UserData($check_calles['from_id']);
            $data = array(
                'status' => 200,
                'call_id' => $wo['incall']['id'],
                'html' => Wo_LoadPage('modals/in_audio_call')
            );
        }
    }

    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Content-type: application/json');
    echo json_encode($data);
    exit();
}