<?php
/**
 * ADMIN BANNER CONTROLLER
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

$db = new Database();

$action = $_GET['action'] ?? '';

function ensureBannerPositionColumn($db) {
    try {
        $db->query("SHOW COLUMNS FROM banners LIKE 'position'");
        $exists = $db->fetch();
        if (!$exists) {
            $db->query("ALTER TABLE banners ADD COLUMN position ENUM('slide','category') NOT NULL DEFAULT 'slide'");
            $db->execute();
        }
    } catch (Throwable $e) {
        // Ignore if table not found or permission denied
    }
}

function uploadBannerImage($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        throw new RuntimeException('Định dạng ảnh không hợp lệ');
    }

    $uploadDir = UPLOADS_PATH . '/banners';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileName = 'banner_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetPath = $uploadDir . '/' . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new RuntimeException('Tải ảnh lên thất bại');
    }

    return rtrim(APP_URL, '/') . '/public/uploads/banners/' . $fileName;
}

try {
    ensureBannerPositionColumn($db);

    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $position = $_POST['position'] ?? 'slide';
        $imageUrl = trim($_POST['image_url'] ?? '');
        $linkUrl = trim($_POST['link_url'] ?? '');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (!empty($_FILES['image_file']['name'] ?? '')) {
            $imageUrl = uploadBannerImage($_FILES['image_file']);
        }

        if ($title === '' || $imageUrl === '') {
            throw new RuntimeException('Vui lòng nhập tiêu đề và hình ảnh');
        }

        $db->query("SHOW COLUMNS FROM banners LIKE 'position'");
        $hasPosition = (bool) $db->fetch();

        $columns = 'title, image_url, link_url, is_active, sort_order';
        $values = ':title, :image_url, :link_url, :is_active, :sort_order';
        if ($hasPosition) {
            $columns .= ', position';
            $values .= ', :position';
        }

        $db->query("INSERT INTO banners ($columns) VALUES ($values)");
        $db->bind(':title', $title);
        $db->bind(':image_url', $imageUrl);
        $db->bind(':link_url', $linkUrl ?: null);
        $db->bind(':is_active', $isActive);
        $db->bind(':sort_order', $sortOrder);
        if ($hasPosition) {
            $db->bind(':position', $position);
        }
        $db->execute();

        echo json_encode(['success' => true, 'message' => 'Thêm banner thành công']);
        exit();
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            throw new RuntimeException('ID banner không hợp lệ');
        }

        $title = trim($_POST['title'] ?? '');
        $position = $_POST['position'] ?? 'slide';
        $imageUrl = trim($_POST['image_url'] ?? '');
        $linkUrl = trim($_POST['link_url'] ?? '');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (!empty($_FILES['image_file']['name'] ?? '')) {
            $imageUrl = uploadBannerImage($_FILES['image_file']);
        }

        if ($title === '') {
            throw new RuntimeException('Vui lòng nhập tiêu đề');
        }

        if ($imageUrl === '') {
            $db->query('SELECT image_url FROM banners WHERE id = :id');
            $db->bind(':id', $id);
            $existing = $db->fetch();
            $imageUrl = $existing['image_url'] ?? '';
        }

        if ($imageUrl === '') {
            throw new RuntimeException('Vui lòng nhập hình ảnh');
        }

        $db->query("SHOW COLUMNS FROM banners LIKE 'position'");
        $hasPosition = (bool) $db->fetch();

        $setParts = [
            'title = :title',
            'image_url = :image_url',
            'link_url = :link_url',
            'is_active = :is_active',
            'sort_order = :sort_order'
        ];
        if ($hasPosition) {
            $setParts[] = 'position = :position';
        }

        $db->query('UPDATE banners SET ' . implode(', ', $setParts) . ' WHERE id = :id');
        $db->bind(':title', $title);
        $db->bind(':image_url', $imageUrl);
        $db->bind(':link_url', $linkUrl ?: null);
        $db->bind(':is_active', $isActive);
        $db->bind(':sort_order', $sortOrder);
        if ($hasPosition) {
            $db->bind(':position', $position);
        }
        $db->bind(':id', $id);
        $db->execute();

        echo json_encode(['success' => true, 'message' => 'Cập nhật banner thành công']);
        exit();
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            throw new RuntimeException('ID banner không hợp lệ');
        }

        $db->query('DELETE FROM banners WHERE id = :id');
        $db->bind(':id', $id);
        $db->execute();

        echo json_encode(['success' => true, 'message' => 'Xóa banner thành công']);
        exit();
    }

    echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
