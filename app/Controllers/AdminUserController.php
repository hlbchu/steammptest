<?php
/**
 * ADMIN USER CONTROLLER - Quản lý người dùng (Admin)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Không có quyền truy cập'
    ]);
    exit();
}

require_once '../../config/config.php';
require_once '../../config/database.php';

$db = new Database();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        createUser($db);
        break;
    case 'update':
        updateUser($db);
        break;
    case 'delete':
        deleteUser($db);
        break;
    case 'adjust_balance':
        adjustBalance($db);
        break;
    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Action không hợp lệ'
        ]);
}

function createUser($db) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';
    $status = $_POST['status'] ?? 'active';

    if ($username === '' || $email === '' || $password === '') {
        echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin']);
        return;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $db->query("SELECT id FROM users WHERE username = :username OR email = :email");
    $db->bind(':username', $username);
    $db->bind(':email', $email);
    if ($db->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Username hoặc Email đã tồn tại']);
        return;
    }

    $pdo = $db->getConnection();
    try {
        $pdo->beginTransaction();

        $db->query("INSERT INTO users (username, email, password_hash, role, status, created_at) 
                    VALUES (:username, :email, :password_hash, :role, :status, NOW())");
        $db->bind(':username', $username);
        $db->bind(':email', $email);
        $db->bind(':password_hash', $hash);
        $db->bind(':role', $role);
        $db->bind(':status', $status);
        $db->execute();

        $userId = $pdo->lastInsertId();

        $db->query("INSERT INTO wallets (user_id, balance, updated_at) VALUES (:user_id, 0, NOW())");
        $db->bind(':user_id', $userId);
        $db->execute();

        $pdo->commit();

        echo json_encode(['success' => true, 'message' => 'Tạo người dùng thành công']);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
    }
}

function updateUser($db) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';
    $status = $_POST['status'] ?? 'active';

    if ($id <= 0 || $username === '' || $email === '') {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
        return;
    }

    $db->query("SELECT id FROM users WHERE (username = :username OR email = :email) AND id <> :id");
    $db->bind(':username', $username);
    $db->bind(':email', $email);
    $db->bind(':id', $id);
    if ($db->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Username hoặc Email đã tồn tại']);
        return;
    }

    $db->query("UPDATE users SET username = :username, email = :email, role = :role, status = :status" .
               ($password !== '' ? ", password_hash = :password_hash" : "") .
               ", updated_at = NOW() WHERE id = :id");
    $db->bind(':username', $username);
    $db->bind(':email', $email);
    $db->bind(':role', $role);
    $db->bind(':status', $status);
    if ($password !== '') {
        $db->bind(':password_hash', password_hash($password, PASSWORD_DEFAULT));
    }
    $db->bind(':id', $id);
    $db->execute();

    echo json_encode(['success' => true, 'message' => 'Cập nhật người dùng thành công']);
}

function deleteUser($db) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
        return;
    }

    $db->query("DELETE FROM users WHERE id = :id");
    $db->bind(':id', $id);
    $db->execute();

    echo json_encode(['success' => true, 'message' => 'Xóa người dùng thành công']);
}

function adjustBalance($db) {
    $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $actionType = $_POST['action_type'] ?? 'add';
    $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
    $note = trim($_POST['note'] ?? '');

    if ($userId <= 0 || $amount <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Dữ liệu không hợp lệ'
        ]);
        return;
    }

    $delta = ($actionType === 'subtract') ? -$amount : $amount;

    $pdo = $db->getConnection();

    try {
        $pdo->beginTransaction();

        $db->query("SELECT balance FROM wallets WHERE user_id = :user_id FOR UPDATE");
        $db->bind(':user_id', $userId);
        $wallet = $db->fetch();

        if (!$wallet) {
            $db->query("INSERT INTO wallets (user_id, balance, updated_at) VALUES (:user_id, 0, NOW())");
            $db->bind(':user_id', $userId);
            $db->execute();
            $current = 0.0;
        } else {
            $current = (float)$wallet['balance'];
        }

        $newBalance = $current + $delta;
        if ($newBalance < 0) {
            $pdo->rollBack();
            echo json_encode([
                'success' => false,
                'message' => 'Số dư không đủ để trừ'
            ]);
            return;
        }

        $db->query("UPDATE wallets SET balance = :balance, updated_at = NOW() WHERE user_id = :user_id");
        $db->bind(':balance', $newBalance);
        $db->bind(':user_id', $userId);
        $db->execute();

        $type = $delta >= 0 ? 'deposit' : 'refund';
        $description = $note !== '' ? ('Admin adjust balance - ' . $note) : 'Admin adjust balance';

        $db->query("INSERT INTO transactions (user_id, type, amount, description, created_at) VALUES (:user_id, :type, :amount, :description, NOW())");
        $db->bind(':user_id', $userId);
        $db->bind(':type', $type);
        $db->bind(':amount', abs($delta));
        $db->bind(':description', $description);
        $db->execute();

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Cập nhật số dư thành công',
            'new_balance' => $newBalance
        ]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
        ]);
    }
}
