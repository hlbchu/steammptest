<!-- ==============================================
     PRODUCT DETAIL PAGE
     ============================================== -->

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

$database = new Database(); // Khởi tạo đối tượng Database

if (!isset($product)) {
    $product = null;
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($id > 0) {
        $db = $database->getConnection();
        $stmt = $db->prepare("SELECT p.*, c.name AS category_name
                              FROM products p
                              LEFT JOIN categories c ON p.category_id = c.id
                              WHERE p.id = :id
                              LIMIT 1");
        $stmt->execute([':id' => $id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

if (!$product) {
    $product = [
        'id' => 0,
        'name' => 'Sản phẩm đang cập nhật',
        'price' => 0,
        'sale_price' => null,
        'badge' => null,
        'image' => null,
        'description' => 'Thông tin sản phẩm sẽ được cập nhật sớm.',
        'rating' => null,
        'category_name' => 'Đang cập nhật'
    ];
}

$price = (float)$product['price'];
$salePrice = $product['sale_price'] !== null ? (float)$product['sale_price'] : null;
$finalPrice = ($salePrice !== null && $salePrice > 0) ? $salePrice : $price;
$discountPercent = ($salePrice !== null && $price > 0) ? round((1 - ($salePrice / $price)) * 100) : 0;
// Phát hiện URL hoặc filename
$imageUrl = (filter_var($product['image'], FILTER_VALIDATE_URL)) 
    ? $product['image'] 
    : ($product['image'] ? 'assets/images/' . $product['image'] : 'assets/images/placeholder.jpg');
function normalize_gallery_image($value) {
    $value = trim($value);
    if ($value === '') {
        return null;
    }

    if (stripos($value, 'data:image') === 0) {
        return (stripos($value, 'base64,') !== false) ? $value : null;
    }

    if (preg_match('/^https?:\/\//i', $value)) {
        return $value;
    }

    if (preg_match('/^\/9j/i', $value)) {
        return 'data:image/jpeg;base64,' . $value;
    }

    if (preg_match('/^iVBOR/i', $value)) {
        return 'data:image/png;base64,' . $value;
    }

    if (preg_match('/^R0lGOD/i', $value)) {
        return 'data:image/gif;base64,' . $value;
    }

    return $value;
}

$galleryImages = [];
if (!empty($product['gallery'])) {
    $lines = preg_split('/[\r\n]+/', $product['gallery']);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }

        $parts = (stripos($line, 'data:image') === 0)
            ? [$line]
            : preg_split('/\s*,\s*/', $line);

        foreach ($parts as $part) {
            $normalized = normalize_gallery_image($part);
            if ($normalized !== null) {
                $galleryImages[] = $normalized;
            }
        }
    }
}
// Ensure main image is first in gallery
if (!empty($imageUrl) && !in_array($imageUrl, $galleryImages, true)) {
    array_unshift($galleryImages, $imageUrl);
}
$videoUrl = $product['video_url'] ?? null;
$videoEmbed = null;
if (!empty($videoUrl)) {
    if (preg_match('~youtu\.be/([\w-]+)~', $videoUrl, $m)) {
        $videoEmbed = 'https://www.youtube.com/embed/' . $m[1];
    } elseif (preg_match('~v=([\w-]+)~', $videoUrl, $m)) {
        $videoEmbed = 'https://www.youtube.com/embed/' . $m[1];
    } else {
        $videoEmbed = $videoUrl;
    }
}
$rating = $product['rating'] !== null ? $product['rating'] : 0;
$categoryName = $product['category_name'] ?? 'Đang cập nhật';
$isEmbedded = isset($_GET['embed']) && $_GET['embed'] === '1';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết sản phẩm - Steam Web</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/css/notification.css">
    <style>
        #mainGameImage {
            transition: opacity 0.5s ease-in-out !important;
        }
        .game-thumb {
            transition: all 0.3s ease !important;
        }
        .game-thumb.active {
            border: 3px solid #00a8ff !important;
            box-shadow: 0 0 12px rgba(0, 168, 255, 0.8), inset 0 0 8px rgba(0, 168, 255, 0.4) !important;
            transform: scale(1.05);
        }
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-secondary);
            font-size: 13px;
            margin: 2px 0 10px;
            position: relative;
            z-index: 5;
        }
        .breadcrumb span.link {
            color: var(--accent-color);
        }
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 25px;
        }
        .action-btn {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 14px 12px;
            border-radius: 12px;
            border: 1px solid transparent;
            font-weight: 700;
            font-size: 12px;
            letter-spacing: 0.6px;
            cursor: pointer;
            transition: all 0.25s ease;
            text-decoration: none;
            min-height: 72px;
        }
        .action-btn svg {
            width: 18px;
            height: 18px;
        }
        .action-btn-primary {
            background: linear-gradient(135deg, #00DCFF, #0099FF);
            color: #0b1020;
            box-shadow: 0 10px 25px rgba(0, 220, 255, 0.25);
        }
        .action-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 32px rgba(0, 220, 255, 0.35);
        }
        .action-btn-outline {
            background: rgba(20, 24, 40, 0.7);
            color: var(--text-primary);
            border-color: rgba(0, 220, 255, 0.35);
        }
        .action-btn-outline:hover {
            border-color: rgba(0, 220, 255, 0.8);
            box-shadow: 0 0 18px rgba(0, 220, 255, 0.25);
            transform: translateY(-2px);
        }
        .action-btn-wishlist.active {
            background: linear-gradient(135deg, #ff5f6d, #ffc371);
            color: #1b1f2e;
            border-color: transparent;
            box-shadow: 0 10px 25px rgba(255, 111, 97, 0.35);
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 100px 20px;
        }
        <?php if ($isEmbedded): ?>
        html, body {
            margin: 0;
            overflow: hidden;
        }
        .container {
            margin-top: 0;
            padding-top: 2px;
        }
        body::-webkit-scrollbar { width: 0; height: 0; }
        body { scrollbar-width: none; }
        <?php endif; ?>
    </style>
    <!-- SVG Icons -->
    <svg style="display: none;" xmlns="http://www.w3.org/2000/svg">
        <symbol id="icon-user" viewBox="0 0 24 24"><path fill="currentColor" d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></symbol>
        <symbol id="icon-star" viewBox="0 0 24 24"><path fill="currentColor" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2l-2.81 6.63L2 9.24l5.46 4.73L5.82 21z"/></symbol>
        <symbol id="icon-cart" viewBox="0 0 24 24"><path fill="currentColor" d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/></symbol>
        <symbol id="icon-news" viewBox="0 0 24 24"><path fill="currentColor" d="M5 3c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2H5zm0 2h14v10H5V5zm0 12h14v2H5v-2zm1-9h4v2H6V8zm0 4h12v2H6v-2zm0-6h12v2H6V6z"/></symbol>
    </svg>
</head>
<body>
    <?php if (!$isEmbedded) include __DIR__ . '/../partials/header.php'; ?>

    <main class="container">
        <nav class="breadcrumb">
            <span class="link">Trang chủ</span>
            <span>/</span>
            <span class="link">GameSteam</span>
            <span>/</span>
            <span><?php echo htmlspecialchars($product['name']); ?></span>
        </nav>
        <section style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 40px; margin: 28px 0;">
            <!-- Product Image - Lớn + Gallery -->
            <div style="background: var(--tertiary-bg); border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; text-align: center; min-height: 600px; position: sticky; top: 20px;">
                <img id="mainGameImage" src="<?php echo htmlspecialchars($imageUrl); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="max-width: 100%; max-height: 520px; object-fit: contain; border-radius: 4px;">

                <?php if (!empty($galleryImages) && count($galleryImages) > 1): ?>
                <div style="display: flex; gap: 8px; flex-wrap: wrap; justify-content: center; margin-top: 15px;">
                    <?php foreach ($galleryImages as $g): ?>
                        <img src="<?php echo htmlspecialchars($g); ?>" alt="thumb" class="game-thumb" style="width: 80px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid rgba(255,255,255,0.2); cursor: pointer;">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Product Info - Bên phải -->
            <div>
                <!-- Tiêu đề & Rating -->
                <h1 style="color: var(--accent-color); margin-bottom: 15px; font-size: 28px; line-height: 1.3;">
                    <?php echo htmlspecialchars($product['name']); ?>
                </h1>

                <!-- Rating & Reviews -->
                <div style="display: flex; gap: 15px; margin-bottom: 25px; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 5px;">
                        <?php for ($i = 0; $i < 5; $i++): ?>
                            <svg width="16" height="16" viewBox="0 0 24 24" style="color: <?php echo $i < round($rating) ? 'var(--warning)' : 'var(--text-secondary)'; ?>;">
                                <path fill="currentColor" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2l-2.81 6.63L2 9.24l5.46 4.73L5.82 21z"/>
                            </svg>
                        <?php endfor; ?>
                    </div>
                    <span style="color: var(--text-secondary); font-size: 14px;"><?php echo number_format($rating, 1); ?>/5 (1,808 đánh giá)</span>
                </div>

                <!-- Giá Section -->
                <div style="background: rgba(255, 102, 0, 0.1); border: 2px solid var(--warning); border-radius: 8px; padding: 20px; margin-bottom: 25px;">
                    <?php if ($salePrice && $discountPercent > 0): ?>
                        <div style="font-size: 12px; color: var(--text-secondary); margin-bottom: 8px;">GIÁ GỐC:</div>
                        <div style="font-size: 16px; text-decoration: line-through; color: var(--text-secondary); margin-bottom: 12px;">
                            <?php echo number_format($price, 0, ',', '.'); ?>₫
                        </div>
                        <div style="color: var(--warning); font-size: 36px; font-weight: bold; margin-bottom: 8px;">
                            <?php echo number_format($finalPrice, 0, ',', '.'); ?>₫
                        </div>
                        <div style="background: var(--danger); color: white; padding: 8px 12px; border-radius: 4px; display: inline-block; font-weight: bold;">
                            -<?php echo $discountPercent; ?>% GIẢM
                        </div>
                    <?php else: ?>
                        <div style="color: var(--warning); font-size: 36px; font-weight: bold;">
                            <?php echo number_format($finalPrice, 0, ',', '.'); ?>₫
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Game Info Box -->
                <div style="background: var(--tertiary-bg); border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; margin-bottom: 25px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 14px;">
                        <div>
                            <div style="color: var(--text-secondary); margin-bottom: 5px;">THỂ LOẠI:</div>
                            <div style="color: var(--accent-color); font-weight: 500;"><?php echo htmlspecialchars($categoryName); ?></div>
                        </div>
                        <div>
                            <div style="color: var(--text-secondary); margin-bottom: 5px;">KHO HÀNG:</div>
                            <div style="color: var(--success); font-weight: 500;">Còn hàng</div>
                        </div>
                        <div>
                            <div style="color: var(--text-secondary); margin-bottom: 5px;">NHÀ PHÁT HÀNH:</div>
                            <div style="color: var(--text-primary); font-weight: 500;">-</div>
                        </div>
                        <div>
                            <div style="color: var(--text-secondary); margin-bottom: 5px;">NỀN TẢNG:</div>
                            <div style="color: var(--text-primary); font-weight: 500;">PC</div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button class="action-btn action-btn-primary btn-add-to-cart" data-add-to-cart data-product-id="<?php echo (int)$product['id']; ?>" data-quantity="1">
                        <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="currentColor" d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
                        </svg>
                        MUA NGAY
                    </button>
                    <button class="action-btn action-btn-outline action-btn-wishlist" type="button" onclick="this.classList.toggle('active');">
                        <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="currentColor" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 6 4 4 6.5 4c1.74 0 3.41.81 4.5 2.09C12.09 4.81 13.76 4 15.5 4 18 4 20 6 20 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                        YÊU THÍCH
                    </button>
                    <button class="action-btn action-btn-outline" type="button">
                        <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="currentColor" d="M3 6h18v2H3V6zm0 4h18v2H3v-2zm0 4h12v2H3v-2zm0 4h12v2H3v-2z"/>
                        </svg>
                        ĐẶT HÀNG
                    </button>
                </div>
            </div>
        </section>

        <!-- Product Description -->
        <section style="background: var(--tertiary-bg); border: 1px solid var(--border-color); border-radius: 8px; padding: 40px; margin: 50px 0;">
            <h2 style="color: var(--accent-color); margin-bottom: 25px; font-size: 22px; border-bottom: 2px solid var(--accent-color); padding-bottom: 15px;">
                📝 MÔ TẢ SẢN PHẨM
            </h2>
            <div style="line-height: 1.8; color: var(--text-secondary); font-size: 15px;">
                <?php echo nl2br(htmlspecialchars($product['description'] ?? 'Đang cập nhật mô tả.')); ?>
            </div>
        </section>

        <!-- Product Content (Chi tiết hành trình, tính năng, hình ảnh...) -->
        <?php if (!empty($product['content'])): ?>
        <section style="background: var(--tertiary-bg); border: 1px solid var(--border-color); border-radius: 8px; padding: 40px; margin: 50px 0;">
            <h2 style="color: var(--accent-color); margin-bottom: 25px; font-size: 22px; border-bottom: 2px solid var(--accent-color); padding-bottom: 15px;">
                🎮 VỀ TRÒ CHƠI NÀY
            </h2>
            <div style="line-height: 1.8; color: var(--text-secondary); font-size: 15px;">
                <?php echo $product['content']; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Video Trailer -->
        <?php if (!empty($videoEmbed)): ?>
        <section style="background: var(--tertiary-bg); border: 1px solid var(--border-color); border-radius: 8px; padding: 30px; margin: 30px 0;">
            <h2 style="color: var(--accent-color); margin-bottom: 20px; font-size: 20px;">
                🎬 Video Trailer
            </h2>
            <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 8px;">
                <iframe src="<?php echo htmlspecialchars($videoEmbed); ?>" style="position: absolute; top:0; left:0; width:100%; height:100%; border:0;" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>
        </section>
        <?php endif; ?>

        <!-- Similar Products -->
        <section class="section">
            <div class="section-header">
                <h2 class="section-title">🎮 Sản phẩm tương tự</h2>
                <a href="?page=hot" class="view-more">Xem tất cả ➜</a>
            </div>
            <div class="game-grid">
                <?php 
                // Lấy sản phẩm tương tự (cùng category hoặc sản phẩm active)
                if ($product['category_id']) {
                    $stmt = $database->getConnection()->prepare("SELECT * FROM products WHERE status = 'active' AND category_id = :category_id AND id != :id ORDER BY rating DESC LIMIT 6");
                    $stmt->execute([':category_id' => $product['category_id'], ':id' => $product['id']]);
                } else {
                    $stmt = $database->getConnection()->prepare("SELECT * FROM products WHERE status = 'active' AND id != :id ORDER BY rating DESC LIMIT 6");
                    $stmt->execute([':id' => $product['id']]);
                }
                $similarProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($similarProducts)) {
                    // Fallback: Lấy sản phẩm hot bất kỳ
                    $stmt = $database->getConnection()->prepare("SELECT * FROM products WHERE status = 'active' AND id != :id ORDER BY created_at DESC LIMIT 6");
                    $stmt->execute([':id' => $product['id']]);
                    $similarProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
                ?>
                <?php foreach ($similarProducts as $similar): ?>
                    <?php
                    $simPrice = (float)$similar['price'];
                    $simSalePrice = $similar['sale_price'] ? (float)$similar['sale_price'] : null;
                    $simFinalPrice = $simSalePrice ?? $simPrice;
                    $simDiscount = ($simSalePrice && $simPrice > 0) ? round((1 - ($simSalePrice / $simPrice)) * 100) : 0;
                    $simImageUrl = (filter_var($similar['image'], FILTER_VALIDATE_URL)) 
                        ? $similar['image'] 
                        : '../../public/assets/images/' . $similar['image'];
                    ?>
                    <div class="game-card" data-id="<?php echo $similar['id']; ?>" data-detail-url="?page=product&id=<?php echo $similar['id']; ?>">
                        <div class="game-image">
                            <img src="<?php echo htmlspecialchars($simImageUrl); ?>" alt="<?php echo htmlspecialchars($similar['name']); ?>" onerror="this.style.display='none'; this.parentElement.style.background='rgba(0,0,0,0.3)'; this.parentElement.textContent='Game Image';" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php if ($simDiscount > 0): ?>
                            <span class="game-badge badge-sale" style="position: absolute; top: 10px; left: 10px;">-<?php echo $simDiscount; ?>%</span>
                            <?php endif; ?>
                        </div>
                        <div class="game-info">
                            <div class="game-name"><?php echo htmlspecialchars(substr($similar['name'], 0, 30)); ?></div>
                            <?php if ($simSalePrice): ?>
                                <div class="game-price-row" style="display: flex; gap: 8px; margin-bottom: 8px;">
                                    <span class="game-price-old" style="font-size: 12px; text-decoration: line-through; color: var(--text-secondary);"><?php echo number_format($simPrice, 0, ',', '.'); ?>₫</span>
                                    <span class="game-price"><?php echo number_format($simFinalPrice, 0, ',', '.'); ?>₫</span>
                                </div>
                            <?php else: ?>
                                <div class="game-price"><?php echo number_format($simFinalPrice, 0, ',', '.'); ?>₫</div>
                            <?php endif; ?>
                            <button class="btn-buy" data-add-to-cart data-product-id="<?php echo (int)$similar['id']; ?>" data-quantity="1" style="width: 100%; padding: 8px; background: var(--warning); color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; margin-top: 10px; font-size: 12px;">
                                Thêm giỏ hàng
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <?php if (!$isEmbedded) include __DIR__ . '/../partials/footer.php'; ?>
    
    <!-- Add to Cart Script -->
    <script src="assets/js/add-to-cart.js"></script>
    <!-- Game Card Navigation Script -->
    <script src="assets/js/game-card.js"></script>
    <script>
        // Similar Products Click Handler - Open in New Tab
        document.querySelectorAll('.section-title').forEach(function(title) {
            if (title.textContent.includes('Sản phẩm tương tự')) {
                const section = title.closest('.section');
                if (section) {
                    const gameCards = section.querySelectorAll('.game-card');
                    gameCards.forEach(function(card) {
                        card.addEventListener('click', function() {
                            const detailUrl = this.getAttribute('data-detail-url');
                            if (detailUrl) {
                                window.location.href = detailUrl;
                            }
                        });
                        // Add pointer cursor to indicate clickability
                        card.style.cursor = 'pointer';
                    });
                }
            }
        });

        // Gallery Thumbnail Click Handler
        document.querySelectorAll('.game-thumb').forEach(function (thumb, index) {
            thumb.addEventListener('click', function () {
                const mainImg = document.getElementById('mainGameImage');
                if (mainImg) {
                    // Fade out effect
                    mainImg.style.opacity = '0';
                    
                    setTimeout(function () {
                        mainImg.src = thumb.src;
                        // Fade in effect
                        mainImg.style.opacity = '1';
                    }, 250);
                    
                    // Update thumbnail highlight
                    updateThumbnailHighlight(index);
                    
                    // Reset auto-play counter when manually clicked
                    clearInterval(galleryAutoPlayInterval);
                    galleryAutoPlayIndex = index;
                    startGalleryAutoPlay();
                }
            });
        });

        // Update Thumbnail Highlight
        function updateThumbnailHighlight(activeIndex) {
            document.querySelectorAll('.game-thumb').forEach(function (thumb, index) {
                if (index === activeIndex) {
                    thumb.classList.add('active');
                } else {
                    thumb.classList.remove('active');
                }
            });
        }

        // Auto-play Gallery Carousel
        let galleryAutoPlayIndex = 0;
        let galleryAutoPlayInterval;

        function startGalleryAutoPlay() {
            const thumbs = document.querySelectorAll('.game-thumb');
            const mainImg = document.getElementById('mainGameImage');
            
            if (thumbs.length <= 1) return; // No auto-play needed for single image
            
            galleryAutoPlayInterval = setInterval(function () {
                galleryAutoPlayIndex = (galleryAutoPlayIndex + 1) % thumbs.length;
                const currentThumb = thumbs[galleryAutoPlayIndex];
                if (mainImg && currentThumb) {
                    // Fade out effect
                    mainImg.style.opacity = '0';
                    
                    setTimeout(function () {
                        mainImg.src = currentThumb.src;
                        // Fade in effect
                        mainImg.style.opacity = '1';
                    }, 250);
                    
                    // Update thumbnail highlight
                    updateThumbnailHighlight(galleryAutoPlayIndex);
                }
            }, 3000); // Change image every 3 seconds
        }

        // Start auto-play when page loads
        document.addEventListener('DOMContentLoaded', function () {
            // Highlight first thumbnail on load
            updateThumbnailHighlight(0);
            startGalleryAutoPlay();
        });
    </script>
</body>
</html>
