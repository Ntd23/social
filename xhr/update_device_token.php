<?php
if ($f == 'update_device_token') {
    if ($wo['loggedin'] == false) {
        $data = array(
            'status' => 400,
            'error' => 'Not logged in'
        );
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if (empty($_POST['token'])) {
        $data = array(
            'status' => 400,
            'error' => 'Missing token'
        );
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    
    $token = Wo_Secure($_POST['token']);
    $user_id = $wo['user']['user_id'];
    
    $query = mysqli_query($sqlConnect, "UPDATE " . T_USERS . " SET `android_m_device_id` = '{$token}' WHERE `user_id` = '{$user_id}'");
    if ($query) {
        $data = array(
            'status' => 200,
            'message' => 'Token updated successfully'
        );
    } else {
        $data = array(
            'status' => 500,
            'error' => 'Database update failed'
        );
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
?>
