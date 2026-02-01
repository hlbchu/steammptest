<?php
/**
 * ADD TO CART - Xử lý thêm sản phẩm vào giỏ
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

$db = new Database();
$productId = $_POST['product_id'] ?? '';
$quantity = (int)($_POST['quantity'] ?? 1);

if (!$productId || $quantity < 1) {
    echo json_encode([
        'success' => false,
        'message' => 'Dữ liệu không hợp lệ'
    ]);
    exit();
}

// Check if product exists
$db->query("SELECT id, name, price FROM products WHERE id = :product_id AND status = 'active'");
$db->bind(':product_id', $productId);
$product = $db->fetch();

if (!$product) {
    echo json_encode([
        'success' => false,
        'message' => 'Sản phẩm không tồn tại'
    ]);
    exit();
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$userId = $_SESSION['user_id'] ?? null;

if ($isLoggedIn && $userId) {
    // Database cart for logged-in users
    // Check if product already in cart
    $db->query("SELECT id, quantity FROM cart WHERE user_id = :user_id AND product_id = :product_id");
    $db->bind(':user_id', $userId);
    $db->bind(':product_id', $productId);
    $cartItem = $db->fetch();

    if ($cartItem) {
        // Update quantity
        $newQuantity = $cartItem['quantity'] + $quantity;
        $db->query("UPDATE cart SET quantity = :quantity, updated_at = NOW() WHERE user_id = :user_id AND product_id = :product_id");
        $db->bind(':quantity', $newQuantity);
        $db->bind(':user_id', $userId);
        $db->bind(':product_id', $productId);
        $db->execute();
    } else {
        // Insert new cart item
        $db->query("INSERT INTO cart (user_id, product_id, quantity) VALUES (:user_id, :product_id, :quantity)");
        $db->bind(':user_id', $userId);
        $db->bind(':product_id', $productId);
        $db->bind(':quantity', $quantity);
        $db->execute();
    }

    // Get cart count
    $db->query("SELECT SUM(quantity) as total FROM cart WHERE user_id = :user_id");
    $db->bind(':user_id', $userId);
    $cartCount = $db->fetch();
    $count = $cartCount['total'] ?? 0;
} else {
    // Session-based cart for guests
    if (!isset($_SESSION['guest_cart'])) {
        $_SESSION['guest_cart'] = [];
    }

    if (isset($_SESSION['guest_cart'][$productId])) {
        $_SESSION['guest_cart'][$productId] += $quantity;
    } else {
        $_SESSION['guest_cart'][$productId] = $quantity;
    }

    $count = array_sum($_SESSION['guest_cart']);
}

echo json_encode([
    'success' => true,
    'message' => $product['name'] . ' đã được thêm vào giỏ hàng',
    'cartCount' => $count
]);
?>
