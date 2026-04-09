<?php
if ($f == 'nearby_shops') {
    if ($s == 'load') {
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
        $users      = Wo_GetNearbyShops($filter);
        $users_info = array();
        if ($users && count($users) > 0) {
            foreach ($users as $wo['UsersList']) {
                $user_info['name'] = $wo['UsersList']['page_data']['name'];
                $user_info['lng']  = $wo['UsersList']['product']['lng'];
                $user_info['lat']  = $wo['UsersList']['product']['lat'];
                $user_info['location'] = (!empty($wo['UsersList']['product']['location']) ? $wo['UsersList']['product']['location'] : $wo['UsersList']['page_data']['address']);
                $users_info[]      = $user_info;
                $wo['result']      = $wo['UsersList']['page_data'];
                $wo['result']['nearby_location'] = $user_info['location'];
                $wo['result']['nearby_lat'] = $user_info['lat'];
                $wo['result']['nearby_lng'] = $user_info['lng'];
                $wo['result']['nearby_distance'] = (!empty($wo['UsersList']['distance']) ? $wo['UsersList']['distance'] : '');
                $html .= Wo_LoadPage('nearby_shops/list');
            }
            $data['status']     = 200;
            $data['html']       = $html;
            $data['users_info'] = $users_info;
            $data['count']      = (!empty($name) ? count($users) : Wo_GetNearbyShopsCount($filter));
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
