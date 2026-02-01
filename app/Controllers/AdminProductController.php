<?php
/**
 * ADMIN PRODUCT CONTROLLER - Xử lý CRUD sản phẩm (Admin)
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
        createProduct($db);
        break;
    case 'update':
        updateProduct($db);
        break;
    case 'delete':
        deleteProduct($db);
        break;
    case 'toggleHot':
        toggleHot($db);
        break;
    case 'quickUpdate':
        quickUpdateField($db);
        break;
    case 'updateCategories':
        updateProductCategories($db);
        break;
    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Action không hợp lệ'
        ]);
}

/**
 * Tạo sản phẩm mới
 */
function createProduct($db) {
    $name = $_POST['name'] ?? '';
    $category_id = $_POST['category_id'] ?? null;
    $purchase_type_id = $_POST['purchase_type_id'] ?? null;
    $price = $_POST['price'] ?? 0;
    $sale_price = $_POST['sale_price'] ?? null;
    $badge = $_POST['badge'] ?? null;
    $rating = $_POST['rating'] ?? null;
    $image = $_POST['image'] ?? null;
    $gallery = $_POST['gallery'] ?? null;
    $video_url = $_POST['video_url'] ?? null;
    $description = $_POST['description'] ?? null;
    $received_content = $_POST['received_content'] ?? null;
    $content = $_POST['content'] ?? null;
    $status = $_POST['status'] ?? 'active';

    if (empty($name)) {
        echo json_encode([
            'success' => false,
            'message' => 'Tên sản phẩm không được để trống'
        ]);
        return;
    }

    $slug = generateSlug($name);

    $db->query("SELECT id FROM products WHERE slug = :slug");
    $db->bind(':slug', $slug);
    if ($db->fetch()) {
        $slug = $slug . '-' . time();
    }

    $db->query("INSERT INTO products (name, slug, category_id, purchase_type_id, price, sale_price, badge, rating, image, gallery, video_url, description, received_content, content, status) 
               VALUES (:name, :slug, :category_id, :purchase_type_id, :price, :sale_price, :badge, :rating, :image, :gallery, :video_url, :description, :received_content, :content, :status)");
    
    $db->bind(':name', $name);
    $db->bind(':slug', $slug);
    $db->bind(':category_id', $category_id);
    $db->bind(':purchase_type_id', $purchase_type_id);
    $db->bind(':price', $price);
    $db->bind(':sale_price', $sale_price);
    $db->bind(':badge', $badge);
    $db->bind(':rating', $rating);
    $db->bind(':image', $image);
    $db->bind(':gallery', $gallery);
    $db->bind(':video_url', $video_url);
    $db->bind(':description', $description);
    $db->bind(':received_content', $received_content);
    $db->bind(':content', $content);
    $db->bind(':status', $status);
    
    $db->execute();

    echo json_encode([
        'success' => true,
        'message' => 'Thêm sản phẩm thành công'
    ]);
}

/**
 * Cập nhật sản phẩm
 */
function updateProduct($db) {
    $id = $_POST['id'] ?? '';
    $name = $_POST['name'] ?? '';
    $category_id = $_POST['category_id'] ?? null;
    $purchase_type_id = $_POST['purchase_type_id'] ?? null;
    $price = $_POST['price'] ?? 0;
    $sale_price = $_POST['sale_price'] ?? null;
    $badge = $_POST['badge'] ?? null;
    $rating = $_POST['rating'] ?? null;
    $image = $_POST['image'] ?? null;
    $gallery = $_POST['gallery'] ?? null;
    $video_url = $_POST['video_url'] ?? null;
    $description = $_POST['description'] ?? null;
    $received_content = $_POST['received_content'] ?? null;
    $content = $_POST['content'] ?? null;
    $status = $_POST['status'] ?? 'active';

    if (empty($id) || empty($name)) {
        echo json_encode([
            'success' => false,
            'message' => 'Dữ liệu không hợp lệ'
        ]);
        return;
    }

    $slug = generateSlug($name);

    $db->query("SELECT id FROM products WHERE slug = :slug AND id != :id");
    $db->bind(':slug', $slug);
    $db->bind(':id', $id);
    if ($db->fetch()) {
        $slug = $slug . '-' . time();
    }

    $db->query("UPDATE products SET 
                name = :name, 
                slug = :slug,
                category_id = :category_id,                 purchase_type_id = :purchase_type_id,                price = :price, 
                sale_price = :sale_price, 
                badge = :badge, 
                rating = :rating, 
                image = :image, 
                gallery = :gallery,
                video_url = :video_url,
                description = :description,
                received_content = :received_content,
                content = :content,
                status = :status,
                updated_at = NOW()
                WHERE id = :id");
    
    $db->bind(':id', $id);
    $db->bind(':name', $name);
    $db->bind(':slug', $slug);
    $db->bind(':category_id', $category_id);
    $db->bind(':purchase_type_id', $purchase_type_id);
    $db->bind(':price', $price);
    $db->bind(':sale_price', $sale_price);
    $db->bind(':badge', $badge);
    $db->bind(':rating', $rating);
    $db->bind(':image', $image);
    $db->bind(':gallery', $gallery);
    $db->bind(':video_url', $video_url);
    $db->bind(':description', $description);
    $db->bind(':received_content', $received_content);
    $db->bind(':content', $content);
    $db->bind(':status', $status);
    
    $db->execute();

    echo json_encode([
        'success' => true,
        'message' => 'Cập nhật sản phẩm thành công'
    ]);
}

/**
 * Xóa sản phẩm
 */
function deleteProduct($db) {
    $id = $_POST['id'] ?? '';

    if (empty($id)) {
        echo json_encode([
            'success' => false,
            'message' => 'ID không hợp lệ'
        ]);
        return;
    }

    $db->query("DELETE FROM products WHERE id = :id");
    $db->bind(':id', $id);
    $db->execute();

    echo json_encode([
        'success' => true,
        'message' => 'Xóa sản phẩm thành công'
    ]);
}

/**
 * Bật/tắt sản phẩm hot
 */
function toggleHot($db) {
    $id = $_POST['id'] ?? '';
    $is_hot = isset($_POST['is_hot']) ? (int)$_POST['is_hot'] : 0;

    if (empty($id)) {
        echo json_encode([
            'success' => false,
            'message' => 'ID không hợp lệ'
        ]);
        return;
    }

    $db->query("UPDATE products SET is_hot = :is_hot, updated_at = NOW() WHERE id = :id");
    $db->bind(':is_hot', $is_hot);
    $db->bind(':id', $id);
    $db->execute();

    echo json_encode([
        'success' => true,
        'message' => $is_hot ? 'Đã đặt sản phẩm Hot' : 'Đã bỏ sản phẩm Hot'
    ]);
}

/**
 * Quick update single field
 */
function quickUpdateField($db) {
    $id = $_POST['id'] ?? '';
    $field = $_POST['field'] ?? '';
    $value = $_POST['value'] ?? null;

    // Validate field name to prevent SQL injection
    $allowedFields = ['category_id', 'purchase_type_id'];
    
    if (empty($id) || empty($field) || !in_array($field, $allowedFields)) {
        echo json_encode([
            'success' => false,
            'message' => 'Dữ liệu không hợp lệ'
        ]);
        return;
    }

    // Allow NULL for empty values
    if ($value === '') {
        $value = null;
    }

    $db->query("UPDATE products SET $field = :value, updated_at = NOW() WHERE id = :id");
    $db->bind(':value', $value);
    $db->bind(':id', $id);
    $db->execute();

    echo json_encode([
        'success' => true,
        'message' => 'Cập nhật thành công'
    ]);
}

/**
 * Update product categories (multi-select)
 */
function updateProductCategories($db) {
    $id = $_POST['id'] ?? '';
    $category_ids = isset($_POST['category_ids']) ? json_decode($_POST['category_ids'], true) : [];

    if (empty($id)) {
        echo json_encode([
            'success' => false,
            'message' => 'ID sản phẩm không hợp lệ'
        ]);
        return;
    }

    // Delete old categories
    $db->query("DELETE FROM product_categories WHERE product_id = :product_id");
    $db->bind(':product_id', $id);
    $db->execute();

    // Insert new categories
    if (!empty($category_ids)) {
        foreach ($category_ids as $catId) {
            $db->query("INSERT IGNORE INTO product_categories (product_id, category_id) VALUES (:product_id, :category_id)");
            $db->bind(':product_id', $id);
            $db->bind(':category_id', (int)$catId);
            $db->execute();
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Cập nhật danh mục thành công'
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
