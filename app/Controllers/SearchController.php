<?php
/**
 * SEARCH CONTROLLER - Tìm kiếm nhanh cho header
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

try {
    $q = trim($_GET['q'] ?? '');
    if (mb_strlen($q) < 2) {
        echo json_encode([
            'success' => true,
            'items' => []
        ]);
        exit();
    }

    $db = new Database();
    $db->query("SELECT id, name, price, sale_price, image
               FROM products
               WHERE status = 'active'
                 AND name LIKE :keyword
               ORDER BY created_at DESC
               LIMIT 6");
    $db->bind(':keyword', '%' . $q . '%');

    $items = $db->fetchAll();

    echo json_encode([
        'success' => true,
        'items' => $items
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi tìm kiếm'
    ]);
}
