<?php
/**
 * Admin Payment Controller
 * API for payment management (transactions, statistics)
 */

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../Helpers/QRCodeHelper.php';

// Check admin role
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit();
}

header('Content-Type: application/json');

try {
    $action = $_GET['action'] ?? '';
    $db = new Database();
    $conn = $db->getConnection();

    switch ($action) {
        case 'get_transactions':
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? '';

            $query = "
                SELECT 
                    dt.id,
                    dt.transaction_id,
                    dt.user_id,
                    dt.bank_account_id,
                    dt.amount,
                    dt.status,
                    dt.notes,
                    dt.created_at,
                    ba.bank_name,
                    u.username
                FROM deposit_transactions dt
                LEFT JOIN bank_accounts ba ON dt.bank_account_id = ba.id
                LEFT JOIN users u ON dt.user_id = u.id
                WHERE 1=1
            ";

            if ($search) {
                $query .= " AND (
                    dt.transaction_id LIKE ? 
                    OR dt.user_id LIKE ?
                    OR u.username LIKE ?
                )";
            }

            if ($status) {
                $query .= " AND dt.status = ?";
            }

            $query .= " ORDER BY dt.created_at DESC LIMIT 100";

            $stmt = $conn->prepare($query);
            $params = [];

            if ($search) {
                $searchTerm = "%{$search}%";
                $params = [$searchTerm, $searchTerm, $searchTerm];
            }

            if ($status) {
                $params[] = $status;
            }

            $stmt->execute($params);
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $transactions
            ]);
            break;

        case 'get_transaction_detail':
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
                break;
            }

            $stmt = $conn->prepare("
                SELECT 
                    dt.id,
                    dt.transaction_id,
                    dt.user_id,
                    dt.amount,
                    dt.status,
                    dt.notes,
                    dt.created_at,
                    dt.updated_at,
                    u.username,
                    u.email,
                    ba.bank_name,
                    ba.bank_code,
                    ba.account_number,
                    ba.account_holder
                FROM deposit_transactions dt
                LEFT JOIN users u ON dt.user_id = u.id
                LEFT JOIN bank_accounts ba ON dt.bank_account_id = ba.id
                WHERE dt.id = ?
                LIMIT 1
            ");
            $stmt->execute([$id]);
            $detail = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($detail) {
                echo json_encode(['success' => true, 'data' => $detail]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Giao dịch không tồn tại']);
            }
            break;

        case 'approve_deposit':
            $deposit_id = (int)($_POST['id'] ?? 0);
            if (!$deposit_id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
                break;
            }

            $stmt = $conn->prepare("SELECT * FROM deposit_transactions WHERE id = ? LIMIT 1");
            $stmt->execute([$deposit_id]);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$transaction) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Giao dịch không tồn tại']);
                break;
            }

            if ($transaction['status'] !== 'pending') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Giao dịch không ở trạng thái chờ xử lý']);
                break;
            }

            $conn->beginTransaction();
            try {
                // Update status to completed
                $stmt = $conn->prepare("
                    UPDATE deposit_transactions 
                    SET status = 'completed', updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$deposit_id]);

                // Update wallet balance
                $stmt = $conn->prepare("
                    INSERT INTO wallets (user_id, balance, updated_at) 
                    VALUES (?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                    balance = balance + ?, 
                    updated_at = NOW()
                ");
                $stmt->execute([
                    $transaction['user_id'],
                    $transaction['amount'],
                    $transaction['amount']
                ]);

                $conn->commit();

                echo json_encode([
                    'success' => true,
                    'message' => 'Duyệt nạp tiền thành công'
                ]);
            } catch (Throwable $e) {
                $conn->rollBack();
                throw $e;
            }
            break;

        case 'reject_deposit':
            $transaction_id = trim($_POST['transaction_id'] ?? '');
            $reason = trim($_POST['reason'] ?? '');

            if (!$transaction_id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Transaction ID không hợp lệ']);
                break;
            }

            $stmt = $conn->prepare("SELECT * FROM deposit_transactions WHERE transaction_id = :transaction_id LIMIT 1");
            $stmt->execute(['transaction_id' => $transaction_id]);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$transaction) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Giao dịch không tồn tại']);
                break;
            }

            if ($transaction['status'] !== 'pending') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Giao dịch không ở trạng thái chờ xử lý']);
                break;
            }

            $stmt = $conn->prepare("
                UPDATE deposit_transactions 
                SET status = 'failed', notes = :reason, updated_at = NOW()
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
            break;

        case 'get_history':
            $bank_id = $_GET['bank_id'] ?? '';
            $search_content = $_GET['search_content'] ?? '';
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
            $offset = ($page - 1) * $limit;

            $query = "
                SELECT 
                    dt.id,
                    dt.user_id,
                    dt.transaction_code,
                    dt.amount,
                    dt.status,
                    dt.notes,
                    dt.created_at,
                    ba.id as bank_id,
                    ba.bank_name,
                    ba.is_active,
                    u.username
                FROM deposit_transactions dt
                LEFT JOIN bank_accounts ba ON dt.bank_account_id = ba.id
                LEFT JOIN users u ON dt.user_id = u.id
                WHERE 1=1
            ";

            $params = [];

            if ($bank_id) {
                $query .= " AND dt.bank_account_id = ?";
                $params[] = $bank_id;
            }

            if ($search_content) {
                $query .= " AND dt.notes LIKE ?";
                $params[] = '%' . $search_content . '%';
            }

            // Count total records for pagination
            $count_query = "
                SELECT COUNT(*) as total
                FROM deposit_transactions dt
                LEFT JOIN bank_accounts ba ON dt.bank_account_id = ba.id
                LEFT JOIN users u ON dt.user_id = u.id
                WHERE 1=1
            ";

            if ($bank_id) {
                $count_query .= " AND dt.bank_account_id = ?";
            }

            if ($search_content) {
                $count_query .= " AND dt.notes LIKE ?";
            }

            $stmt_count = $conn->prepare($count_query);
            $stmt_count->execute($params);
            $total_records = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];

            $query .= " ORDER BY dt.created_at DESC LIMIT :limit OFFSET :offset";

            $stmt = $conn->prepare($query);
            
            // Bind regular params
            foreach ($params as $idx => $val) {
                $stmt->bindValue($idx + 1, $val);
            }
            
            // Bind LIMIT and OFFSET as integers
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt_banks = $conn->prepare("
                SELECT id, bank_name, is_active, total_received 
                FROM bank_accounts 
                ORDER BY is_active DESC, sort_order ASC
            ");
            $stmt_banks->execute();
            $banks = $stmt_banks->fetchAll(PDO::FETCH_ASSOC);

            $stmt_active = $conn->prepare("
                SELECT bank_name FROM bank_accounts WHERE is_active = TRUE LIMIT 1
            ");
            $stmt_active->execute();
            $active_bank = $stmt_active->fetch(PDO::FETCH_ASSOC);

            // Tổng tiền đã nhận (chỉ completed)
            $total_amount = 0;
            foreach ($history as $h) {
                if ($h['status'] === 'completed') {
                    $total_amount += (float)$h['amount'];
                }
            }

            echo json_encode([
                'success' => true,
                'data' => $history,
                'banks' => $banks,
                'current_bank' => $active_bank['bank_name'] ?? '-',
                'total_amount' => $total_amount,
                'total_records' => $total_records,
                'current_page' => $page,
                'total_pages' => ceil($total_records / $limit)
            ]);
            break;

        case 'get_statistics':
            $stmt = $conn->prepare("
                SELECT 
                    id,
                    bank_name,
                    account_number,
                    account_holder,
                    total_received,
                    max_amount_threshold,
                    is_active
                FROM bank_accounts
                WHERE is_active = TRUE
                ORDER BY sort_order ASC
            ");
            $stmt->execute();
            $banks = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $total_received = 0;
            $total_limit = 0;
            $total_capacity = 0;
            $capacity_count = 0;

            foreach ($banks as &$bank) {
                $bank['total_received'] = (float)$bank['total_received'];
                $bank['max_amount_threshold'] = (float)$bank['max_amount_threshold'];
                
                $total_received += $bank['total_received'];
                
                if ($bank['max_amount_threshold'] > 0) {
                    $total_limit += $bank['max_amount_threshold'];
                    $capacity = ($bank['total_received'] / $bank['max_amount_threshold']) * 100;
                    $total_capacity += $capacity;
                    $capacity_count++;
                }
            }

            $avg_capacity = $capacity_count > 0 ? ($total_capacity / $capacity_count) : 0;

            echo json_encode([
                'success' => true,
                'data' => $banks,
                'statistics' => [
                    'total_received' => $total_received,
                    'total_limit' => $total_limit,
                    'avg_capacity' => $avg_capacity
                ]
            ]);
            break;

        case 'get_banks':
            $stmt = $conn->prepare("
                SELECT id, bank_name, is_active 
                FROM bank_accounts 
                ORDER BY is_active DESC, sort_order ASC
            ");
            $stmt->execute();
            $banks = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $banks
            ]);
            break;

        case 'get_bank_detail':
            $bank_id = (int)($_GET['id'] ?? 0);
            
            $stmt = $conn->prepare("
                SELECT id, bank_name, bank_code, account_number, account_holder, description, is_active, qr_code_data, qr_generated_at
                FROM bank_accounts 
                WHERE id = ?
            ");
            $stmt->execute([$bank_id]);
            $bank = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($bank) {
                echo json_encode([
                    'success' => true,
                    'data' => $bank
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Bank account not found'
                ]);
            }
            break;

        case 'generate_qr':
            $bank_id = (int)($_POST['id'] ?? 0);
            if ($bank_id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
                break;
            }

            $stmt = $conn->prepare("SELECT * FROM bank_accounts WHERE id = ?");
            $stmt->execute([$bank_id]);
            $bank = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$bank) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Tài khoản ngân hàng không tồn tại']);
                break;
            }

            $qrBase64 = createBankQRCode($bank);

            $stmt = $conn->prepare("
                UPDATE bank_accounts 
                SET qr_code_data = :qr_data, qr_generated_at = NOW() 
                WHERE id = :id
            ");
            $stmt->execute([
                'qr_data' => $qrBase64,
                'id' => $bank_id
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Tạo mã QR thành công',
                'qr_data' => $qrBase64
            ]);
            break;

        case 'update_bank_info':
            $bank_id = $_POST['id'] ?? 0;
            $bank_name = $_POST['bank_name'] ?? '';
            $bank_code = $_POST['bank_code'] ?? '';
            $account_number = $_POST['account_number'] ?? '';
            $account_holder = $_POST['account_holder'] ?? '';
            $description = $_POST['description'] ?? '';

            $stmt = $conn->prepare("
                UPDATE bank_accounts 
                SET bank_name = ?, bank_code = ?, account_number = ?, account_holder = ?, description = ?
                WHERE id = ?
            ");
            $stmt->execute([$bank_name, $bank_code, $account_number, $account_holder, $description, $bank_id]);

            echo json_encode([
                'success' => true,
                'message' => 'Cập nhật thông tin tài khoản thành công'
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
