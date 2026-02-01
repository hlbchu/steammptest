<?php
/**
 * ADMIN SIDEBAR COMPONENT - Menu bên trái cho admin
 */
?>

<aside class="admin-sidebar">
    <div class="admin-sidebar-title">⚙️ Quản lý hệ thống</div>
    <ul class="admin-menu">
        <li class="admin-menu-item">
            <a href="?page=admin&section=dashboard" class="admin-menu-link <?php echo (!isset($_GET['section']) || $_GET['section'] === '' ? 'active' : ''); ?>">
                📊 Dashboard
            </a>
        </li>
        <li class="admin-menu-item">
            <a href="?page=admin&section=products" class="admin-menu-link <?php echo ($_GET['section'] ?? '' === 'products' ? 'active' : ''); ?>">
                🎮 Sản phẩm
            </a>
        </li>
        <li class="admin-menu-item">
            <a href="?page=admin&section=users" class="admin-menu-link <?php echo ($_GET['section'] ?? '' === 'users' ? 'active' : ''); ?>">
                👥 Người dùng
            </a>
        </li>
    </ul>

    <div class="admin-sidebar-title" style="margin-top: 20px;">📝 Nội dung</div>
    <ul class="admin-menu">
        <li class="admin-menu-item">
            <a href="?page=admin&section=game-downloads" class="admin-menu-link <?php echo ($_GET['section'] ?? '' === 'game-downloads' ? 'active' : ''); ?>">
                📥 Link Tải Game
            </a>
        </li>
        <li class="admin-menu-item">
            <a href="?page=admin&section=categories" class="admin-menu-link <?php echo ($_GET['section'] ?? '' === 'categories' ? 'active' : ''); ?>">
                🏷️ Danh mục
            </a>
        </li>
        <li class="admin-menu-item">
            <a href="?page=admin&section=sections" class="admin-menu-link <?php echo ($_GET['section'] ?? '' === 'sections' ? 'active' : ''); ?>">
                📑 Sections
            </a>
        </li>
    </ul>

    <div class="admin-sidebar-title" style="margin-top: 20px;">💼 Kinh doanh</div>
    <ul class="admin-menu">
        <li class="admin-menu-item">
            <a href="?page=admin&section=orders" class="admin-menu-link <?php echo ($_GET['section'] ?? '' === 'orders' ? 'active' : ''); ?>">
                📦 Đơn hàng
            </a>
        </li>
        <li class="admin-menu-item">
            <a href="?page=admin&section=transactions" class="admin-menu-link <?php echo ($_GET['section'] ?? '' === 'transactions' ? 'active' : ''); ?>">
                💳 Giao dịch
            </a>
        </li>
        <li class="admin-menu-item">
            <a href="?page=admin&section=reports" class="admin-menu-link <?php echo ($_GET['section'] ?? '' === 'reports' ? 'active' : ''); ?>">
                📈 Báo cáo
            </a>
        </li>
    </ul>

    <div class="admin-sidebar-title" style="margin-top: 20px;">⚡ Khác</div>
    <ul class="admin-menu">
        <li class="admin-menu-item">
            <a href="?page=admin&section=settings" class="admin-menu-link <?php echo ($_GET['section'] ?? '' === 'settings' ? 'active' : ''); ?>">
                ⚙️ Cài đặt
            </a>
        </li>
        <li class="admin-menu-item">
            <a href="?action=logout" class="admin-menu-link">
                🚪 Đăng xuất
            </a>
        </li>
    </ul>
</aside>
