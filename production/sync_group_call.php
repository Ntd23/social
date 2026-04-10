<?php
if ($f == 'sync_group_call') {
    $data = array('status' => 400);
    $call_id = !empty($_GET['call_id']) ? intval($_GET['call_id']) : 0;
    if ($call_id > 0 && Wo_CheckMainSession($hash_id) === true) {
        $sync_data = Wo_GetGroupCallSyncData($call_id, $wo['user']['user_id']);
        if (!empty($sync_data)) {
            $group_call = $sync_data['call'];
            $data = array(
                'status' => 200,
                'call_id' => intval($group_call['id']),
                'group_id' => intval($group_call['group_id']),
                'call_type' => $group_call['call_type'],
                'call_status' => !empty($group_call['status']) ? $group_call['status'] : 'ended',
                'participant_count' => intval(!empty($group_call['participant_count']) ? $group_call['participant_count'] : 0),
                'group_name' => !empty($sync_data['group']['group_name']) ? $sync_data['group']['group_name'] : '',
                'group_avatar' => !empty($sync_data['group']['avatar']) ? $sync_data['group']['avatar'] : '',
                'participants' => !empty($sync_data['participants']) ? $sync_data['participants'] : array()
            );
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
