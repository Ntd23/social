<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../assets/init.php'; // chỉnh lại đường dẫn nếu khác

// --- helpers ---
function uuidv4() {
  $d = random_bytes(16);
  $d[6] = chr((ord($d[6]) & 0x0f) | 0x40);
  $d[8] = chr((ord($d[8]) & 0x3f) | 0x80);
  return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($d), 4));
}
function get_auth_header() {
  if (isset($_SERVER['HTTP_AUTHORIZATION'])) return trim($_SERVER['HTTP_AUTHORIZATION']);
  if (function_exists('getallheaders')) { foreach (getallheaders() as $k=>$v) if (strcasecmp($k,'Authorization')===0) return trim($v); }
  return '';
}

// --- load config ---
$SEPAY_BANK_CODE   = $wo['config']['sepay_bank_code'] ?? '';
$SEPAY_ACC_NUMBER  = $wo['config']['sepay_bank_acc'] ?? '';
$SEPAY_ACC_NAME    = $wo['config']['siteName'] ?? '';
$SEPAY_WEBHOOK_KEY = $wo['config']['sepay_webhook_token'] ?? '';
$BASE_URL          = $wo['config']['site_url'] ?? '';

$path   = $_GET['action'] ?? ''; // ?action=pay|status|webhook

// ----------------- PAY -----------------
// GET /api/v3/payment/sepay.php?action=pay&amount=1000&customer_id=2
if ($path === 'pay') {
  // Nếu bạn yêu cầu user đăng nhập thì bật check này:
  // if ($wo['loggedin'] == false) { http_response_code(401); echo json_encode(['success'=>false]); exit; }

  $amount      = max(1000, (int)($_GET['amount'] ?? 0));
  $customer_id = (int)($_GET['customer_id'] ?? 0);
  if ($amount <= 0) { echo json_encode(['success'=>false,'message'=>'invalid amount']); exit; }
  if (!$SEPAY_BANK_CODE || !$SEPAY_ACC_NUMBER) { echo json_encode(['success'=>false,'message'=>'sepay not configured']); exit; }

  $payment_id = uuidv4();
  $order_code = 'DH' . str_replace('-', '', $payment_id);

  // Lưu DB
  $stmt = $sqlConnect->prepare("INSERT INTO payments (id,order_code,customer_id,amount,bank_code,account_number,account_name,method,status)
                                VALUES (?,?,?,?,?,?,?,'sepay','pending')");
  $stmt->bind_param('ssiiiss', $payment_id, $order_code, $customer_id, $amount, $SEPAY_BANK_CODE, $SEPAY_ACC_NUMBER, $SEPAY_ACC_NAME);
  $stmt->execute();

  // Tạo URL QR (SePay host)
  $qr_url = 'https://qr.sepay.vn/img?' . http_build_query([
    'bank'   => $SEPAY_BANK_CODE,
    'acc'    => $SEPAY_ACC_NUMBER,
    'amount' => $amount,
    'des'    => $order_code
  ]);

  echo json_encode([
    'success'          => true,
    'payment_id'       => $payment_id,
    'qr_url'           => $qr_url,
    'bank_code'        => $SEPAY_BANK_CODE,
    'account_number'   => $SEPAY_ACC_NUMBER,
    'account_name'     => $SEPAY_ACC_NAME,
    'order_code'       => $order_code,
    'amount'           => (string)$amount,
    'status_check_url' => $BASE_URL . '/api/v3/payment/sepay.php?action=status'
  ]);
  exit;
}

// --------------- WEBHOOK ---------------
// POST /api/v3/payment/sepay.php?action=webhook
if ($path === 'webhook') {
  $auth = get_auth_header();
  if (!$auth || !hash_equals('Apikey ' . $SEPAY_WEBHOOK_KEY, $auth)) {
    http_response_code(401);
    echo json_encode(['success'=>false,'error'=>'unauthorized']); exit;
  }

  $raw  = file_get_contents('php://input');
  $data = json_decode($raw, true);

  // Kỳ vọng: { id, transferType:'in', transferAmount, content/description, referenceCode? }
  if (empty($data['id']) || strtolower($data['transferType'] ?? '') !== 'in' || empty($data['transferAmount'])) {
    echo json_encode(['success'=>true]); exit; // bỏ qua nhưng trả 200 để không retry
  }
  $sepay_id = (int)$data['id'];
  $amount   = (int)$data['transferAmount'];
  $content  = trim($data['content'] ?? ($data['description'] ?? ''));
  $ref      = trim($data['referenceCode'] ?? '');

  // Idempotent theo sepay_id
  $stmt = $sqlConnect->prepare("SELECT id FROM payments WHERE sepay_tx_id=? LIMIT 1");
  $stmt->bind_param('i',$sepay_id);
  $stmt->execute();
  if ($stmt->get_result()->num_rows) { echo json_encode(['success'=>true]); exit; }

  // Tìm payment theo order_code (des = đúng order_code)
  $stmt = $sqlConnect->prepare("SELECT id, amount, customer_id, status FROM payments WHERE order_code=? LIMIT 1");
  $stmt->bind_param('s', $content);
  $stmt->execute();
  $pay = $stmt->get_result()->fetch_assoc();
  if (!$pay) { echo json_encode(['success'=>true]); exit; }

  // Khớp số tiền (bắt buộc)
  if ((int)$pay['amount'] !== $amount) { echo json_encode(['success'=>true]); exit; }

  // Cập nhật paid + lưu sepay_tx_id
  $stmt = $sqlConnect->prepare("UPDATE payments SET status='paid', paid_at=NOW(), sepay_tx_id=? WHERE id=? AND status='pending' LIMIT 1");
  $stmt->bind_param('is', $sepay_id, $pay['id']);
  $stmt->execute();

  // (tuỳ chọn) NẠP VÍ người dùng WoWonder
  // mysqli_query($sqlConnect, "UPDATE Wo_Users SET wallet = wallet + {$amount} WHERE user_id = ".(int)$pay['customer_id']." LIMIT 1");

  echo json_encode(['success'=>true]); exit;
}

// ---------------- STATUS ----------------
// GET /api/v3/payment/sepay.php?action=status&payment_id=UUID
if ($path === 'status') {
  $payment_id = $_GET['payment_id'] ?? '';
  $stmt = $sqlConnect->prepare("SELECT id, amount, method, status FROM payments WHERE id=? LIMIT 1");
  $stmt->bind_param('s', $payment_id);
  $stmt->execute();
  $res = $stmt->get_result()->fetch_assoc();
  if (!$res) { echo json_encode(['success'=>false,'message'=>'not_found']); exit; }

  echo json_encode([
    'success'    => true,
    'paid'       => ($res['status']==='paid'),
    'payment_id' => $res['id'],
    'amount'     => number_format((int)$res['amount'], 2, '.', ''),
    'method'     => $res['method']
  ]);
  exit;
}

// ---------------- fallback --------------
http_response_code(404);
echo json_encode(['success'=>false,'message'=>'unknown_action']);
