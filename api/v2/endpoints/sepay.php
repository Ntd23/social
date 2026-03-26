<?php
// --- CORS (whitelist domain gọi) ---
$origin  = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = ['https://vnseea.vn','https://turn.vnseea.vn'];
if ($origin && in_array($origin, $allowed, true)) {
  header("Access-Control-Allow-Origin: $origin");
  header("Vary: Origin");
  header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
  header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
  header("Access-Control-Max-Age: 86400");
}
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
  http_response_code(204);
  exit;
}

header('Content-Type: application/json; charset=utf-8');

global $sqlConnect, $wo;

// Nạp core
$CORE = dirname(__DIR__, 3) . '/assets/includes/sepay_core.php';
if (!is_file($CORE)) {
  echo json_encode(['api_status'=>500,'errors'=>['error_text'=>'sepay_core missing']]);
  exit;
}
require_once $CORE;

// Helpers
function v2_ok($data=[]) {
  echo json_encode(['api_status'=>200,'data'=>$data], JSON_UNESCAPED_UNICODE);
  exit;
}
function v2_err($code,$text) {
  echo json_encode(['api_status'=>$code,'errors'=>['error_id'=>1,'error_text'=>$text]], JSON_UNESCAPED_UNICODE);
  exit;
}
function require_post() {
  if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    v2_err(405, 'Method Not Allowed (POST required)');
  }
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Yêu cầu đã đăng nhập (router v2 sẽ check server_key + access_token)
if (empty($wo['loggedin'])) v2_err(403, 'Not authorized');

// Chặn sớm nếu admin tắt / thiếu config
$enabled  = !empty($wo['config']['sepay']);
$bankCode = strtoupper(trim($wo['config']['sepay_bank_code'] ?? ''));
$bankAcc  = trim($wo['config']['sepay_bank_acc'] ?? '');
if (!$enabled) v2_err(400, 'SePay is disabled by admin.');
if (!$bankCode || !$bankAcc) v2_err(500, 'SePay not configured (bank_code/account).');

// ------------- ACTIONS ------------- //

// POST /api/v2/?type=sepay&action=make_qr
if ($action === 'make_qr') {
  require_post();
  $amount = (int)($_POST['amount'] ?? 0);
  $out = Wo_SepayCreateOrderQr((int)$wo['user']['user_id'], $amount, $wo, $sqlConnect);
  if (!empty($out['error'])) v2_err($out['code'] ?? 500, $out['error']);
  v2_ok($out);
}

// GET|POST /api/v2/?type=sepay&action=check&order_code=...
if ($action === 'check') {
  $order_code = trim((string)($_REQUEST['order_code'] ?? ''));
  if ($order_code === '') v2_err(400, 'Missing order_code');
  $out = Wo_SepayCheck($order_code, (int)$wo['user']['user_id'], $sqlConnect);
  if (!empty($out['error'])) v2_err($out['code'] ?? 500, $out['error']);
  v2_ok($out);
}

// (tuỳ chọn) GET /api/v2/?type=sepay&action=orders&limit=20&offset=0
if ($action === 'orders') {
  $limit  = max(1, min(50, (int)($_GET['limit'] ?? 20)));
  $offset = max(0, (int)($_GET['offset'] ?? 0));
  $uid    = (int)$wo['user']['user_id'];
  $q = mysqli_query($sqlConnect, "
    SELECT id, order_code, amount, status, created_at, updated_at
    FROM ".PAYMENT."
    WHERE customer_id={$uid} AND method='sepay'
    ORDER BY id DESC
    LIMIT {$limit} OFFSET {$offset}
  ");
  $items = [];
  while ($q && $row = mysqli_fetch_assoc($q)) {
    $row['amount'] = (int)$row['amount'];
    $items[] = $row;
  }
  v2_ok(['items'=>$items,'limit'=>$limit,'offset'=>$offset]);
}

v2_err(404, 'Error: 404 API Action Not Found');
