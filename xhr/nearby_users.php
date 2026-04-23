<?php 
if ($f == 'nearby_users' && $wo['config']['find_friends'] == 1) {
    if ($s == 'load') {
        $name     = (isset($_GET['name'])) ? $_GET['name'] : false;
        $gender   = (isset($_GET['gender'])) ? $_GET['gender'] : false;
        $offset   = (isset($_GET['offset'])) ? $_GET['offset'] : false;
        $distance = (isset($_GET['distance'])) ? $_GET['distance'] : false;
        $relship  = (isset($_GET['relship'])) ? $_GET['relship'] : false;
        $status   = (isset($_GET['status'])) ? $_GET['status'] : false;
        $data     = array(
            'status' => 404,
            'users_info' => array(),
            'count' => 0
        );
        $html     = '';
        $filter   = array(
            'name' => $name,
            'gender' => $gender,
            'distance' => $distance,
            'offset' => $offset,
            'relship' => $relship,
            'status' => $status
        );
        $users    = Wo_GetNearbyUsers($filter);
        $users_info = array();
        if ($users && count($users) > 0) {
            foreach ($users as $wo['UsersList']) {
                $user_location = '';
                if (!empty($wo['UsersList']['user_data']['address'])) {
                    $user_location = trim($wo['UsersList']['user_data']['address']);
                }
                elseif (!empty($wo['UsersList']['user_geoinfo'])) {
                    $user_location = trim($wo['UsersList']['user_geoinfo']);
                }
                $user_info = array(
                    'name'   => $wo['UsersList']['user_data']['name'],
                    'lng'    => (float) $wo['UsersList']['user_data']['lng'],
                    'lat'    => (float) $wo['UsersList']['user_data']['lat'],
                    'avatar' => $wo['UsersList']['user_data']['avatar'],
                    'url'    => $wo['UsersList']['user_data']['url'],
                    'location' => $user_location
                );
                $users_info[] = $user_info;

                $html .= Wo_LoadPage('friends_nearby/includes/user-list');
            }
            $data['status'] = 200;
            $data['html']   = $html;
            $data['users_info']   = $users_info;
            $data['count']  = Wo_GetNearbyUsersCount($filter);
            //$data['count']  = count($users);
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
