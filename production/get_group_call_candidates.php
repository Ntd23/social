<?php
if ($f == 'get_group_call_candidates') {
    $data = array('status' => 400);
    $group_id = !empty($_GET['group_id']) ? intval($_GET['group_id']) : 0;
    $call_id = !empty($_GET['call_id']) ? intval($_GET['call_id']) : 0;
    if ($group_id > 0 && Wo_CheckMainSession($hash_id) === true) {
        $candidates = Wo_GetGroupCallCandidates($group_id, $call_id, $wo['user']['user_id']);
        $data = array(
            'status' => 200,
            'candidates' => $candidates
        );
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
