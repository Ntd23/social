<?php
require_once('assets/init.php');
header("Content-Type: text/plain");

echo "=== WEBSITE PUSH CONFIGURATION ===\n";
echo "Push system enabled (push): " . $wo['config']['push'] . "\n";
echo "Android Messenger Push enabled (android_push_messages): " . $wo['config']['android_push_messages'] . "\n";
echo "Android Messenger Push ID (OneSignal App ID): " . $wo['config']['android_m_push_id'] . "\n";
echo "Android Messenger Push Key: " . (empty($wo['config']['android_m_push_key']) ? 'Empty' : substr($wo['config']['android_m_push_key'], 0, 8) . '...') . "\n";
echo "Flutter App OneSignal App ID: f97566fc-b9c0-4fb8-b355-ae9d4e2145e8\n";
echo "Does ID match?: " . (($wo['config']['android_m_push_id'] == 'f97566fc-b9c0-4fb8-b355-ae9d4e2145e8') ? "YES" : "NO - NEED TO UPDATE IN ADMIN PANEL / DATABASE") . "\n\n";

echo "=== USER DEVICE TOKENS ===\n";
echo "Logged in: " . ($wo['loggedin'] ? 'Yes' : 'No') . "\n";
if ($wo['loggedin']) {
    echo "User ID: " . $wo['user']['user_id'] . "\n";
    echo "Username: " . $wo['user']['username'] . "\n";
    echo "android_m_device_id: " . $wo['user']['android_m_device_id'] . "\n";
} else {
    $res = mysqli_query($sqlConnect, "SELECT user_id, username, android_m_device_id FROM " . T_USERS . " WHERE android_m_device_id != '' ORDER BY user_id DESC LIMIT 10");
    if ($res) {
        echo "Users with device token:\n";
        while ($row = mysqli_fetch_assoc($res)) {
            echo "ID: " . $row['user_id'] . " | Name: " . $row['username'] . " | Token: " . $row['android_m_device_id'] . "\n";
        }
    } else {
        echo "No users with token found or query failed\n";
    }
}
?>
