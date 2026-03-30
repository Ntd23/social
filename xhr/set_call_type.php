<?php
if ($f == 'set_call_type') {
    $data = array(
        'status' => 404
    );
    if ($wo['loggedin'] === true && !empty($_GET['id']) && !empty($_GET['display_call_type'])) {
        $call_id = intval($_GET['id']);
        $source_call_type = (!empty($_GET['source_call_type']) && $_GET['source_call_type'] == 'video') ? 'video' : 'audio';
        $display_call_type = ($_GET['display_call_type'] == 'video') ? 'video' : 'audio';
        $provider = !empty($_GET['provider']) ? Wo_Secure($_GET['provider']) : 'twilio';
        if ($call_id > 0 && Wo_SetCallLogDisplayType($call_id, $source_call_type, $display_call_type, array(
            'provider' => $provider,
            'status' => 'answered',
            'status_by' => $wo['user']['user_id'],
            'started_at' => time()
        ))) {
            $data['status'] = 200;
        }
    }
    header('Content-type: application/json');
    echo json_encode($data);
    exit();
}
