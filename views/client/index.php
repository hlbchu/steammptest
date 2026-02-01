<!-- ==============================================
     HOME PAGE - Trang chủ
     ============================================== -->

<?php 
// Lấy sản phẩm hot từ database
try {
    require_once __DIR__ . '/../../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->prepare("SELECT * FROM products WHERE status = 'active' AND is_hot = 1 ORDER BY updated_at DESC LIMIT 6");
    $stmt->execute();
    $games_hot = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Debug mode
    echo "<!-- Database Error: " . htmlspecialchars($e->getMessage()) . " -->";
    $games_hot = [];
}

// Không có game hot thì ẩn section
if (empty($games_hot)) {
    $games_hot = [];
}

$recent_transactions = [
    ['name' => 'Elden Ring', 'time' => '12:30 hôm nay', 'amount' => '-499.000₫'],
    ['name' => 'Baldur\'s Gate 3', 'time' => 'Hôm qua', 'amount' => '-699.000₫'],
    ['name' => 'Nạp tiền', 'time' => '2 ngày trước', 'amount' => '+2.000.000₫'],
];

$recent_deposits = [
    ['method' => 'Thẻ Credit', 'time' => 'Hôm nay', 'amount' => '+2.000.000₫'],
    ['method' => 'Ví Điện Tử', 'time' => 'Hôm qua', 'amount' => '+500.000₫'],
    ['method' => 'Bank Transfer', 'time' => '3 ngày trước', 'amount' => '+5.000.000₫'],
];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Steam Web - Trang chủ</title>
    <!-- CSS riêng cho trang chủ -->
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link rel="stylesheet" href="assets/css/home.css">
    <link rel="stylesheet" href="assets/css/game-review.css">
    <link rel="stylesheet" href="assets/css/game-card.css">
    <link rel="stylesheet" href="assets/css/top-recharge.css">
    <link rel="stylesheet" href="assets/css/section.css">
    <link rel="stylesheet" href="assets/css/banner.css">
    <link rel="stylesheet" href="../../public/assets/css/notification.css">
    <!-- SVG Icons -->
    <svg style="display: none;" xmlns="http://www.w3.org/2000/svg">
        <symbol id="icon-gamepad" viewBox="0 0 24 24"><path fill="currentColor" d="M6.5 6.5C5.12 6.5 4 7.62 4 9v6c0 1.38 1.12 2.5 2.5 2.5.83 0 1.54-.41 2-1.02.46.61 1.17 1.02 2 1.02.83 0 1.54-.41 2-1.02.46.61 1.17 1.02 2 1.02.83 0 1.54-.41 2-1.02.46.61 1.17 1.02 2 1.02 1.38 0 2.5-1.12 2.5-2.5V9c0-1.38-1.12-2.5-2.5-2.5-.83 0-1.54.41-2 1.02-.46-.61-1.17-1.02-2-1.02-.83 0-1.54.41-2 1.02-.46-.61-1.17-1.02-2-1.02-.83 0-1.54.41-2 1.02-.46-.61-1.17-1.02-2-1.02zm3.5 2c.83 0 1.5.67 1.5 1.5S10.33 11 9.5 11 8 10.33 8 9.5 8.67 8 9.5 8zm5 0c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5-1.5-.67-1.5-1.5.67-1.5 1.5-1.5z"/></symbol>
        <symbol id="icon-fire" viewBox="0 0 24 24"><path fill="currentColor" d="M13.5.67s.74 2.65.74 4.8c0 2.06-1.35 3.73-3.41 3.73-2.07 0-3.63-1.67-3.63-3.73l.03-.36C5.21 7.51 4 10.3 4 13c0 5.25 3.07 7.8 5.5 7.8 1.5 0 2.7-.8 3.57-1.64.48.34 1.84 1.64 3.57 1.64 2.43 0 5.5-2.55 5.5-7.8 0-5.25-3.07-7.8-5.5-7.8-1.5 0-2.7.8-3.57 1.64-.48-.34-1.84-1.64-3.57-1.64z"/></symbol>
        <symbol id="icon-star" viewBox="0 0 24 24"><path fill="currentColor" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2l-2.81 6.63L2 9.24l5.46 4.73L5.82 21z"/></symbol>
        <symbol id="icon-gift" viewBox="0 0 24 24"><path fill="currentColor" d="M20 6h-2.18c.11-.31.18-.65.18-1a2.996 2.996 0 0 0-5.362-1.646l-.328.592-.328-.592A2.991 2.991 0 0 0 6 5c0 .35.07.69.18 1H4c-1.1 0-1.99.9-1.99 2v11c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm-5-2c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zM4 6h5.08L8.46 4.65c-.39-.39-1.02-.39-1.41 0L4 8.24V6zm16 15H4V8h5v3h6V8h5v13z"/></symbol>
        <symbol id="icon-cart" viewBox="0 0 24 24"><path fill="currentColor" d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/></symbol>
        <symbol id="icon-clipboard" viewBox="0 0 24 24"><path fill="currentColor" d="M19 2h-4.18C14.4.84 13.3 0 12 0c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm7 18H5V4h14v16z"/></symbol>
        <symbol id="icon-creditcard" viewBox="0 0 24 24"><path fill="currentColor" d="M20 8H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm0 12H4V10h16v10zm-10-3h6v2h-6z"/></symbol>
        <symbol id="icon-news" viewBox="0 0 24 24"><path fill="currentColor" d="M5 3c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2H5zm0 2h14v10H5V5zm0 12h14v2H5v-2zm1-9h4v2H6V8zm0 4h12v2H6v-2zm0-6h12v2H6V6z"/></symbol>
    </svg>
