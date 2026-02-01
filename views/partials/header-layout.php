<?php
/**
 * HEADER LAYOUT - Header, Header-main, Nav-menu
 * Include này ở trong tất cả pages
 */

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$username = $_SESSION['username'] ?? null;
$userRole = $_SESSION['role'] ?? 'user';
$userId = $_SESSION['user_id'] ?? null;

// Get user wallet balance if logged in
$walletBalance = 0;
if ($isLoggedIn && $userId) {
    // Only try to load database if not already loaded
    if (!defined('DB_HOST')) {
        require_once __DIR__ . '/../../config/config.php';
    }
    
    if (!class_exists('Database')) {
        require_once __DIR__ . '/../../config/database.php';
    }
    
    try {
        $db = new Database();
        $db->query("SELECT balance FROM wallets WHERE user_id = :user_id");
        $db->bind(':user_id', $userId);
        $wallet = $db->fetch();
        $walletBalance = isset($wallet['balance']) ? (float)$wallet['balance'] : 0;
    } catch (PDOException $e) {
        $walletBalance = 0;
    } catch (Exception $e) {
        $walletBalance = 0;
    }
}

// Get navigation menu from database
$navMenuItems = [];
if (!class_exists('Database')) {
    if (!defined('DB_HOST')) {
        require_once __DIR__ . '/../../config/config.php';
    }
    require_once __DIR__ . '/../../config/database.php';
}
try {
    $db = new Database();
    $db->query("SELECT * FROM nav_menu WHERE parent_id IS NULL AND is_active = 1 ORDER BY display_order ASC, id ASC");
    $navMenuItems = $db->fetchAll();
    
    // Get submenus for each parent menu
    foreach ($navMenuItems as &$menu) {
        $db->query("SELECT * FROM nav_menu WHERE parent_id = :parent_id AND is_active = 1 ORDER BY display_order ASC, id ASC");
        $db->bind(':parent_id', $menu['id']);
        $menu['submenus'] = $db->fetchAll();
    }
} catch (Exception $e) {
    // Fallback to empty if error
    $navMenuItems = [];
}

// Ensure config for APP_URL
if (!defined('APP_URL')) {
    require_once __DIR__ . '/../../config/config.php';
}

$app_url = rtrim(APP_URL ?? 'http://localhost/steamweb', '/');
$home_path = $app_url . '/public/index.php?page=home';
$hot_path = $app_url . '/public/index.php?page=hot';
$new_path = $app_url . '/public/index.php?page=new';
$games_path = $app_url . '/views/client/steam-games.php';
$login_path = $app_url . '/views/client/login.php';
$register_path = $app_url . '/views/client/register.php';
$profile_path = $app_url . '/views/client/profile.php';
$orders_path = $app_url . '/views/client/profile.php#orders';
$cart_path = $app_url . '/public/index.php?page=cart';
$admin_path = $app_url . '/views/admin/dashboard.php';
$logout_path = $app_url . '/app/Controllers/AuthController.php?action=logout';
?>
<?php include __DIR__ . '/../../public/assets/svg/icons.svg'; ?>

