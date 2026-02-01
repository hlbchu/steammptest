<?php
/**
 * ROOT INDEX - Web chính hiển thị giống Steam Store
 */
// Redirect to public router to handle ?page routes
$query = $_SERVER['QUERY_STRING'] ?? '';
$target = 'public/index.php' . ($query ? '?' . $query : '');
header('Location: ' . $target);
exit;

session_start();

// Load config
require_once 'config/config.php';
require_once 'app/Helpers/functions.php';

// Dữ liệu mẫu
$games_hot = [
    ['id' => 1, 'name' => 'ELDEN RING Game With Dark Souls', 'price' => '499.000₫', 'badge' => '-20%', 'image' => 'game1.jpg'],
    ['id' => 2, 'name' => 'ELDEN RING Game With Dark Souls', 'price' => '499.000₫', 'badge' => '-20%', 'image' => 'game2.jpg'],
    ['id' => 3, 'name' => 'Black Myth Wukong Game With Dark Souls', 'price' => '899.000₫', 'badge' => '-15%', 'image' => 'game3.jpg'],
    ['id' => 4, 'name' => 'ELDEN RING Game With Dark Souls', 'price' => '499.000₫', 'badge' => '-20%', 'image' => 'game4.jpg'],
    ['id' => 5, 'name' => 'ELDEN RING Game With Dark Souls', 'price' => '499.000₫', 'badge' => '-20%', 'image' => 'game5.jpg'],
    ['id' => 6, 'name' => 'Black Myth Wukong - ALL CUTE GAME', 'price' => '899.000₫', 'badge' => 'Hot', 'image' => 'game6.jpg'],
];

$recent_transactions = [
    ['name' => 'Elden Ring', 'time' => '12:30 hôm nay', 'amount' => '-499.000₫'],
    ['name' => 'Baldur\'s Gate 3', 'time' => 'Hôm qua', 'amount' => '-699.000₫'],
    ['name' => 'Nạp tiền', 'time' => '2 ngày trước', 'amount' => '+2.000.000₫'],
    ['name' => 'Mua game khác', 'time' => '1 tuần trước', 'amount' => '-299.000₫'],
    ['name' => 'Nạp tiền', 'time' => '2 tuần trước', 'amount' => '+5.000.000₫'],
    ['name' => 'Mua game bundle', 'time' => '3 tuần trước', 'amount' => '-1.599.000₫'],
];

$recent_deposits = [
    ['method' => 'Thẻ Credit', 'time' => 'Hôm nay', 'amount' => '+2.000.000₫'],
    ['method' => 'Ví Điện Tử', 'time' => 'Hôm qua', 'amount' => '+500.000₫'],
    ['method' => 'Bank Transfer', 'time' => '3 ngày trước', 'amount' => '+5.000.000₫'],
    ['method' => 'Thẻ Visa', 'time' => '1 tuần trước', 'amount' => '+1.000.000₫'],
    ['method' => 'Ví Điện Tử', 'time' => '2 tuần trước', 'amount' => '+3.000.000₫'],
    ['method' => 'Bank Transfer', 'time' => '3 tuần trước', 'amount' => '+2.500.000₫'],
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STEAM GAME STORE - Cửa hàng game chính thức</title>
    <!-- CSS Components -->
    <link rel="stylesheet" href="public/assets/css/base.css">
    <link rel="stylesheet" href="public/assets/css/header.css">
    <link rel="stylesheet" href="public/assets/css/footer.css">
    <link rel="stylesheet" href="public/assets/css/banner.css">
    <link rel="stylesheet" href="public/assets/css/section.css">
    <link rel="stylesheet" href="public/assets/css/game-card.css">
    <link rel="stylesheet" href="public/assets/css/top-recharge.css">
    <link rel="stylesheet" href="public/assets/css/game-review.css">
    <link rel="stylesheet" href="public/assets/css/guide.css">
    <!-- SVG Icons -->
    <?php include 'public/assets/svg/icons.svg'; ?>
</head>
<body>
    <!-- Header Layout Include -->
    <?php include 'views/partials/header-layout.php'; ?>

    <!-- Main Content -->
    <main class="container">
        
        <?php include 'views/components/banner.php'; ?>
        
        <?php include 'views/components/game-hot.php'; ?>
        
        <?php include 'views/components/top-recharge.php'; ?>
        
        <?php include 'views/components/game-review.php'; ?>
        
        <?php include 'views/components/guide.php'; ?>

    </main>

    <!-- Footer Layout Include -->
    <?php include 'views/partials/footer-layout.php'; ?>

    <!-- JavaScript riêng cho trang chủ -->
    <script src="public/assets/js/home.js"></script>

    <!-- Banner Slider Script -->
    <script>
        let currentSlide = 0;
        const slides = document.querySelectorAll('.banner-slide');
        const dots = document.querySelectorAll('.dot');
        const totalSlides = slides.length;

        function showSlide(index) {
            slides.forEach(slide => slide.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));
            slides[index].classList.add('active');
            dots[index].classList.add('active');
        }

        function nextSlide() {
            currentSlide = (currentSlide + 1) % totalSlides;
            showSlide(currentSlide);
        }

        // Click on dots to change slide
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                currentSlide = index;
                showSlide(currentSlide);
            });
        });

        // Auto slide every 5 seconds
        setInterval(nextSlide, 5000);
    </script>
    
    <!-- JavaScript Components -->
    <script src="public/assets/js/banner.js"></script>
    <script src="public/assets/js/game-card.js"></script>
    <script src="public/assets/js/top-recharge.js"></script>
    <script src="public/assets/js/game-review.js"></script>
    <script src="public/assets/js/navigation.js"></script>


</body>
</html>
