<?php
// Save the current user's browser geolocation coordinates.
if ($f == 'save_user_location' && isset($_POST['lat']) && isset($_POST['lng'])) {
    $lat          = is_numeric($_POST['lat']) ? (float) $_POST['lat'] : 0;
    $lng          = is_numeric($_POST['lng']) ? (float) $_POST['lng'] : 0;
    $is_valid_coordinate = is_numeric($lat) && is_numeric($lng) &&
        $lat >= -90 && $lat <= 90 &&
        $lng >= -180 && $lng <= 180 &&
        !($lat == 0.0 && $lng == 0.0);
    $data         = array(
        'status' => 304
    );
    if (!$is_valid_coordinate) {
        $data['status'] = 400;
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    $update_array = array(
        'lat' => $lat,
        'lng' => $lng,
        'last_location_update' => (strtotime("+6 hours"))
    );
    if (Wo_UpdateUserData($wo['user']['id'], $update_array)) {
        $data['status'] = 200;
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
