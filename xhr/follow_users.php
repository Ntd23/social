<?php
// Handles onboarding follow submission and final redirect payload.
if ($f == 'follow_users') {
    $data = array(
        'status' => 400,
        'location' => $wo['config']['site_url']
    );

    if ($wo['loggedin'] == true && !empty($wo['user']['user_id'])) {
        $ids = !empty($_POST['user']) ? @explode(',', $_POST['user']) : array();
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id > 0 && $id !== (int) $wo['user']['user_id']) {
                Wo_RegisterFollow($id, $wo['user']['user_id']);
            }
        }

        Wo_UpdateUserData($wo['user']['user_id'], array(
            'startup_follow' => '1',
            'start_up' => '1'
        ));
        Wo_UpdateUserDetails($wo['user']['user_id'], false, false, true);

        $data = array(
            'status' => 200,
            'location' => $wo['config']['site_url']
        );
    }

    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
