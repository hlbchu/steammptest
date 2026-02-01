<?php
/**
 * ADMIN PURCHASE TYPES - Quản lý loại mua
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

// Get all purchase types
$db->query("SELECT * FROM purchase_types ORDER BY name ASC");
$purchaseTypes = $db->fetchAll();

// Count products using each type
foreach ($purchaseTypes as &$type) {
    $db->query("SELECT COUNT(*) as count FROM products WHERE purchase_type_id = :id");
    $db->bind(':id', $type['id']);
    $result = $db->fetch();
    $type['product_count'] = $result['count'];
}
unset($type);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Loại mua - Admin</title>
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
            <h1>Quản lý Loại mua</h1>
            <button class="btn-primary" onclick="openModal()">
                <svg width="18" height="18" viewBox="0 0 24 24">
                    <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" fill="currentColor"/>
                </svg>
                Thêm loại mua
            </button>
        </div>

        <!-- Purchase Types Table -->
        <div class="content-card">
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên loại mua</th>
                            <th>Slug</th>
                            <th>Mô tả</th>
                            <th>Sản phẩm</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($purchaseTypes as $type): ?>
                        <tr>
                            <td>#<?php echo $type['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($type['name']); ?></strong></td>
                            <td><code style="background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 3px; font-size: 12px;"><?php echo htmlspecialchars($type['slug']); ?></code></td>
                            <td><?php echo htmlspecialchars($type['description'] ?? '-'); ?></td>
                            <td>
                                <span class="product-count-badge">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M20 6h-2.18c.11-.31.18-.65.18-1 0-1.66-1.34-3-3-3-1.05 0-1.96.54-2.5 1.35l-.5.67-.5-.68C10.96 2.54 10.05 2 9 2 7.34 2 6 3.34 6 5c0 .35.07.69.18 1H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-5-2c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zM9 4c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm11 15H4v-2h16v2zm0-5H4V8h5.08L7 10.83 8.62 12 11 8.76l1-1.36 1 1.36L15.38 12 17 10.83 14.92 8H20v6z"/>
                                    </svg>
                                    <?php echo $type['product_count']; ?> sản phẩm
                                </span>
                            </td>
                            <td>
                                <label class="switch">
                                    <input type="checkbox" 
                                           <?php echo $type['is_active'] ? 'checked' : ''; ?>
                                           onchange="toggleActive(<?php echo $type['id']; ?>, this.checked ? 1 : 0)">
                                    <span class="slider"></span>
                                </label>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($type['created_at'])); ?></td>
                            <td>
                                <div style="display: flex; gap: 5px;">
                                    <button class="btn-action btn-edit" onclick='editPurchaseType(<?php echo json_encode($type); ?>)' title="Sửa">
                                        <svg width="16" height="16" viewBox="0 0 24 24">
                                            <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z" fill="currentColor"/>
                                        </svg>
                                    </button>
                                    <button class="btn-action btn-delete" onclick="deletePurchaseType(<?php echo $type['id']; ?>)" title="Xóa">
                                        <svg width="16" height="16" viewBox="0 0 24 24">
                                            <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z" fill="currentColor"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal -->
    <div id="purchaseTypeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Thêm loại mua</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form id="purchaseTypeForm">
                <input type="hidden" id="purchaseTypeId" name="id">
                
                <div class="form-grid">
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Tên loại mua *</label>
                        <input type="text" id="purchaseTypeName" name="name" required placeholder="VD: Game Steam Offline, Game ROM">
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Mô tả</label>
                        <textarea id="purchaseTypeDescription" name="description" rows="3" placeholder="Mô tả về loại mua này..."></textarea>
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="checkbox-label">
                            <input type="checkbox" id="purchaseTypeActive" name="is_active" checked>
                            <span>Kích hoạt</span>
                        </label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal()">Hủy</button>
                    <button type="submit" class="btn-primary">Lưu</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../public/assets/js/admin-purchase-types.js"></script>
    <style>
        /* Purchase Types Page Custom Styles */
        .content-header {
            margin-bottom: 24px;
        }

        .content-header h1 {
            font-size: 28px;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }

        /* Table Enhancements */
        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .data-table thead th {
            background: rgba(var(--accent-rgb), 0.1);
            color: var(--text-primary);
            font-weight: 600;
            padding: 14px 16px;
            text-align: left;
            border-bottom: 2px solid var(--border-color);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .data-table tbody tr {
            transition: all 0.2s ease;
        }

        .data-table tbody tr:hover {
            background: rgba(var(--accent-rgb), 0.05);
            transform: translateX(2px);
        }

        .data-table tbody td {
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-secondary);
            font-size: 14px;
        }

        .data-table tbody td strong {
            color: var(--text-primary);
            font-weight: 600;
        }

        .data-table tbody td code {
            font-family: 'Courier New', monospace;
            background: rgba(52, 211, 153, 0.1);
            color: #34d399;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        /* Badge Styles */
        .product-count-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, rgba(var(--accent-rgb), 0.2), rgba(var(--accent-rgb), 0.1));
            color: var(--accent-color);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid rgba(var(--accent-rgb), 0.3);
        }

        /* Switch Toggle Enhancement */
        .switch {
            position: relative;
            display: inline-block;
            width: 48px;
            height: 26px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.1);
            transition: .3s;
            border-radius: 26px;
            border: 1px solid var(--border-color);
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 4px;
            bottom: 3px;
            background-color: white;
            transition: .3s;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        input:checked + .slider {
            background: linear-gradient(135deg, var(--accent-color), rgba(var(--accent-rgb), 0.8));
            border-color: var(--accent-color);
        }

        input:checked + .slider:before {
            transform: translateX(22px);
        }

        /* Action Buttons */
        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: rgba(255, 255, 255, 0.05);
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .btn-edit {
            color: #3b82f6;
        }

        .btn-edit:hover {
            background: rgba(59, 130, 246, 0.2);
        }

        .btn-delete {
            color: #ef4444;
        }

        .btn-delete:hover {
            background: rgba(239, 68, 68, 0.2);
        }

        /* Modal Enhancements */
        .modal-content {
            background: var(--secondary-bg);
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            max-width: 500px;
            width: 90%;
        }

        .modal-header {
            padding: 24px;
            border-bottom: 1px solid var(--border-color);
        }

        .modal-header h2 {
            margin: 0;
            font-size: 22px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .form-grid {
            padding: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-primary);
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input[type="text"],
        .form-group textarea {
            width: 100%;
            padding: 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 14px;
            font-family: inherit;
            transition: all 0.2s ease;
        }

        .form-group input[type="text"]:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--accent-color);
            background: rgba(var(--accent-rgb), 0.05);
            box-shadow: 0 0 0 3px rgba(var(--accent-rgb), 0.1);
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            user-select: none;
        }

        .checkbox-label input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--accent-color);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }

        .empty-state svg {
            width: 80px;
            height: 80px;
            margin-bottom: 16px;
            opacity: 0.3;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .data-table {
                font-size: 12px;
            }

            .data-table thead th,
            .data-table tbody td {
                padding: 10px 8px;
            }
        }
    </style>
</body>
</html>
