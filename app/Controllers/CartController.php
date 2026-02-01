<?php
/**
 * CART CONTROLLER - Xử lý giỏ hàng
 */
// Start output buffering to catch any errors
ob_start();

// Disable error display, log instead
ini_set('display_errors', 0);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON header first
header('Content-Type: application/json');

try {
    require_once '../../config/config.php';
    require_once '../../config/database.php';
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi hệ thống: ' . $e->getMessage()
    ]);
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    ob_end_clean();
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng đăng nhập'
    ]);
    exit();
}

$db = new Database();
$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add':
        addToCart($db, $userId);
        break;
    case 'update':
        updateCart($db, $userId);
        break;
    case 'remove':
        removeFromCart($db, $userId);
        break;
    case 'applyPromo':
        applyPromoCode($db, $userId);
        break;
    case 'clear':
        clearCart($db, $userId);
        break;
    case 'checkout':
        processCheckout($db, $userId);
        break;
    default:
        ob_end_clean();
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Action không hợp lệ'
        ]);
}

/**
 * Thêm sản phẩm vào giỏ hàng
 */
function addToCart($db, $userId) {
    $productId = $_POST['product_id'] ?? '';
    $quantity = (int)($_POST['quantity'] ?? 1);

    if (!$productId || $quantity < 1) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Dữ liệu không hợp lệ'
        ]);
        return;
    }

    // Check if product exists
    $db->query("SELECT id FROM products WHERE id = :product_id");
    $db->bind(':product_id', $productId);
    if (!$db->fetch()) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Sản phẩm không tồn tại'
        ]);
        return;
    }

    // Check if product already in cart
    $db->query("SELECT id, quantity FROM cart WHERE user_id = :user_id AND product_id = :product_id");
    $db->bind(':user_id', $userId);
    $db->bind(':product_id', $productId);
    $cartItem = $db->fetch();

    if ($cartItem) {
        // Update quantity
        $newQuantity = $cartItem['quantity'] + $quantity;
        $db->query("UPDATE cart SET quantity = :quantity WHERE user_id = :user_id AND product_id = :product_id");
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

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Thêm vào giỏ hàng thành công'
    ]);
}

/**
 * Cập nhật số lượng sản phẩm trong giỏ
 */
function updateCart($db, $userId) {
    $productId = $_POST['product_id'] ?? '';
    $quantity = (int)($_POST['quantity'] ?? 1);

    if (!$productId || $quantity < 1) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Dữ liệu không hợp lệ'
        ]);
        return;
    }

    $db->query("UPDATE cart SET quantity = :quantity WHERE user_id = :user_id AND product_id = :product_id");
    $db->bind(':quantity', $quantity);
    $db->bind(':user_id', $userId);
    $db->bind(':product_id', $productId);
    $db->execute();

    // Get updated cart info
    $cartInfo = getCartInfo($db, $userId);

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Cập nhật giỏ hàng thành công',
        'cartCount' => $cartInfo['count'],
        'itemCount' => $cartInfo['itemCount'],
        'cartTotal' => $cartInfo['total']
    ]);
}

/**
 * Xóa sản phẩm khỏi giỏ
 */
function removeFromCart($db, $userId) {
    $productId = $_POST['product_id'] ?? '';

    if (!$productId) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Dữ liệu không hợp lệ'
        ]);
        return;
    }

    $db->query("DELETE FROM cart WHERE user_id = :user_id AND product_id = :product_id");
    $db->bind(':user_id', $userId);
    $db->bind(':product_id', $productId);
    $db->execute();

    // Get updated cart info
    $cartInfo = getCartInfo($db, $userId);

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Xóa sản phẩm khỏi giỏ hàng',
        'cartCount' => $cartInfo['count'],
        'itemCount' => $cartInfo['itemCount'],
        'cartTotal' => $cartInfo['total']
    ]);
}

/**
 * Lấy thông tin giỏ hàng
 */
