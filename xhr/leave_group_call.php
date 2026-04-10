<?php
if ($f == 'leave_group_call') {
    $data = array('status' => 400);
    $call_id = !empty($_GET['call_id']) ? intval($_GET['call_id']) : 0;
    if ($call_id > 0 && Wo_CheckMainSession($hash_id) === true) {
        $group_call = Wo_LeaveGroupCall($call_id, $wo['user']['user_id']);
        if (!empty($group_call)) {
            $data = array(
                'status' => 200,
                'id' => intval($group_call['id']),
                'group_id' => intval($group_call['group_id']),
                'call_status' => !empty($group_call['status']) ? $group_call['status'] : 'ended',
                'participant_count' => intval(!empty($group_call['participant_count']) ? $group_call['participant_count'] : 0)
            );
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
