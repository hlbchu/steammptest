<?php
/**
 * ADMIN PRODUCT CONTENT - Chỉnh nội dung chi tiết game
 */
session_start();

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
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_content = isset($_GET['content']) ? $_GET['content'] : '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build WHERE clause
$where_conditions = [];
$where_params = [];

if (!empty($search)) {
    $where_conditions[] = "(p.name LIKE :search OR p.description LIKE :search OR p.content LIKE :search)";
    $where_params[':search'] = '%' . $search . '%';
}

if ($filter_category > 0) {
    $where_conditions[] = "p.id IN (SELECT product_id FROM product_categories WHERE category_id = :category)";
    $where_params[':category'] = $filter_category;
}

if (!empty($filter_status)) {
    $where_conditions[] = "p.status = :status";
    $where_params[':status'] = $filter_status;
}

if ($filter_content === 'has_content') {
    $where_conditions[] = "(p.content IS NOT NULL AND p.content != '')";
} elseif ($filter_content === 'no_content') {
    $where_conditions[] = "(p.content IS NULL OR p.content = '')";
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

// Get products for current page
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          " . $where_clause . "
          ORDER BY p.updated_at DESC
          LIMIT :limit OFFSET :offset";

$db->query($query);
foreach ($where_params as $key => $value) {
    $db->bind($key, $value);
}
$db->bind(':limit', $per_page);
$db->bind(':offset', $offset);
$products = $db->fetchAll();

// Get all categories for dropdown
$db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");
$categories = $db->fetchAll();

// Build query string for pagination
function buildPaginationQuery($page, $search, $filter_category, $filter_status, $filter_content) {
    $params = ['page' => $page];
    if (!empty($search)) $params['search'] = $search;
    if ($filter_category > 0) $params['category'] = $filter_category;
    if (!empty($filter_status)) $params['status'] = $filter_status;
    if (!empty($filter_content)) $params['content'] = $filter_content;
    return '?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nội dung game - Admin</title>
    <link rel="stylesheet" href="../../public/assets/css/base.css">
    <link rel="stylesheet" href="../../public/assets/css/admin.css">
    <link rel="stylesheet" href="../../public/assets/css/notification.css">
    <style>
        /* Pagination Styles */
        .pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 24px;
            padding: 20px 0;
        }

        .pagination-btn,
        .pagination-numbers a {
            padding: 8px 16px;
            background: var(--secondary-bg);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .pagination-btn:hover,
        .pagination-numbers a:hover {
            background: rgba(var(--accent-rgb), 0.1);
            border-color: var(--accent-color);
            transform: translateY(-1px);
        }

        .pagination-numbers {
            display: flex;
            gap: 4px;
            align-items: center;
        }

        .pagination-numbers a.active {
            background: linear-gradient(135deg, var(--accent-color), #667eea);
            border-color: var(--accent-color);
            color: white;
            font-weight: 600;
        }

        .pagination-ellipsis {
            padding: 8px 4px;
            color: var(--text-secondary);
        }

        /* Enhanced Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
            animation: fadeIn 0.2s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            position: relative;
            background: linear-gradient(145deg, #1a1d2e, #161929);
            margin: 2% auto;
            padding: 0;
            border: 1px solid rgba(124, 58, 237, 0.3);
            border-radius: 12px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px 30px;
            border-bottom: 1px solid var(--border-color);
            background: linear-gradient(135deg, rgba(124, 58, 237, 0.1), rgba(102, 126, 234, 0.05));
        }

        .modal-header h2 {
            margin: 0;
            font-size: 22px;
            font-weight: 600;
            background: linear-gradient(135deg, var(--accent-color), #667eea);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .btn-close {
            background: transparent;
            border: none;
            color: var(--text-secondary);
            font-size: 32px;
            cursor: pointer;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .btn-close:hover {
            background: rgba(255, 59, 48, 0.2);
            color: #ff3b30;
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 30px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-size: 14px;
            font-weight: 500;
            color: var(--text-primary);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px 16px;
            background: var(--secondary-bg);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s ease;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--accent-color);
            background: rgba(var(--accent-rgb), 0.05);
            box-shadow: 0 0 0 3px rgba(var(--accent-rgb), 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }

        .btn-primary,
        .btn-secondary {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent-color), #667eea);
            color: white;
            box-shadow: 0 4px 12px rgba(var(--accent-rgb), 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(var(--accent-rgb), 0.4);
        }

        .btn-secondary {
            background: var(--secondary-bg);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.2);
        }

        /* Custom scrollbar for modal */
        .modal-content::-webkit-scrollbar {
            width: 8px;
        }

        .modal-content::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 4px;
        }

        .modal-content::-webkit-scrollbar-thumb {
            background: rgba(var(--accent-rgb), 0.5);
            border-radius: 4px;
        }

        .modal-content::-webkit-scrollbar-thumb:hover {
            background: var(--accent-color);
        }

        /* Enhanced table styles with scrolling */
        .table-container {
            overflow-x: auto;
            overflow-y: visible;
            position: relative;
            border-radius: 8px;
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

        .btn-edit {
            background: linear-gradient(135deg, var(--accent-color), #667eea);
            padding: 10px 18px;
            border-radius: 8px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(var(--accent-rgb), 0.3);
        }

        .btn-edit:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 6px 20px rgba(var(--accent-rgb), 0.5);
        }

        /* Enhanced Pagination */
        .pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 32px;
            padding: 24px 0;
            flex-wrap: wrap;
        }

        .pagination-btn,
        .pagination-numbers a {
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
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .pagination-btn:hover,
        .pagination-numbers a:hover {
            background: linear-gradient(135deg, var(--accent-color), #667eea);
            border-color: var(--accent-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(var(--accent-rgb), 0.4);
        }

        .pagination-numbers {
            display: flex;
            gap: 6px;
            align-items: center;
            flex-wrap: wrap;
        }

        .pagination-numbers a.active {
            background: linear-gradient(135deg, var(--accent-color), #667eea);
            border-color: var(--accent-color);
            color: white;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(var(--accent-rgb), 0.4);
            transform: scale(1.1);
        }

        .pagination-ellipsis {
            padding: 10px 8px;
            color: var(--text-secondary);
            font-weight: bold;
        }
    </style>
</head>
<body class="admin-body">
    <!-- Admin Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="admin-content">
        <div class="content-header" style="margin-bottom: 24px;">
            <h1 style="margin-bottom: 16px;">Nội dung chi tiết game</h1>
            <form method="GET" style="display: flex; gap: 10px; flex-wrap: wrap; width: 100%;">
                <input type="text" name="search" placeholder="Tìm kiếm sản phẩm..." value="<?php echo htmlspecialchars($search); ?>" style="flex: 1; min-width: 250px; padding: 10px 14px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--secondary-bg); color: var(--text-primary);">
                
                <select name="category" style="padding: 10px 14px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--secondary-bg); color: var(--text-primary); cursor: pointer;">
                    <option value="0">Tất cả danh mục</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $filter_category == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                
                <select name="status" style="padding: 10px 14px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--secondary-bg); color: var(--text-primary); cursor: pointer;">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active" <?php echo $filter_status === 'active' ? 'selected' : ''; ?>>Hoạt động</option>
                    <option value="hidden" <?php echo $filter_status === 'hidden' ? 'selected' : ''; ?>>Ẩn</option>
                </select>
                
                <select name="content" style="padding: 10px 14px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--secondary-bg); color: var(--text-primary); cursor: pointer;">
                    <option value="">Tất cả nội dung</option>
                    <option value="has_content" <?php echo $filter_content === 'has_content' ? 'selected' : ''; ?>>Đã có nội dung</option>
                    <option value="no_content" <?php echo $filter_content === 'no_content' ? 'selected' : ''; ?>>Chưa có nội dung</option>
                </select>
                
                <button type="submit" class="btn-primary" style="padding: 10px 20px; display: inline-flex; align-items: center;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 6px;">
                        <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
                    </svg>
                    Lọc
                </button>
                
                <?php if (!empty($search) || $filter_category > 0 || !empty($filter_status) || !empty($filter_content)): ?>
                <a href="product-content.php" class="btn-secondary" style="padding: 10px 20px; text-decoration: none; border-radius: 8px; display: inline-flex; align-items: center;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 6px;">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                    </svg>
                    Xóa lọc
                </a>
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
                            <th>Trạng thái</th>
                            <th>Nội dung</th>
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
                            <td>
                                <span class="status-badge status-<?php echo $product['status']; ?>">
                                    <?php echo $product['status'] === 'active' ? 'Hoạt động' : 'Ẩn'; ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($product['content'])): ?>
                                    <span style="color: var(--success);">Đã có</span>
                                <?php else: ?>
                                    <span style="color: var(--text-secondary);">Chưa có</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn-action btn-edit" onclick='editProduct(<?php echo json_encode($product); ?>)' title="Sửa nội dung">Sửa nội dung</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-secondary); padding: 40px;">
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
                    <a href="<?php echo buildPaginationQuery($page - 1, $search, $filter_category, $filter_status, $filter_content); ?>" class="pagination-btn">← Trước</a>
                <?php endif; ?>

                <div class="pagination-numbers">
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    if ($start_page > 1) {
                        echo '<a href="' . buildPaginationQuery(1, $search, $filter_category, $filter_status, $filter_content) . '">1</a>';
                        if ($start_page > 2) {
                            echo '<span class="pagination-ellipsis">...</span>';
                        }
                    }
                    
                    for ($i = $start_page; $i <= $end_page; $i++) {
                        $active = $i === $page ? 'active' : '';
                        echo '<a href="' . buildPaginationQuery($i, $search, $filter_category, $filter_status, $filter_content) . '" class="' . $active . '">' . $i . '</a>';
                    }
                    
                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) {
                            echo '<span class="pagination-ellipsis">...</span>';
                        }
                        echo '<a href="' . buildPaginationQuery($total_pages, $search, $filter_category, $filter_status, $filter_content) . '">' . $total_pages . '</a>';
                    }
                    ?>
                </div>

                <?php if ($page < $total_pages): ?>
                    <a href="<?php echo buildPaginationQuery($page + 1, $search, $filter_category, $filter_status, $filter_content); ?>" class="pagination-btn">Sau →</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Product Modal (Reuse full modal for edit) -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Sửa nội dung game</h2>
                <button class="btn-close" onclick="closeProductModal()">×</button>
            </div>
            <form id="productForm" class="modal-body">
                <input type="hidden" id="productId" name="id">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Tên sản phẩm *</label>
                        <input type="text" id="productName" name="name" required>
                    </div>

                    <div class="form-group">
                        <label>Danh mục *</label>
                        <select id="productCategory" name="category_id" required>
                            <option value="">Chọn danh mục</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
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
                        <input type="url" id="productImage" name="image" placeholder="Nhập URL hình ảnh">
                        <div id="imagePreview" style="margin-top: 10px; display: none;">
                            <img id="previewImg" src="" alt="Preview" style="max-width: 100%; max-height: 200px; border-radius: 4px; border: 1px solid rgba(255,255,255,0.1);">
                        </div>
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Ảnh gallery (nhiều ảnh)</label>
                        <textarea id="productGallery" name="gallery" rows="3" placeholder="Mỗi dòng 1 URL ảnh hoặc cách nhau bằng dấu phẩy"></textarea>
                        <small style="color: var(--text-secondary); font-size: 12px; margin-top: 5px; display: block;">Ví dụ: https://.../1.jpg\nhttps://.../2.jpg</small>
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Video (YouTube URL)</label>
                        <input type="url" id="productVideo" name="video_url" placeholder="https://www.youtube.com/watch?v=...">
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
                    <textarea id="productDescription" name="description" rows="4"></textarea>
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                    <label>Nội dung chi tiết (Hành trình, tính năng, hình ảnh, video...)</label>
                    <textarea id="productContent" name="content" rows="6" placeholder="Viết nội dung chi tiết về game - Có thể chứa HTML để định dạng"></textarea>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeProductModal()">Hủy</button>
                    <button type="submit" class="btn-primary">Lưu nội dung</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../public/assets/js/admin-products.js"></script>
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
