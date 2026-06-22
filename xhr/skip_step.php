<?php // Handles onboarding step skip actions and final redirect payloads. ?>
if ($f == 'skip_step') {
    $data = array(
        'status' => 400
    );
    if (!empty($_GET['type'])) {
        $types = array(
            'start_up_info',
            'startup_image',
            'startup_follow'
        );
        if (in_array($_GET['type'], $types)) {
            $update_data = array(
                $_GET['type'] => 1
            );
            if ($_GET['type'] === 'startup_follow') {
                $update_data['start_up'] = 1;
            }
            $register_skip = Wo_UpdateUserData($wo['user']['user_id'], $update_data);
            if ($register_skip === true) {
                $data = array(
                    'status' => 200,
                    'location' => ($_GET['type'] === 'startup_follow') ? $wo['config']['site_url'] : Wo_SeoLink('index.php?link1=start-up')
                );
            }
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
