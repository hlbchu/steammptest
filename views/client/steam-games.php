<?php
/**
 * STEAM GAMES PAGE - Trang danh sách game Steam
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load config
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/functions.php';


$db = new Database();

function getActiveCategories($db) {
    $cacheKey = 'steamweb_categories_active_v1';
    $apcuAvailable = function_exists('apcu_fetch') && function_exists('apcu_store');

    if ($apcuAvailable) {
        $cached = apcu_fetch($cacheKey, $success);
        if ($success && is_array($cached)) {
            return $cached;
        }
    }

    $db->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
    $categories = $db->fetchAll();

    if ($apcuAvailable) {
        apcu_store($cacheKey, $categories, 300);
    }

    return $categories;
}

$filter = strtolower(trim($_GET['filter'] ?? ''));
$isHotFilter = $filter === 'hot';
$categoryId = (int)($_GET['category'] ?? 0);

$baseUrl = basename($_SERVER['PHP_SELF']);
$hotLink = $baseUrl . '?filter=hot';
$allLink = $baseUrl;

$categories = getActiveCategories($db);

$perPage = 12;
$currentPage = max(1, (int)($_GET['page'] ?? 1));

if ($isHotFilter) {
    $db->query("SELECT COUNT(*) as total FROM products WHERE status = 'active' AND (is_hot = 1 OR (sale_price IS NOT NULL AND sale_price > 0 AND sale_price < price))");
} elseif ($categoryId > 0) {
    $db->query("SELECT COUNT(*) as total FROM products WHERE status = 'active' AND category_id = :category_id");
    $db->bind(':category_id', $categoryId);
} else {
    $db->query("SELECT COUNT(*) as total FROM products WHERE status = 'active'");
}
$totalProducts = (int)($db->fetch()['total'] ?? 0);
$totalPages = max(1, (int)ceil($totalProducts / $perPage));

if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
}

$offset = ($currentPage - 1) * $perPage;

$listSql = "SELECT id, name, price, sale_price, badge, image, rating FROM products WHERE status = 'active'";
if ($isHotFilter) {
    $listSql .= " AND (is_hot = 1 OR (sale_price IS NOT NULL AND sale_price > 0 AND sale_price < price))";
} elseif ($categoryId > 0) {
    $listSql .= " AND category_id = :category_id";
}
$listSql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

$db->query($listSql);
if (!$isHotFilter && $categoryId > 0) {
    $db->bind(':category_id', $categoryId);
}
$db->bind(':limit', $perPage);
$db->bind(':offset', $offset);
$games = $db->fetchAll();

function resolveSteamGameImage($image) {
    if (!$image) {
        return '';
    }
    if (filter_var($image, FILTER_VALIDATE_URL)) {
        return $image;
    }
    if (strpos($image, '/') === 0) {
        return $image;
    }
    return '../../public/assets/images/' . ltrim($image, '/');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Steam - GAMES STORE</title>
    <!-- CSS Components -->
    <link rel="stylesheet" href="../../public/assets/css/base.css">
    <link rel="stylesheet" href="../../public/assets/css/header.css">
    <link rel="stylesheet" href="../../public/assets/css/footer.css">
    <link rel="stylesheet" href="../../public/assets/css/section.css">
    <link rel="stylesheet" href="../../public/assets/css/game-card.css">
    <!-- SVG Icons -->
    <?php include '../../public/assets/svg/icons.svg'; ?>
    
    <style>
        .steam-games-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 95px 20px;
        }

        .games-main-wrapper {
            display: flex;
            gap: 30px;
            min-height: calc(100vh - 400px);
        }

        .games-sidebar {
            width: 200px;
            flex-shrink: 0;
        }

        .sidebar-title {
            font-size: 1.2rem;
            color: var(--text-primary);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(0, 180, 216, 0.2);
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu li {
            margin-bottom: 12px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-secondary);
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            color: var(--accent-color);
            background: rgba(0, 180, 216, 0.1);
            padding-left: 16px;
        }

        .sidebar-menu svg {
            width: 14px;
            height: 14px;
        }

        .games-content {
            flex: 1;
            min-width: 0;
        }

        .games-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(0, 180, 216, 0.2);
        }

        .games-header h1 {
            font-size: 2.5rem;
            color: var(--text-primary);
            margin: 0;
        }

        .games-header p {
            font-size: 0.95rem;
            color: var(--text-secondary);
            margin: 5px 0 0 0;
        }

        .games-filters {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-btn {
            padding: 10px 20px;
            background: rgba(0, 180, 216, 0.1);
            border: 1px solid rgba(0, 180, 216, 0.3);
            color: var(--text-primary);
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: rgba(0, 180, 216, 0.3);
            border-color: var(--accent-color);
        }

        .filter-select {
            padding: 10px 15px;
            background: rgba(0, 180, 216, 0.05);
            border: 1px solid rgba(0, 180, 216, 0.2);
            color: var(--text-primary);
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .games-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 40px;
        }

        .game-card {
            display: flex;
            flex-direction: row;
            background: rgba(15, 17, 32, 0.7);
            border: 1px solid rgba(0, 180, 216, 0.15);
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s ease;
            height: 160px;
            gap: 12px;
        }

        .game-card:hover {
            background: rgba(15, 17, 32, 0.9);
            border-color: rgba(0, 180, 216, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 180, 216, 0.15);
        }

        .game-card .game-image {
            width: 130px;
            height: 160px;
            flex-shrink: 0;
            position: relative;
            overflow: hidden;
            border-radius: 0;
        }

        .game-card .game-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .game-card .game-badge {
            position: absolute;
            top: 6px;
            left: 6px;
            background: linear-gradient(135deg, #ff4444, #ff6600);
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.65rem;
            font-weight: bold;
            z-index: 2;
        }

        .game-card .game-info {
            flex: 1;
            padding: 12px 12px 12px 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-width: 0;
        }

        .game-card .game-info h3 {
            margin: 0 0 6px 0;
            font-size: 0.85rem;
            color: var(--text-primary);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.2;
        }

        .game-card-header {
            display: flex;
            gap: 6px;
            align-items: flex-start;
            margin-bottom: 5px;
        }

        .game-card-tag {
            display: inline-block;
            background: rgba(0, 180, 216, 0.2);
            border: 1px solid rgba(0, 180, 216, 0.4);
            color: var(--accent-color);
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.6rem;
            font-weight: bold;
            text-transform: uppercase;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .game-card .game-rating {
            display: flex;
            gap: 4px;
            align-items: center;
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin-bottom: 5px;
        }

        .game-card .stars {
            color: #ffd700;
            font-size: 0.8rem;
        }

        .game-card .rating-value {
            color: var(--text-secondary);
        }

        .game-card .game-price {
            margin-bottom: 6px;
        }

        .game-card .price {
            font-size: 0.95rem;
            color: var(--accent-color);
            font-weight: bold;
            text-shadow: 0 0 8px rgba(0, 180, 216, 0.4);
        }

        .game-card .buy-btn {
            align-self: flex-start;
            padding: 5px 12px;
            background: linear-gradient(135deg, var(--accent-color), #0088aa);
            border: none;
            color: white;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.7rem;
            display: flex;
            gap: 4px;
            align-items: center;
            transition: all 0.3s ease;
        }

        .game-card .buy-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 180, 216, 0.4);
        }



        .games-pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 40px;
        }

        .pagination-btn {
            padding: 10px 15px;
            background: rgba(0, 180, 216, 0.1);
            border: 1px solid rgba(0, 180, 216, 0.2);
            color: var(--text-primary);
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .pagination-btn:hover,
        .pagination-btn.active {
            background: var(--accent-color);
            border-color: var(--accent-color);
            color: white;
        }

        @media (max-width: 1200px) {
            .games-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .game-card {
                height: 150px;
            }

            .game-card .game-image {
                width: 120px;
                height: 150px;
            }
        }

        @media (max-width: 768px) {
            .steam-games-container {
                padding: 30px 15px;
            }

            .games-main-wrapper {
                flex-direction: column;
                gap: 15px;
            }

            .games-sidebar {
                width: 100%;
            }

            .sidebar-menu {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 8px;
            }

            .games-sidebar .sidebar-title {
                grid-column: 1 / -1;
            }

            .games-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .game-card {
                height: 140px;
            }

            .game-card .game-image {
                width: 110px;
                height: 140px;
            }

            .games-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .games-header h1 {
                font-size: 1.8rem;
            }

            .games-filters {
                width: 100%;
                margin-top: 15px;
            }
        }

        @media (max-width: 480px) {
            .games-grid {
                grid-template-columns: 1fr;
            }

            .game-card {
                height: 150px;
            }

            .game-card .game-image {
                width: 120px;
                height: 150px;
            }
        }
    </style>
</head>
<body>
    <!-- Header Layout Include -->
    <?php include '../partials/header-layout.php'; ?>

    <!-- Main Content -->
    <main>
        <div class="steam-games-container">
            <!-- Header Section -->
            <div class="games-header">
                <div>
                    <p>MinePerry game Steam - an toàn và đảm bảo</p>
                </div>
            </div>

            <!-- Main Wrapper: Sidebar + Content -->
            <div class="games-main-wrapper">
                <!-- Sidebar - Categories -->
                <aside class="games-sidebar">
                    <h3 class="sidebar-title">Danh Mục</h3>
                    <ul class="sidebar-menu">
                        <li><a href="<?php echo htmlspecialchars($allLink); ?>" class="<?php echo (!$isHotFilter && $categoryId === 0) ? 'active' : ''; ?>">
                            <svg viewBox="0 0 24 24"><use xlink:href="#icon-gamepad"></use></svg>
                            Tất cả
                        </a></li>
                        <li><a href="<?php echo htmlspecialchars($hotLink); ?>" class="<?php echo $isHotFilter ? 'active' : ''; ?>">
                            <svg viewBox="0 0 24 24"><use xlink:href="#icon-fire"></use></svg>
                            Game HOT
                        </a></li>
                        <?php foreach ($categories as $category): ?>
                            <li>
                                <a href="<?php echo htmlspecialchars($baseUrl . '?category=' . (int)$category['id']); ?>" class="<?php echo (!$isHotFilter && $categoryId === (int)$category['id']) ? 'active' : ''; ?>">
                                    <svg viewBox="0 0 24 24"><use xlink:href="#icon-gamepad"></use></svg>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </aside>

                <!-- Games Content -->
                <div class="games-content">
                    <!-- Filters -->
                    <div class="games-filters">
                        <button class="filter-btn active">Tất Cả</button>
                        <button class="filter-btn">Đề Cử</button>
                        <button class="filter-btn">Hot Nhất</button>
                <button class="filter-btn">Mới Nhất</button>
                <select class="filter-select">
                    <option>Sắp xếp: Phổ biến</option>
                    <option>Giá thấp → cao</option>
                    <option>Giá cao → thấp</option>
                    <option>Mới nhất</option>
                    <option>Rating cao nhất</option>
                </select>
            </div>

            <!-- Games Grid -->
            <div class="games-grid">
                <?php if (!empty($games)): ?>
                    <?php foreach ($games as $game): ?>
                        <?php
                            $imageSrc = resolveSteamGameImage($game['image'] ?? '');
                            $price = (float)($game['price'] ?? 0);
                            $salePrice = (float)($game['sale_price'] ?? 0);
                            $displayPrice = $salePrice > 0 ? $salePrice : $price;
                            $badge = trim((string)($game['badge'] ?? ''));
                            if ($badge === '' && $salePrice > 0 && $price > 0 && $salePrice < $price) {
                                $discount = (int)round((($price - $salePrice) / $price) * 100);
                                $badge = '-' . $discount . '%';
                            }
                            $rating = $game['rating'] ?? null;
                        ?>
                        <div class="game-card" data-id="<?php echo (int)$game['id']; ?>">
                            <div class="game-image">
                                <?php if ($imageSrc): ?>
                                    <img src="<?php echo htmlspecialchars($imageSrc); ?>" alt="<?php echo htmlspecialchars($game['name']); ?>">
                                <?php endif; ?>
                                <?php if ($badge !== ''): ?>
                                    <span class="game-badge"><?php echo htmlspecialchars($badge); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="game-info">
                                <div class="game-card-header">
                                    <h3><?php echo htmlspecialchars($game['name']); ?></h3>
                                    <span class="game-card-tag">Game</span>
                                </div>
                                <div class="game-rating">
                                    <span class="stars">★</span>
                                    <span class="rating-value">
                                        <?php echo $rating !== null ? number_format((float)$rating, 1) : 'N/A'; ?>
                                    </span>
                                </div>
                                <div class="game-price">
                                    <span class="price">
                                        <?php echo $displayPrice > 0 ? number_format($displayPrice, 0, ',', '.') . '₫' : 'Liên hệ'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; text-align: center; color: var(--text-secondary); padding: 30px;">Chưa có sản phẩm</div>
                <?php endif; ?>
            </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="games-pagination">
                            <?php if ($currentPage > 1): ?>
                                <a class="pagination-btn" href="?page=<?php echo $currentPage - 1; ?><?php echo $isHotFilter ? '&filter=hot' : ($categoryId > 0 ? '&category=' . $categoryId : ''); ?>">&laquo; Trước</a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a class="pagination-btn <?php echo $i === $currentPage ? 'active' : ''; ?>" href="?page=<?php echo $i; ?><?php echo $isHotFilter ? '&filter=hot' : ($categoryId > 0 ? '&category=' . $categoryId : ''); ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($currentPage < $totalPages): ?>
                                <a class="pagination-btn" href="?page=<?php echo $currentPage + 1; ?><?php echo $isHotFilter ? '&filter=hot' : ($categoryId > 0 ? '&category=' . $categoryId : ''); ?>">Tiếp &raquo;</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer Layout Include -->
    <?php include '../partials/footer-layout.php'; ?>

    <!-- JavaScript -->
    <script src="assets/js/game-card.js"></script>
    <script>
        // Filter functionality
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Game card click navigation
        document.querySelectorAll('.game-card').forEach(card => {
            card.addEventListener('click', function() {
                const gameId = this.getAttribute('data-id');
                if (gameId) {
                    window.location.href = '../../public/index.php?page=product&id=' + gameId;
                }
            });
        });

        // Server-side pagination handles active state
    </script>
</body>
</html>
