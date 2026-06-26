<?php
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/';
require_once 'assets/init.php';

// Insert a fake call
$user_id = 2; // fake caller
$to_id = 1; // admin receiver

$query = mysqli_query($sqlConnect, "INSERT INTO " . T_AUDIO_CALLES . " (`from_id`, `to_id`, `active`, `status`, `room_name`) VALUES ('$user_id', '$to_id', '0', 'calling', 'test_room_123')");
$call_id = mysqli_insert_id($sqlConnect);

// Mock get parameters
$_GET['source'] = 'app';
$_GET['room'] = 'test_room_123';
$_GET['type'] = 'audio';
$_GET['id'] = $call_id;

$wo['user'] = Wo_UserData($to_id);
$wo['loggedin'] = true;

ob_start();
include 'call_livekit.php';
$output = ob_get_clean();

// Check if there are Location headers
$headers = headers_list();
var_dump($headers);

// Check if it was marked as answered in DB
$check = $db->where('id', $call_id)->getOne(T_AUDIO_CALLES);
echo "Status in DB: " . $check['status'] . "\n";
echo "Active in DB: " . $check['active'] . "\n";
?>
