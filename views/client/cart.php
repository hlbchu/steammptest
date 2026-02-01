<?php
/**
 * CART PAGE - Giỏ hàng
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$userId = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? '';
$userRole = $_SESSION['role'] ?? 'user';

$db = new Database();
$cartItems = [];
$cartTotal = 0;

// Get cart items - either from database (logged in) or session (guest)
if ($isLoggedIn && $userId) {
    $db->query("SELECT c.id, c.quantity, p.id as product_id, p.name, p.price, p.image, p.category_id
               FROM cart c
               JOIN products p ON c.product_id = p.id
               WHERE c.user_id = :user_id");
    $db->bind(':user_id', $userId);
    $cartItems = $db->fetchAll();

    // Calculate total
    foreach ($cartItems as $item) {
        $cartTotal += $item['price'] * $item['quantity'];
    }
} else if (isset($_SESSION['guest_cart']) && !empty($_SESSION['guest_cart'])) {
    // Guest cart from session
    foreach ($_SESSION['guest_cart'] as $productId => $quantity) {
        $db->query("SELECT id, name, price, image, category_id FROM products WHERE id = :product_id AND status = 'active'");
        $db->bind(':product_id', $productId);
        $product = $db->fetch();
        
        if ($product) {
            $product['quantity'] = $quantity;
            $cartItems[] = $product;
            $cartTotal += $product['price'] * $quantity;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ hàng - GAMES STORE</title>
    <style>
        .success-check-anim {
            animation: checkPop 0.7s ease-out both;
            transform-origin: center;
        }
        @keyframes checkPop {
            0% { transform: scale(0.3); opacity: 0; }
            60% { transform: scale(1.15); opacity: 1; }
            100% { transform: scale(1); opacity: 1; }
        }
        .modal-fade-in {
            animation: modalFade 0.25s ease-out both;
        }
        @keyframes modalFade {
            0% { opacity: 0; transform: translateY(10px); }
            100% { opacity: 1; transform: translateY(0); }
        }
    </style>
    <!-- CSS Components -->
    <link rel="stylesheet" href="/steamweb/public/assets/css/base.css">
    <link rel="stylesheet" href="/steamweb/public/assets/css/header.css">
    <link rel="stylesheet" href="/steamweb/public/assets/css/footer.css">
    <link rel="stylesheet" href="/steamweb/public/assets/css/cart.css">
    <!-- SVG Icons -->
    <?php include __DIR__ . '/../../public/assets/svg/icons.svg'; ?>
</head>
<body>
    <!-- Header Layout Include -->
    <?php include __DIR__ . '/../partials/header-layout.php'; ?>

    <!-- Main Content -->
    <main class="cart-container">
        <div class="cart-wrapper">
            <!-- Cart Items -->
            <div class="cart-items-section">
                <div class="section-header">
                    <h2>Giỏ hàng của tôi</h2>
                    <div style="display: flex; gap: 15px; align-items: center;">
                        <span class="item-count"><?php echo count($cartItems); ?> sản phẩm</span>
                        <?php if (!empty($cartItems)): ?>
                        <button class="btn-clear-all" id="btnClearAll" style="color: #ff4757; font-size: 14px; text-decoration: underline; cursor: pointer; background: none; border: none;">
                            Xóa tất cả
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (empty($cartItems)): ?>
                <div class="empty-cart">
                    <svg width="80" height="80" viewBox="0 0 24 24">
                        <use xlink:href="#icon-cart"></use>
                    </svg>
                    <h3>Giỏ hàng của bạn trống</h3>
                    <p>Hãy thêm một số sản phẩm vào giỏ hàng của bạn</p>
                    <a href="index.php" class="btn-primary">Tiếp tục mua sắm</a>
                </div>
                <?php else: ?>
                <div class="cart-list">
                    <?php foreach ($cartItems as $item): ?>
                    <div class="cart-item" data-product-id="<?php echo $item['product_id']; ?>">
                        <div class="item-image">
                            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        </div>

                        <div class="item-details">
                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p class="category">
                                <?php
                                $db->query("SELECT name FROM categories WHERE id = :category_id");
                                $db->bind(':category_id', $item['category_id']);
                                $category = $db->fetch();
                                echo htmlspecialchars($category['name'] ?? 'Không xác định');
                                ?>
                            </p>
                        </div>

                        <div class="item-quantity">
                            <button class="qty-btn qty-decrease" data-product-id="<?php echo $item['product_id']; ?>">−</button>
                            <input type="number" class="qty-input" value="<?php echo $item['quantity']; ?>" min="1" max="100">
                            <button class="qty-btn qty-increase" data-product-id="<?php echo $item['product_id']; ?>">+</button>
                        </div>

                        <div class="item-price">
                            <p class="unit-price"><?php echo number_format($item['price'], 0, ',', '.'); ?>đ</p>
                            <p class="total-price"><?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?>đ</p>
                        </div>

                        <button class="btn-remove" data-product-id="<?php echo $item['product_id']; ?>">
                            <svg width="20" height="20" viewBox="0 0 24 24">
                                <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-9l-1 1H5v2h14V4z" fill="currentColor"/>
                            </svg>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Cart Summary -->
            <aside class="cart-summary">
                <div class="summary-card">
                    <h3>Tóm tắt đơn hàng</h3>

                    <div class="summary-row">
                        <span>Tổng tiền hàng:</span>
                        <span id="subtotal"><?php echo number_format($cartTotal, 0, ',', '.'); ?>đ</span>
                    </div>

                    <div class="summary-row">
                        <span>Phí vận chuyển:</span>
                        <span>Miễn phí</span>
                    </div>

                    <div class="summary-row">
                        <span>Giảm giá:</span>
                        <span id="discount">0đ</span>
                    </div>

                    <div class="summary-divider"></div>

                    <div class="summary-row total">
                        <span>Tổng cộng:</span>
                        <span id="total"><?php echo number_format($cartTotal, 0, ',', '.'); ?>đ</span>
                    </div>

                    <div class="promo-section">
                        <label>Mã khuyến mãi</label>
                        <div class="promo-input-group">
                            <input type="text" id="promoCode" placeholder="Nhập mã">
                            <button class="btn-apply" id="btnApplyPromo">Áp dụng</button>
                        </div>
                        <p id="promoMessage" class="promo-message"></p>
                    </div>

                    <?php if (!empty($cartItems)): ?>
                    <button class="btn-checkout" id="btnCheckout">
                        <?php if (!$isLoggedIn): ?>
                        Đăng nhập để thanh toán
                        <?php else: ?>
                        Tiến hành thanh toán
                        <?php endif; ?>
                    </button>
                    <?php endif; ?>

                    <button class="btn-continue" onclick="window.location.href='index.php'">Tiếp tục mua sắm</button>
                </div>

                <!-- Recommended Products -->
                <div class="recommended-section">
                    <h3>Sản phẩm gợi ý</h3>
                    <div class="recommended-list">
                        <!-- TODO: Load recommended products -->
                    </div>
                </div>
            </aside>
        </div>
    </main>

    <!-- Footer Layout Include -->
    <?php include __DIR__ . '/../partials/footer-layout.php'; ?>

    <!-- Confirmation Modal -->
    <div id="confirmModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: linear-gradient(135deg, #1a1f2e 0%, #0f1419 100%); border-radius: 16px; padding: 40px; max-width: 450px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.5); border: 1px solid rgba(0,220,255,0.2);">
            <div style="text-align: center; margin-bottom: 30px;">
                <svg width="60" height="60" viewBox="0 0 24 24" style="color: #00DCFF; margin-bottom: 20px;">
                    <path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                </svg>
                <h3 style="color: #00DCFF; font-size: 22px; margin-bottom: 10px;">Xác nhận thanh toán</h3>
                <p style="color: rgba(255,255,255,0.7); font-size: 14px;">Bạn có chắc chắn muốn thanh toán đơn hàng này?</p>
                <p style="color: #00DCFF; font-size: 24px; font-weight: bold; margin-top: 15px;" id="confirmTotal"></p>
            </div>
            <div style="display: flex; gap: 12px;">
                <button onclick="cancelCheckout()" style="flex: 1; padding: 14px; background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; transition: all 0.3s;">
                    Hủy
                </button>
                <button onclick="confirmCheckout()" style="flex: 1; padding: 14px; background: linear-gradient(135deg, #00DCFF, #0099FF); color: #0a0e1a; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; box-shadow: 0 8px 20px rgba(0,220,255,0.3); transition: all 0.3s;">
                    Xác nhận
                </button>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 9999; align-items: center; justify-content: center;">
        <div class="modal-fade-in" style="background: linear-gradient(135deg, #1a1f2e 0%, #0f1419 100%); border-radius: 16px; padding: 40px; max-width: 450px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.5); border: 1px solid rgba(0,220,255,0.2); text-align: center;">
            <svg class="success-check-anim" width="80" height="80" viewBox="0 0 24 24" style="color: #00ff88; margin-bottom: 20px;">
                <path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
            </svg>
            <h3 style="color: #00ff88; font-size: 24px; margin-bottom: 15px;">Thanh toán thành công!</h3>
            <p style="color: rgba(255,255,255,0.7); font-size: 14px; margin-bottom: 10px;">Đơn hàng của bạn đã được xử lý thành công</p>
            <p style="color: #00DCFF; font-size: 18px; font-weight: bold; margin-bottom: 25px;" id="successAmount"></p>
            <button onclick="closeSuccessModal()" style="width: 100%; padding: 14px; background: linear-gradient(135deg, #00DCFF, #0099FF); color: #0a0e1a; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; box-shadow: 0 8px 20px rgba(0,220,255,0.3);">
                Đóng
            </button>
        </div>
    </div>

    <!-- Insufficient Balance Modal -->
    <div id="insufficientModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 9999; align-items: center; justify-content: center;">
        <div class="modal-fade-in" style="background: linear-gradient(135deg, #1a1f2e 0%, #0f1419 100%); border-radius: 16px; padding: 40px; max-width: 450px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.5); border: 1px solid rgba(255,77,77,0.35); text-align: center;">
            <svg width="80" height="80" viewBox="0 0 24 24" style="color: #ff4d4d; margin-bottom: 20px;">
                <path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 14h-2v-2h2v2zm0-4h-2V6h2v6z"/>
            </svg>
            <h3 style="color: #ff4d4d; font-size: 22px; margin-bottom: 10px;">Không đủ số dư</h3>
            <p style="color: rgba(255,255,255,0.7); font-size: 14px; margin-bottom: 25px;">Số dư ví của bạn không đủ để thanh toán đơn hàng này.</p>
            <button onclick="closeInsufficientModal()" style="width: 100%; padding: 14px; background: rgba(255,255,255,0.08); color: white; border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600;">
                Đóng
            </button>
        </div>
    </div>

    <audio id="successSound" src="/steamweb/public/assets/sounds/som_matricula-464025.mp3" preload="auto"></audio>

    <!-- JavaScript -->
    <script src="/steamweb/public/assets/js/cart.js"></script>
    <script src="/steamweb/public/assets/js/cart-management.js"></script>
    <script>
        const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
        const cartTotal = <?php echo $cartTotal; ?>;

        // Clear all cart items
        document.getElementById('btnClearAll')?.addEventListener('click', function() {
            if (confirm('Bạn có chắc chắn muốn xóa tất cả sản phẩm khỏi giỏ hàng?')) {
                fetch('../app/Controllers/CartController.php?action=clear', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Lỗi: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Có lỗi xảy ra');
                });
            }
        });

        // Checkout confirmation
        document.getElementById('btnCheckout')?.addEventListener('click', function() {
            if (!isLoggedIn) {
                window.location.href = 'login.php';
                return;
            }
            
            // Show confirmation modal
            document.getElementById('confirmTotal').textContent = document.getElementById('total').textContent;
            document.getElementById('confirmModal').style.display = 'flex';
        });

        function cancelCheckout() {
            document.getElementById('confirmModal').style.display = 'none';
        }

        function confirmCheckout() {
            // Hide confirm modal
            document.getElementById('confirmModal').style.display = 'none';
            
            // Process checkout (simulate payment)
            fetch('../app/Controllers/CartController.php?action=checkout', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'total=' + cartTotal
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP error ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Show success modal
                    document.getElementById('successAmount').textContent = 'Đã thanh toán: ' + document.getElementById('total').textContent;
                    document.getElementById('successModal').style.display = 'flex';
                    const successSound = document.getElementById('successSound');
                    if (successSound) {
                        successSound.currentTime = 0;
                        successSound.play().catch(() => {});
                    }
                    
                    // Reload after 3 seconds
                    setTimeout(() => {
                        location.reload();
                    }, 3000);
                } else {
                    if (data.message && data.message.toLowerCase().includes('số dư')) {
                        document.getElementById('insufficientModal').style.display = 'flex';
                    } else {
                        alert('Lỗi thanh toán: ' + data.message);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi thanh toán: ' + error.message);
            });
        }

        function closeSuccessModal() {
            document.getElementById('successModal').style.display = 'none';
            location.reload();
        }

        function closeInsufficientModal() {
            document.getElementById('insufficientModal').style.display = 'none';
        }
    </script>
</body>
</html>
