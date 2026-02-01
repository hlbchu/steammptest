<?php
/**
 * ADMIN PRODUCTS - Quản lý sản phẩm
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

// Get search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$filter_purchase_type = isset($_GET['purchase_type']) ? (int)$_GET['purchase_type'] : 0;
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build WHERE clause
$where_conditions = [];
$where_params = [];

if (!empty($search)) {
    $where_conditions[] = "(p.name LIKE :search OR p.description LIKE :search)";
    $where_params[':search'] = '%' . $search . '%';
}

if ($filter_category > 0) {
    $where_conditions[] = "p.id IN (SELECT product_id FROM product_categories WHERE category_id = :category)";
    $where_params[':category'] = $filter_category;
}

if ($filter_purchase_type > 0) {
    $where_conditions[] = "p.purchase_type_id = :purchase_type";
    $where_params[':purchase_type'] = $filter_purchase_type;
}

if (!empty($filter_status)) {
    $where_conditions[] = "p.status = :status";
    $where_params[':status'] = $filter_status;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Count total products
$count_query = "SELECT COUNT(*) as total FROM products p " . $where_clause;
$db->query($count_query);
foreach ($where_params as $key => $value) {
    $db->bind($key, $value);
}
$total_result = $db->fetch();
$total_products = $total_result['total'];
$total_pages = ceil($total_products / $per_page);

// Get products with filters
$query = "SELECT p.*, c.name as category_name, pt.name as purchase_type_name
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id
          LEFT JOIN purchase_types pt ON p.purchase_type_id = pt.id
          " . $where_clause . "
          ORDER BY p.created_at DESC
          LIMIT :limit OFFSET :offset";

$db->query($query);
foreach ($where_params as $key => $value) {
    $db->bind($key, $value);
}
$db->bind(':limit', $per_page);
$db->bind(':offset', $offset);
$products = $db->fetchAll();

// Load categories for each product
foreach ($products as &$product) {
    $db->query("SELECT category_id FROM product_categories WHERE product_id = :product_id");
    $db->bind(':product_id', $product['id']);
    $result = $db->fetchAll();
    $product['category_ids'] = array_column($result, 'category_id');
}
unset($product);

// Get all categories for dropdown
$db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");
$categories = $db->fetchAll();

// Get all purchase types for dropdown
$db->query("SELECT * FROM purchase_types WHERE is_active = 1 ORDER BY name");
$purchaseTypes = $db->fetchAll();

// Build query string for pagination
function buildPaginationQuery($page, $search, $filter_category, $filter_purchase_type, $filter_status) {
    $params = ['page' => $page];
    if (!empty($search)) $params['search'] = $search;
    if ($filter_category > 0) $params['category'] = $filter_category;
    if ($filter_purchase_type > 0) $params['purchase_type'] = $filter_purchase_type;
    if (!empty($filter_status)) $params['status'] = $filter_status;
    return '?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý sản phẩm - Admin</title>
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
            <h1>Quản lý sản phẩm</h1>
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <form method="GET" style="display: flex; gap: 10px; flex-wrap: wrap; flex: 1;">
                    <input type="text" name="search" placeholder="Tìm kiếm sản phẩm..." value="<?php echo htmlspecialchars($search); ?>" style="padding: 10px 14px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--secondary-bg); color: var(--text-primary); min-width: 250px; flex: 1;">
                    
                    <select name="category" style="padding: 10px 14px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--secondary-bg); color: var(--text-primary); cursor: pointer;">
                        <option value="0">Tất cả danh mục</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $filter_category == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="purchase_type" style="padding: 10px 14px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--secondary-bg); color: var(--text-primary); cursor: pointer;">
                        <option value="0">Tất cả loại mua</option>
                        <?php foreach ($purchaseTypes as $pt): ?>
                        <option value="<?php echo $pt['id']; ?>" <?php echo $filter_purchase_type == $pt['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($pt['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="status" style="padding: 10px 14px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--secondary-bg); color: var(--text-primary); cursor: pointer;">
                        <option value="">Tất cả trạng thái</option>
                        <option value="active" <?php echo $filter_status === 'active' ? 'selected' : ''; ?>>Hoạt động</option>
                        <option value="hidden" <?php echo $filter_status === 'hidden' ? 'selected' : ''; ?>>Ẩn</option>
                    </select>
                    
                    <button type="submit" class="btn-primary" style="padding: 10px 20px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 6px;">
                            <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
                        </svg>
                        Lọc
                    </button>
                    <?php if (!empty($search) || $filter_category > 0 || $filter_purchase_type > 0 || !empty($filter_status)): ?>
                    <a href="products.php" class="btn-secondary" style="padding: 10px 20px; text-decoration: none; border-radius: 8px; display: inline-flex; align-items: center;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 6px;">
                            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                        </svg>
                        Xóa lọc
                    </a>
                    <?php endif; ?>
                </form>
                <button class="btn-primary" onclick="openProductModal()" style="white-space: nowrap;">
                    <svg width="18" height="18" viewBox="0 0 24 24">
                        <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" fill="currentColor"/>
                    </svg>
                    Thêm sản phẩm
                </button>
            </div>
        </div>

        <!-- Products Table -->
        <div class="content-card">
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Hình ảnh</th>
                            <th>Tên sản phẩm</th>
                            <th>Danh mục</th>
                            <th>Loại mua</th>
                            <th>Giá</th>
                            <th>Giá sale</th>
                            <th>Badge</th>
                            <th>Rating</th>
                            <th>Trạng thái</th>
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
                                    // Check if image is URL or filename
                                    $imageSrc = (filter_var($product['image'], FILTER_VALIDATE_URL)) 
                                        ? $product['image'] 
                                        : '../../public/assets/images/' . $product['image'];
                                    ?>
                                    <img src="<?php echo htmlspecialchars($imageSrc); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         style="width: 60px; height: 40px; object-fit: cover; border-radius: 4px;"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div style="width: 60px; height: 40px; background: rgba(0,0,0,0.3); border-radius: 4px; display: none; align-items: center; justify-content: center; font-size: 10px;">No img</div>
                                <?php else: ?>
                                    <div style="width: 60px; height: 40px; background: rgba(0,0,0,0.3); border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 10px;">No img</div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td>
                                <div class="multi-select-wrapper">
                                    <button class="multi-select-trigger" onclick="toggleMultiSelect(event, <?php echo $product['id']; ?>)">
                                        <span class="selected-count">
                                            <?php 
                                            $count = count($product['category_ids']);
                                            echo $count > 0 ? $count . ' danh mục' : 'Chọn danh mục';
                                            ?>
                                        </span>
                                        <span class="dropdown-arrow">▼</span>
                                    </button>
                                    <div class="multi-select-dropdown" id="category-dropdown-<?php echo $product['id']; ?>">
                                        <?php foreach ($categories as $cat): ?>
                                        <label class="multi-select-option">
                                            <input type="checkbox" 
                                                   value="<?php echo $cat['id']; ?>"
                                                   <?php echo in_array($cat['id'], $product['category_ids']) ? 'checked' : ''; ?>
                                                   onchange="updateCategories(<?php echo $product['id']; ?>)">
                                            <span><?php echo htmlspecialchars($cat['name']); ?></span>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <select class="quick-edit-select" onchange="quickUpdateField(<?php echo $product['id']; ?>, 'purchase_type_id', this.value)">
                                    <option value="">- Chọn -</option>
                                    <?php foreach ($purchaseTypes as $type): ?>
                                    <option value="<?php echo $type['id']; ?>" <?php echo ($product['purchase_type_id'] == $type['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><?php echo number_format($product['price'], 0, ',', '.'); ?>₫</td>
                            <td>
                                <?php if ($product['sale_price']): ?>
                                    <?php echo number_format($product['sale_price'], 0, ',', '.'); ?>₫
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($product['badge'] ?? '-'); ?></td>
                            <td>
                                <?php if ($product['rating']): ?>
                                    ⭐ <?php echo $product['rating']; ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $product['status']; ?>">
                                    <?php echo $product['status'] === 'active' ? 'Hoạt động' : 'Ẩn'; ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 5px;">
                                    <button class="btn-action btn-edit" onclick='editProduct(<?php echo json_encode($product); ?>)' title="Sửa">
                                        <svg width="16" height="16" viewBox="0 0 24 24">
                                            <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z" fill="currentColor"/>
                                        </svg>
                                    </button>
                                    <button class="btn-action btn-delete" onclick="deleteProduct(<?php echo $product['id']; ?>)" title="Xóa">
                                        <svg width="16" height="16" viewBox="0 0 24 24">
                                            <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-9l-1 1H5v2h14V4z" fill="currentColor"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="10" style="text-align: center; color: var(--text-secondary); padding: 40px;">
                                Chưa có sản phẩm nào
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="<?php echo buildPaginationQuery($page - 1, $search, $filter_category, $filter_purchase_type, $filter_status); ?>" class="pagination-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/>
                        </svg>
                        Trước
                    </a>
                <?php endif; ?>

                <div class="pagination-numbers">
                    <?php
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);
                    
                    if ($start > 1) {
                        echo '<a href="' . buildPaginationQuery(1, $search, $filter_category, $filter_purchase_type, $filter_status) . '" class="pagination-number">1</a>';
                        if ($start > 2) echo '<span class="pagination-dots">...</span>';
                    }
                    
                    for ($i = $start; $i <= $end; $i++) {
                        $active = $i == $page ? 'active' : '';
                        echo '<a href="' . buildPaginationQuery($i, $search, $filter_category, $filter_purchase_type, $filter_status) . '" class="pagination-number ' . $active . '">' . $i . '</a>';
                    }
                    
                    if ($end < $total_pages) {
                        if ($end < $total_pages - 1) echo '<span class="pagination-dots">...</span>';
                        echo '<a href="' . buildPaginationQuery($total_pages, $search, $filter_category, $filter_purchase_type, $filter_status) . '" class="pagination-number">' . $total_pages . '</a>';
                    }
                    ?>
                </div>

                <?php if ($page < $total_pages): ?>
                    <a href="<?php echo buildPaginationQuery($page + 1, $search, $filter_category, $filter_purchase_type, $filter_status); ?>" class="pagination-btn">
                        Sau
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/>
                        </svg>
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Product Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Thêm sản phẩm</h2>
                <button class="btn-close" onclick="closeProductModal()">×</button>
            </div>
            <form id="productForm" class="modal-body">
                <input type="hidden" id="productId" name="id">
                
                <div class="form-grid">
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Tên sản phẩm *</label>
                        <input type="text" id="productName" name="name" required>
                    </div>

                    <div class="form-group">
                        <label>Danh mục</label>
                        <select id="productCategory" name="category_id">
                            <option value="">Chọn danh mục</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Loại mua</label>
                        <select id="productPurchaseType" name="purchase_type_id">
                            <option value="">Chọn loại mua</option>
                            <?php foreach ($purchaseTypes as $type): ?>
                            <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Giá *</label>
                        <input type="number" id="productPrice" name="price" required min="0" step="1000">
                    </div>

                    <div class="form-group">
                        <label>Giá sale</label>
                        <input type="number" id="productSalePrice" name="sale_price" min="0" step="1000">
                    </div>

                    <div class="form-group">
                        <label>Badge</label>
                        <input type="text" id="productBadge" name="badge" placeholder="VD: -20%, HOT, NEW">
                    </div>

                    <div class="form-group">
                        <label>Rating</label>
                        <input type="number" id="productRating" name="rating" min="0" max="5" step="0.1">
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Link hình ảnh</label>
                        <input type="url" id="productImage" name="image" placeholder="Nhập URL hình ảnh (VD: https://cdn.cloudflare.steamstatic.com/steam/apps/730/header.jpg)">
                        <small style="color: var(--text-secondary); font-size: 12px; margin-top: 5px; display: block;">Dán link URL đầy đủ của hình ảnh từ internet</small>
                        <div id="imagePreview" style="margin-top: 10px; display: none;">
                            <img id="previewImg" src="" alt="Preview" style="max-width: 100%; max-height: 200px; border-radius: 4px; border: 1px solid rgba(255,255,255,0.1);">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Trạng thái</label>
                        <select id="productStatus" name="status">
                            <option value="active">Hoạt động</option>
                            <option value="hidden">Ẩn</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Mô tả ngắn</label>
                    <textarea id="productDescription" name="description" rows="4" placeholder="Mô tả ngắn về sản phẩm"></textarea>
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                    <label>Nội dung nhận được sau khi mua</label>
                    <textarea id="productReceivedContent" name="received_content" rows="4" placeholder="Ví dụ: tài khoản game, key kích hoạt, hướng dẫn nhận game, link tải, v.v..."></textarea>
                    <small style="color: var(--text-secondary); font-size: 12px; margin-top: 5px; display: block;">
                        Nội dung này sẽ hiển thị cho khách sau khi mua.
                    </small>
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                    <label>Nội dung chi tiết (Hành trình, tính năng, hình ảnh, video...)</label>
                    <textarea id="productContent" name="content" rows="6" placeholder="Viết nội dung chi tiết về game - Có thể chứa HTML để định dạng"></textarea>
                    <small style="color: var(--text-secondary); font-size: 12px; margin-top: 5px; display: block;">
                        Hỗ trợ HTML tags: &lt;p&gt;, &lt;h3&gt;, &lt;ul&gt;, &lt;li&gt;, &lt;img&gt;, &lt;br&gt;, etc.
                    </small>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeProductModal()">Hủy</button>
                    <button type="submit" class="btn-primary">Lưu sản phẩm</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../public/assets/js/admin-products.js"></script>
    <style>
        /* Content Card - Allow overflow for dropdowns */
        .content-card {
            overflow: visible !important;
        }

        /* Enhanced Table Container with Scrolling */
        .table-container {
            overflow-x: auto;
            overflow-y: visible;
            position: relative;
            border-radius: 8px;
        }

        /* Table cells need position relative for dropdown positioning */
        .data-table tbody td {
            position: relative;
        }

        .table-container::-webkit-scrollbar {
            height: 8px;
        }

        .table-container::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 4px;
        }

        .table-container::-webkit-scrollbar-thumb {
            background: rgba(var(--accent-rgb), 0.5);
            border-radius: 4px;
        }

        .table-container::-webkit-scrollbar-thumb:hover {
            background: var(--accent-color);
        }

        /* Enhanced Table Row Hover - Màu xanh khi di chuột vào */
        .data-table tbody tr {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
        }

        .data-table tbody tr:hover {
            background: linear-gradient(90deg, rgba(124, 58, 237, 0.15), rgba(102, 126, 234, 0.1)) !important;
            transform: translateX(4px);
            box-shadow: -4px 0 0 0 var(--accent-color), 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .data-table tbody tr:hover td {
            color: var(--text-primary);
        }

        /* Enhanced Add Product Button */
        .btn-primary {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #7c3aed, #6366f1, #667eea);
            background-size: 200% 200%;
            animation: gradientShift 3s ease infinite;
            box-shadow: 0 4px 15px rgba(124, 58, 237, 0.4);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .btn-primary:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 8px 25px rgba(124, 58, 237, 0.6);
        }

        .btn-primary:active {
            transform: translateY(0) scale(0.98);
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn-primary:hover::before {
            width: 300px;
            height: 300px;
        }

        /* Enhanced Pagination Styles */
        .pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 32px;
            padding: 24px 0;
            flex-wrap: wrap;
        }

        .pagination-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background: linear-gradient(135deg, var(--secondary-bg), rgba(var(--accent-rgb), 0.05));
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
        }

        .pagination-btn:hover {
            background: linear-gradient(135deg, var(--accent-color), #667eea);
            border-color: var(--accent-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(var(--accent-rgb), 0.4);
        }

        .pagination-btn svg {
            transition: transform 0.3s ease;
        }

        .pagination-btn:hover svg {
            transform: scale(1.2);
        }

        .pagination-numbers {
            display: flex;
            gap: 6px;
            align-items: center;
            flex-wrap: wrap;
        }

        .pagination-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            height: 40px;
            padding: 0 12px;
            background: var(--secondary-bg);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .pagination-number:hover {
            background: rgba(var(--accent-rgb), 0.2);
            border-color: var(--accent-color);
            color: var(--accent-color);
            transform: translateY(-2px);
        }

        .pagination-number.active {
            background: linear-gradient(135deg, var(--accent-color), #667eea);
            border-color: var(--accent-color);
            color: white;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(var(--accent-rgb), 0.4);
            transform: scale(1.1);
        }

        .pagination-dots {
            padding: 0 8px;
            color: var(--text-secondary);
            font-weight: bold;
        }

        /* Multi-select dropdown styles */
        .multi-select-wrapper {
            position: relative;
            min-width: 150px;
            max-width: 200px;
            z-index: 1;
        }

        .multi-select-wrapper.open {
            z-index: 100000;
        }

        /* Backdrop overlay khi dropdown mở - ngăn hover vào table */
        .multi-select-wrapper.open::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(2px);
            z-index: 99998;
            animation: fadeIn 0.2s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Đảm bảo trigger và dropdown nằm trên backdrop */
        .multi-select-wrapper.open .multi-select-trigger,
        .multi-select-wrapper.open .multi-select-dropdown {
            position: relative;
            z-index: 99999;
        }

        .multi-select-trigger {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            width: 100%;
            padding: 10px 14px;
            background: linear-gradient(135deg, var(--secondary-bg), rgba(var(--accent-rgb), 0.03));
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .multi-select-trigger:hover {
            border-color: var(--accent-color);
            background: linear-gradient(135deg, rgba(var(--accent-rgb), 0.1), rgba(var(--accent-rgb), 0.05));
            box-shadow: 0 4px 8px rgba(var(--accent-rgb), 0.2);
            transform: translateY(-1px);
        }

        .multi-select-trigger:active {
            transform: translateY(0);
        }

        .selected-count {
            flex: 1;
            text-align: left;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: var(--accent-color);
        }

        .dropdown-arrow {
            font-size: 10px;
            transition: transform 0.3s ease;
            color: var(--text-secondary);
        }

        .multi-select-wrapper.open .dropdown-arrow {
            transform: rotate(180deg);
            color: var(--accent-color);
        }

        .multi-select-wrapper.open .multi-select-trigger {
            border-color: var(--accent-color);
            background: linear-gradient(135deg, rgba(var(--accent-rgb), 0.15), rgba(var(--accent-rgb), 0.08));
        }

        .multi-select-dropdown {
            display: none;
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            right: 0;
            max-height: 280px;
            overflow-y: auto;
            background: linear-gradient(145deg, #1a1d2e, #161929);
            border: 1px solid var(--accent-color);
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(var(--accent-rgb), 0.2);
            z-index: 10000;
            animation: slideDown 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-12px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .multi-select-wrapper.open .multi-select-dropdown {
            display: block;
        }

        .multi-select-option {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            user-select: none;
            border-left: 3px solid transparent;
        }

        .multi-select-option:hover {
            background: linear-gradient(90deg, rgba(var(--accent-rgb), 0.2), rgba(var(--accent-rgb), 0.1));
            border-left-color: var(--accent-color);
            padding-left: 16px;
        }

        .multi-select-option:first-child {
            border-top-left-radius: 6px;
            border-top-right-radius: 6px;
        }

        .multi-select-option:last-child {
            border-bottom-left-radius: 6px;
            border-bottom-right-radius: 6px;
        }

        .multi-select-option input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--accent-color);
            border-radius: 4px;
        }

        .multi-select-option span {
            flex: 1;
            font-size: 13px;
            color: var(--text-primary);
            font-weight: 500;
        }

        .multi-select-option input[type="checkbox"]:checked + span {
            color: var(--accent-color);
            font-weight: 600;
        }

        /* Single select styles */
        .quick-edit-select {
            padding: 10px 14px;
            background: linear-gradient(135deg, var(--secondary-bg), rgba(var(--accent-rgb), 0.03));
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            min-width: 160px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .quick-edit-select:hover {
            border-color: var(--accent-color);
            background: linear-gradient(135deg, rgba(var(--accent-rgb), 0.1), rgba(var(--accent-rgb), 0.05));
            box-shadow: 0 4px 8px rgba(var(--accent-rgb), 0.2);
            transform: translateY(-1px);
        }

        .quick-edit-select:focus {
            outline: none;
            border-color: var(--accent-color);
            background: linear-gradient(135deg, rgba(var(--accent-rgb), 0.15), rgba(var(--accent-rgb), 0.08));
            box-shadow: 0 0 0 3px rgba(var(--accent-rgb), 0.2), 0 4px 12px rgba(var(--accent-rgb), 0.3);
        }

        .quick-edit-select option {
            background: var(--secondary-bg);
            color: var(--text-primary);
            padding: 10px;
        }

        .quick-edit-select option:hover {
            background: var(--accent-color);
        }

        /* Custom scrollbar for dropdown */
        .multi-select-dropdown::-webkit-scrollbar {
            width: 8px;
        }

        .multi-select-dropdown::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 4px;
            margin: 4px;
        }

        .multi-select-dropdown::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, var(--accent-color), rgba(var(--accent-rgb), 0.6));
            border-radius: 4px;
            border: 2px solid transparent;
            background-clip: padding-box;
        }

        .multi-select-dropdown::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, #667eea, var(--accent-color));
        }
    </style>
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
