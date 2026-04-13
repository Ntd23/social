<?php
// +------------------------------------------------------------------------+
// | WoWonder - The Ultimate Social Networking Platform
// | Endpoint: subscriptions.php
// | Lấy danh sách monetization mà user hiện tại đang subscribe
// +------------------------------------------------------------------------+

$response_data = array(
    'api_status' => 400,
);

if (!$wo['loggedin']) {
    $error_code    = 0;
    $error_message = 'User not logged in';
}

if (empty($error_code) && $wo['config']['monetization'] != 1) {
    $error_code    = 5;
    $error_message = 'Monetization is disabled on this platform';
}

if (empty($error_code)) {
    $logged_user_id = $wo['user']['user_id'];

    // Lấy tất cả subscription đang active của user
    $subscribed_rows = $db
        ->where('user_id', $logged_user_id)
        ->where('status', 1, '=')
        ->get(T_MONETIZATION_SUBSCRIBTION);

    $subscriptions = array();

    foreach ($subscribed_rows as $sub_row) {
        // Lấy thông tin gói monetization tương ứng
        $monetization = $db
            ->where('id', $sub_row->monetization_id)
            ->where('status', 1, '=')
            ->getOne(T_USER_MONETIZATION);

        if (!$monetization) {
            continue;
        }

        // Lấy thông tin owner của gói
        $owner_data = Wo_UserData($monetization->user_id);
        foreach ($non_allowed as $field) {
            unset($owner_data[$field]);
        }

        $subscriptions[] = array(
            'subscription_id'  => $sub_row->id,
            'monetization_id'  => $monetization->id,
            'title'            => $monetization->title,
            'description'      => $monetization->description,
            'price'            => $monetization->price,
            'currency'         => $monetization->currency ?? $wo['config']['ads_currency'],
            'subscribed_since' => $sub_row->time ?? '',
            'owner'            => $owner_data,
        );
    }

    $response_data = array(
        'api_status'    => 200,
        'subscriptions' => $subscriptions,
        'total'         => count($subscriptions),
    );
}
