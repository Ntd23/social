<?php
if ($f == 'create_new_group_call') {
    $data = array('status' => 400);
    $group_id = !empty($_GET['group_id']) ? intval($_GET['group_id']) : 0;
    $call_type = Wo_NormalizeGroupCallType(!empty($_GET['call_type']) ? $_GET['call_type'] : 'video');
    $can_use = ($call_type == 'audio') ? (!empty($wo['config']['audio_chat']) && !empty($wo['config']['can_use_audio_call'])) : (!empty($wo['config']['video_chat']) && !empty($wo['config']['can_use_video_call']));
    if ($group_id > 0 && Wo_CheckMainSession($hash_id) === true && $can_use && Wo_IsGroupChatCallMember($group_id, $wo['user']['user_id'])) {
        $group_call = Wo_CreateNewGroupCall($group_id, $call_type, $wo['user']['user_id']);
        if (!empty($group_call)) {
            $group = Wo_GroupTabData($group_id, false);
            $data = array(
                'status' => 200,
                'id' => intval($group_call['id']),
                'group_id' => $group_id,
                'call_type' => $group_call['call_type'],
                'participant_count' => intval(!empty($group_call['participant_count']) ? $group_call['participant_count'] : 0),
                'url' => Wo_BuildGroupCallJoinUrl($group_call['id'], $group_call['call_type']),
                'group_name' => !empty($group['group_name']) ? $group['group_name'] : '',
                'is_existing' => (!empty($group_call['time']) && intval($group_call['time']) < time()) ? 1 : 0
            );
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
