<?php
// Updates user avatar media and advances startup onboarding when requested.
if ($f == 'update_user_avatar_picture') {
    $data = array(
        'status' => 400
    );
    $images = array(
        '1',
        '2',
        '3',
        '4',
        '5',
        '6',
        '7',
        '8',
        '9',
        '10',
        '11',
        '12',
        '13',
        '14',
        '15',
        '16',
        '17',
        '18',
        '19',
        '20',
        '21',
        '22',
        '23',
        '24',
        '25',
        '26',
        '27',
        '28',
        '29',
        '30'
    );
    if (isset($_FILES['avatar']['name'])) {
        $ai_post = 0;
        if ($wo['config']['ai_user_system'] == 1 && !empty($_POST['ai_post']) && $_POST['ai_post'] == 'on') {
            $ai_post = 1;
        }
        $target_user_id = !empty($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
        $upload = Wo_UploadImage($_FILES["avatar"]["tmp_name"], $_FILES['avatar']['name'], 'avatar', $_FILES['avatar']['type'], $target_user_id, '', $ai_post);
        if ($upload === true) {
            if (!empty($s) && $s == 'start' && $target_user_id > 0) {
                Wo_UpdateUserData($target_user_id, array(
                    'startup_image' => 1
                ));
                Wo_UpdateUserDetails($target_user_id, false, false, true);
            }

            $img  = Wo_UserData($target_user_id);
            $data = array(
                'status' => 200,
                'img' => $img['avatar'] . '?cache=' . rand(11, 22),
                'img_or' => $img['avatar_org'],
                'avatar_full' => Wo_GetMedia($img['avatar_full']) . '?cache=' . rand(11, 22),
                'avatar_full_or' => $img['avatar_full'],
                'big_text' => $wo['lang']['looks_good'],
                'small_text' => $wo['lang']['looks_good_des'],
                'location' => (!empty($s) && $s == 'start') ? Wo_SeoLink('index.php?link1=start-up') : ''
            );
        } else {
            $data = $upload;
        }
    }
    Wo_CleanCache();
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
