<?php 
if ($f == 'verificate-shop') {
    $data  = array(
        'status' => 304,
        'message' => ($error_icon . $wo['lang']['please_check_details'])
    );
    $error = false;
    if (!isset($_POST['name']) || !isset($_POST['text_shop']) || !isset($_FILES['passport']) || !isset($_FILES['photo'])) {
        $error = true;
    } else {
        if (strlen($_POST['name']) < 5 || strlen($_POST['name']) > 50) {
            $error           = true;
            $data['message'] = $error_icon . $wo['lang']['name_shop_characters_length'];
        }
        if (!file_exists($_FILES['passport']['tmp_name']) || !file_exists($_FILES['photo']['tmp_name'])) {
            $error           = true;
            $data['message'] = $error_icon . $wo['lang']['please_select_passport_id'];
        }
        
        if(strlen($_POST['text_shop'])<5){
            $error=true;
            $data['message']=$error_icon . $wo['lang']['mess_is_not_require'];
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
         if (file_exists($_FILES["shop_image"]["tmp_name"])) {
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
         if (file_exists($_FILES["license"]["tmp_name"])) {
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
            'message' => Wo_Secure($_POST['text_shop']),
            'user_name' => Wo_Secure($_POST['name']),
            'passport' => '',
            'photo' => '',
            'type' => 'Shop',
            'seen' => 0,
            'shop_image'=>'',
            'license'=>''
        );
        $last_id           = Wo_SendVerificationRequest($registration_data);
        if ($last_id && is_numeric($last_id)) {
            $files       = array(
                'passport' => $_FILES,
                'photo' => $_FILES,
                'shop_image'=>$_FILES,
                'license'=>$_FILES
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

