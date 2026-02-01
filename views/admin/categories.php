<?php
/**
 * ADMIN CATEGORIES - Quản lý danh mục
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

// Get all categories
$db->query("SELECT id, name, slug, icon, sort_order, is_active FROM categories ORDER BY sort_order ASC, name ASC");
$categories = $db->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý danh mục - Admin</title>
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
            <h1>Quản lý danh mục</h1>
            <button class="btn-primary" onclick="openCategoryModal()">
                <svg width="18" height="18" viewBox="0 0 24 24">
                    <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" fill="currentColor"/>
                </svg>
                Thêm danh mục
            </button>
        </div>

        <div class="content-card">
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên danh mục</th>
                            <th>Slug</th>
                            <th>Icon</th>
                            <th>Thứ tự</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                        <tr>
                            <td>#<?php echo $category['id']; ?></td>
                            <td><?php echo htmlspecialchars($category['name']); ?></td>
                            <td><?php echo htmlspecialchars($category['slug']); ?></td>
                            <td><?php echo htmlspecialchars($category['icon'] ?? 'icon-gamepad'); ?></td>
                            <td><?php echo (int)($category['sort_order'] ?? 0); ?></td>
                            <td>
                                <span class="status-badge <?php echo !empty($category['is_active']) ? 'status-active' : 'status-hidden'; ?>">
                                    <?php echo !empty($category['is_active']) ? 'Hoạt động' : 'Ẩn'; ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 5px;">
                                    <button class="btn-action btn-edit" onclick='editCategory(<?php echo json_encode($category); ?>)' title="Sửa">
                                        <svg width="16" height="16" viewBox="0 0 24 24">
                                            <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z" fill="currentColor"/>
                                        </svg>
                                    </button>
                                    <button class="btn-action btn-delete" onclick="deleteCategory(<?php echo $category['id']; ?>)" title="Xóa">
                                        <svg width="16" height="16" viewBox="0 0 24 24">
                                            <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-9l-1 1H5v2h14V4z" fill="currentColor"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-secondary); padding: 40px;">
                                Chưa có danh mục nào
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Category Modal -->
    <div id="categoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Thêm danh mục</h2>
                <button class="btn-close" onclick="closeCategoryModal()">×</button>
            </div>
            <form id="categoryForm" class="modal-body">
                <input type="hidden" id="categoryId" name="id">

                <div class="form-grid">
                    <div class="form-group">
                        <label>Tên danh mục *</label>
                        <input type="text" id="categoryName" name="name" required>
                    </div>

                    <div class="form-group">
                        <label>Slug</label>
                        <input type="text" id="categorySlug" name="slug" placeholder="Tự tạo nếu bỏ trống">
                    </div>

                    <div class="form-group">
                        <label>Icon (class)</label>
                        <input type="text" id="categoryIcon" name="icon" placeholder="icon-gamepad">
                    </div>

                    <div class="form-group">
                        <label>Thứ tự</label>
                        <input type="number" id="categoryOrder" name="sort_order" value="0" min="0">
                    </div>

                    <div class="form-group">
                        <label>Trạng thái</label>
                        <select id="categoryStatus" name="is_active">
                            <option value="1">Hoạt động</option>
                            <option value="0">Ẩn</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeCategoryModal()">Hủy</button>
                    <button type="submit" class="btn-primary">Lưu</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../public/assets/js/admin-categories.js"></script>
</body>
</html>
