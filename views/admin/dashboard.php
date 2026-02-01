<?php
/**
 * ADMIN DASHBOARD - Trang quản trị
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

// Get statistics
// Total users
$db->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
$totalUsers = $db->fetch()['total'] ?? 0;

// Total products
$db->query("SELECT COUNT(*) as total FROM products WHERE status = 'active'");
$totalProducts = $db->fetch()['total'] ?? 0;

// Total orders
$db->query("SELECT COUNT(*) as total FROM orders");
$totalOrders = $db->fetch()['total'] ?? 0;

// Total revenue
$db->query("SELECT SUM(total_amount) as revenue FROM orders WHERE status = 'paid'");
$totalRevenue = $db->fetch()['revenue'] ?? 0;

// Recent orders
$db->query("SELECT o.id, o.total_amount, o.status, o.created_at, u.username 
           FROM orders o 
           JOIN users u ON o.user_id = u.id 
           ORDER BY o.created_at DESC 
           LIMIT 10");
$recentOrders = $db->fetchAll();

// Recent users
$db->query("SELECT id, username, email, created_at, status 
           FROM users 
           WHERE role = 'user'
           ORDER BY created_at DESC 
           LIMIT 10");
$recentUsers = $db->fetchAll();

// Banner management
$banners = [];
$bannerHasPosition = false;
try {
    $db->query("SHOW TABLES LIKE 'banners'");
    $hasBannersTable = (bool) $db->fetch();
    if ($hasBannersTable) {
        $db->query("SHOW COLUMNS FROM banners LIKE 'position'");
        $bannerHasPosition = (bool) $db->fetch();

        $select = $bannerHasPosition
            ? "SELECT id, title, image_url, link_url, position, is_active, sort_order FROM banners ORDER BY sort_order ASC, id DESC"
            : "SELECT id, title, image_url, link_url, is_active, sort_order FROM banners ORDER BY sort_order ASC, id DESC";

        $db->query($select);
        $banners = $db->fetchAll();
    }
} catch (Throwable $e) {
    $banners = [];
    $bannerHasPosition = false;
}

function resolveAdminBannerUrl($url) {
    if (!$url) {
        return '';
    }
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        return $url;
    }
    if (strpos($url, '/') === 0) {
        return $url;
    }
    if (strpos($url, 'public/') === 0) {
        return rtrim(APP_URL, '/') . '/' . $url;
    }
    return rtrim(APP_URL, '/') . '/public/uploads/banners/' . ltrim($url, '/');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - GAMES STORE</title>
    <!-- CSS Components -->
    <link rel="stylesheet" href="../../public/assets/css/base.css">
    <link rel="stylesheet" href="../../public/assets/css/admin.css">
    <link rel="stylesheet" href="../../public/assets/css/notification.css">
    <link rel="stylesheet" href="../../public/assets/css/banner.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css" integrity="sha512-4jA2qkV9vG1FfQ1bqk7Rnyo0KBRI3S6x5Ue+jsW4dHL8v6fp9H6EVrIdG3bQ3Gm1C1Jf59o3qg6M96tH8Z2H9A==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <!-- SVG Icons -->
    <?php include '../../public/assets/svg/icons.svg'; ?>
</head>
<body class="admin-body">
    <!-- Admin Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="admin-content">
        <!-- Header -->
        <div class="content-header">
            <h1>Dashboard</h1>
            <div class="header-actions">
                <span class="date-time"><?php echo date('d/m/Y H:i'); ?></span>
            </div>
        </div>

        <div class="content-card">
            <div class="card-header">
                <h3>Quản lý Banner</h3>
                <button type="button" class="btn btn-primary" onclick="openAddBannerModal()">Thêm banner</button>
            </div>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Hình ảnh</th>
                            <th>Tiêu đề</th>
                            <th>Vị trí</th>
                            <th>Thứ tự</th>
                            <th>Trạng thái</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($banners)): ?>
                            <?php foreach ($banners as $banner): ?>
                                <?php
                                    $imageSrc = resolveAdminBannerUrl($banner['image_url'] ?? '');
                                    $position = $bannerHasPosition ? ($banner['position'] ?? 'slide') : 'slide';
                                    $positionLabel = $position === 'category' ? 'Danh mục' : 'Slide';
                                ?>
                                <tr>
                                    <td>#<?php echo $banner['id']; ?></td>
                                    <td>
                                        <img src="<?php echo htmlspecialchars($imageSrc); ?>" alt="Banner" style="width: 120px; height: 60px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border-color);">
                                    </td>
                                    <td><?php echo htmlspecialchars($banner['title']); ?></td>
                                    <td><?php echo htmlspecialchars($positionLabel); ?></td>
                                    <td><?php echo (int)($banner['sort_order'] ?? 0); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo !empty($banner['is_active']) ? 'status-active' : 'status-hidden'; ?>">
                                            <?php echo !empty($banner['is_active']) ? 'Hoạt động' : 'Ẩn'; ?>
                                        </span>
                                    </td>
                                    <td style="display: flex; gap: 6px;">
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="editBanner(this)"
                                            data-id="<?php echo $banner['id']; ?>"
                                            data-title="<?php echo htmlspecialchars($banner['title']); ?>"
                                            data-image="<?php echo htmlspecialchars($banner['image_url']); ?>"
                                            data-link="<?php echo htmlspecialchars($banner['link_url'] ?? ''); ?>"
                                            data-position="<?php echo htmlspecialchars($position); ?>"
                                            data-sort="<?php echo (int)($banner['sort_order'] ?? 0); ?>"
                                            data-active="<?php echo !empty($banner['is_active']) ? '1' : '0'; ?>">
                                            Sửa
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="deleteBanner(<?php echo $banner['id']; ?>)">Xóa</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--text-secondary); padding: 30px;">
                                    Chưa có banner nào
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="content-card">
            <div class="card-header">
                <h3>Xem trước Banner</h3>
            </div>
            <?php include '../../views/components/banner.php'; ?>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(76, 175, 80, 0.15);">
                    <svg width="28" height="28" viewBox="0 0 24 24">
                        <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z" fill="#4CAF50"/>
                    </svg>
                </div>
                <div class="stat-info">
                    <h3>Người dùng</h3>
                    <p class="stat-value"><?php echo number_format($totalUsers); ?></p>
                    <span class="stat-label">Tổng số người dùng</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(33, 150, 243, 0.15);">
                    <svg width="28" height="28" viewBox="0 0 24 24">
                        <path d="M21.58 11.59l-9-9C12.37 2.39 12.19 2.31 12 2.31s-.37.08-.59.28l-9 9c-.37.36-.37.95 0 1.31.18.18.43.28.69.28s.51-.1.69-.28L12 4.69l8.21 8.21c.37.37.98.37 1.37 0 .37-.36.37-.95 0-1.31z" fill="#2196F3"/>
                    </svg>
                </div>
                <div class="stat-info">
                    <h3>Sản phẩm</h3>
                    <p class="stat-value"><?php echo number_format($totalProducts); ?></p>
                    <span class="stat-label">Đang hoạt động</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(255, 193, 7, 0.15);">
                    <svg width="28" height="28" viewBox="0 0 24 24">
                        <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z" fill="#FFC107"/>
                    </svg>
                </div>
                <div class="stat-info">
                    <h3>Đơn hàng</h3>
                    <p class="stat-value"><?php echo number_format($totalOrders); ?></p>
                    <span class="stat-label">Tổng đơn</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(0, 180, 216, 0.15);">
                    <svg width="28" height="28" viewBox="0 0 24 24">
                        <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z" fill="#00B4D8"/>
                    </svg>
                </div>
                <div class="stat-info">
                    <h3>Doanh thu</h3>
                    <p class="stat-value"><?php echo number_format($totalRevenue, 0, ',', '.'); ?>₫</p>
                    <span class="stat-label">Tổng thu</span>
                </div>
            </div>
        </div>

        <!-- Recent Orders & Users -->
        <div class="content-grid">
            <!-- Recent Orders -->
            <div class="content-card">
                <div class="card-header">
                    <h3>Đơn hàng gần đây</h3>
                    <a href="orders.php" class="btn-view-all">Xem tất cả</a>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Khách hàng</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th>Ngày</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['username']); ?></td>
                                <td><?php echo number_format($order['total_amount'], 0, ',', '.'); ?>₫</td>
                                <td>
                                    <span class="status-badge status-<?php echo $order['status']; ?>">
                                        <?php
                                        $statusText = [
                                            'pending' => 'Chờ xử lý',
                                            'paid' => 'Đã thanh toán',
                                            'cancelled' => 'Đã hủy',
                                            'refunded' => 'Đã hoàn'
                                        ];
                                        echo $statusText[$order['status']] ?? $order['status'];
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recentOrders)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-secondary);">Chưa có đơn hàng</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="content-card">
                <div class="card-header">
                    <h3>Người dùng mới</h3>
                    <a href="users.php" class="btn-view-all">Xem tất cả</a>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Trạng thái</th>
                                <th>Ngày tạo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentUsers as $user): ?>
                            <tr>
                                <td>#<?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $user['status']; ?>">
                                        <?php echo $user['status'] === 'active' ? 'Hoạt động' : 'Bị khóa'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recentUsers)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-secondary);">Chưa có người dùng</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <div class="modal" id="bannerModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="bannerModalTitle">Thêm banner mới</h2>
                <button type="button" class="modal-close" onclick="closeBannerModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="bannerForm" class="admin-form">
                    <input type="hidden" name="id" id="bannerId">
                    <div class="form-group">
                        <label class="form-label">Tiêu đề *</label>
                        <input type="text" class="form-control" name="title" id="bannerTitle" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Vị trí</label>
                        <select class="form-control" name="position" id="bannerPosition">
                            <option value="slide">Slide</option>
                            <option value="category">Danh mục</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ảnh từ URL</label>
                        <input type="url" class="form-control" name="image_url" id="bannerImageUrl" placeholder="https://...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Hoặc tải ảnh lên</label>
                        <input type="file" class="form-control" name="image_file" id="bannerImageFile" accept="image/*">
                        <small class="form-help">Ưu tiên ảnh tải lên nếu có</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cắt ảnh (kéo 4 góc)</label>
                        <div style="width: 100%; max-width: 520px; height: 220px; background: var(--tertiary-bg); border: 1px dashed var(--border-color); border-radius: 8px; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                            <img id="bannerCropPreview" alt="Preview" style="max-width: 100%; display: none;">
                            <span id="bannerCropHint" style="color: var(--text-secondary);">Chọn ảnh để cắt</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Link (tùy chỉnh)</label>
                        <input type="url" class="form-control" name="link_url" id="bannerLinkUrl" placeholder="https://...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Thứ tự hiển thị</label>
                        <input type="number" class="form-control" name="sort_order" id="bannerSortOrder" value="0" min="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">
                            <input type="checkbox" name="is_active" id="bannerIsActive" checked> Hoạt động
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeBannerModal()">Hủy bỏ</button>
                <button type="button" class="btn btn-primary" onclick="saveBanner()">Lưu banner</button>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js" integrity="sha512-ODq1lDH7n9R1cN2p0V9bgu5pSBM7wGQwgdBDBR70BylZt0qkDFvB8s1R61U+ad22nM8a9o0q3dn4sKqH8G2Z8g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="../../public/assets/admin.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.nav-group-toggle').forEach(btn => {
                btn.addEventListener('click', () => {
                    const group = btn.closest('.nav-group');
                    const isOpen = group.classList.toggle('open');
                    btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                });
            });
        });

        const bannerModal = document.getElementById('bannerModal');
        const bannerForm = document.getElementById('bannerForm');
        const bannerImageFile = document.getElementById('bannerImageFile');
        const bannerImageUrl = document.getElementById('bannerImageUrl');
        const bannerCropPreview = document.getElementById('bannerCropPreview');
        const bannerCropHint = document.getElementById('bannerCropHint');
        let bannerCropper = null;
        let bannerCropSource = null;

        function destroyBannerCropper() {
            if (bannerCropper) {
                bannerCropper.destroy();
                bannerCropper = null;
            }
            bannerCropPreview.style.display = 'none';
            bannerCropHint.style.display = 'block';
            bannerCropSource = null;
        }

        function initBannerCropper() {
            if (!bannerCropPreview.src) {
                return;
            }
            if (bannerCropper) {
                bannerCropper.destroy();
            }
            bannerCropPreview.style.display = 'block';
            bannerCropHint.style.display = 'none';
            bannerCropper = new Cropper(bannerCropPreview, {
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 1,
                responsive: true,
                background: false,
                aspectRatio: 16 / 9
            });
        }

        function openAddBannerModal() {
            document.getElementById('bannerModalTitle').textContent = 'Thêm banner mới';
            bannerForm.reset();
            document.getElementById('bannerId').value = '';
            document.getElementById('bannerPosition').value = 'slide';
            document.getElementById('bannerSortOrder').value = '0';
            document.getElementById('bannerIsActive').checked = true;
            bannerCropPreview.src = '';
            destroyBannerCropper();
            bannerModal.classList.add('show');
        }

        function editBanner(button) {
            document.getElementById('bannerModalTitle').textContent = 'Cập nhật banner';
            bannerForm.reset();
            document.getElementById('bannerId').value = button.dataset.id || '';
            document.getElementById('bannerTitle').value = button.dataset.title || '';
            document.getElementById('bannerImageUrl').value = button.dataset.image || '';
            document.getElementById('bannerLinkUrl').value = button.dataset.link || '';
            document.getElementById('bannerPosition').value = button.dataset.position || 'slide';
            document.getElementById('bannerSortOrder').value = button.dataset.sort || '0';
            document.getElementById('bannerIsActive').checked = (button.dataset.active || '0') === '1';

            destroyBannerCropper();
            const previewUrl = button.dataset.image || '';
            bannerCropPreview.src = previewUrl;
            bannerCropPreview.crossOrigin = 'anonymous';
            bannerCropSource = previewUrl ? 'url' : null;
            if (previewUrl) {
                bannerCropPreview.onload = () => {
                    initBannerCropper();
                };
            }

            bannerModal.classList.add('show');
        }

        function closeBannerModal() {
            bannerModal.classList.remove('show');
            destroyBannerCropper();
        }

        bannerImageFile.addEventListener('change', (event) => {
            const file = event.target.files && event.target.files[0];
            if (!file) {
                return;
            }
            const reader = new FileReader();
            reader.onload = () => {
                bannerCropPreview.src = reader.result;
                bannerCropPreview.crossOrigin = 'anonymous';
                bannerCropSource = 'file';
                initBannerCropper();
            };
            reader.readAsDataURL(file);
        });

        bannerImageUrl.addEventListener('change', () => {
            const url = bannerImageUrl.value.trim();
            if (!url) {
                destroyBannerCropper();
                return;
            }
            bannerCropPreview.src = url;
            bannerCropPreview.crossOrigin = 'anonymous';
            bannerCropSource = 'url';
            bannerCropPreview.onload = () => {
                initBannerCropper();
            };
        });

        async function saveBanner() {
            if (!bannerForm.checkValidity()) {
                bannerForm.reportValidity();
                return;
            }

            const formData = new FormData(bannerForm);
            const id = formData.get('id');
            const action = id ? 'update' : 'create';

            if (bannerCropper) {
                try {
                    const canvas = bannerCropper.getCroppedCanvas({
                        width: 1280,
                        height: 720
                    });
                    const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.92));
                    if (!blob) {
                        showError('Không thể cắt ảnh từ URL do hạn chế CORS. Hãy tải ảnh lên thay vì URL.');
                        return;
                    }
                    formData.set('image_file', blob, 'banner.jpg');
                    formData.set('image_url', '');
                } catch (error) {
                    showError('Không thể cắt ảnh, vui lòng thử lại');
                    return;
                }
            }

            try {
                const response = await fetch(`../../app/Controllers/AdminBannerController.php?action=${action}`, {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    showSuccess(result.message || 'Lưu banner thành công');
                    setTimeout(() => window.location.reload(), 400);
                } else {
                    showError(result.message || 'Không thể lưu banner');
                }
            } catch (error) {
                showError('Có lỗi xảy ra, vui lòng thử lại');
            }
        }

        async function deleteBanner(id) {
            if (!confirm('Bạn có chắc chắn muốn xóa banner này?')) {
                return;
            }
            const formData = new FormData();
            formData.append('id', id);

            try {
                const response = await fetch('../../app/Controllers/AdminBannerController.php?action=delete', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    showSuccess(result.message || 'Xóa banner thành công');
                    setTimeout(() => window.location.reload(), 400);
                } else {
                    showError(result.message || 'Không thể xóa banner');
                }
            } catch (error) {
                showError('Có lỗi xảy ra, vui lòng thử lại');
            }
        }
    </script>
    <script src="../../public/assets/js/banner.js"></script>
</body>
</html>
