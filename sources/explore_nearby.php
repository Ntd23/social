<?php
if ($wo['loggedin'] == false) {
    header("Location: " . Wo_SeoLink('index.php?link1=welcome'));
    exit();
}

$users_enabled = ($wo['config']['find_friends'] == 1);
$shops_enabled = ($wo['config']['nearby_shop_system'] == 1 && $wo['config']['classified'] == 1);

if (!$users_enabled && !$shops_enabled) {
    header("Location: " . Wo_SeoLink('index.php?link1=welcome'));
    exit();
}

$active_tab = 'combined';
$default_distance = 100;
$nearby_users = array();
$nearby_shops = array();
$nearby_users_count = 0;
$nearby_shops_count = 0;

if ($users_enabled) {
    $users_filter = array(
        'limit' => 20,
        'distance' => $default_distance
    );
    $nearby_users = Wo_GetNearbyUsers($users_filter);
    $nearby_users_count = (int) Wo_GetNearbyUsersCount($users_filter);
}

if ($shops_enabled) {
    $shops_filter = array(
        'limit' => 20,
        'distance' => $default_distance
    );
    $nearby_shops = Wo_GetNearbyShops($shops_filter);
    $nearby_shops_count = (int) Wo_GetNearbyShopsCount($shops_filter);
}

$wo['explore_nearby'] = array(
    'active_tab' => $active_tab,
    'users_enabled' => $users_enabled,
    'shops_enabled' => $shops_enabled,
    'default_distance' => $default_distance,
    'nearby_users' => $nearby_users,
    'nearby_shops' => $nearby_shops,
    'nearby_users_count' => $nearby_users_count,
    'nearby_shops_count' => $nearby_shops_count
);

$wo['description'] = $wo['config']['siteDesc'];
$wo['keywords']    = $wo['config']['siteKeywords'];
$wo['page']        = 'explore_nearby';
$wo['title']       = $wo['lang']['find_friends'] . ' / ' . $wo['lang']['nearby_shops'];
$wo['content']     = Wo_LoadPage('explore_nearby/content');
