<?php

require_once __DIR__ . '/../assets/includes/functions_general.php';
require_once __DIR__ . '/../assets/includes/functions_one.php';

function assert_same($expected, $actual, $message)
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message . PHP_EOL);
        fwrite(STDERR, 'Expected: ' . var_export($expected, true) . PHP_EOL);
        fwrite(STDERR, 'Actual:   ' . var_export($actual, true) . PHP_EOL);
        exit(1);
    }
}

function assert_true($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

$site_url = 'https://vnseea.vn';
$wo = array(
    'config' => array(
        'site_url' => 'https://vnseea.vn',
        'asset_url' => 'https://vnseea.b-cdn.net',
        'cdn_url' => 'https://vnseea.b-cdn.net',
        'amazone_s3' => 0,
        'bucket_name' => '',
        'amazon_endpoint' => '',
        's3_site_url' => 'https://bucket.s3.amazonaws.com',
        'wasabi_storage' => 0,
        'wasabi_bucket_name' => '',
        'wasabi_access_key' => '',
        'wasabi_secret_key' => '',
        'wasabi_bucket_region' => '',
        'wasabi_endpoint' => '',
        'wasabi_site_url' => '',
        'spaces' => 0,
        'space_region' => '',
        'space_name' => '',
        'spaces_endpoint' => '',
        'ftp_upload' => 0,
        'ftp_endpoint' => '',
        'cloud_upload' => 0,
        'cloud_endpoint' => '',
        'cloud_bucket_name' => '',
        'backblaze_storage' => 0,
        'backblaze_endpoint' => '',
        'backblaze_bucket_name' => '',
        'backblaze_bucket_region' => ''
    )
);

assert_true(function_exists('Wo_GetAssetUrl'), 'Wo_GetAssetUrl() should exist.');
assert_true(function_exists('Wo_IsAppWebViewRequest'), 'Wo_IsAppWebViewRequest() should exist.');
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 vnseea-webview';
$_GET = array();
assert_true(Wo_IsAppWebViewRequest(), 'Wo_IsAppWebViewRequest() should detect the app user agent token.');
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 Safari/604.1';
$_GET = array('source' => 'app');
assert_true(Wo_IsAppWebViewRequest(), 'Wo_IsAppWebViewRequest() should detect the app source query.');
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 Safari/604.1';
$_GET = array();
assert_true(!Wo_IsAppWebViewRequest(), 'Wo_IsAppWebViewRequest() should not match normal browser requests.');

assert_same(
    'https://vnseea.b-cdn.net/themes/wowonder/stylesheet/style.css',
    Wo_GetAssetUrl('/themes/wowonder/stylesheet/style.css'),
    'Wo_GetAssetUrl() should use asset_url and normalize slashes.'
);
assert_same(
    'https://vnseea.b-cdn.net/admin-panel/assets/js/admin.js',
    Wo_LoadAdminLink('assets/js/admin.js'),
    'Wo_LoadAdminLink() should use asset_url for admin static assets.'
);
assert_same(
    'https://vnseea.b-cdn.net/upload/photos/d-avatar.jpg',
    Wo_GetMedia('upload/photos/d-avatar.jpg'),
    'Wo_GetMedia() should use CDN for local upload media.'
);

$wo['config']['cdn_url'] = '';
$wo['config']['asset_url'] = $wo['config']['site_url'];
assert_same(
    'https://vnseea.vn/upload/photos/d-avatar.jpg',
    Wo_GetMedia('upload/photos/d-avatar.jpg'),
    'Wo_GetMedia() should fall back to site_url when CDN is disabled.'
);

$wo['config']['cdn_url'] = 'https://vnseea.b-cdn.net';
$wo['config']['asset_url'] = $wo['config']['site_url'];
$wo['config']['is_app_webview'] = 1;
assert_same(
    'https://vnseea.vn/upload/photos/d-avatar.jpg',
    Wo_GetMedia('upload/photos/d-avatar.jpg'),
    'Wo_GetMedia() should force local media through site_url for app WebView requests.'
);

$wo['config']['cdn_url'] = 'https://vnseea.b-cdn.net';
$wo['config']['asset_url'] = 'https://vnseea.b-cdn.net';
$wo['config']['is_app_webview'] = 0;
$wo['config']['amazone_s3'] = 1;
$wo['config']['bucket_name'] = 'vnseea';
$wo['config']['amazon_endpoint'] = 'https://media.example.com';
assert_same(
    'https://media.example.com/upload/photos/d-avatar.jpg',
    Wo_GetMedia('upload/photos/d-avatar.jpg'),
    'Wo_GetMedia() should keep configured storage endpoints ahead of CDN.'
);

echo "CDN helper tests passed.\n";
