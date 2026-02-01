<?php
/**
 * PROFILE PAGE - Thông tin tài khoản
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: /steamweb/public/index.php?page=login');
    exit();
}

// Load config and database
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

$userId = $_SESSION['user_id'];
$db = new Database();

// Get user info
$db->query("SELECT u.*, up.vip_level, up.total_deposit, up.monthly_deposit, w.balance 
            FROM users u 
            LEFT JOIN user_profiles up ON u.id = up.user_id 
            LEFT JOIN wallets w ON u.id = w.user_id 
            WHERE u.id = :user_id");
$db->bind(':user_id', $userId);
$user = $db->fetch();

if (!$user) {
    header('Location: /steamweb/public/index.php?page=login');
    exit();
}

// Get recent orders count
$db->query("SELECT COUNT(*) as total FROM orders WHERE user_id = :user_id");
$db->bind(':user_id', $userId);
$ordersCount = $db->fetch();

// Orders pagination (max 5 orders per page)
$ordersPerPage = 5;
$ordersPage = isset($_GET['orders_page']) ? max(1, (int)$_GET['orders_page']) : 1;
$totalOrders = (int)($ordersCount['total'] ?? 0);
$totalOrderPages = (int)ceil($totalOrders / $ordersPerPage);
$ordersOffset = ($ordersPage - 1) * $ordersPerPage;

// Get orders for current page
$db->query("SELECT id, total_amount, status, payment_method, created_at
            FROM orders
            WHERE user_id = :user_id
            ORDER BY created_at DESC, id DESC
            LIMIT :limit OFFSET :offset");
$db->bind(':user_id', $userId);
$db->bind(':limit', $ordersPerPage, PDO::PARAM_INT);
$db->bind(':offset', $ordersOffset, PDO::PARAM_INT);
$ordersPageRows = $db->fetchAll();

$orders = [];
$orderIds = [];
foreach ($ordersPageRows as $row) {
    $orders[$row['id']] = [
        'id' => $row['id'],
        'total_amount' => $row['total_amount'],
        'status' => $row['status'],
        'payment_method' => $row['payment_method'],
        'created_at' => $row['created_at'],
        'items' => []
    ];
    $orderIds[] = (int)$row['id'];
}

// Get items for orders on current page
if (!empty($orderIds)) {
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $db->query("SELECT oi.order_id, oi.quantity, oi.price, p.name, p.image, p.received_content, p.content
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id IN ($placeholders)
                ORDER BY oi.order_id DESC");
    $index = 1;
    foreach ($orderIds as $id) {
        $db->bind($index, $id, PDO::PARAM_INT);
        $index++;
    }
    $orderItemsRows = $db->fetchAll();

    foreach ($orderItemsRows as $row) {
        $contentValue = $row['received_content'] ?: $row['content'];
        $orders[$row['order_id']]['items'][] = [
            'name' => $row['name'],
            'image' => $row['image'],
            'price' => $row['price'],
            'quantity' => $row['quantity'],
            'content' => $contentValue
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin tài khoản - GAMES STORE</title>
    <!-- CSS Components -->
    <link rel="stylesheet" href="../../public/assets/css/base.css">
    <link rel="stylesheet" href="../../public/assets/css/header.css">
    <link rel="stylesheet" href="../../public/assets/css/footer.css">
    <link rel="stylesheet" href="../../public/assets/css/profile.css">
    <!-- SVG Icons -->
    <?php include '../../public/assets/svg/icons.svg'; ?>
</head>
<body>
    <!-- Header Layout Include -->
    <?php include '../partials/header-layout.php'; ?>

    <!-- Main Content -->
    <main class="profile-container">
        <div class="profile-wrapper">
            <!-- Sidebar Menu -->
            <aside class="profile-sidebar">
                <div class="sidebar-user">
                    <div class="user-avatar">
                        <?php if ($user['avatar_url']): ?>
                            <img src="<?php echo htmlspecialchars($user['avatar_url']); ?>" alt="Avatar">
                        <?php else: ?>
                            <svg width="60" height="60" viewBox="0 0 24 24">
                                <use xlink:href="#icon-user"></use>
                            </svg>
                        <?php endif; ?>
                    </div>
                    <div class="user-info">
                        <h3><?php echo htmlspecialchars($user['username']); ?></h3>
                        <p class="user-email"><?php echo htmlspecialchars($user['email']); ?></p>
                        <span class="vip-badge">VIP Level <?php echo $user['vip_level'] ?? 0; ?></span>
                    </div>
                </div>
                <nav class="sidebar-nav">
                    <a href="#info" class="nav-item active" data-tab="info">
                        <svg width="20" height="20" viewBox="0 0 24 24">
                            <use xlink:href="#icon-user"></use>
                        </svg>
                        Thông tin cá nhân
                    </a>
                    <a href="#password" class="nav-item" data-tab="password">
                        <svg width="20" height="20" viewBox="0 0 24 24">
                            <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm9 14H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z" fill="currentColor"/>
                        </svg>
                        Đổi mật khẩu
                    </a>
                    <a href="#wallet" class="nav-item" data-tab="wallet">
                        <svg width="20" height="20" viewBox="0 0 24 24">
                            <path d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z" fill="currentColor"/>
                        </svg>
                        Ví của tôi
                    </a>
                    <a href="#orders" class="nav-item" data-tab="orders">
                        <svg width="20" height="20" viewBox="0 0 24 24">
                            <use xlink:href="#icon-cart"></use>
                        </svg>
                        Lịch Sử Đơn Hàng
                    </a>
                </nav>
            </aside>

            <!-- Main Content Area -->
            <div class="profile-content">
                <!-- Tab: Thông tin cá nhân -->
                <div id="info" class="tab-pane active">
                    <div class="content-header">
                        <h2>Thông tin cá nhân</h2>
                        <button class="btn-edit" id="btnEdit">
                            <svg width="18" height="18" viewBox="0 0 24 24">
                                <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z" fill="currentColor"/>
                            </svg>
                            Chỉnh sửa
                        </button>
                    </div>

                    <form id="profileForm" class="profile-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Tên đăng nhập</label>
                                <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Họ và tên</label>
                                <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label>Số điện thoại</label>
                                <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" disabled>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Avatar URL</label>
                            <input type="text" name="avatar_url" value="<?php echo htmlspecialchars($user['avatar_url'] ?? ''); ?>" disabled>
                        </div>

                        <div class="form-actions" style="display: none;">
                            <button type="submit" class="btn-primary">Lưu thay đổi</button>
                            <button type="button" class="btn-cancel" id="btnCancel">Hủy</button>
                        </div>

                        <div class="message" id="profileMessage"></div>
                    </form>

                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z" fill="currentColor"/>
                                </svg>
                            </div>
                            <div class="stat-info">
                                <h4>VIP Level</h4>
                                <p><?php echo $user['vip_level'] ?? 0; ?></p>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24">
                                    <path d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z" fill="currentColor"/>
                                </svg>
                            </div>
                            <div class="stat-info">
                                <h4>Số dư ví</h4>
                                <p><?php echo number_format($user['balance'] ?? 0, 0, ',', '.'); ?>đ</p>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24">
                                    <use xlink:href="#icon-cart"></use>
                                </svg>
                            </div>
                            <div class="stat-info">
                                <h4>Đơn hàng</h4>
                                <p><?php echo $ordersCount['total'] ?? 0; ?></p>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24">
                                    <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z" fill="currentColor"/>
                                </svg>
                            </div>
                            <div class="stat-info">
                                <h4>Tổng nạp</h4>
                                <p><?php echo number_format($user['total_deposit'] ?? 0, 0, ',', '.'); ?>đ</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Đổi mật khẩu -->
                <div id="password" class="tab-pane">
                    <div class="content-header">
                        <h2>Đổi mật khẩu</h2>
                    </div>

                    <form id="passwordForm" class="profile-form">
                        <div class="form-group">
                            <label>Mật khẩu hiện tại</label>
                            <input type="password" name="current_password" required>
                        </div>

                        <div class="form-group">
                            <label>Mật khẩu mới</label>
                            <input type="password" name="new_password" required>
                        </div>

                        <div class="form-group">
                            <label>Xác nhận mật khẩu mới</label>
                            <input type="password" name="confirm_password" required>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-primary">Đổi mật khẩu</button>
                        </div>

                        <div class="message" id="passwordMessage"></div>
                    </form>
                </div>

                <!-- Tab: Ví của tôi -->
                <div id="wallet" class="tab-pane">
                    <div class="content-header">
                        <h2>Ví của tôi</h2>
                        <button class="btn-primary">
                            <svg width="18" height="18" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 11h-4v4h-2v-4H7v-2h4V7h2v4h4v2z" fill="currentColor"/>
                            </svg>
                            Nạp tiền
                        </button>
                    </div>

                    <div class="wallet-balance">
                        <h3>Số dư hiện tại</h3>
                        <p class="balance-amount"><?php echo number_format($user['balance'] ?? 0, 0, ',', '.'); ?>đ</p>
                    </div>

                    <h3 style="margin-top: 30px;">Lịch sử giao dịch</h3>
                    <div class="transaction-list">
                        <p class="empty-state">Chưa có giao dịch nào</p>
                    </div>
                </div>

                <!-- Tab: Đơn hàng -->
                <div id="orders" class="tab-pane">
                    <div class="content-header">
                        <h2>Đơn hàng của tôi</h2>
                    </div>

                    <div class="orders-list">
                        <?php if (empty($orders)): ?>
                            <p class="empty-state">Chưa có đơn hàng nào</p>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <?php
                                    $statusText = 'Đang xử lý';
                                    $statusClass = 'status-pending';
                                    if ($order['status'] === 'paid') {
                                        $statusText = 'Đã thanh toán';
                                        $statusClass = 'status-paid';
                                    } elseif ($order['status'] === 'cancelled') {
                                        $statusText = 'Đã hủy';
                                        $statusClass = 'status-cancelled';
                                    } elseif ($order['status'] === 'refunded') {
                                        $statusText = 'Hoàn tiền';
                                        $statusClass = 'status-refunded';
                                    }
                                ?>
                                <div class="order-card">
                                    <div class="order-header">
                                        <div>
                                            <div class="order-id">Đơn #<?php echo $order['id']; ?></div>
                                            <div class="order-time"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></div>
                                        </div>
                                        <div class="order-status <?php echo $statusClass; ?>"><?php echo $statusText; ?></div>
                                    </div>
                                    <div class="order-items">
                                        <?php foreach ($order['items'] as $item): ?>
                                            <div class="order-item">
                                                <div class="order-item-image">
                                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                                </div>
                                                <div class="order-item-info">
                                                    <div class="order-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                                    <div class="order-item-meta">
                                                        <?php echo number_format($item['price'], 0, ',', '.'); ?>đ x <?php echo (int)$item['quantity']; ?>
                                                    </div>
                                                    <button class="order-item-btn" type="button"
                                                        data-name="<?php echo htmlspecialchars($item['name']); ?>"
                                                        data-content-b64="<?php echo htmlspecialchars(base64_encode($item['content'] ?? '')); ?>">
                                                        Xem nội dung sản phẩm
                                                    </button>
                                                </div>
                                                <div class="order-item-total">
                                                    <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?>đ
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="order-footer">
                                        <div class="order-method">Thanh toán: <?php echo htmlspecialchars($order['payment_method']); ?></div>
                                        <div class="order-total">Tổng: <?php echo number_format($order['total_amount'], 0, ',', '.'); ?>đ</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <?php if ($totalOrderPages > 1): ?>
                                <div class="orders-pagination">
                                    <?php for ($page = 1; $page <= $totalOrderPages; $page++): ?>
                                        <a class="orders-page-link <?php echo $page === $ordersPage ? 'active' : ''; ?>"
                                           href="?orders_page=<?php echo $page; ?>#orders">
                                            <?php echo $page; ?>
                                        </a>
                                    <?php endfor; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Product Content Modal -->
    <div id="productContentModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 9999; align-items: center; justify-content: center;">
        <div class="modal-fade-in" style="background: linear-gradient(135deg, #1a1f2e 0%, #0f1419 100%); border-radius: 14px; padding: 28px; max-width: 560px; width: 92%; box-shadow: 0 20px 60px rgba(0,0,0,0.5); border: 1px solid rgba(0,220,255,0.2); text-align: left;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h3 style="color: #00DCFF; font-size: 18px; margin: 0;" id="productContentTitle"></h3>
                <button type="button" onclick="closeProductContentModal()" style="background: none; border: none; color: rgba(255,255,255,0.7); font-size: 18px; cursor: pointer;">×</button>
            </div>
            <div id="productContentBody" style="color: rgba(255,255,255,0.85); font-size: 14px; line-height: 1.6; white-space: pre-wrap; max-height: 360px; overflow: auto;"></div>
            <button type="button" onclick="closeProductContentModal()" style="margin-top: 16px; width: 100%; padding: 12px; background: linear-gradient(135deg, #00DCFF, #0099FF); color: #0a0e1a; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; box-shadow: 0 8px 20px rgba(0,220,255,0.3);">
                Đóng
            </button>
        </div>
    </div>

    <!-- Footer Layout Include -->
    <?php include '../partials/footer-layout.php'; ?>

    <!-- JavaScript -->
    <script src="../../public/assets/js/profile.js"></script>
    <script>
        document.querySelectorAll('.order-item-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const name = btn.getAttribute('data-name') || '';
                const contentB64 = btn.getAttribute('data-content-b64') || '';
                let content = '';
                if (contentB64) {
                    try {
                        const binary = atob(contentB64);
                        if (window.TextDecoder) {
                            const bytes = Uint8Array.from(binary, c => c.charCodeAt(0));
                            content = new TextDecoder('utf-8').decode(bytes);
                        } else {
                            content = decodeURIComponent(escape(binary));
                        }
                    } catch (e) {
                        content = '';
                    }
                }
                document.getElementById('productContentTitle').textContent = name;
                document.getElementById('productContentBody').innerHTML = content ? content : 'Chưa có nội dung cho sản phẩm này.';
                document.getElementById('productContentModal').style.display = 'flex';
            });
        });

        function closeProductContentModal() {
            document.getElementById('productContentModal').style.display = 'none';
        }
    </script>
</body>
</html>
