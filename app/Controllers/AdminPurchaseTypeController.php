<?php
/**
 * ADMIN PURCHASE TYPE CONTROLLER - Xử lý CRUD loại mua (Admin)
 */
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
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
        createPurchaseType($db);
        break;
    case 'update':
        updatePurchaseType($db);
        break;
    case 'delete':
        deletePurchaseType($db);
        break;
    case 'toggleActive':
        toggleActive($db);
        break;
    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Action không hợp lệ'
        ]);
}

/**
 * Tạo loại mua mới
 */
function createPurchaseType($db) {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($name)) {
        echo json_encode([
            'success' => false,
            'message' => 'Tên loại mua không được để trống'
        ]);
        return;
    }

    $slug = generateSlug($name);

    // Check if slug exists
    $db->query("SELECT id FROM purchase_types WHERE slug = :slug");
    $db->bind(':slug', $slug);
    if ($db->fetch()) {
        $slug = $slug . '-' . time();
    }

    $db->query("INSERT INTO purchase_types (name, slug, description, is_active) 
               VALUES (:name, :slug, :description, :is_active)");
    
    $db->bind(':name', $name);
    $db->bind(':slug', $slug);
    $db->bind(':description', $description);
    $db->bind(':is_active', $is_active);
    
    $db->execute();

    echo json_encode([
        'success' => true,
        'message' => 'Thêm loại mua thành công'
    ]);
}

/**
 * Cập nhật loại mua
 */
function updatePurchaseType($db) {
    $id = $_POST['id'] ?? '';
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($id) || empty($name)) {
        echo json_encode([
            'success' => false,
            'message' => 'Dữ liệu không hợp lệ'
        ]);
        return;
    }

    $slug = generateSlug($name);

    // Check if slug exists (excluding current record)
    $db->query("SELECT id FROM purchase_types WHERE slug = :slug AND id != :id");
    $db->bind(':slug', $slug);
    $db->bind(':id', $id);
    if ($db->fetch()) {
        $slug = $slug . '-' . time();
    }

    $db->query("UPDATE purchase_types 
               SET name = :name, 
                   slug = :slug, 
                   description = :description,
                   is_active = :is_active,
                   updated_at = NOW()
               WHERE id = :id");
    
    $db->bind(':id', $id);
    $db->bind(':name', $name);
    $db->bind(':slug', $slug);
    $db->bind(':description', $description);
    $db->bind(':is_active', $is_active);
    
    $db->execute();

    echo json_encode([
        'success' => true,
        'message' => 'Cập nhật loại mua thành công'
    ]);
}

/**
 * Xóa loại mua
 */
function deletePurchaseType($db) {
    $id = $_POST['id'] ?? '';

    if (empty($id)) {
        echo json_encode([
            'success' => false,
            'message' => 'ID không hợp lệ'
        ]);
        return;
    }

    // Check if purchase type is being used
    $db->query("SELECT COUNT(*) as count FROM products WHERE purchase_type_id = :id");
    $db->bind(':id', $id);
    $result = $db->fetch();
    
    if ($result['count'] > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Không thể xóa loại mua đang được sử dụng bởi ' . $result['count'] . ' sản phẩm'
        ]);
        return;
    }

    $db->query("DELETE FROM purchase_types WHERE id = :id");
    $db->bind(':id', $id);
    $db->execute();

    echo json_encode([
        'success' => true,
        'message' => 'Xóa loại mua thành công'
    ]);
}

/**
 * Toggle active status
 */
function toggleActive($db) {
    $id = $_POST['id'] ?? '';
    $is_active = $_POST['is_active'] ?? 0;

    if (empty($id)) {
        echo json_encode([
            'success' => false,
            'message' => 'ID không hợp lệ'
        ]);
        return;
    }

    $db->query("UPDATE purchase_types SET is_active = :is_active, updated_at = NOW() WHERE id = :id");
    $db->bind(':is_active', $is_active);
    $db->bind(':id', $id);
    $db->execute();

    echo json_encode([
        'success' => true,
        'message' => $is_active ? 'Đã kích hoạt loại mua' : 'Đã ẩn loại mua'
    ]);
}

/**
 * Generate slug from string
 */
function generateSlug($str) {
    $str = trim(mb_strtolower($str));
    $str = preg_replace('/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/', 'a', $str);
    $str = preg_replace('/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/', 'e', $str);
    $str = preg_replace('/(ì|í|ị|ỉ|ĩ)/', 'i', $str);
    $str = preg_replace('/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/', 'o', $str);
    $str = preg_replace('/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/', 'u', $str);
    $str = preg_replace('/(ỳ|ý|ỵ|ỷ|ỹ)/', 'y', $str);
    $str = preg_replace('/(đ)/', 'd', $str);
    $str = preg_replace('/[^a-z0-9-\s]/', '', $str);
    $str = preg_replace('/([\s]+)/', '-', $str);
    return $str;
}
