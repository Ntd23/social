<?php
if ($f == 'view_all_stories') {
    header("Content-type: application/json; charset=utf-8"); // đặt sớm để khỏi vướng headers
    $data = ['status' => 400];

    if (!empty($_POST['user_id']) && is_numeric($_POST['user_id']) && $_POST['user_id'] > 0
        && !empty($wo['user']) && !empty($wo['user']['user_id'])) {

        $viewer_id = (int)$wo['user']['user_id'];
        $owner_id  = (int)Wo_Secure($_POST['user_id']);

        // Lấy danh sách ad story id theo type
        $ads_ids = (array) Wo_GetAlddAdIdsByType('story');
        $ads_ids = array_filter(array_map('intval', $ads_ids));
        $ads_in  = $ads_ids ? implode(',', $ads_ids) : '';

        // loại các story đã xem bởi viewer
        $exclude_seen = "id NOT IN (SELECT story_id FROM " . T_STORY_SEEN . " WHERE user_id = {$viewer_id})";

        // Ưu tiên story thường của user chưa xem
        $stories = $db->where("ad_id IS NULL AND user_id = {$owner_id} AND {$exclude_seen}")
                      ->get(T_USER_STORY, null, ['id']);

        $story = null;
        if (!empty($stories)) {
            $show_ids = array_map(fn($r) => (int)$r->id, $stories);
            $story = $db->where('user_id', $owner_id)
                        ->where('id', $show_ids, 'IN')
                        ->orderBy('id', 'ASC')
                        ->getOne(T_USER_STORY);
        }

        // Fallback: nếu không có, có thể lấy ad story (nếu tồn tại) hoặc story bất kỳ của user
        if (empty($story)) {
            if ($ads_in !== '') {
                $story = $db->where("(ad_id IN ({$ads_in})) OR (ad_id IS NULL AND user_id = {$owner_id})")
                            ->orderBy('id', 'ASC')
                            ->getOne(T_USER_STORY);
            } else {
                $story = $db->where("ad_id IS NULL AND user_id = {$owner_id}")
                            ->orderBy('id', 'ASC')
                            ->getOne(T_USER_STORY);
            }
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
