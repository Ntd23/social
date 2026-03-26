<?php

if ($f == 'tags') {
    header('Content-Type: application/json; charset=utf-8');

    if ($wo['loggedin'] == false) {
        echo json_encode(['status' => 401, 'message' => 'Please login']);
        exit();
    }

    // Nếu site bật CSRF:
    if (!empty($wo['config']['csrf_system'])) {
        if (empty($hash_id) || $hash_id != $wo['user']['session_hash']) {
            echo json_encode(['status' => 403, 'message' => 'Bad CSRF']);
            exit();
        }
    }

    // Lấy danh sách nhãn của tôi
    if ($s == 'list_labels') {
        $data = Wo_ListUserTagLabels($wo['user']['user_id']);
        echo json_encode(['status' => 200, 'labels' => $data]);
        exit();
    }
    if ($s == 'create_label') {
        $owner_id = $wo['user']['user_id'];
        error_log("POST: " . print_r($_POST, true)); // Ghi log $_POST
        error_log("REQUEST: " . print_r($_REQUEST, true)); // Ghi log $_REQUEST
        $name = trim($_POST['label_name'] ?? $_REQUEST['label_name'] ?? '');
        $color = trim($_POST['label_color'] ?? $_REQUEST['label_color'] ?? '#999999');
        error_log("After trim - name: '$name', color: '$color'"); // Ghi log sau trim
        if (empty($name)) {
            echo json_encode(['status' => 400, 'message' => 'Name can not be empty']);
            exit();
        }
        if (empty($color)) {
            echo json_encode(['status' => 400, 'message' => 'Color can not be empty']);
            exit();
        }
        $data = Wo_CreateTagLabel([
            'owner_id' => $owner_id,
            'name' => $name,
            'color' => $color
        ]);
        if ($data) {
            echo json_encode(['status' => 200, 'message' => 'Label created successfully']);
        }
        exit();
    }

    // if($s=='delete_label'){
    //     $id=isset($_POST['id'])  ? $_POST['id']  : '';
    //     if(empty($id) || !is_numeric($id)){
    //         echo json_encode(['status' => 400, 'message' => 'Id can not be empty']); exit();
    //     }
    //     $data=Wo_DeleteTagLabel($id);
    //     echo json_encode($data); exit();
    // }
    if ($s == 'attach_label') {
        $owner_id = $wo['user']['user_id'];
        $target_id = isset($_POST['target_user_id']) ? (int)$_POST['target_user_id']
         : (isset($_POST['target_id']) ? (int)$_POST['target_id'] : 0);
        $tag_id = isset($_POST['label_id'])  ? $_POST['label_id']  : '';
        if (empty($target_id) || !is_numeric($target_id)) {
            echo json_encode(['status' => 400, 'message' => 'Target user id can not be empty']);
            exit();
        }
        if (empty($tag_id) || !is_numeric($tag_id)) {
            echo json_encode(['status' => 400, 'message' => 'Tag id can not be empty']);
            exit();
        }
        $data = Wo_AttachUserTag([
            'owner_id' => $owner_id,
            'target_user_id' => $target_id,
            'tag_id' => $tag_id
        ]);
        echo json_encode($data);
        exit();
    }
    if ($s == 'selected_tags') {
        $owner_id = $wo['user']['user_id'];
        $tag_id = isset($_POST['tag_id'])  ? $_POST['tag_id']  : '';
        if (empty($tag_id) || !is_numeric($tag_id)) {
            echo json_encode(['status' => 400, 'message' => 'Tag id can not be empty']);
            exit();
        }
        $data = Wo_GetUserIdsByTag($tag_id);
        echo json_encode(['status' => 200, 'data' => $data]);
        exit();
    }
    if ($s == 'all_tags') {
        $owner_id = $wo['user']['user_id'];
        $data = Wo_GetAllAssignedTagsByOwner();

        if (empty($data)) {
            echo json_encode(['status' => 200, 'message' => 'No users have been tagged yet']);
            exit();
        }
        $user_ids = [];
        foreach ((array)$data as $r) {
            if (isset($r['target_user_id'])) {
                $user_ids[] = (int)$r['target_user_id'];
            }
        }
        echo json_encode(['status' => 200, 'user_ids' => $user_ids]);
        exit();
    }
    if ($s == 'list_target_tags') {
        $owner_id    = $wo['user']['user_id'];
        $target_type = isset($_POST['target_type']) ? trim($_POST['target_type']) : 'user';
        $target_id = isset($_POST['target_user_id']) ? (int)$_POST['target_user_id']
         : (isset($_POST['target_id']) ? (int)$_POST['target_id'] : 0);

        if (empty($target_id) || !is_numeric($target_id)) {
            echo json_encode(['status' => 400, 'message' => 'Target id can not be empty']);
            exit();
        }
        $tags = Wo_GetTagForUser($owner_id, $target_id);

        echo json_encode([
            'status' => 200,
            'tags'   => $tags,   // mảng [{id,name,color}, ...] đúng với loadAssign()
        ]);
        exit();
    }
    if ($s == 'detach') {
        $owner_id = $wo['user']['user_id'];
        $target_type = isset($_POST['target_type']) ? trim($_POST['target_type']) : 'user';
        $target_id = isset($_POST['target_user_id']) ? (int)$_POST['target_user_id']
         : (isset($_POST['target_id']) ? (int)$_POST['target_id'] : 0);
         $label_id= isset($_POST['label_id'])  ? $_POST['label_id']  : '';
        if(empty($target_id) || !is_numeric($target_id)){
            echo json_encode(['status' => 400, 'message' => 'Target id can not be empty']); exit();
        }
        $data= Wo_DeleteTagUser($owner_id, $target_id, $label_id);
        echo json_encode([
            'status' => 200,
            'data'   => "Label removed successfully",
        ]);
        exit();
    }
    if($s=='delete_label'){
        $owner_id = $wo['user']['user_id'];
        $id=isset($_POST['label_id'])  ? $_POST['label_id']  : '';
        if(empty($id) || !is_numeric($id)){
            echo json_encode(['status' => 400, 'message' => 'Id can not be empty']); exit();
        }
        $data=Wo_DeleteTag($owner_id,$id);
        echo json_encode([
            'status' =>200,
            'data'=>'Tag delete successfully',
        ]);
        exit();
    }
    if($s=='get_tag_user'){
        $owner_id = $wo['user']['user_id'];
        $data=Wo_GetAllTagsForUser($owner_id);
        echo json_encode([
            'status' =>200,
            'user_ids' => $user_ids,
        ]);
        exit();
    }
}
