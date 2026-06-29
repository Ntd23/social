<?php
if ($f == "verify_email_phone") {
    if (empty($_POST['code'])) {
        $error = $error_icon . $wo['lang']['please_check_details'];
    } else {
        $user_id = (int) $wo['user']['user_id'];
        $hash_code = Wo_Secure(md5($_POST['code']));
        $time = time();
        $confirm_code_query = mysqli_query($sqlConnect, "SELECT COUNT(`user_id`) FROM " . T_USERS . " WHERE `user_id` = '{$user_id}' AND `email_code` = '{$hash_code}' AND (`time_code_sent` = '0' OR `time_code_sent` = '' OR `time_code_sent` >= '{$time}')");
        $confirm_code = Wo_Sql_Result($confirm_code_query, 0);
        $Update_data  = array();
        if (empty($confirm_code)) {
            $error = $error_icon . $wo['lang']['wrong_confirmation_code'];
        }
        if (empty($error)) {
            $message = '';
            if (!empty($wo['user']['new_phone'])) {
                $message                     = $success_icon . $wo['lang']['your_phone_verified'];
                $Update_data['phone_number'] = $wo['user']['new_phone'];
                $Update_data['new_phone']    = '';
            }
            if (!empty($wo['user']['new_email'])) {
                $message                  = $success_icon . $wo['lang']['your_email_verified'];
                $Update_data['email']     = $wo['user']['new_email'];
                $Update_data['new_email'] = '';
            }
            Wo_UpdateUserData($wo['user']['user_id'], $Update_data);
            $db->where('user_id', $wo['user']['user_id'])->update(T_USERS, array(
                'time_code_sent' => '0'
            ));
            $data = array(
                'status' => 200,
                'message' => $message
            );
        }
    }
    if (!empty($error)) {
        $data = array(
            'status' => 400,
            'message' => $error
        );
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
