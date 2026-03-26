<?php
require_once __DIR__ .  '/../assets/includes/sepay_core.php';
if ($f == 'sepay') {
    header('Content-Type: application/json; charset=utf-8');
    $enabled       = ($wo['config']['sepay'] == 1);
    $mode          = $wo['config']['sepay_mode'] ?? 'live';
    $bankCode      = $wo['config']['sepay_bank_code'] ?? '';
    // var_dump($bankCode);
    $bankAcc       = $wo['config']['sepay_bank_acc'] ?? '';
    $webhookToken  = $wo['config']['sepay_webhook_token'] ?? '';
    $descPrefix    = $wo['config']['sepay_desc_prefix'] ?? 'SE';
    $webhookUrl    = rtrim($wo['config']['site_url'], '/') . '/requests.php?f=sepay&s=webhook';

    $bankCode = strtoupper(trim($wo['config']['sepay_bank_code']));
    // Nếu site bật CSRF:
    if (!empty($wo['config']['csrf_system']) && $s !== 'webhook') {
        if (empty($hash_id) || $hash_id != $wo['user']['session_hash']) {
            echo json_encode(['status' => 403, 'message' => 'Bad CSRF']);
            exit();
        }
    }
    if ($s == 'make_qr') {
        if (empty($wo['loggedin'])) {
            echo json_encode(['status' => 403, 'message' => 'Unauthorized']);
            exit;
        }
        $amount = (int)($_GET['amount'] ?? $_POST['amount'] ?? 0);
        $out = Wo_SepayCreateOrderQr((int)$wo['user']['user_id'], $amount, $wo, $sqlConnect);
        if (!empty($out['error'])) {
            echo json_encode(['status' => $out['code'] ?? 500, 'message' => $out['error']]);
            exit;
        }
        echo json_encode(['status' => 200] + $out);
        exit;
    }

    // ... phía trên giữ nguyên
    // NHỚ: chỉ bỏ qua CSRF cho webhook. Các endpoint khác (kể cả check) vẫn kiểm CSRF như bạn đã làm.
    if ($s === 'webhook') {
        // 1) Xác thực token
        $resp = Wo_SepayReturnWebhook($wo, $sqlConnect, (string)($_GET['token'] ?? ''));
        http_response_code($resp['http'] ?? 200);

        echo $resp['body'] ?? 'ok';
        exit;
    }

    // /requests.php?f=sepay&s=check&order_code=SEABC123&hash_id=...
    if ($s === 'check') {
        // Trả JSON + tránh cache
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

        // Yêu cầu đăng nhập
        if (empty($wo['loggedin'])) {
            echo json_encode(['status' => 403, 'message' => 'Unauthorized']);
            exit;
        }
        $order_code = trim((string)($_GET['order_code'] ?? $_POST['order_code'] ?? ''));
        if ($order_code === '') {
            echo json_encode(['status' => 400, 'message' => 'Missing order_code']);
            exit;
        }
        $out = Wo_SepayCheck($order_code, (int)$wo['user']['user_id'], $sqlConnect);
        if (!empty($out['error'])) {
            echo json_encode(['status' => $out['code'] ?? 500, 'message' => $out['error']]);
            exit;
        }
        echo json_encode(['status' => 200] + $out);
        exit;
    }
}