</head>
<body>
    <!-- Header -->
    <?php include __DIR__ . '/../partials/header.php'; ?>

    <!-- Main Content -->
    <main class="container">
        
        <!-- Spacer for banner -->
        <div style="height: 15px;"></div>
        
        <!-- ==============================================
             BANNER SECTION (From Database)
             ============================================== -->
        <?php 
            // Initialize database for banner component
            if (!isset($db)) {
                require_once __DIR__ . '/../../config/database.php';
                $database = new Database();
                $db = $database->getConnection();
            }
            include __DIR__ . '/../components/banner.php'; 
        ?>

           <!-- ==============================================
               HOT GAMES SECTION
               ============================================== -->
           <?php if (!empty($games_hot)): ?>
           <section class="section">
            <div class="section-header">
                <h2 class="section-title">
                    <svg width="20" height="20" viewBox="0 0 24 24">
                        <use xlink:href="#icon-fire"></use>
                    </svg>
                    Game Hot
                </h2>
                <a href="?page=hot" class="view-more">Thêm ➜</a>
            </div>
            <div class="game-grid">
                <?php 
                // Hiển thị 6 game (2 hàng x 3 cột)
                for ($i = 0; $i < 6 && $i < count($games_hot); $i++): 
                    $game = $games_hot[$i];
                    // Phát hiện URL hoặc filename
                    $imageSrc = (filter_var($game['image'], FILTER_VALIDATE_URL)) 
                        ? $game['image'] 
                        : '../../public/assets/images/' . $game['image'];
                    
                    // Tính giá và giảm giá
                    $price = (float)$game['price'];
                    $salePrice = isset($game['sale_price']) && $game['sale_price'] > 0 ? (float)$game['sale_price'] : null;
                    $finalPrice = $salePrice ?? $price;
                    $discountPercent = ($salePrice && $price > 0) ? round((1 - ($salePrice / $price)) * 100) : 0;
                    $badge = $discountPercent > 0 ? "-{$discountPercent}%" : 'Hot';
                ?>
                    <div class="game-card" data-id="<?php echo $game['id']; ?>" data-detail-url="?page=product&id=<?php echo $game['id']; ?>">
                        <div class="game-image">
                            <img src="<?php echo htmlspecialchars($imageSrc); ?>" alt="<?php echo htmlspecialchars($game['name']); ?>" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22120%22%3E%3Crect fill=%22%23171a21%22 width=%22200%22 height=%22120%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 fill=%22%2366c0f4%22 font-family=%22Arial%22 font-size=%2214%22 text-anchor=%22middle%22 dy=%22.3em%22%3EGame Image%3C/text%3E%3C/svg%3E'">
                            <span class="game-badge badge-sale"><?php echo htmlspecialchars($badge); ?></span>
                        </div>
                        <div class="game-info">
                            <h3 class="game-name"><?php echo htmlspecialchars($game['name']); ?></h3>
                            <div class="game-price-row">
                                <?php if ($salePrice): ?>
                                <span class="game-price-old"><?php echo number_format($price, 0, ',', '.'); ?>₫</span>
                                <?php endif; ?>
                                <span class="game-price"><?php echo number_format($finalPrice, 0, ',', '.'); ?>₫</span>
                            </div>
                            <button class="btn-buy" data-add-to-cart data-product-id="<?php echo $game['id']; ?>" data-quantity="1">
                                <svg width="16" height="16" viewBox="0 0 24 24">
                                    <use xlink:href="#icon-cart"></use>
                                </svg>
                                Thêm giỏ hàng
                            </button>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </section>
        <?php endif; ?>

           <!-- ==============================================
               REVIEW GAME HOT SECTION (Component)
               ============================================== -->
           <?php include __DIR__ . '/../components/game-review.php'; ?>

           <!-- ==============================================
               TOP RECHARGE SECTION (Component)
               ============================================== -->
           <?php include __DIR__ . '/../components/top-recharge.php'; ?>

        <!-- ==============================================
             TWO COLUMN SECTION (Giao dịch & Nạp tiền)
             ============================================== -->
        <section class="two-column-section">
            <!-- Recent Transactions -->
            <div class="column-box">
                <h3 class="column-title">📋 GIAO DỊCH GẦN ĐÂY</h3>
                <ul class="transaction-list">
                    <?php foreach ($recent_transactions as $transaction): ?>
                        <li class="transaction-item">
                            <div class="transaction-info">
                                <span class="transaction-name"><?php echo htmlspecialchars($transaction['name']); ?></span>
                                <span class="transaction-time"><?php echo htmlspecialchars($transaction['time']); ?></span>
                            </div>
                            <span class="transaction-amount <?php echo strpos($transaction['amount'], '-') !== false ? 'negative' : 'positive'; ?>">
                                <?php echo htmlspecialchars($transaction['amount']); ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Recent Deposits -->
            <div class="column-box">
                <h3 class="column-title">
                    <svg width="16" height="16" viewBox="0 0 24 24">
                        <use xlink:href="#icon-creditcard"></use>
                    </svg>
                    NạP TIỀN GầN ĐÂY
                </h3>
                <ul class="transaction-list">
                    <?php foreach ($recent_deposits as $deposit): ?>
                        <li class="transaction-item">
                            <div class="transaction-info">
                                <span class="transaction-name"><?php echo htmlspecialchars($deposit['method']); ?></span>
                                <span class="transaction-time"><?php echo htmlspecialchars($deposit['time']); ?></span>
                            </div>
                            <span class="transaction-amount positive">
                                <?php echo htmlspecialchars($deposit['amount']); ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>

        <!-- ==============================================
             GUIDE SECTION
             ============================================== -->
        <section class="section">
            <div class="section-header">
                <h2 class="section-title">
                    <svg width="20" height="20" viewBox="0 0 24 24">
                        <use xlink:href="#icon-news"></use>
                    </svg>
                    TIN TỨC & HƯỚNG DẪN
                </h2>
            </div>
            <div class="guide-grid">
                <div class="guide-card">
                    <div class="guide-image">
                        <span class="guide-tag">Blog</span>
                    </div>
                    <div class="guide-content">
                        <span class="guide-date">1 tuần trước</span>
                        <h3 class="guide-title">Hướng dẫn tạo tài khoản và mua thẻ Steam.</h3>
                        <p class="guide-desc">Các bước tạo tài khoản Steam và cách mua thẻ nạp tiền một cách đơn giản...</p>
                    </div>
                </div>
                <div class="guide-card">
                    <div class="guide-image">
                        <span class="guide-tag">Blog</span>
                    </div>
                    <div class="guide-content">
                        <span class="guide-date">2 tuần trước</span>
                        <h3 class="guide-title">Hướng dẫn tùy chỉnh hồ sơ của bạn trên Steam.</h3>
                        <p class="guide-desc">Cách tùy chỉnh hồ sơ cá nhân với avatar, background và nhiều hơn nữa...</p>
                    </div>
                </div>
                <div class="guide-card">
                    <div class="guide-image">
                        <span class="guide-tag">Blog</span>
                    </div>
                    <div class="guide-content">
                        <span class="guide-date">3 tuần trước</span>
                        <h3 class="guide-title">Cách để bảo vệ tài khoản để tránh bị hack.</h3>
                        <p class="guide-desc">Những tips quan trọng để bảo vệ tài khoản Steam của bạn khỏi hacker...</p>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <!-- Footer -->
    <?php include __DIR__ . '/../partials/footer.php'; ?>

    <!-- JavaScript riêng cho trang chủ -->
    <script src="assets/js/add-to-cart.js"></script>
    <script src="assets/js/home.js"></script>

    <!-- Banner Slider Script -->
    
    <!-- Banner Slider Script -->
    <script src="assets/js/banner.js"></script>
    <!-- Sound Manager -->
    <script src="assets/js/sound-manager.js"></script>
    <!-- Add to Cart Script -->
    <script src="assets/js/add-to-cart.js"></script>
    <script src="assets/js/game-card.js"></script>

</body>
</html>
