<?php
// +------------------------------------------------------------------------+
// | WoWonder - The Ultimate Social Networking Platform
// | Endpoint: monetization-user.php
// | Lấy danh sách gói monetization của 1 user cụ thể,
// | kèm trạng thái viewer đã subscribe chưa
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

if (empty($error_code) && empty($_POST['user_id']) && empty($_POST['username'])) {
    $error_code    = 3;
    $error_message = 'user_id or username (POST) is required';
}

if (empty($error_code)) {
    $logged_user_id = $wo['user']['user_id'];

    // Resolve user_id từ username hoặc user_id trực tiếp
    if (!empty($_POST['username'])) {
        $target_user_id = Wo_UserIdFromUsername(Wo_Secure($_POST['username']));
    } else {
        $target_user_id = Wo_Secure($_POST['user_id']);
    }

    $target_user = Wo_UserData($target_user_id);

    if (empty($target_user)) {
        $error_code    = 6;
        $error_message = 'User not found';
    }
}

if (empty($error_code)) {
    // Lấy tất cả gói monetization active của target user
    $monetization_rows = $db
        ->where('user_id', $target_user_id)
        ->where('status', 1, '=')
        ->get(T_USER_MONETIZATION);

    $plans = array();

    foreach ($monetization_rows as $monetization) {
        // Kiểm tra viewer (logged user) đã subscribe gói này chưa
        $existing_sub = $db
            ->where('user_id', $logged_user_id)
            ->where('monetization_id', $monetization->id)
            ->where('status', 1, '=')
            ->getOne(T_MONETIZATION_SUBSCRIBTION);

        $plans[] = array(
            'id'            => $monetization->id,
            'title'         => $monetization->title,
            'description'   => $monetization->description,
            'price'         => $monetization->price,
            'currency'      => $monetization->currency ?? $wo['config']['ads_currency'],
            'is_subscribed' => !empty($existing_sub) ? 1 : 0,
            'subscription_id' => !empty($existing_sub) ? $existing_sub->id : null,
        );
    }

    // Thông tin owner (ẩn các field nhạy cảm)
    foreach ($non_allowed as $field) {
        unset($target_user[$field]);
    }

    $response_data = array(
        'api_status'    => 200,
        'user'          => $target_user,
        'plans'         => $plans,
        'total_plans'   => count($plans),
    );
}
