<?php
/**
 * ADMIN BANK CONTROLLER - Quản lý ngân hàng
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit();
}

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../Helpers/QRCodeHelper.php';

$db = new Database();
$action = $_GET['action'] ?? '';

// ========== PAYMENT GATEWAYS ==========

if ($action === 'get_gateways') {
    try {
        $db->query("SELECT * FROM payment_gateways ORDER BY sort_order ASC, id DESC");
        $gateways = $db->fetchAll();
        echo json_encode(['success' => true, 'data' => $gateways]);
    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

if ($action === 'create_gateway') {
    try {
        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $api_key = trim($_POST['api_key'] ?? '');
        $api_secret = trim($_POST['api_secret'] ?? '');
        $webhook_url = trim($_POST['webhook_url'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $sort_order = (int)($_POST['sort_order'] ?? 0);

        if (!$name || !$code) {
            throw new RuntimeException('Tên và mã payment gateway không được trống');
        }

        $db->query("INSERT INTO payment_gateways (name, code, api_key, api_secret, webhook_url, is_active, sort_order) 
                   VALUES (:name, :code, :api_key, :api_secret, :webhook_url, :is_active, :sort_order)");
        $db->bind(':name', $name);
        $db->bind(':code', $code);
        $db->bind(':api_key', $api_key ?: null);
        $db->bind(':api_secret', $api_secret ?: null);
        $db->bind(':webhook_url', $webhook_url ?: null);
        $db->bind(':is_active', $is_active);
        $db->bind(':sort_order', $sort_order);
        $db->execute();

        echo json_encode(['success' => true, 'message' => 'Thêm payment gateway thành công']);
    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

if ($action === 'update_gateway') {
    try {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new RuntimeException('ID không hợp lệ');

        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $api_key = trim($_POST['api_key'] ?? '');
        $api_secret = trim($_POST['api_secret'] ?? '');
        $webhook_url = trim($_POST['webhook_url'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $sort_order = (int)($_POST['sort_order'] ?? 0);

        if (!$name || !$code) {
            throw new RuntimeException('Tên và mã payment gateway không được trống');
        }

        $db->query("UPDATE payment_gateways SET name = :name, code = :code, api_key = :api_key, 
                   api_secret = :api_secret, webhook_url = :webhook_url, is_active = :is_active, 
                   sort_order = :sort_order WHERE id = :id");
        $db->bind(':name', $name);
        $db->bind(':code', $code);
        $db->bind(':api_key', $api_key ?: null);
        $db->bind(':api_secret', $api_secret ?: null);
        $db->bind(':webhook_url', $webhook_url ?: null);
        $db->bind(':is_active', $is_active);
        $db->bind(':sort_order', $sort_order);
        $db->bind(':id', $id);
        $db->execute();

        echo json_encode(['success' => true, 'message' => 'Cập nhật payment gateway thành công']);
    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

if ($action === 'delete_gateway') {
    try {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new RuntimeException('ID không hợp lệ');

        $db->query("DELETE FROM payment_gateways WHERE id = :id");
        $db->bind(':id', $id);
        $db->execute();

        echo json_encode(['success' => true, 'message' => 'Xóa payment gateway thành công']);
    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// ========== BANK ACCOUNTS ==========

function getDefaultGatewayId($db) {
    try {
        $db->query("SELECT id FROM payment_gateways WHERE code = 'direct_bank' LIMIT 1");
        $row = $db->fetch();
        if ($row && !empty($row['id'])) {
            return (int)$row['id'];
        }

        $db->query("SELECT id FROM payment_gateways WHERE is_active = 1 ORDER BY sort_order ASC, id ASC LIMIT 1");
        $row = $db->fetch();
        if ($row && !empty($row['id'])) {
            return (int)$row['id'];
        }
    } catch (Throwable $e) {
        // ignore, fallback below
    }

    return 1;
}

if ($action === 'get_banks') {
    try {
        $db->query("SELECT ba.* FROM bank_accounts ba ORDER BY ba.sort_order ASC, ba.id DESC");
        $banks = $db->fetchAll();
        echo json_encode(['success' => true, 'data' => $banks]);
    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

if ($action === 'create_bank') {
    try {
        $gateway_id = (int)($_POST['gateway_id'] ?? 0);
        $bank_name = trim($_POST['bank_name'] ?? '');
        $bank_code = trim($_POST['bank_code'] ?? '');
        $account_number = trim($_POST['account_number'] ?? '');
        $account_holder = trim($_POST['account_holder'] ?? '');
        $icon_url = trim($_POST['icon_url'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $max_amount_threshold = trim($_POST['max_amount_threshold'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $sort_order = (int)($_POST['sort_order'] ?? 0);

        if (!$bank_name || !$bank_code) {
            throw new RuntimeException('Tên ngân hàng và mã ngân hàng không được trống');
        }

        if ($gateway_id <= 0) {
            $gateway_id = getDefaultGatewayId($db);
        }

        $db->query("INSERT INTO bank_accounts (gateway_id, bank_name, bank_code, account_number, account_holder, icon_url, description, max_amount_threshold, is_active, sort_order) 
                   VALUES (:gateway_id, :bank_name, :bank_code, :account_number, :account_holder, :icon_url, :description, :max_amount_threshold, :is_active, :sort_order)");
        $db->bind(':gateway_id', $gateway_id);
        $db->bind(':bank_name', $bank_name);
        $db->bind(':bank_code', $bank_code);
        $db->bind(':account_number', $account_number ?: null);
        $db->bind(':account_holder', $account_holder ?: null);
        $db->bind(':icon_url', $icon_url ?: null);
        $db->bind(':description', $description ?: null);
        $db->bind(':max_amount_threshold', $max_amount_threshold ?: null);
        $db->bind(':is_active', $is_active);
        $db->bind(':sort_order', $sort_order);
        $db->execute();

        echo json_encode(['success' => true, 'message' => 'Thêm ngân hàng thành công']);
    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

if ($action === 'update_bank') {
    try {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new RuntimeException('ID không hợp lệ');

        $gateway_id = (int)($_POST['gateway_id'] ?? 0);
        $bank_name = trim($_POST['bank_name'] ?? '');
        $bank_code = trim($_POST['bank_code'] ?? '');
        $account_number = trim($_POST['account_number'] ?? '');
        $account_holder = trim($_POST['account_holder'] ?? '');
        $icon_url = trim($_POST['icon_url'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $max_amount_threshold = trim($_POST['max_amount_threshold'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $sort_order = (int)($_POST['sort_order'] ?? 0);

        if (!$bank_name || !$bank_code) {
            throw new RuntimeException('Tên ngân hàng và mã ngân hàng không được trống');
        }

        if ($gateway_id <= 0) {
            $gateway_id = getDefaultGatewayId($db);
        }

        $db->query("UPDATE bank_accounts SET gateway_id = :gateway_id, bank_name = :bank_name, bank_code = :bank_code, 
                   account_number = :account_number, account_holder = :account_holder, icon_url = :icon_url, 
                   description = :description, max_amount_threshold = :max_amount_threshold, is_active = :is_active, sort_order = :sort_order WHERE id = :id");
        $db->bind(':gateway_id', $gateway_id);
        $db->bind(':bank_name', $bank_name);
        $db->bind(':bank_code', $bank_code);
        $db->bind(':account_number', $account_number ?: null);
        $db->bind(':account_holder', $account_holder ?: null);
        $db->bind(':icon_url', $icon_url ?: null);
        $db->bind(':description', $description ?: null);
        $db->bind(':max_amount_threshold', $max_amount_threshold ?: null);
        $db->bind(':is_active', $is_active);
        $db->bind(':sort_order', $sort_order);
        $db->bind(':id', $id);
        $db->execute();

        echo json_encode(['success' => true, 'message' => 'Cập nhật ngân hàng thành công']);
    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

if ($action === 'delete_bank') {
    try {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new RuntimeException('ID không hợp lệ');

        $db->query("DELETE FROM bank_accounts WHERE id = :id");
        $db->bind(':id', $id);
        $db->execute();

        echo json_encode(['success' => true, 'message' => 'Xóa ngân hàng thành công']);
    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

if ($action === 'reset_bank_total') {
    try {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new RuntimeException('ID không hợp lệ');

        $db->query("UPDATE bank_accounts SET total_received = 0 WHERE id = :id");
        $db->bind(':id', $id);
        $db->execute();

        echo json_encode(['success' => true, 'message' => 'Reset số tiền đã nhận thành công']);
    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// ========== QR CODE MANAGEMENT ==========

if ($action === 'generate_qr') {
    try {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new RuntimeException('ID không hợp lệ');

        // Get bank account info
        $db->query("SELECT * FROM bank_accounts WHERE id = :id");
        $db->bind(':id', $id);
        $db->execute();
        $bank = $db->fetch();

        if (!$bank) {
            throw new RuntimeException('Tài khoản ngân hàng không tồn tại');
        }

        // Generate QR code using helper function
        $qrBase64 = createBankQRCode($bank);

        // Update database
        $db->query("UPDATE bank_accounts SET qr_code_data = :qr_data, qr_generated_at = NOW() WHERE id = :id");
        $db->bind(':qr_data', $qrBase64);
        $db->bind(':id', $id);
        $db->execute();

        echo json_encode([
            'success' => true,
            'message' => 'Tạo mã QR thành công',
            'qr_data' => $qrBase64
        ]);
    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

if ($action === 'get_qr') {
    try {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) throw new RuntimeException('ID không hợp lệ');

        $db->query("SELECT qr_code_data, qr_code_url FROM bank_accounts WHERE id = :id");
        $db->bind(':id', $id);
        $db->execute();
        $bank = $db->fetch();

        if (!$bank || !$bank['qr_code_data']) {
            throw new RuntimeException('Mã QR không tồn tại');
        }

        echo json_encode([
            'success' => true,
            'qr_data' => $bank['qr_code_data']
        ]);
    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// ========== HELPER FUNCTIONS ==========

echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
