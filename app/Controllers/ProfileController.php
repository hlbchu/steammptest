<?php
/**
 * PROFILE CONTROLLER - Xử lý cập nhật thông tin profile
 */
session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng đăng nhập'
    ]);
    exit();
}

$db = new Database();
$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'update':
        updateProfile($db, $userId);
        break;
    case 'changePassword':
        changePassword($db, $userId);
        break;
    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Action không hợp lệ'
        ]);
}

/**
 * Cập nhật thông tin profile
 */
function updateProfile($db, $userId) {
    $email = $_POST['email'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $avatar_url = $_POST['avatar_url'] ?? '';

    // Validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'message' => 'Email không hợp lệ'
        ]);
        return;
    }

    // Check if email already exists (excluding current user)
    $db->query("SELECT id FROM users WHERE email = :email AND id != :user_id");
    $db->bind(':email', $email);
    $db->bind(':user_id', $userId);
    if ($db->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'Email này đã được sử dụng'
        ]);
        return;
    }

    // Validate avatar URL if provided
    if (!empty($avatar_url) && !filter_var($avatar_url, FILTER_VALIDATE_URL)) {
        echo json_encode([
            'success' => false,
            'message' => 'URL avatar không hợp lệ'
        ]);
        return;
    }

    // Validate phone if provided
    if (!empty($phone) && !preg_match('/^(\+84|0)[0-9]{9,10}$/', $phone)) {
        echo json_encode([
            'success' => false,
            'message' => 'Số điện thoại không hợp lệ'
        ]);
        return;
    }

    // Update user email
    $db->query("UPDATE users SET email = :email WHERE id = :user_id");
    $db->bind(':email', $email);
    $db->bind(':user_id', $userId);
    $db->execute();

    // Update user profile
    $db->query("UPDATE user_profiles SET full_name = :full_name, phone = :phone, avatar_url = :avatar_url WHERE user_id = :user_id");
    $db->bind(':full_name', $full_name);
    $db->bind(':phone', $phone);
    $db->bind(':avatar_url', $avatar_url);
    $db->bind(':user_id', $userId);
    $db->execute();

    // Update session email
    $_SESSION['email'] = $email;

    echo json_encode([
        'success' => true,
        'message' => 'Cập nhật thông tin thành công'
    ]);
}

/**
 * Đổi mật khẩu
 */
function changePassword($db, $userId) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Get current password hash
    $db->query("SELECT password_hash FROM users WHERE id = :user_id");
    $db->bind(':user_id', $userId);
    $user = $db->fetch();

    if (!$user) {
        echo json_encode([
            'success' => false,
            'message' => 'Người dùng không tồn tại'
        ]);
        return;
    }

    // Verify current password
    if (!password_verify($current_password, $user['password_hash'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Mật khẩu hiện tại không chính xác'
        ]);
        return;
    }

    // Validate new password
    if (strlen($new_password) < 6) {
        echo json_encode([
            'success' => false,
            'message' => 'Mật khẩu mới phải có ít nhất 6 ký tự'
        ]);
        return;
    }

    if ($new_password !== $confirm_password) {
        echo json_encode([
            'success' => false,
            'message' => 'Mật khẩu xác nhận không khớp'
        ]);
        return;
    }

    // Hash new password
    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

    // Update password
    $db->query("UPDATE users SET password_hash = :password_hash WHERE id = :user_id");
    $db->bind(':password_hash', $new_password_hash);
    $db->bind(':user_id', $userId);
    $db->execute();

    echo json_encode([
        'success' => true,
        'message' => 'Đổi mật khẩu thành công'
    ]);
}
?>
