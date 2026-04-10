<?php
if ($f == 'add_group_call_members') {
    $data = array('status' => 400);
    $call_id = !empty($_POST['call_id']) ? intval($_POST['call_id']) : (!empty($_GET['call_id']) ? intval($_GET['call_id']) : 0);
    $raw_user_ids = !empty($_POST['user_ids']) ? $_POST['user_ids'] : (!empty($_GET['user_ids']) ? $_GET['user_ids'] : array());
    $user_ids = array();
    if (is_string($raw_user_ids)) {
        $raw_user_ids = explode(',', $raw_user_ids);
    }
    foreach ((array) $raw_user_ids as $user_id) {
        $user_id = intval($user_id);
        if ($user_id > 0) {
            $user_ids[] = $user_id;
        }
    }
    $user_ids = array_values(array_unique($user_ids));
    if ($call_id > 0 && !empty($user_ids) && Wo_CheckMainSession($hash_id) === true) {
        $invited = Wo_AddGroupCallMembers($call_id, $user_ids, $wo['user']['user_id']);
        if (!empty($invited)) {
            $data = array(
                'status' => 200,
                'invited_user_ids' => $invited,
                'count' => count($invited)
            );
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
