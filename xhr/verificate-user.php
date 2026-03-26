<?php 
if ($f == 'verificate-user') {
    ob_start();
    // echo "<pre>";
    // print_r($_POST['dob']);
    // echo "</pre>";
    $data  = array(
        'status' => 304,
        'message' => ($error_icon . $wo['lang']['please_check_details'])
    );
    $error = false;
    // echo "<pre>";
    // print_r($data);
    // echo "</pre>";
    if (!isset($_POST['full_name']) || !isset($_POST['dob']) || !isset($_FILES['passport']) || !isset($_FILES['photo'])) {
        $error = true;
    } else {
        if (strlen($_POST['full_name']) < 5 || strlen($_POST['full_name']) > 50) {
            $error           = true;
            $data['message'] = $error_icon . $wo['lang']['username_characters_length'];
        }
        if (!file_exists($_FILES['passport']['tmp_name']) || !file_exists($_FILES['photo']['tmp_name'])) {
            $error           = true;
            $data['message'] = $error_icon . $wo['lang']['please_select_passport_id'];
        }
        if(!isset($_POST['dob'])){
            $error =true; 
            $data['message']=$error_icon . $wo['lang']['data_is_not_validate'];
        }
        if(!isset($_POST['cccd']) ){
            $error=true;
            $data['message']=$error_icon . $wo['lang']['cccd_is_not_validate'];
        }
        if (file_exists($_FILES["passport"]["tmp_name"])) {
            $image = getimagesize($_FILES["passport"]["tmp_name"]);
            if (!in_array($image[2], array(
                IMAGETYPE_GIF,
                IMAGETYPE_JPEG,
                IMAGETYPE_PNG,
                IMAGETYPE_BMP
            ))) {
                $error           = true;
                $data['message'] = $error_icon . $wo['lang']['passport_id_invalid'];
            }
        }
        if (file_exists($_FILES["photo"]["tmp_name"])) {
            $image = getimagesize($_FILES["photo"]["tmp_name"]);
            if (!in_array($image[2], array(
                IMAGETYPE_GIF,
                IMAGETYPE_JPEG,
                IMAGETYPE_PNG,
                IMAGETYPE_BMP
            ))) {
                $error           = true;
                $data['message'] = $error_icon . $wo['lang']['user_picture_invalid'];
            }
        }
    }
    
    if (!$error) {
        $registration_data = array(
            'user_id' => $wo['user']['id'],
            'message' => "Xác minh người dùng",
            'user_name' => Wo_Secure($_POST['full_name']),
            'passport' => '',
            'photo' => '',
            'type' => 'User',
            'seen' => 0,
            'dob'=>Wo_Secure($_POST['dob']),
            'cccd'=>$_POST['cccd'],
        );
        $last_id           = Wo_SendVerificationRequest($registration_data);
        if ($last_id && is_numeric($last_id)) {
            $files       = array(
                'passport' => $_FILES,
                'photo' => $_FILES
            );
            $update_data = array();
            foreach ($files as $key => $file) {
                $fileInfo          = array(
                    'file' => $file[$key]["tmp_name"],
                    'name' => $file[$key]['name'],
                    'size' => $file[$key]["size"],
                    'type' => $file[$key]["type"],
                    'types' => 'jpg,png,bmp,gif'
                );
                $media             = Wo_ShareFile($fileInfo);
                if (!empty($media)) {
                    $update_data[$key] = $media['filename'];
                }
                
            }
            if (Wo_UpdateVerificationRequest($last_id, $update_data)) {
                $data['status']  = 200;
                $data['message'] = $success_icon . $wo['lang']['verification_request_sent'];
                $data['url']     = $wo['config']['site_url'];
            }
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}

