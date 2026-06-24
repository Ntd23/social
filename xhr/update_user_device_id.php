<?php 
if ($f == 'update_user_device_id') {
    if (!empty($_GET['id'])) {
        $id = Wo_Secure($_GET['id']);
        $type = !empty($_GET['type']) ? Wo_Secure($_GET['type']) : 'web';
        
        $update_data = array();
        if ($type == 'android') {
            if ($id != $wo['user']['android_m_device_id']) {
                $update_data['android_m_device_id'] = $id;
            }
        } elseif ($type == 'ios') {
            if ($id != $wo['user']['ios_m_device_id']) {
                $update_data['ios_m_device_id'] = $id;
            }
        } else {
            if ($id != $wo['user']['web_device_id']) {
                $update_data['web_device_id'] = $id;
            }
        }

        if (!empty($update_data)) {
            $update = Wo_UpdateUserData($wo['user']['user_id'], $update_data);
            if ($update) {
                $data = array(
                    'status' => 200
                );
            }
        } else {
            $data = array(
                'status' => 200
            );
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
