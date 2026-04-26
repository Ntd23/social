<?php 
if ($f == 'nearby_users' && $wo['config']['find_friends'] == 1) {
    if ($s == 'load') {
        $name     = (isset($_GET['name'])) ? $_GET['name'] : false;
        $gender   = (isset($_GET['gender'])) ? $_GET['gender'] : false;
        $offset   = (isset($_GET['offset'])) ? $_GET['offset'] : false;
        $distance = (isset($_GET['distance'])) ? $_GET['distance'] : false;
        $relship  = (isset($_GET['relship'])) ? $_GET['relship'] : false;
        $status   = (isset($_GET['status'])) ? $_GET['status'] : false;
        $search_mode = (isset($_GET['search_mode'])) ? $_GET['search_mode'] : false;
        $center_lat = (isset($_GET['center_lat'])) ? $_GET['center_lat'] : false;
        $center_lng = (isset($_GET['center_lng'])) ? $_GET['center_lng'] : false;
        $data     = array(
            'status' => 404,
            'users_info' => array(),
            'items_info' => array(),
            'count' => 0
        );
        $html     = '';
        $filter   = array(
            'name' => $name,
            'gender' => $gender,
            'distance' => $distance,
            'offset' => $offset,
            'relship' => $relship,
            'status' => $status,
            'search_mode' => $search_mode,
            'center_lat' => $center_lat,
            'center_lng' => $center_lng
        );
        $users    = Wo_GetNearbyUsers($filter);
        $users_info = array();
        if ($users && count($users) > 0) {
            $user_type_label = (!empty($wo['lang']['user'])) ? $wo['lang']['user'] : 'User';
            foreach ($users as $wo['UsersList']) {
                $user_location = '';
                if (!empty($wo['UsersList']['user_data']['address'])) {
                    $user_location = trim($wo['UsersList']['user_data']['address']);
                }
                elseif (!empty($wo['UsersList']['user_geoinfo'])) {
                    $user_location = trim($wo['UsersList']['user_geoinfo']);
                }
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
                $user_info = array(
                    'item_key' => 'user|' . md5((!empty($wo['UsersList']['user_data']['url']) ? $wo['UsersList']['user_data']['url'] : $wo['UsersList']['user_data']['user_id'])),
                    'offset_id' => (int) $wo['UsersList']['user_data']['id'],
                    'name'   => $wo['UsersList']['user_data']['name'],
                    'title'   => $wo['UsersList']['user_data']['name'],
                    'subtitle' => (!empty($wo['UsersList']['user_data']['username']) ? '@' . $wo['UsersList']['user_data']['username'] : $user_type_label),
                    'description' => '',
                    'lng'    => (float) $wo['UsersList']['user_data']['lng'],
                    'lat'    => (float) $wo['UsersList']['user_data']['lat'],
                    'avatar' => $wo['UsersList']['user_data']['avatar'],
                    'url'    => $wo['UsersList']['user_data']['url'],
                    'ajax_url' => (!empty($wo['UsersList']['user_data']['username']) ? '?link1=timeline&u=' . $wo['UsersList']['user_data']['username'] : ''),
                    'location' => $user_location,
                    'type' => 'user',
                    'type_label' => $user_type_label,
                    'distance_value' => $distance_value,
                    'distance_text' => $distance_text,
                    'is_self' => ((int) $wo['UsersList']['user_data']['user_id'] === (int) $wo['user']['user_id'])
                );
                $users_info[] = $user_info;

                $html .= Wo_LoadPage('friends_nearby/includes/user-list');
            }
            $data['status'] = 200;
            $data['html']   = $html;
            $data['users_info']   = $users_info;
            $data['items_info']   = $users_info;
            $data['count']  = Wo_GetNearbyUsersCount($filter);
            //$data['count']  = count($users);
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
