<!-- ==============================================
    GAME CONTENT PAGE VIEW - Nội dung chi tiết game
    ============================================== -->

<?php
    $rawDescription = trim((string)($product['description'] ?? ''));
    $rawContent = trim((string)($product['content'] ?? ''));
    $normalizedDescription = preg_replace('/\s+/', ' ', strip_tags($rawDescription));
    $normalizedContent = preg_replace('/\s+/', ' ', strip_tags($rawContent));
    $showDescription = $normalizedDescription !== '' && $normalizedDescription !== $normalizedContent;
    $showContent = $normalizedContent !== '';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Nội dung game</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link rel="stylesheet" href="assets/css/section.css">
    <link rel="stylesheet" href="assets/css/game-card.css">
    <link rel="stylesheet" href="assets/css/home.css">
    <link rel="stylesheet" href="assets/css/notification.css">
    <link rel="stylesheet" href="assets/css/game-content.css">
</head>
<body data-gallery-images='<?php echo htmlspecialchars(json_encode($galleryImages), ENT_QUOTES); ?>'>
    <?php include __DIR__ . '/../partials/header.php'; ?>

    <main class="game-content-container">
        <!-- Embedded Product Detail -->
        <div class="product-embed">
            <div class="product-embed-header">Chi tiết sản phẩm</div>
            <iframe id="productEmbedFrame" src="?page=product&id=<?php echo (int)$product['id']; ?>&embed=1" loading="lazy"></iframe>
        </div>

        <!-- Content Sections -->
        <div class="content-sections">
            <!-- Gallery Section -->
            <?php if (!empty($galleryImages)): ?>
            <div class="content-section">
                <h2>Bộ sưu tập hình ảnh</h2>
                <p style="color: var(--text-secondary); margin-bottom: 20px;">Nhấp vào hình ảnh để xem chi tiết</p>
                <div class="gallery-grid">
                    <?php foreach ($galleryImages as $index => $image): ?>
                    <div class="gallery-item" data-gallery-index="<?php echo $index; ?>">
                        <img src="<?php echo htmlspecialchars($image); ?>" alt="Gallery <?php echo $index + 1; ?>">
                        <div class="gallery-item-overlay">
                            <span>Nhấp để xem</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Game Info Box -->
            <div class="content-section">
                <h2>Thông tin chi tiết</h2>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="description-box">
                        <h3>Tên game</h3>
                        <p><?php echo htmlspecialchars($product['name']); ?></p>
                    </div>
                    <div class="description-box">
                        <h3>Trạng thái</h3>
                        <p><?php echo strtoupper($product['status'] ?? 'active'); ?></p>
                    </div>
                    <div class="description-box">
                        <h3>Giá</h3>
                        <p><?php echo number_format($product['price'], 0, ',', '.'); ?>₫</p>
                    </div>
                    <div class="description-box">
                        <h3>Đánh giá</h3>
                        <p>★★★★★ <?php echo $product['rating'] ?? 5; ?>/5</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../partials/footer.php'; ?>

    <!-- Gallery Modal -->
    <div class="gallery-modal" id="galleryModal">
        <button class="modal-close" onclick="closeGalleryModal()">×</button>
        <div class="modal-content">
            <img id="modalImage" src="" alt="Gallery" class="modal-image">
            <?php if (count($galleryImages) > 1): ?>
            <button class="modal-nav prev" onclick="prevGalleryImage()">‹</button>
            <button class="modal-nav next" onclick="nextGalleryImage()">›</button>
            <?php endif; ?>
        </div>
    </div>

    <script src="assets/js/game-content.js"></script>
</body>
</html>
