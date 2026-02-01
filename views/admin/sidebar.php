<?php
/**
 * Admin Sidebar Component
 * Reusable sidebar for all admin pages
 */

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="admin-sidebar">
    <div class="admin-logo">
        <svg width="30" height="30" viewBox="0 0 24 24">
            <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z" fill="currentColor"/>
        </svg>
        <h2>Admin Panel</h2>
    </div>

    <nav class="admin-nav">
        <a href="dashboard.php" class="nav-item <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
            <svg width="20" height="20" viewBox="0 0 24 24">
                <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z" fill="currentColor"/>
            </svg>
            Dashboard
        </a>
        <div class="nav-group">
            <button type="button" class="nav-group-title nav-group-toggle" aria-expanded="false">
                <svg width="20" height="20" viewBox="0 0 24 24">
                    <path d="M21.58 11.59l-9-9C12.37 2.39 12.19 2.31 12 2.31s-.37.08-.59.28l-9 9c-.37.36-.37.95 0 1.31.18.18.43.28.69.28s.51-.1.69-.28L12 4.69l8.21 8.21c.37.37.98.37 1.37 0 .37-.36.37-.95 0-1.31z" fill="currentColor"/>
                </svg>
                Sản phẩm
                <span class="nav-group-arrow">▸</span>
            </button>
            <div class="nav-sub">
                <a href="products.php" class="nav-item nav-sub-item <?php echo $currentPage === 'products.php' ? 'active' : ''; ?>">Sửa sản phẩm</a>
                <a href="product-content.php" class="nav-item nav-sub-item <?php echo $currentPage === 'product-content.php' ? 'active' : ''; ?>">Nội dung sản phẩm</a>
                <a href="categories.php" class="nav-item nav-sub-item <?php echo $currentPage === 'categories.php' ? 'active' : ''; ?>">Danh mục</a>
                <a href="purchase-types.php" class="nav-item nav-sub-item <?php echo $currentPage === 'purchase-types.php' ? 'active' : ''; ?>">Loại mục mua</a>
            </div>
        </div>
        <a href="hot-products.php" class="nav-item <?php echo $currentPage === 'hot-products.php' ? 'active' : ''; ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M13.5.67s.74 2.65.74 4.8c0 2.06-1.35 3.73-3.41 3.73-2.07 0-3.63-1.67-3.63-3.73l.03-.36C5.21 7.51 4 10.3 4 13c0 5.25 3.07 7.8 5.5 7.8 1.5 0 2.7-.8 3.57-1.64.48.34 1.84 1.64 3.57 1.64 2.43 0 5.5-2.55 5.5-7.8 0-5.25-3.07-7.8-5.5-7.8-1.5 0-2.7.8-3.57 1.64-.48-.34-1.84-1.64-3.57-1.64z"/>
            </svg>
            Game Hot
        </a>
        <a href="users.php" class="nav-item <?php echo $currentPage === 'users.php' ? 'active' : ''; ?>">
            <svg width="20" height="20" viewBox="0 0 24 24">
                <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z" fill="currentColor"/>
            </svg>
            Người dùng
        </a>
        <div class="nav-group">
            <button type="button" class="nav-group-title nav-group-toggle" aria-expanded="false">
                <svg width="20" height="20" viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z" fill="currentColor"/>
                </svg>
                Quản Lý Thanh Toán
                <span class="nav-group-arrow">▸</span>
            </button>
            <div class="nav-sub">
                <a href="payments.php" class="nav-item nav-sub-item <?php echo $currentPage === 'payments.php' ? 'active' : ''; ?>">Quản lý thanh toán</a>
                <a href="banks.php" class="nav-item nav-sub-item <?php echo $currentPage === 'banks.php' ? 'active' : ''; ?>">Quản lý ngân hàng</a>
            </div>
        </div>
        <a href="nav-menu.php" class="nav-item <?php echo $currentPage === 'nav-menu.php' ? 'active' : ''; ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/>
            </svg>
            Sửa Trang Mục
        </a>
    </nav>

    <div class="admin-user">
        <div class="user-info">
            <div class="user-avatar">
                <svg width="24" height="24" viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z" fill="currentColor"/>
                </svg>
            </div>
            <div>
                <p class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></p>
                <p class="user-role">Administrator</p>
            </div>
        </div>
        <a href="../client/index.php" class="btn-exit">
            <svg width="18" height="18" viewBox="0 0 24 24">
                <path d="M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.11 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z" fill="currentColor"/>
            </svg>
        </a>
    </div>
</aside>

<script>
// Collapsible nav groups
document.querySelectorAll('.nav-group-toggle').forEach(button => {
    button.addEventListener('click', function() {
        const navGroup = this.parentElement;
        const navSub = navGroup.querySelector('.nav-sub');
        const isExpanded = this.getAttribute('aria-expanded') === 'true';
        
        this.setAttribute('aria-expanded', !isExpanded);
        navGroup.classList.toggle('expanded');
        
        if (navSub) {
            navSub.style.display = isExpanded ? 'none' : 'block';
        }
    });
});

// Auto-expand if current page is in sub-menu
document.querySelectorAll('.nav-sub-item.active').forEach(item => {
    const navGroup = item.closest('.nav-group');
    if (navGroup) {
        const toggle = navGroup.querySelector('.nav-group-toggle');
        const navSub = navGroup.querySelector('.nav-sub');
        if (toggle && navSub) {
            toggle.setAttribute('aria-expanded', 'true');
            navGroup.classList.add('expanded');
            navSub.style.display = 'block';
        }
    }
});
</script>
