<?php
/**
 * DEPOSIT CONTROLLER - Quản lý yêu cầu nạp tiền
 * Trả về danh sách ngân hàng và xử lý yêu cầu nạp tiền
 */

session_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once '../../config/config.php';
require_once '../../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// ========== GET ACTIVE BANKS FOR USER ==========

if ($_GET['action'] === 'get_active_banks') {
    try {
        $stmt = $conn->prepare("
            SELECT ba.id, ba.bank_name, ba.bank_code, ba.account_number, 
                   ba.account_holder, ba.icon_url, ba.qr_code_data, ba.description
            FROM bank_accounts ba
            LEFT JOIN payment_gateways pg ON ba.gateway_id = pg.id
            WHERE ba.is_active = 1 AND pg.is_active = 1
            ORDER BY ba.sort_order ASC, ba.id ASC
        ");
        $stmt->execute();
        $banks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => $banks
        ]);
    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// ========== CREATE DEPOSIT REQUEST ==========

if ($_GET['action'] === 'create_deposit') {
    try {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            throw new RuntimeException('Bạn cần đăng nhập');
        }

        $user_id = $_SESSION['user_id'];
        $bank_id = (int)($_POST['bank_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);

        if ($bank_id <= 0) {
            throw new RuntimeException('Ngân hàng không hợp lệ');
        }

        if ($amount < 10000) {
            throw new RuntimeException('Số tiền tối thiểu là 10,000 VND');
        }

        if ($amount > 100000000) {
            throw new RuntimeException('Số tiền tối đa là 100,000,000 VND');
        }

        // Verify bank exists and is active
        $stmt = $conn->prepare("
            SELECT ba.id FROM bank_accounts ba
            LEFT JOIN payment_gateways pg ON ba.gateway_id = pg.id
            WHERE ba.id = :bank_id AND ba.is_active = 1 AND pg.is_active = 1
        ");
        $stmt->execute(['bank_id' => $bank_id]);
        $bank = $stmt->fetch();

        if (!$bank) {
            throw new RuntimeException('Ngân hàng không tồn tại hoặc không hoạt động');
        }

        // Create deposit transaction
        $transaction_id = generateTransactionId();
        
        $stmt = $conn->prepare("
            INSERT INTO deposit_transactions (transaction_id, user_id, bank_account_id, amount, status, created_at)
            VALUES (:transaction_id, :user_id, :bank_account_id, :amount, 'pending', NOW())
        ");
        
        $stmt->execute([
            'transaction_id' => $transaction_id,
            'user_id' => $user_id,
            'bank_account_id' => $bank_id,
            'amount' => $amount
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Yêu cầu nạp tiền đã được tạo',
            'transaction_id' => $transaction_id,
            'amount' => $amount
        ]);
    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// ========== GET DEPOSIT HISTORY (USER) ==========

if ($_GET['action'] === 'get_user_deposits') {
    try {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            throw new RuntimeException('Bạn cần đăng nhập');
        }

        $user_id = $_SESSION['user_id'];
        
        $stmt = $conn->prepare("
            SELECT dt.id, dt.transaction_id, dt.amount, dt.status, dt.created_at,
                   ba.bank_name, ba.bank_code
            FROM deposit_transactions dt
            LEFT JOIN bank_accounts ba ON dt.bank_account_id = ba.id
            WHERE dt.user_id = :user_id
            ORDER BY dt.created_at DESC
            LIMIT 50
        ");
        
        $stmt->execute(['user_id' => $user_id]);
        $deposits = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => $deposits
        ]);
    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// ========== CHECK DEPOSIT SUCCESS (USER) ==========

if ($_GET['action'] === 'check_deposit_success') {
    try {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            throw new RuntimeException('Bạn cần đăng nhập');
        }

        $user_id = $_SESSION['user_id'];
        $amount = (float)($_GET['amount'] ?? 0);
        $bank_id = (int)($_GET['bank_id'] ?? 0);
        $since = (int)($_GET['since'] ?? 0);

        if ($amount <= 0 || $bank_id <= 0 || $since <= 0) {
            throw new RuntimeException('Tham số không hợp lệ');
        }

        $stmt = $conn->prepare("
            SELECT id, amount, created_at
            FROM deposit_transactions
            WHERE user_id = :user_id
              AND bank_account_id = :bank_id
              AND status = 'success'
              AND amount = :amount
              AND created_at >= FROM_UNIXTIME(:since)
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $stmt->execute([
            'user_id' => $user_id,
            'bank_id' => $bank_id,
            'amount' => $amount,
            'since' => floor($since / 1000)
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'is_paid' => $row ? true : false
        ]);
    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// ========== ADMIN: APPROVE DEPOSIT ==========

if ($_GET['action'] === 'approve_deposit') {
    try {
        if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
            throw new RuntimeException('Bạn không có quyền');
        }

        $transaction_id = trim($_POST['transaction_id'] ?? '');
        if (!$transaction_id) {
            throw new RuntimeException('Transaction ID không hợp lệ');
        }

        // Get transaction details
        $stmt = $conn->prepare("
            SELECT * FROM deposit_transactions WHERE transaction_id = :transaction_id
        ");
        $stmt->execute(['transaction_id' => $transaction_id]);
        $transaction = $stmt->fetch();

        if (!$transaction) {
            throw new RuntimeException('Giao dịch không tồn tại');
        }

        if ($transaction['status'] !== 'pending') {
            throw new RuntimeException('Giao dịch không ở trạng thái chờ xử lý');
        }

        // Start transaction
        $conn->beginTransaction();

        try {
            // Update transaction status
            $stmt = $conn->prepare("
                UPDATE deposit_transactions SET status = 'success', updated_at = NOW() 
                WHERE transaction_id = :transaction_id
            ");
            $stmt->execute(['transaction_id' => $transaction_id]);

            // Add balance to user wallet
            $stmt = $conn->prepare("
                UPDATE users SET wallet_balance = wallet_balance + :amount, updated_at = NOW()
                WHERE id = :user_id
            ");
            $stmt->execute([
                'amount' => $transaction['amount'],
                'user_id' => $transaction['user_id']
            ]);

            // Update bank total received
            $stmt = $conn->prepare("
                UPDATE bank_accounts SET total_received = total_received + :amount
                WHERE id = :bank_id
            ");
            $stmt->execute([
                'amount' => $transaction['amount'],
                'bank_id' => $transaction['bank_account_id']
            ]);

            // Log transaction
            $stmt = $conn->prepare("
                INSERT INTO transaction_logs (user_id, type, amount, reference_id, status, created_at)
                VALUES (:user_id, 'deposit', :amount, :transaction_id, 'success', NOW())
            ");
            $stmt->execute([
                'user_id' => $transaction['user_id'],
                'amount' => $transaction['amount'],
                'transaction_id' => $transaction_id
            ]);

            $conn->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Xác nhận nạp tiền thành công',
                'user_id' => $transaction['user_id'],
                'amount' => $transaction['amount']
            ]);
        } catch (Throwable $e) {
            $conn->rollBack();
            throw $e;
        }
    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// ========== ADMIN: REJECT DEPOSIT ==========

if ($_GET['action'] === 'reject_deposit') {
    try {
        if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
            throw new RuntimeException('Bạn không có quyền');
        }

        $transaction_id = trim($_POST['transaction_id'] ?? '');
        $reason = trim($_POST['reason'] ?? '');
        
        if (!$transaction_id) {
            throw new RuntimeException('Transaction ID không hợp lệ');
        }

        $stmt = $conn->prepare("
            SELECT * FROM deposit_transactions WHERE transaction_id = :transaction_id
        ");
        $stmt->execute(['transaction_id' => $transaction_id]);
        $transaction = $stmt->fetch();

        if (!$transaction) {
            throw new RuntimeException('Giao dịch không tồn tại');
        }

        if ($transaction['status'] !== 'pending') {
            throw new RuntimeException('Giao dịch không ở trạng thái chờ xử lý');
        }

        // Update transaction status
        $stmt = $conn->prepare("
            UPDATE deposit_transactions SET status = 'failed', notes = :reason, updated_at = NOW() 
            WHERE transaction_id = :transaction_id
        ");
        $stmt->execute([
            'transaction_id' => $transaction_id,
            'reason' => $reason
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Từ chối nạp tiền thành công'
        ]);
    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// ========== HELPER FUNCTIONS ==========

/**
 * Generate unique transaction ID
 */
function generateTransactionId() {
    return 'DEP-' . date('YmdHis') . '-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
}

// No action specified
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
