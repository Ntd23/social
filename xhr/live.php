<?php
if ($f == 'live') {
    if ($s == 'create' && $wo['config']['can_use_live']) {
        if (!Wo_IsLiveKitAvailable()) {
            $data['message'] = $error_icon . $wo['lang']['please_check_details'];
        } else {
            $stream_name = !empty($_POST['stream_name']) ? Wo_Secure($_POST['stream_name']) : Wo_GenerateLiveStreamName($wo['user']['id']);
            $live_title = !empty($_POST['title']) ? Wo_Secure(trim($_POST['title'])) : '';
            $live_description = !empty($_POST['description']) ? Wo_Secure(trim($_POST['description'])) : '';
            $post_text_parts = array();
            if ($live_title !== '') {
                $post_text_parts[] = $live_title;
            }
            if ($live_description !== '') {
                $post_text_parts[] = $live_description;
            }
            $post_text = implode(PHP_EOL . PHP_EOL, $post_text_parts);
            $join_payload = Wo_GetLiveKitLivestreamJoinPayload($stream_name, 'host', $wo['user']['id'], $wo['user']);
            if (empty($stream_name) || empty($join_payload)) {
                $data['message'] = $error_icon . $wo['lang']['please_check_details'];
            } else {
                $postPrivacy   = '0';
                $privacy_array = array(
                    '0',
                    '1',
                    '2',
                    '3',
                    '4'
                );
                if (!empty($_COOKIE['post_privacy']) && in_array($_COOKIE['post_privacy'], $privacy_array)) {
                    $postPrivacy = Wo_Secure($_COOKIE['post_privacy']);
                }
                $post_id = $db->insert(T_POSTS, array(
                    'user_id' => $wo['user']['id'],
                    'postText' => $post_text,
                    'postType' => 'live',
                    'postPrivacy' => $postPrivacy,
                    'stream_name' => $stream_name,
                    'time' => time(),
                    'live_time' => time(),
                    'live_ended' => 0
                ));
                if (!empty($post_id)) {
                    $db->where('id', $post_id)->update(T_POSTS, array(
                        'post_id' => $post_id
                    ));
                    Wo_notifyUsersLive($post_id);
                    $data['status']    = 200;
                    $data['post_id']   = $post_id;
                    $data['provider']  = 'livekit';
                    $data['stream_name'] = $stream_name;
                    $data['room_name'] = $join_payload['room_name'];
                    $data['ws_url']    = $join_payload['ws_url'];
                    $data['token']     = $join_payload['token'];
                    $data['title']     = $live_title;
                    $data['description'] = $live_description;
                } else {
                    $data['message'] = $error_icon . $wo['lang']['please_check_details'];
                }
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'join') {
        if (!Wo_IsLiveKitAvailable()) {
            $data['message'] = $error_icon . $wo['lang']['please_check_details'];
        } else {
            $post_id = (!empty($_POST['post_id']) && is_numeric($_POST['post_id']) && $_POST['post_id'] > 0) ? Wo_Secure($_POST['post_id']) : 0;
            $post = !empty($post_id) ? Wo_PostData($post_id) : false;
            $heartbeat_window = 10;
            $stale_window = 45;
            $live_time = !empty($post['live_time']) ? intval($post['live_time']) : 0;
            $heartbeat_age = ($live_time > 0) ? max(0, time() - $live_time) : ($stale_window + 1);
            $stream_state = 'offline';
            if ($live_time > 0 && $heartbeat_age <= $heartbeat_window) {
                $stream_state = 'live';
            } else if ($live_time > 0 && $heartbeat_age <= $stale_window) {
                $stream_state = 'stale';
            }
            if (empty($post) || empty($post['stream_name']) || $post['postType'] !== 'live') {
                $data['removed'] = 'yes';
                $data['message'] = $error_icon . $wo['lang']['please_check_details'];
            } else if (intval($post['live_ended']) === 1 || $stream_state === 'offline') {
                $data['removed'] = 'yes';
                $data['stream_state'] = 'offline';
                $data['message'] = $error_icon . $wo['lang']['stream_has_ended'];
            } else {
                $join_payload = Wo_GetLiveKitLivestreamJoinPayload($post['stream_name'], 'viewer', $wo['user']['id'], $wo['user']);
                if (empty($join_payload)) {
                    $data['message'] = $error_icon . $wo['lang']['please_check_details'];
                } else {
                    $data['status']    = 200;
                    $data['post_id']   = intval($post['id']);
                    $data['provider']  = 'livekit';
                    $data['stream_name'] = $post['stream_name'];
                    $data['room_name'] = $join_payload['room_name'];
                    $data['ws_url']    = $join_payload['ws_url'];
                    $data['token']     = $join_payload['token'];
                    $data['stream_state'] = $stream_state;
                    $data['heartbeat_age'] = $heartbeat_age;
                }
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'check_comments') {
        if (!empty($_POST['post_id']) && is_numeric($_POST['post_id']) && $_POST['post_id'] > 0) {
            $post_id   = Wo_Secure($_POST['post_id']);
            $post_row  = $db->where('id', $post_id)->getOne(T_POSTS);
            $post_data = is_object($post_row) ? (array) $post_row : (is_array($post_row) ? $post_row : array());
            if (!empty($post_data)) {
                $heartbeat_window = 10;
                $stale_window = 45;
                $live_time = !empty($post_data['live_time']) ? intval($post_data['live_time']) : 0;
                $heartbeat_age = ($live_time > 0) ? max(0, time() - $live_time) : ($stale_window + 1);
                $stream_state = 'offline';
                if (intval(!empty($post_data['live_ended']) ? $post_data['live_ended'] : 0) === 0 && $live_time > 0) {
                    if ($heartbeat_age <= $heartbeat_window) {
                        $stream_state = 'live';
                    } else if ($heartbeat_age <= $stale_window) {
                        $stream_state = 'stale';
                    }
                }
                $word = ($stream_state === 'offline') ? $wo['lang']['offline'] : $wo['lang']['live'];
                $reactions_count = intval($db->where('post_id', $post_id)->getValue(T_REACTIONS, 'COUNT(*)'));
                $shares_count = intval(Wo_CountShares($post_id)) + intval(Wo_CountPostShare($post_id));
                $clips_count = 0;
                if (isset($post_data['clips_count'])) {
                    $clips_count = intval($post_data['clips_count']);
                } else if (isset($post_data['clip_count'])) {
                    $clips_count = intval($post_data['clip_count']);
                }
                $html = '';
                $count = 0;
                if (intval(!empty($post_data['live_ended']) ? $post_data['live_ended'] : 0) == 0) {
                    $user_comment_row = $db->where('post_id', $post_id)->where('user_id', $wo['user']['id'])->getOne(T_COMMENTS);
                    $user_comment = is_object($user_comment_row) ? (array) $user_comment_row : (is_array($user_comment_row) ? $user_comment_row : array());
                    if (!empty($user_comment)) {
                        $db->where('id', intval($user_comment['id']), '>');
                    }
                    if (!empty($_POST['ids'])) {
                        $ids = array();
                        foreach ($_POST['ids'] as $key => $one_id) {
                            $ids[] = Wo_Secure($one_id);
                        }
                        $db->where('id', $ids, 'NOT IN')->where('id', end($ids), '>');
                    }
                    $db->where('user_id', $wo['user']['id'], '!=');
                    $comments = $db->where('post_id', $post_id)->where('text', '', '!=')->get(T_COMMENTS);
                    foreach ($comments as $key => $value) {
                        if (!empty($value->text)) {
                            $wo['comment'] = Wo_GetPostComment($value->id);
                            $html .= Wo_LoadPage('story/includes/live_comment');
                            $count = $count + 1;
                            if ($count == 4) {
                                break;
                            }
                        }
                    }
                    if ($stream_state !== 'offline') {
                        $count = $db->where('post_id', $post_id)->where('time', time() - 6, '>=')->getValue(T_LIVE_SUB, 'COUNT(*)');
                        if ($wo['user']['id'] == intval(!empty($post_data['user_id']) ? $post_data['user_id'] : 0)) {
                            $joined_users = $db->where('post_id', $post_id)->where('time', time() - 6, '>=')->where('is_watching', 0)->get(T_LIVE_SUB);
                            $joined_ids   = array();
                            if (!empty($joined_users)) {
                                foreach ($joined_users as $key => $value) {
                                    $joined_ids[]  = $value->user_id;
                                    $wo['comment'] = array(
                                        'id' => '',
                                        'text' => 'joined live video'
                                    );
                                    $user_data     = Wo_UserData($value->user_id);
                                    if (!empty($user_data)) {
                                        $wo['comment']['publisher'] = $user_data;
                                        $html .= Wo_LoadPage('story/includes/live_comment');
                                    }
                                }
                                if (!empty($joined_ids)) {
                                    $db->where('post_id', $post_id)->where('user_id', $joined_ids, 'IN')->update(T_LIVE_SUB, array(
                                        'is_watching' => 1
                                    ));
                                }
                            }
                            $left_users = $db->where('post_id', $post_id)->where('time', time() - 6, '<')->where('is_watching', 1)->get(T_LIVE_SUB);
                            $left_ids   = array();
                            if (!empty($left_users)) {
                                foreach ($left_users as $key => $value) {
                                    $left_ids[]    = $value->user_id;
                                    $wo['comment'] = array(
                                        'id' => '',
                                        'text' => 'left live video'
                                    );
                                    $user_data     = Wo_UserData($value->user_id);
                                    if (!empty($user_data)) {
                                        $wo['comment']['publisher'] = $user_data;
                                        $html .= Wo_LoadPage('story/includes/live_comment');
                                    }
                                }
                                if (!empty($left_ids)) {
                                    $db->where('post_id', $post_id)->where('user_id', $left_ids, 'IN')->delete(T_LIVE_SUB);
                                }
                            }
                        }
                    }
                    $data = array(
                        'status' => 200,
                        'html' => $html,
                        'count' => $count,
                        'word' => $word,
                        'still_live' => $stream_state,
                        'is_final' => intval($stream_state === 'offline'),
                        'heartbeat_age' => $heartbeat_age,
                        'reactions_count' => $reactions_count,
                        'shares_count' => $shares_count,
                        'clips_count' => $clips_count
                    );
                    if ($wo['user']['id'] == intval(!empty($post_data['user_id']) ? $post_data['user_id'] : 0)) {
                        if ($_POST['page'] == 'live') {
                            $time = time();
                            $db->where('id', $post_id)->update(T_POSTS, array(
                                'live_time' => $time
                            ));
                            $db->where('parent_id', $post_id)->update(T_POSTS, array(
                                'live_time' => $time
                            ));
                        }
                    } else {
                        if ($stream_state !== 'offline' && $_POST['page'] == 'story') {
                            $is_watching = $db->where('user_id', $wo['user']['id'])->where('post_id', $post_id)->getValue(T_LIVE_SUB, 'COUNT(*)');
                            if ($is_watching > 0) {
                                $db->where('user_id', $wo['user']['id'])->where('post_id', $post_id)->update(T_LIVE_SUB, array(
                                    'time' => time()
                                ));
                            } else {
                                $db->insert(T_LIVE_SUB, array(
                                    'user_id' => $wo['user']['id'],
                                    'post_id' => $post_id,
                                    'time' => time(),
                                    'is_watching' => 0
                                ));
                            }
                        }
                    }
                } else {
                    $data = array(
                        'status' => 200,
                        'html' => '',
                        'count' => 0,
                        'word' => $wo['lang']['offline'],
                        'still_live' => 'offline',
                        'is_final' => 1,
                        'heartbeat_age' => $heartbeat_age,
                        'reactions_count' => $reactions_count,
                        'shares_count' => $shares_count,
                        'clips_count' => $clips_count
                    );
                }
            } else {
                $data['message'] = $error_icon . $wo['lang']['please_check_details'];
                $data['removed'] = 'yes';
            }
        } else {
            $data['message'] = $error_icon . $wo['lang']['please_check_details'];
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete') {
        if (!empty($_POST['post_id']) && is_numeric($_POST['post_id']) && $_POST['post_id'] > 0) {
            $db->where('post_id', Wo_Secure($_POST['post_id']))->where('user_id', $wo['user']['id'])->update(T_POSTS, array(
                'live_ended' => 1,
                'live_time' => 0
            ));
            if ($wo['config']['live_video_save'] == 0) {
                Wo_DeletePost(Wo_Secure($_POST['post_id']));
            } else {
                if ($wo['config']['agora_live_video'] == 1 && !empty($wo['config']['agora_app_id']) && !empty($wo['config']['agora_customer_id']) && !empty($wo['config']['agora_customer_certificate']) && $wo['config']['live_video_save'] == 1) {
                    $post = $db->where('post_id', Wo_Secure($_POST['post_id']))->getOne(T_POSTS);
                    if (!empty($post)) {
                        StopCloudRecording(array(
                            'resourceId' => $post->agora_resource_id,
                            'sid' => $post->agora_sid,
                            'cname' => $post->stream_name,
                            'post_id' => $post->post_id,
                            'token' => $post->agora_token,
                            'uid' => 12
                        ));
                    }
                }
                if ($wo['config']['agora_live_video'] == 1 && $wo['config']['amazone_s3_2'] != 1) {
                    try {
                        Wo_DeletePost(Wo_Secure($_POST['post_id']));
                    }
                    catch (Exception $e) {
                    }
                }
            }
        }
        $posts = $db->where('stream_name','','<>')->where('postFile','')->get(T_POSTS);
        if (!empty($posts)) {
            foreach ($posts as $key => $value) {
                if ((!empty($value->agora_resource_id) || !empty($value->agora_sid) || !empty($value->agora_token)) && empty($value->postFile)) {
                    Wo_DeletePost($value->id,'shared');
                }
            }
        }
    }
    if ($s == 'create_thumb') {
        if (!empty($_POST['post_id']) && is_numeric($_POST['post_id']) && $_POST['post_id'] > 0 && !empty($_FILES['thumb'])) {
            $is_post = $db->where('post_id', Wo_Secure($_POST['post_id']))->where('user_id', $wo['user']['id'])->getValue(T_POSTS, 'COUNT(*)');
            if ($is_post > 0) {
                $fileInfo = array(
                    'file' => $_FILES["thumb"]["tmp_name"],
                    'name' => $_FILES['thumb']['name'],
                    'size' => $_FILES["thumb"]["size"],
                    'type' => $_FILES["thumb"]["type"],
                    'types' => 'jpeg,png,jpg,gif',
                    'crop' => array(
                        'width' => 525,
                        'height' => 295
                    )
                );
                $media    = Wo_ShareFile($fileInfo);
                if (!empty($media)) {
                    $thumb = $media['filename'];
                    if (!empty($thumb)) {
                        $db->where('post_id', Wo_Secure($_POST['post_id']))->where('user_id', $wo['user']['id'])->update(T_POSTS, array(
                            'postFileThumb' => $thumb
                        ));
                        $data['status'] = 200;
                        header("Content-type: application/json");
                        echo json_encode($data);
                        exit();
                    }
                }
            }
        }
    }
}
