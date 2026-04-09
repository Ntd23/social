<?php
if ($f == 'sync_call_timer') {
    $data = array(
        'status' => 404
    );

    if (!empty($wo['user']['user_id']) && !empty($_GET['id'])) {
        $call_id = intval($_GET['id']);
        $call_type = (!empty($_GET['call_type']) && $_GET['call_type'] == 'video') ? 'video' : 'audio';
        $source = Wo_GetCallSourceById($call_id, $call_type);
        $current_user_id = intval($wo['user']['user_id']);

        if (!empty($source) && is_array($source)) {
            $from_id = intval(!empty($source['from_id']) ? $source['from_id'] : 0);
            $to_id = intval(!empty($source['to_id']) ? $source['to_id'] : 0);

            if ($current_user_id === $from_id || $current_user_id === $to_id) {
                $provider = !empty($source['provider']) ? $source['provider'] : 'twilio';
                $resolved_call_type = (!empty($source['call_type']) && $source['call_type'] == 'video') ? 'video' : 'audio';
                $payload = Wo_GetCallLogPayload($call_id, $resolved_call_type, $provider, $from_id, $to_id);
                $server_now = time();
                $started_at = !empty($payload['started_at']) ? intval($payload['started_at']) : 0;
                $elapsed = 0;

                if ($started_at > 0) {
                    $elapsed = max(0, $server_now - $started_at);
                    if (!empty($payload['duration'])) {
                        $elapsed = max($elapsed, intval($payload['duration']));
                    }
                }

                $data = array(
                    'status' => 200,
                    'call_id' => $call_id,
                    'call_type' => $resolved_call_type,
                    'provider' => $provider,
                    'started_at' => $started_at,
                    'server_now' => $server_now,
                    'elapsed' => $elapsed,
                    'active' => intval(!empty($source['active']) ? $source['active'] : 0),
                    'call_status' => (!empty($source['status']) ? $source['status'] : ''),
                    'in_call' => (intval(!empty($source['active']) ? $source['active'] : 0) === 1 && !empty($source['status']) && $source['status'] === 'answered')
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