<!-- Header -->
<header>
    <div class="header-top">
        <div class="header-top-wrapper">
            <div class="header-top-left">
                <a href="#">
                    <svg width="14" height="14" viewBox="0 0 24 24">
                        <use xlink:href="#icon-news"></use>
                    </svg>
                    Tìm tức
                </a>
                <a href="#">
                    <svg width="14" height="14" viewBox="0 0 24 24">
                        <use xlink:href="#icon-clipboard"></use>
                    </svg>
                    Hướng dẫn
                </a>
                <a href="#">
                    <svg width="14" height="14" viewBox="0 0 24 24">
                        <use xlink:href="#icon-user"></use>
                    </svg>
                    Tuyển dụng
                </a>
            </div>
            <div class="header-top-right">
                <a href="#">
                    <svg width="14" height="14" viewBox="0 0 24 24">
                        <use xlink:href="#icon-home"></use>
                    </svg>
                    Tiếng Việt
                </a>
            </div>
        </div>
    </div>
    <div class="header-main">
        <div class="header-main-wrapper">
            <a href="<?php echo $home_path; ?>" class="logo">
                <svg class="icon-logo" width="80" height="80" viewBox="0 0 1080 1080">
                    <use xlink:href="#icon-logo"></use>
                </svg>
                GAMES STORE
            </a>
            <div class="search-box" data-search-scope="header">
                <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24">
                    <use xlink:href="#icon-search"></use>
                </svg>
                <input type="text"
                       placeholder="Tìm game, steam, tài khoản..."
                       id="headerSearchInput"
                       autocomplete="off"
                       data-search-endpoint="<?php echo $app_url; ?>/app/Controllers/SearchController.php"
                       data-search-page="<?php echo $app_url; ?>/public/index.php?page=search"
                       data-product-page="<?php echo $app_url; ?>/public/index.php?page=product&id=">
                <div class="search-suggestions" id="searchSuggestions" aria-hidden="true"></div>
            </div>
            <div class="user-actions">
                <a href="<?php echo $cart_path; ?>" class="action-icon">
                    <svg class="icon" width="20" height="20" viewBox="0 0 24 24">
                        <use xlink:href="#icon-cart"></use>
                    </svg>
                    <?php 
                    // Get cart count for logged in users
                    $cartCount = 0;
                    if ($isLoggedIn && $userId) {
                        try {
                            // Load database if not already loaded
                            if (!defined('DB_HOST')) {
                                require_once __DIR__ . '/../../config/config.php';
                            }
                            if (!class_exists('Database')) {
                                require_once __DIR__ . '/../../config/database.php';
                            }
                            
                            $db = new Database();
                            $db->query("SELECT SUM(quantity) as total_qty FROM cart WHERE user_id = :user_id");
                            $db->bind(':user_id', $userId);
                            $result = $db->fetch();
                            $cartCount = (int)($result['total_qty'] ?? 0);
                        } catch (Exception $e) {
                            $cartCount = 0;
                        }
                    } else {
                        $cartCount = isset($_SESSION['guest_cart']) ? count($_SESSION['guest_cart']) : 0;
                    }
                    ?>
                    <?php if ($cartCount > 0): ?>
                    <span class="cart-badge"><?php echo $cartCount; ?></span>
                    <?php endif; ?>
                </a>
                
                <?php if ($isLoggedIn): ?>
                    <!-- Wallet balance (khi đã đăng nhập) -->
                    <a href="#" class="action-icon wallet-info">
                        <svg width="20" height="20" viewBox="0 0 24 24">
                            <path d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z" fill="currentColor"/>
                        </svg>
                        <span><?php echo number_format($walletBalance, 0, ',', '.'); ?>đ</span>
                    </a>
                <?php endif; ?>
                
                <?php if ($isLoggedIn): ?>
                    <!-- User logged in -->
                    <div class="user-dropdown">
                        <a href="#" class="action-icon user-menu-trigger">
                            <svg width="18" height="18" viewBox="0 0 24 24">
                                <use xlink:href="#icon-user"></use>
                            </svg>
                            <span><?php echo htmlspecialchars($username); ?></span>
                            <svg width="12" height="12" viewBox="0 0 24 24" style="margin-left: 4px;">
                                <path d="M7 10l5 5 5-5z" fill="currentColor"/>
                            </svg>
                        </a>
                        <div class="dropdown-menu">
                            <a href="<?php echo $profile_path; ?>" class="dropdown-item">
                                <svg width="16" height="16" viewBox="0 0 24 24">
                                    <use xlink:href="#icon-user"></use>
                                </svg>
                                Thông tin tài khoản
                            </a>
                            <a href="<?php echo $app_url; ?>/views/client/deposit.php" class="dropdown-item">
                                <svg width="16" height="16" viewBox="0 0 24 24">
                                    <path d="M9 11H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm2-7H3c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H3V7h16v12z" fill="currentColor"/>
                                </svg>
                                Nạp tiền
                            </a>
                            <a href="<?php echo $orders_path; ?>" class="dropdown-item">
                                <svg width="16" height="16" viewBox="0 0 24 24">
                                    <use xlink:href="#icon-cart"></use>
                                </svg>
                                Đơn hàng của tôi
                            </a>
                            <?php if ($userRole === 'admin'): ?>
                            <a href="<?php echo $admin_path; ?>" class="dropdown-item">
                                <svg width="16" height="16" viewBox="0 0 24 24">
                                    <path d="M12 2l-5.5 9h11z M12 22l5.5-9h-11z" fill="currentColor"/>
                                </svg>
                                Quản trị
                            </a>
                            <?php endif; ?>
                            <div class="dropdown-divider"></div>
                            <a href="<?php echo $logout_path; ?>" class="dropdown-item logout">
                                <svg width="16" height="16" viewBox="0 0 24 24">
                                    <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z" fill="currentColor"/>
                                </svg>
                                Đăng xuất
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- User not logged in -->
                    <a href="<?php echo $login_path; ?>" class="action-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24">
                            <use xlink:href="#icon-user"></use>
                        </svg>
                        <span>Đăng nhập</span>
                    </a>
                    <a href="<?php echo $register_path; ?>" class="action-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24">
                            <use xlink:href="#icon-user"></use>
                        </svg>
                        <span>Đăng ký</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <nav class="nav-menu">
        <ul>
            <?php if (!empty($navMenuItems)): ?>
                <?php foreach ($navMenuItems as $menuItem): ?>
                    <li>
                        <a href="<?php 
                            $link = htmlspecialchars($menuItem['link'] ?? '#');
                            // Nếu link bắt đầu bằng / thì thêm app_url
                            if (strpos($link, '/') === 0) {
                                echo $app_url . $link;
                            } else {
                                echo $link;
                            }
                        ?>" <?php if (!empty($menuItem['text_color'])): ?>style="color: <?php echo htmlspecialchars($menuItem['text_color']); ?> !important;"<?php endif; ?>>
                            <?php if (!empty($menuItem['icon'])): ?>
                                <?php if (strpos($menuItem['icon'], '<svg') !== false): ?>
                                    <!-- Inline SVG -->
                                    <span style="display: inline-flex; width: 16px; height: 16px; vertical-align: middle;">
                                        <?php echo $menuItem['icon']; ?>
                                    </span>
                                <?php elseif (strpos($menuItem['icon'], '.svg') !== false): ?>
                                    <!-- SVG File -->
                                    <img src="<?php echo $app_url; ?>/public/assets/svg/<?php echo htmlspecialchars($menuItem['icon']); ?>" 
                                         width="16" height="16" 
                                         style="vertical-align: middle; filter: brightness(0) saturate(100%) invert(100%);"
                                         alt="">
                                <?php else: ?>
                                    <!-- Emoji/Text -->
                                    <span style="font-size: 16px; vertical-align: middle;"><?php echo $menuItem['icon']; ?></span>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($menuItem['name']); ?>
                        </a>
                        <?php if (!empty($menuItem['submenus'])): ?>
                        <ul class="submenu">
                            <?php foreach ($menuItem['submenus'] as $submenu): ?>
                            <li>
                                <a href="<?php 
                                    $sublink = htmlspecialchars($submenu['link'] ?? '#');
                                    if (strpos($sublink, '/') === 0) {
                                        echo $app_url . $sublink;
                                    } else {
                                        echo $sublink;
                                    }
                                ?>" <?php if (!empty($submenu['text_color'])): ?>style="color: <?php echo htmlspecialchars($submenu['text_color']); ?> !important;"<?php endif; ?>>
                                    <?php if (!empty($submenu['icon'])): ?>
                                        <?php if (strpos($submenu['icon'], '<svg') !== false): ?>
                                            <span class="submenu-icon submenu-icon-svg"><?php echo $submenu['icon']; ?></span>
                                        <?php elseif (preg_match('/\.(svg|png|jpg|jpeg|webp)$/i', $submenu['icon'])): ?>
                                            <img src="<?php echo $app_url; ?>/public/assets/svg/<?php echo htmlspecialchars($submenu['icon']); ?>" 
                                                 alt="" class="submenu-icon submenu-icon-img" 
                                                 onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';">
                                            <span class="submenu-icon submenu-icon-fallback" style="display:none;">📄</span>
                                        <?php else: ?>
                                            <span class="submenu-icon submenu-icon-fallback"><?php echo htmlspecialchars($submenu['icon']); ?></span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <span class="submenu-text"><?php echo htmlspecialchars($submenu['name']); ?></span>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Fallback menu nếu database lỗi -->
                <li>
                    <a href="<?php echo $home_path; ?>">
                        <svg width="16" height="16" viewBox="0 0 24 24">
                            <use xlink:href="#icon-home"></use>
                        </svg>
                        Trang Chủ
                    </a>
                </li>
                <li>
                    <a href="<?php echo $games_path; ?>">
                        <svg width="16" height="16" viewBox="0 0 24 24">
                            <use xlink:href="#icon-gamepad"></use>
                        </svg>
                        Game Steam
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<script src="<?php echo $app_url; ?>/public/assets/js/search.js" defer></script>
<style>
    /* ============ SUBMENU DROPDOWN STYLES ============ */
    
    .nav-menu li {
        position: relative;
        z-index: 100;
    }

    /* Submenu Container */
    .nav-menu .submenu {
        display: none;
        position: fixed;
        top: auto;
        left: auto;
        background: var(--secondary-bg);
        border: 1px solid rgba(var(--accent-rgb), 0.3);
        border-radius: 10px;
        min-width: 200px;
        max-width: 320px;
        width: max-content;
        list-style: none;
        margin: 0;
        padding: 8px 0;
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.5);
        z-index: 10000;
        overflow-y: auto;
        overflow-x: hidden;
        max-height: 400px;
        backdrop-filter: blur(8px);
        animation: none;
    }

    /* Smooth show/hide with improved positioning */
    .nav-menu li:hover > .submenu {
        display: block;
        animation: submenuSlideIn 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    .nav-menu li:hover > .submenu {
        position: fixed;
    }

    /* Calculate position dynamically */
    .nav-menu li:has(> .submenu) {
        overflow: visible;
    }

    /* Submenu Scrollbar Styling */
    .nav-menu .submenu::-webkit-scrollbar {
        width: 6px;
    }

    .nav-menu .submenu::-webkit-scrollbar-track {
        background: transparent;
        margin: 8px 0;
    }

    .nav-menu .submenu::-webkit-scrollbar-thumb {
        background: rgba(var(--accent-rgb), 0.25);
        border-radius: 10px;
        border: 2px solid transparent;
        background-clip: padding-box;
    }

    .nav-menu .submenu::-webkit-scrollbar-thumb:hover {
        background: rgba(var(--accent-rgb), 0.5);
        background-clip: padding-box;
    }

    /* Submenu List Items */
    .nav-menu .submenu li {
        margin: 0;
        list-style: none;
        border-bottom: 0;
    }

    .nav-menu .submenu li:last-child {
        border-bottom: none;
    }

    /* Submenu Links */
    .nav-menu .submenu a {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 12px 16px;
        color: var(--text-secondary);
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
        border-left: 3px solid transparent;
        white-space: nowrap;
        text-overflow: ellipsis;
        overflow: hidden;
    }

    .nav-menu .submenu .submenu-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 16px;
        height: 16px;
        flex-shrink: 0;
        color: currentColor;
    }

    .nav-menu .submenu .submenu-icon-svg svg {
        width: 16px;
        height: 16px;
        fill: currentColor;
    }

    .nav-menu .submenu .submenu-icon-img {
        width: 16px;
        height: 16px;
        display: inline-block;
        vertical-align: middle;
    }

    .nav-menu .submenu .submenu-text {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .nav-menu .submenu a:hover {
        background: rgba(var(--accent-rgb), 0.12);
        color: var(--accent-color);
        border-left-color: var(--accent-color);
        padding-left: 18px;
    }

    .nav-menu .submenu a:active {
        background: rgba(var(--accent-rgb), 0.2);
    }

    /* Animation */
    @keyframes submenuSlideIn {
        from {
            opacity: 0;
            transform: translateY(-8px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    /* Position fix for dropdown - Calculate with JavaScript fallback */
    .nav-menu {
        position: relative;
    }

    .nav-menu li:hover .submenu {
        /* Will be positioned by JavaScript */
    }

    /* Responsive */
    @media (max-width: 768px) {
        .nav-menu .submenu {
            min-width: 180px;
            max-width: 280px;
            font-size: 13px;
        }

        .nav-menu .submenu a {
            padding: 10px 12px;
        }

        .nav-menu .submenu a:hover {
            padding-left: 14px;
        }
    }
</style>

<script>
    // Position submenus correctly when hovering
    document.addEventListener('DOMContentLoaded', function() {
        const navItems = document.querySelectorAll('.nav-menu > li');
        
        navItems.forEach(item => {
            item.addEventListener('mouseenter', function() {
                const submenu = this.querySelector('.submenu');
                if (submenu) {
                    setTimeout(() => {
                        const rect = this.getBoundingClientRect();
                        const submenuRect = submenu.getBoundingClientRect();
                        
                        // Calculate position
                        let top = rect.bottom + 6;
                        let left = rect.left;
                        
                        // Adjust if goes beyond viewport
                        if (left + submenuRect.width > window.innerWidth) {
                            left = window.innerWidth - submenuRect.width - 16;
                        }
                        if (left < 0) {
                            left = 16;
                        }
                        
                        submenu.style.top = top + 'px';
                        submenu.style.left = left + 'px';
                    }, 0);
                }
            });
        });
    });
</script>
