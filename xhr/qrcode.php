<?php
ini_set('display_errors', 0);   // không in lỗi ra trình duyệt
error_reporting(E_ALL); 
    function wo_parse_amount($raw)
    {
        $s = trim((string)$raw);
        if ($s === '') return null;
        $s = preg_replace('/\s+/', '', $s);

        // Cả '.' và ',' cùng xuất hiện
        if (strpos($s, '.') !== false && strpos($s, ',') !== false) {
            $lastDot   = strrpos($s, '.');
            $lastComma = strrpos($s, ',');
            if ($lastComma > $lastDot) {        // thập phân là ','
                $s = str_replace('.', '', $s);  // bỏ dấu . ngăn nghìn
                $s = str_replace(',', '.', $s); // ',' -> '.'
            } else {                            // thập phân là '.'
                $s = str_replace(',', '', $s);  // bỏ dấu , ngăn nghìn
            }
        }
        // Chỉ có ','
        else if (strpos($s, ',') !== false) {
            if (preg_match('/^\d{1,3}(,\d{3})+$/', $s)) {
                $s = str_replace(',', '', $s);      // 1,000 -> 1000
            } else {
                $s = str_replace(',', '.', $s);     // 1000,5 -> 1000.5
            }
        }
        // Chỉ có '.'
        else if (strpos($s, '.') !== false) {
            if (preg_match('/^\d{1,3}(\.\d{3})+$/', $s)) {
                $s = str_replace('.', '', $s);      // 1.000 -> 1000
            } // ngược lại: 1000.5 đã đúng
        }

        // Giữ duy nhất 1 dấu '.'
        $s = preg_replace('/[^0-9.]/', '', $s);
        if (substr_count($s, '.') > 1) {
            $first = strpos($s, '.');
            $s = substr($s, 0, $first + 1) . str_replace('.', '', substr($s, $first + 1));
        }

        return is_numeric($s) ? (float)$s : null;
    }

    if ($f === 'qrcode'  ) {
        if($s === 'wallet-qr-code'){
        // yêu cầu đăng nhập qua cookie phiên
        if (empty($wo['loggedin'])) {
            http_response_code(401);
            exit;
        }

        $to = isset($_GET['to']) ? (int)$_GET['to'] : (int)$wo['user']['user_id'];
        $ud = Wo_UserData($to);
        if (empty($ud['user_id']) || $ud['banned'] == 1 || $ud['active'] == 0) {
            http_response_code(404);
            exit;
        }

        $amount  = isset($_GET['amount']) ? wo_parse_amount($_GET['amount']) : null;
        $payload = 'WALLET|to=' . $to  . (($amount !== null && $amount > 0) ? '|amount='.number_format($amount, 2, '.', '') : '');
        // nạp thư viện phpqrcode
        $paths = [
            __DIR__ . '/assets/includes/phpqrcode/qrlib.php',
            __DIR__ . '/../assets/includes/phpqrcode/qrlib.php',
            dirname(__DIR__) . '/assets/includes/phpqrcode/qrlib.php',
        ];
        $ok = false;
        foreach ($paths as $p) {
            if (file_exists($p)) {
                require_once $p;
                $ok = true;
                break;
            }
        }
        if (!$ok || !class_exists('QRcode')) {
            http_response_code(500);
            header('Content-Type:text/plain');
            echo 'QR library not found';
            exit;
        }

        // debug nhanh: thêm &debug=1 để xem text
        if (!empty($_GET['debug'])) {
            header('Content-Type: text/plain; charset=utf-8');
            echo "to={$to}\npayload={$payload}\n";
            exit;
        }

        // dọn buffer & trả PNG
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: image/png');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        QRcode::png($payload, false, QR_ECLEVEL_Q, 6, 2);
        exit;
    }
    if($s==='brief_by_id'){
         header('Content-Type: application/json; charset=utf-8');

    if (empty($wo['loggedin'])) {
        echo json_encode(['status' => 403, 'message' => 'Unauthorized']); exit;
    }

    $id = (int)($_GET['id'] ?? 0);
    $u  = Wo_UserData($id);

    if (empty($u['user_id']) || $u['active'] == 0 || $u['banned'] == 1) {
        echo json_encode(['status' => 404, 'message' => 'User not found']); exit;
    }

    // Chỉ trả email khi là chính họ hoặc admin (tuỳ chính sách bạn)
    $resp = [
        'status'   => 200,
        'id'       => (int)$u['user_id'],
        'name'     => (string)($u['name'] ?? ''),
        'username' => (string)($u['username'] ?? ''),
        'avatar'   => (string)($u['avatar'] ?? '')
    ];
    if (!empty($u['email']) && ($wo['user']['user_id'] == $u['user_id'] || !empty($wo['user']['admin']))) {
        $resp['email'] = (string)$u['email'];
    }

    echo json_encode($resp); exit;
    }

}