function getCartInfo($db, $userId) {
    // Get item count
    $db->query("SELECT SUM(quantity) as total_quantity, COUNT(*) as item_count, SUM(p.price * c.quantity) as total
               FROM cart c
               JOIN products p ON c.product_id = p.id
               WHERE c.user_id = :user_id");
    $db->bind(':user_id', $userId);
    $result = $db->fetch();

    return [
        'count' => (int)($result['total_quantity'] ?? 0),
        'itemCount' => (int)($result['item_count'] ?? 0),
        'total' => (float)($result['total'] ?? 0)
    ];
}

/**
 * Lấy tổng tiền giỏ hàng
 */
function getCartTotal($db, $userId) {
    $db->query("SELECT SUM(p.price * c.quantity) as total
               FROM cart c
               JOIN products p ON c.product_id = p.id
               WHERE c.user_id = :user_id");
    $db->bind(':user_id', $userId);
    $result = $db->fetch();
    
    return (float)($result['total'] ?? 0);
}

/**
 * Áp dụng mã khuyến mãi
 */
function applyPromoCode($db, $userId) {
    $promoCode = strtoupper(trim($_POST['promo_code'] ?? ''));

    if (!$promoCode) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Vui lòng nhập mã khuyến mãi'
        ]);
        return;
    }

    // Check if promo code exists and is valid
    $db->query("SELECT discount_percent, discount_amount, max_uses, used_count, start_date, end_date, minimum_order
               FROM promotion_codes 
               WHERE code = :code AND status = 'active'");
    $db->bind(':code', $promoCode);
    $promo = $db->fetch();

    if (!$promo) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Mã khuyến mãi không hợp lệ hoặc đã hết hạn'
        ]);
        return;
    }

    // Check usage limit
    if ($promo['used_count'] >= $promo['max_uses']) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Mã khuyến mãi đã hết lượt sử dụng'
        ]);
        return;
    }

    // Check date range
    $now = date('Y-m-d H:i:s');
    if ($now < $promo['start_date'] || $now > $promo['end_date']) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Mã khuyến mãi không còn hoặc chưa hợp lệ'
        ]);
        return;
    }

    // Check minimum order
    $cartTotal = getCartTotal($db, $userId);
    if ($cartTotal < $promo['minimum_order']) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Đơn hàng chưa đạt giá trị tối thiểu để áp dụng mã khuyến mãi'
        ]);
        return;
    }

    // Store promo code in session
    $_SESSION['promo_code'] = $promoCode;
    $_SESSION['promo_discount'] = $promo['discount_percent'] > 0 ? $promo['discount_percent'] : $promo['discount_amount'];

    $discountText = $promo['discount_percent'] > 0 ? $promo['discount_percent'] . '%' : $promo['discount_amount'] . 'đ';

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Áp dụng mã khuyến mãi thành công',
        'discount' => $discountText
    ]);
}

/**
 * Xóa toàn bộ giỏ hàng
 */
function clearCart($db, $userId) {
    $db->query("DELETE FROM cart WHERE user_id = :user_id");
    $db->bind(':user_id', $userId);
    $db->execute();

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Giỏ hàng đã được xóa'
    ]);
}

/**
 * Xử lý thanh toán
 */
function processCheckout($db, $userId) {
    try {
        $pdo = $db->getConnection();
        $pdo->beginTransaction();

        // Get cart items
        $db->query("SELECT c.product_id, c.quantity, p.price
                   FROM cart c
                   JOIN products p ON c.product_id = p.id
                   WHERE c.user_id = :user_id");
        $db->bind(':user_id', $userId);
        $cartItems = $db->fetchAll();

        if (empty($cartItems)) {
            $pdo->rollBack();
            ob_end_clean();
            echo json_encode([
                'success' => false,
                'message' => 'Giỏ hàng trống'
            ]);
            return;
        }

        $total = 0;
        foreach ($cartItems as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        if ($total <= 0) {
            $pdo->rollBack();
            ob_end_clean();
            echo json_encode([
                'success' => false,
                'message' => 'Số tiền không hợp lệ'
            ]);
            return;
        }

        // Get user's wallet balance (lock row)
        $db->query("SELECT balance FROM wallets WHERE user_id = :user_id FOR UPDATE");
        $db->bind(':user_id', $userId);
        $wallet = $db->fetch();

        if (!$wallet) {
            $wallet = ['balance' => 0];
        }

        // Check if user has enough balance
        if ($wallet['balance'] < $total) {
            $pdo->rollBack();
            ob_end_clean();
            echo json_encode([
                'success' => false,
                'message' => 'Số dư ví không đủ. Vui lòng nạp thêm tiền!'
            ]);
            return;
        }

        // Deduct money from wallet
        $newBalance = $wallet['balance'] - $total;
        $db->query("UPDATE wallets SET balance = :balance WHERE user_id = :user_id");
        $db->bind(':balance', $newBalance);
        $db->bind(':user_id', $userId);
        $db->execute();

        // Create order
        $db->query("INSERT INTO orders (user_id, total_amount, status, payment_method)
                   VALUES (:user_id, :total_amount, 'paid', 'wallet')");
        $db->bind(':user_id', $userId);
        $db->bind(':total_amount', $total);
        $db->execute();
        $orderId = $pdo->lastInsertId();

        // Insert order items
        foreach ($cartItems as $item) {
            $subtotal = $item['price'] * $item['quantity'];
            $db->query("INSERT INTO order_items (order_id, product_id, price, quantity, subtotal)
                       VALUES (:order_id, :product_id, :price, :quantity, :subtotal)");
            $db->bind(':order_id', $orderId);
            $db->bind(':product_id', $item['product_id']);
            $db->bind(':price', $item['price']);
            $db->bind(':quantity', $item['quantity']);
            $db->bind(':subtotal', $subtotal);
            $db->execute();
        }

        // Clear cart after successful checkout
        $db->query("DELETE FROM cart WHERE user_id = :user_id");
        $db->bind(':user_id', $userId);
        $db->execute();

        $pdo->commit();

        ob_end_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Thanh toán thành công',
            'newBalance' => $newBalance
        ]);
    } catch (Exception $e) {
        if ($db->getConnection()->inTransaction()) {
            $db->getConnection()->rollBack();
        }
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi: ' . $e->getMessage()
        ]);
    }
}
?>
