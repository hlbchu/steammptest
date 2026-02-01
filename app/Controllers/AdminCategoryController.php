<?php
/**
 * ADMIN CATEGORY CONTROLLER - Xử lý CRUD danh mục (Admin)
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

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
        createCategory($db);
        break;
    case 'update':
        updateCategory($db);
        break;
    case 'delete':
        deleteCategory($db);
        break;
    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Action không hợp lệ'
        ]);
}

function createCategory($db) {
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $icon = trim($_POST['icon'] ?? '');
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

    if ($name === '') {
        echo json_encode([
            'success' => false,
            'message' => 'Tên danh mục không được để trống'
        ]);
        return;
    }

    if ($slug === '') {
        $slug = generateSlug($name);
    }

    if ($slug === '') {
        $slug = 'category-' . time();
    }

    $db->query("SELECT id FROM categories WHERE slug = :slug");
    $db->bind(':slug', $slug);
    if ($db->fetch()) {
        $slug = $slug . '-' . time();
    }

    if ($icon === '') {
        $icon = 'icon-gamepad';
    }

    $db->query("INSERT INTO categories (name, slug, icon, sort_order, is_active) VALUES (:name, :slug, :icon, :sort_order, :is_active)");
    $db->bind(':name', $name);
    $db->bind(':slug', $slug);
    $db->bind(':icon', $icon);
    $db->bind(':sort_order', $sort_order);
    $db->bind(':is_active', $is_active);
    $db->execute();
    clearCategoryCache();

    echo json_encode([
        'success' => true,
        'message' => 'Thêm danh mục thành công'
    ]);
}

function updateCategory($db) {
    $id = $_POST['id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $icon = trim($_POST['icon'] ?? '');
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

    if (empty($id) || $name === '') {
        echo json_encode([
            'success' => false,
            'message' => 'Dữ liệu không hợp lệ'
        ]);
        return;
    }

    if ($slug === '') {
        $slug = generateSlug($name);
    }

    if ($slug === '') {
        $slug = 'category-' . time();
    }

    $db->query("SELECT id FROM categories WHERE slug = :slug AND id != :id");
    $db->bind(':slug', $slug);
    $db->bind(':id', $id);
    if ($db->fetch()) {
        $slug = $slug . '-' . time();
    }

    if ($icon === '') {
        $icon = 'icon-gamepad';
    }

    $db->query("UPDATE categories SET name = :name, slug = :slug, icon = :icon, sort_order = :sort_order, is_active = :is_active WHERE id = :id");
    $db->bind(':id', $id);
    $db->bind(':name', $name);
    $db->bind(':slug', $slug);
    $db->bind(':icon', $icon);
    $db->bind(':sort_order', $sort_order);
    $db->bind(':is_active', $is_active);
    $db->execute();
    clearCategoryCache();

    echo json_encode([
        'success' => true,
        'message' => 'Cập nhật danh mục thành công'
    ]);
}

function deleteCategory($db) {
    $id = $_POST['id'] ?? '';

    if (empty($id)) {
        echo json_encode([
            'success' => false,
            'message' => 'ID không hợp lệ'
        ]);
        return;
    }

    $db->query("DELETE FROM categories WHERE id = :id");
    $db->bind(':id', $id);
    $db->execute();
    clearCategoryCache();

    echo json_encode([
        'success' => true,
        'message' => 'Xóa danh mục thành công'
    ]);
}

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

function clearCategoryCache() {
    if (function_exists('apcu_delete')) {
        apcu_delete('steamweb_categories_active_v1');
    }
}
