<?php
if ($f == 'view_all_stories') {
    header("Content-type: application/json; charset=utf-8"); // đặt sớm để khỏi vướng headers
    $data = ['status' => 400];

    if (!empty($_POST['user_id']) && is_numeric($_POST['user_id']) && $_POST['user_id'] > 0
        && !empty($wo['user']) && !empty($wo['user']['user_id'])) {

        $viewer_id = (int)$wo['user']['user_id'];
        $owner_id  = (int)Wo_Secure($_POST['user_id']);

        $story = null;
        if (!empty($_POST['story_id']) && is_numeric($_POST['story_id']) && $_POST['story_id'] > 0) {
            $requested_story_id = (int) Wo_Secure($_POST['story_id']);
            $story = $db->where('id', $requested_story_id)
                        ->where('user_id', $owner_id)
                        ->where('expire', time(), '>')
                        ->getOne(T_USER_STORY);
        }

        if (empty($story)) {
            $story = Wo_GetStoryEntryPoint($owner_id, $viewer_id);
        }

        // >>> Đặt mọi xử lý dựa trên $story SAU khi đã kiểm tra rỗng <<<
        if (!empty($story)) {
            $story_id    = (int)$story->id;
            $wo['story'] = ToArray($story);

            $story_media = Wo_GetStoryMedia($story_id, 'image');
            if (empty($story_media)) {
                $story_media = Wo_GetStoryMedia($story_id, 'video');
            }
            $wo['story']['story_media'] = $story_media;

            $wo['story']['view_count']  = $db->where('story_id', $story_id)
                                             ->where('user_id', $story->user_id, '!=')
                                             ->getValue(T_STORY_SEEN, 'COUNT(*)');

            $story_views = $db->where('story_id', $story_id)
                              ->where('user_id', $story->user_id, '!=')
                              ->orderBy('id', "DESC")
                              ->get(T_STORY_SEEN, 10);

            if (!empty($story_views)) {
                foreach ($story_views as $v) {
                    $u = Wo_UserData($v->user_id);
                    $u['id']     = $v->id;
                    $u['seenOn'] = Wo_Time_Elapsed_String($v->time);
                    $wo['story']['story_views'][] = $u;
                }
            }

            $wo['story']['is_owner']  = false;
            $wo['story']['user_data'] = $user_data = Wo_UserData($story->user_id);
            if (!empty($user_data) && $user_data['user_id'] == $viewer_id) {
                $wo['story']['is_owner'] = true;
            }

            // đánh dấu đã xem
            $is_viewed = $db->where('story_id', $story_id)
                            ->where('user_id', $viewer_id)
                            ->getValue(T_STORY_SEEN, 'COUNT(*)');
            if ((int)$is_viewed === 0) {
                $db->insert(T_STORY_SEEN, ['story_id'=>$story_id,'user_id'=>$viewer_id,'time'=>time()]);
                if (!empty($user_data) && $user_data['user_id'] != $viewer_id) {
                    Wo_RegisterNotification([
                        'recipient_id' => $user_data['user_id'],
                        'type'         => 'viewed_story',
                        'story_id'     => $story_id,
                        'text'         => '',
                        'url'          => 'index.php?link1=timeline&u='.$wo['user']['username'].'&story=true&story_id='.$story_id,
                    ]);
                }
            }

            // nếu là ad story
            if (!empty($wo['story']['ad_id'])) {
                $ad = $db->where('id', (int)$wo['story']['ad_id'])->ArrayBuilder()->getOne(T_USER_ADS);
                if ($ad) {
                    $wo['story']['story_media'][0]['type']     = 'image';
                    $wo['story']['story_media'][0]['filename'] = Wo_GetMedia($ad['ad_media']);
                    $wo['story']['description'] = $ad['description'];
                    $wo['story']['ad']          = $ad;
                    if ($ad['bidding'] === 'views') {
                        @Wo_RegisterAdConversionView((int)$ad['id']);
                    }
                }
            }

            $data['story_id']   = $story_id;
            $wo['story_type']   = $_POST['type'] ?? '';
            $data['html']       = Wo_LoadPage('lightbox/story');
            $data['status']     = 200;
        } else {
            $data = ['status'=>404, 'message'=>'No story/ad matched for this user'];
        }
    }

    Wo_CleanCache();
    echo json_encode($data);
    exit;
}
