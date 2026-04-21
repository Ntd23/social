<?php
if ($f == 'decline_group_call_invite') {
    $data = array(
        'status' => 404
    );
    $call_id = !empty($_GET['call_id']) ? intval($_GET['call_id']) : 0;
    if ($call_id > 0 && Wo_CheckMainSession($hash_id) === true) {
        $declined = Wo_DeclineGroupCallInvite($call_id, $wo['user']['user_id']);
        if (!empty($declined)) {
            $data = array(
                'status' => 200
            );
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
