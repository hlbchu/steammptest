<?php
/**
 * ADMIN HOT PRODUCTS - Quản lý game hot
 */
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../client/login.php');
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    header('Location: ../client/index.php');
    exit();
}

require_once '../../config/config.php';
require_once '../../config/database.php';

$db = new Database();
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if (!empty($search)) {
    $db->query("SELECT p.*, c.name as category_name 
               FROM products p 
               LEFT JOIN categories c ON p.category_id = c.id 
               WHERE p.name LIKE :search OR p.description LIKE :search
               ORDER BY p.is_hot DESC, p.updated_at DESC");
    $db->bind(':search', '%' . $search . '%');
    $products = $db->fetchAll();
} else {
    $db->query("SELECT p.*, c.name as category_name 
               FROM products p 
               LEFT JOIN categories c ON p.category_id = c.id 
               ORDER BY p.is_hot DESC, p.updated_at DESC");
    $products = $db->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Game Hot - Admin</title>
    <link rel="stylesheet" href="../../public/assets/css/base.css">
    <link rel="stylesheet" href="../../public/assets/css/admin.css">
    <link rel="stylesheet" href="../../public/assets/css/notification.css">
</head>
<body class="admin-body">
    <!-- Admin Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="admin-content">
        <div class="content-header">
            <h1>Quản lý Game Hot</h1>
            <form method="GET" style="display: flex; gap: 10px; flex: 1; max-width: 400px;">
                <input type="text" name="search" placeholder="Tìm kiếm sản phẩm..." value="<?php echo htmlspecialchars($search); ?>" style="flex: 1; padding: 10px; border: 1px solid var(--border-color); border-radius: 4px; background: var(--secondary-bg); color: var(--text-primary);">
                <button type="submit" class="btn-primary" style="padding: 10px 20px;">Tìm</button>
                <?php if (!empty($search)): ?>
                <a href="hot-products.php" class="btn-secondary" style="padding: 10px 20px; text-decoration: none; border-radius: 4px;">Xóa</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="content-card">
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Hình ảnh</th>
                            <th>Tên sản phẩm</th>
                            <th>Danh mục</th>
                            <th>Nội dung nhận được</th>
                            <th>Trạng thái</th>
                            <th>Hot</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td>#<?php echo $product['id']; ?></td>
                            <td>
                                <?php if ($product['image']): ?>
                                    <?php 
                                    $imageSrc = (filter_var($product['image'], FILTER_VALIDATE_URL)) 
                                        ? $product['image'] 
                                        : '../../public/assets/images/' . $product['image'];
                                    ?>
                                    <img src="<?php echo htmlspecialchars($imageSrc); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width: 60px; height: 40px; object-fit: cover; border-radius: 4px;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div style="width: 60px; height: 40px; background: rgba(0,0,0,0.3); border-radius: 4px; display: none; align-items: center; justify-content: center; font-size: 10px;">No img</div>
                                <?php else: ?>
                                    <div style="width: 60px; height: 40px; background: rgba(0,0,0,0.3); border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 10px;">No img</div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                            <td>
                                <?php
                                $receivedContent = trim(strip_tags($product['received_content'] ?? ''));
                                if ($receivedContent === '') {
                                    echo '-';
                                } else {
                                    $short = function_exists('mb_strimwidth')
                                        ? mb_strimwidth($receivedContent, 0, 80, '...')
                                        : (strlen($receivedContent) > 80 ? substr($receivedContent, 0, 77) . '...' : $receivedContent);
                                    echo htmlspecialchars($short);
                                }
                                ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $product['status']; ?>">
                                    <?php echo $product['status'] === 'active' ? 'Hoạt động' : 'Ẩn'; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ((int)$product['is_hot'] === 1): ?>
                                    <span class="status-badge status-active">HOT</span>
                                <?php else: ?>
                                    <span class="status-badge status-hidden">OFF</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn-action btn-edit" data-id="<?php echo $product['id']; ?>" data-hot="<?php echo (int)$product['is_hot']; ?>" title="Toggle Hot">
                                    <?php if ((int)$product['is_hot'] === 1): ?>
                                        Bỏ Hot
                                    <?php else: ?>
                                        Đặt Hot
                                    <?php endif; ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: var(--text-secondary); padding: 40px;">
                                Chưa có sản phẩm nào
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="../../public/assets/js/admin-hot.js"></script>
    <script>
        document.querySelectorAll('.nav-group-toggle').forEach(btn => {
            btn.addEventListener('click', () => {
                const group = btn.closest('.nav-group');
                const isOpen = group.classList.toggle('open');
                btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });
        });
    </script>
</body>
</html>
