<?php
if ($f == 'check_incoming_group_call') {
    $data = array(
        'status' => 204
    );
    if (Wo_CheckMainSession($hash_id) === true) {
        $expected_call_id = !empty($_GET['call_id']) ? intval($_GET['call_id']) : 0;
        $group_call = Wo_GetPendingGroupCallInvite($wo['user']['user_id'], $expected_call_id);
        if ($group_call !== false && is_array($group_call)) {
            $wo['incall'] = $group_call;
            $data = array(
                'status' => 200,
                'call_id' => intval($group_call['id']),
                'call_type' => $group_call['call_type'],
                'html' => Wo_LoadPage($group_call['call_type'] == 'video' ? 'modals/in_group_call' : 'modals/in_group_audio_call')
            );
        }
    }
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
