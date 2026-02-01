<!-- ==============================================
     GAME CONTENT PAGE - Nội dung chi tiết game
     ============================================== -->

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

$database = new Database();
$gameId = isset($_GET['id']) ? (int)$_GET['id'] : 2; // Default to id=2

// Fetch game data
$db = $database->getConnection();
$stmt = $db->prepare("SELECT * FROM products WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $gameId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    $product = [
        'id' => $gameId,
        'name' => 'Sản phẩm không tồn tại',
        'content' => 'Không tìm thấy nội dung cho sản phẩm này.',
        'description' => '',
        'gallery' => ''
    ];
}

// Parse gallery images
$galleryImages = [];
if (!empty($product['gallery'])) {
    $parts = preg_split('/[\r\n,]+/', $product['gallery']);
    $parts = array_map('trim', $parts);
    $parts = array_filter($parts, function ($v) { return $v !== ''; });
    $galleryImages = array_values($parts);
}

// Get main image
$imageUrl = (filter_var($product['image'], FILTER_VALIDATE_URL)) 
    ? $product['image'] 
    : ($product['image'] ? 'assets/images/' . $product['image'] : 'assets/images/placeholder.jpg');
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Nội dung game</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="../../public/assets/css/notification.css">
    <style>
        body {
            background: var(--primary-bg);
            color: var(--text-primary);
        }

        .game-content-container {
            max-width: 1200px;
            margin: 40px auto;
           
        }

        .game-content-header {
            background: linear-gradient(135deg, rgba(255, 102, 0, 0.1), rgba(0, 168, 255, 0.1));
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 40px;
            margin-bottom: 40px;
            text-align: center;
        }

        .game-content-header h1 {
            color: var(--accent-color);
            font-size: 48px;
            margin: 0 0 10px 0;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
        }

        .game-content-header p {
            color: var(--text-secondary);
            font-size: 16px;
            margin: 0;
        }

        .content-sections {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
            margin-bottom: 50px;
        }

        .content-section {
            background: var(--tertiary-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 35px;
            transition: all 0.3s ease;
        }

        .content-section:hover {
            border-color: var(--accent-color);
            box-shadow: 0 8px 24px rgba(0, 168, 255, 0.15);
        }

        .content-section h2 {
            color: var(--accent-color);
            font-size: 28px;
            margin: 0 0 20px 0;
            padding-bottom: 15px;
            border-bottom: 3px solid var(--accent-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .content-section h2::before {
            content: "▶";
            color: var(--warning);
        }

        .content-text {
            line-height: 1.9;
            color: var(--text-secondary);
            font-size: 16px;
            margin-bottom: 20px;
        }

        .content-text p {
            margin-bottom: 20px;
            text-align: justify;
        }

        /* Gallery Section */
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }

        .gallery-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .gallery-item:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 28px rgba(0, 168, 255, 0.25);
            border-color: var(--accent-color);
        }

        .gallery-item img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            display: block;
        }

        .gallery-item-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .gallery-item:hover .gallery-item-overlay {
            opacity: 1;
        }

        .gallery-item-overlay span {
            color: white;
            font-size: 14px;
            text-align: center;
        }

        /* Modal for gallery */
        .gallery-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.9);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .gallery-modal.active {
            display: flex;
        }

        .modal-content {
            position: relative;
            max-width: 90%;
            max-height: 90vh;
        }

        .modal-image {
            width: 100%;
            height: auto;
            max-height: 80vh;
            object-fit: contain;
            border-radius: 8px;
        }

        .modal-close {
            position: absolute;
            top: -40px;
            right: 0;
            background: var(--danger);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 24px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .modal-close:hover {
            background: var(--danger);
            transform: rotate(90deg);
        }

        .modal-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: none;
            padding: 15px 20px;
            font-size: 24px;
            cursor: pointer;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .modal-nav:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .modal-nav.prev {
            left: 20px;
        }

        .modal-nav.next {
            right: 20px;
        }

        /* Description Section */
        .description-box {
            background: rgba(255, 102, 0, 0.05);
            border-left: 4px solid var(--warning);
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 25px;
        }

        .description-box h3 {
            color: var(--warning);
            margin: 0 0 10px 0;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .description-box p {
            margin: 0;
            color: var(--text-secondary);
            line-height: 1.6;
        }

        /* Back Button */
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--tertiary-bg);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.3s ease;
            margin-bottom: 40px;
            cursor: pointer;
        }

        .back-button:hover {
            background: var(--accent-color);
            border-color: var(--accent-color);
            color: white;
            transform: translateX(-4px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .game-content-header {
                padding: 25px;
            }

            .game-content-header h1 {
                font-size: 32px;
            }

            .content-section {
                padding: 20px;
            }

            .gallery-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }

            .modal-nav {
                padding: 10px 15px;
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../partials/header.php'; ?>

    <main class="game-content-container">
        <!-- Back Button -->
        <a href="?page=product&id=<?php echo (int)$product['id']; ?>" class="back-button">
            ← Quay lại chi tiết sản phẩm
        </a>

        <!-- Header -->
        <div class="game-content-header">
            <h1><?php echo htmlspecialchars($product['name']); ?></h1>
            <p>Khám phá nội dung chi tiết về tựa game này</p>
        </div>

        <!-- Content Sections -->
        <div class="content-sections">
            <!-- Description Section -->
            <?php if (!empty($product['description'])): ?>
            <div class="content-section">
                <h2>Mô tả sản phẩm</h2>
                <div class="content-text">
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Main Content Section -->
            <?php if (!empty($product['content'])): ?>
            <div class="content-section">
                <h2>Về trò chơi này</h2>
                <div class="content-text">
                    <?php echo $product['content']; ?>
                </div>
            </div>
            <?php endif; ?>

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

    <script>
        let currentGalleryIndex = 0;
        const galleryImages = <?php echo json_encode($galleryImages); ?>;

        // Gallery Modal Functions
        function openGalleryModal(index) {
            currentGalleryIndex = index;
            const modal = document.getElementById('galleryModal');
            const modalImage = document.getElementById('modalImage');
            modalImage.src = galleryImages[currentGalleryIndex];
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeGalleryModal() {
            const modal = document.getElementById('galleryModal');
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        function nextGalleryImage() {
            currentGalleryIndex = (currentGalleryIndex + 1) % galleryImages.length;
            document.getElementById('modalImage').src = galleryImages[currentGalleryIndex];
        }

        function prevGalleryImage() {
            currentGalleryIndex = (currentGalleryIndex - 1 + galleryImages.length) % galleryImages.length;
            document.getElementById('modalImage').src = galleryImages[currentGalleryIndex];
        }

        // Gallery Item Click Handlers
        document.querySelectorAll('.gallery-item').forEach(function(item) {
            item.addEventListener('click', function() {
                const index = parseInt(this.dataset.galleryIndex);
                openGalleryModal(index);
            });
        });

        // Close modal on background click
        document.getElementById('galleryModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeGalleryModal();
            }
        });

        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            const modal = document.getElementById('galleryModal');
            if (modal.classList.contains('active')) {
                if (e.key === 'ArrowRight') nextGalleryImage();
                if (e.key === 'ArrowLeft') prevGalleryImage();
                if (e.key === 'Escape') closeGalleryModal();
            }
        });
    </script>
</body>
</html>
