<?php
if ($f == 'nearby_shops') {
    if ($s == 'load') {
        $name       = (isset($_GET['name'])) ? $_GET['name'] : false;
        $offset     = (isset($_GET['offset'])) ? $_GET['offset'] : false;
        $distance   = (isset($_GET['distance'])) ? $_GET['distance'] : false;
        $data       = array(
            'status' => 404,
            'items_info' => array(),
            'users_info' => array(),
            'count' => 0
        );
        $html       = '';
        $filter     = array(
            'name' => $name,
            'distance' => $distance,
            'offset' => $offset
        );
        $users      = Wo_GetNearbyShops($filter);
        $users_info = array();
        if ($users && count($users) > 0) {
            $shop_type_label = (!empty($wo['lang']['shop'])) ? $wo['lang']['shop'] : 'Shop';
            foreach ($users as $wo['UsersList']) {
                $user_info = array();
                $shop_lat = (!empty($wo['UsersList']['product']['lat'])) ? (float) $wo['UsersList']['product']['lat'] : 0;
                $shop_lng = (!empty($wo['UsersList']['product']['lng'])) ? (float) $wo['UsersList']['product']['lng'] : 0;
                $shop_location = (!empty($wo['UsersList']['product']['location']) ? $wo['UsersList']['product']['location'] : $wo['UsersList']['page_data']['address']);
                $distance_value = (isset($wo['UsersList']['distance']) && is_numeric($wo['UsersList']['distance'])) ? round((float) $wo['UsersList']['distance'], 2) : null;
                $distance_text = '';
                if ($distance_value !== null && $distance_value > 0) {
                    if ($distance_value < 10) {
                        $distance_text = rtrim(rtrim(number_format($distance_value, 1, '.', ''), '0'), '.') . ' km';
                    }
                    else {
                        $distance_text = number_format($distance_value, 0, '.', ',') . ' km';
                    }
                }
                $user_info['name'] = $wo['UsersList']['page_data']['name'];
                $user_info['title'] = $wo['UsersList']['page_data']['name'];
                $user_info['item_key'] = 'shop|' . md5((!empty($wo['UsersList']['page_data']['url']) ? $wo['UsersList']['page_data']['url'] : $wo['UsersList']['page_data']['page_id']));
                $user_info['offset_id'] = (int) $wo['UsersList']['page_data']['page_id'];
                $user_info['lng']  = $shop_lng;
                $user_info['lat']  = $shop_lat;
                $user_info['location'] = $shop_location;
                $user_info['avatar'] = $wo['UsersList']['page_data']['avatar'];
                $user_info['url'] = $wo['UsersList']['page_data']['url'];
                $user_info['ajax_url'] = (!empty($wo['UsersList']['page_data']['username']) ? '?link1=timeline&u=' . $wo['UsersList']['page_data']['username'] : '');
                $user_info['subtitle'] = (!empty($wo['UsersList']['page_data']['category']) ? $wo['UsersList']['page_data']['category'] : $shop_type_label);
                $user_info['description'] = (!empty($wo['UsersList']['page_data']['page_description']) ? trim($wo['UsersList']['page_data']['page_description']) : '');
                $user_info['type'] = 'shop';
                $user_info['type_label'] = $shop_type_label;
                $user_info['distance_value'] = $distance_value;
                $user_info['distance_text'] = $distance_text;
                if (empty($shop_lat) || empty($shop_lng)) {
                    $user_info['lat'] = null;
                    $user_info['lng'] = null;
                }
                $users_info[] = $user_info;
                $wo['result']      = $wo['UsersList']['page_data'];
                $wo['result']['nearby_location'] = $shop_location;
                $wo['result']['nearby_lat'] = $shop_lat;
                $wo['result']['nearby_lng'] = $shop_lng;
                $wo['result']['nearby_distance'] = (!empty($wo['UsersList']['distance']) ? $wo['UsersList']['distance'] : '');
                $html .= Wo_LoadPage('nearby_shops/list');
            }
            $data['status']     = 200;
            $data['html']       = $html;
            $data['users_info'] = $users_info;
            $data['items_info'] = $users_info;
            $data['count']      = Wo_GetNearbyShopsCount($filter);
        }
        else {
            $data['count'] = 0;
        }
    }
    if ($s == 'load_jobs') {
        $name       = (isset($_GET['name'])) ? $_GET['name'] : false;
        $offset     = (isset($_GET['offset'])) ? $_GET['offset'] : false;
        $distance   = (isset($_GET['distance'])) ? $_GET['distance'] : false;
        $data       = array(
            'status' => 404
        );
        $html       = '';
        $filter     = array(
            'name' => $name,
            'distance' => $distance,
            'offset' => $offset
        );
        $users      = Wo_GetNearbyBusiness($filter);
        $users_info = array();
        if ($users && count($users) > 0) {
            foreach ($users as $wo['UsersList']) {
                $user_info['name'] = $wo['UsersList']['page_data']['name'];
                $user_info['lng']  = $wo['UsersList']['job']['lng'];
                $user_info['lat']  = $wo['UsersList']['job']['lat'];
                $users_info[]      = $user_info;
                $wo['result']      = $wo['UsersList']['page_data'];
                $html .= Wo_LoadPage('nearby_business/list');
            }
            $data['status']     = 200;
            $data['html']       = $html;
            $data['users_info'] = $users_info;
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
