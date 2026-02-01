<?php
/**
 * ADMIN NAV MENU - Quản lý menu điều hướng
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

// Get all navigation menus
$db->query("SELECT * FROM nav_menu WHERE parent_id IS NULL ORDER BY display_order ASC, id ASC");
$navMenus = $db->fetchAll();

// Get parent menu list for dropdown (excluding parent items in submenu form)
$db->query("SELECT id, name FROM nav_menu WHERE parent_id IS NULL ORDER BY display_order ASC, name ASC");
$parentMenus = $db->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Menu Điều hướng - Admin</title>
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
            <h1>Sửa Trang Mục</h1>
            <button class="btn-primary" onclick="openModal()">
                <svg width="18" height="18" viewBox="0 0 24 24">
                    <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" fill="currentColor"/>
                </svg>
                Thêm mục menu
            </button>
        </div>

        <div class="info-banner">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
            </svg>
            <span><strong>Lưu ý:</strong> Mục "Trang chủ" và "Flash Sale" được bảo vệ và không thể xóa.</span>
        </div>

        <!-- Nav Menu Table -->
        <div class="content-card">
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Thứ tự</th>
                            <th>Icon</th>
                            <th>Tên menu</th>
                            <th>Màu chữ</th>
                            <th>Link</th>
                            <th>Slug</th>
                            <th>Trạng thái</th>
                            <th>Bảo vệ</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($navMenus as $menu): ?>
                        <tr>
                            <td>
                                <span class="order-badge">#<?php echo $menu['display_order']; ?></span>
                            </td>
                            <td>
                                <?php if (!empty($menu['icon'])): ?>
                                    <?php if (strpos($menu['icon'], '<svg') !== false): ?>
                                        <div class="menu-icon-svg"><?php echo $menu['icon']; ?></div>
                                    <?php else: ?>
                                        <img src="../../public/assets/svg/<?php echo htmlspecialchars($menu['icon']); ?>" 
                                             alt="<?php echo htmlspecialchars($menu['name']); ?>" 
                                             class="menu-icon-svg" 
                                             onerror="this.style.display='none';this.nextElementSibling.style.display='inline-block';">
                                        <span class="menu-icon-fallback" style="display:none;">📄</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="menu-icon-fallback">📄</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($menu['name']); ?></strong></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="width: 24px; height: 24px; background: <?php echo htmlspecialchars($menu['text_color'] ?: '#ffffff'); ?>; border: 1px solid rgba(255,255,255,0.2); border-radius: 4px;"></div>
                                    <code style="background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 3px; font-size: 11px;"><?php echo htmlspecialchars($menu['text_color'] ?: '#ffffff'); ?></code>
                                </div>
                            </td>
                            <td>
                                <code class="link-code"><?php echo htmlspecialchars($menu['link'] ?: '-'); ?></code>
                            </td>
                            <td>
                                <code class="slug-code"><?php echo htmlspecialchars($menu['slug']); ?></code>
                            </td>
                            <td>
                                <label class="switch">
                                    <input type="checkbox" 
                                           <?php echo $menu['is_active'] ? 'checked' : ''; ?>
                                           onchange="toggleActive(<?php echo $menu['id']; ?>, this.checked ? 1 : 0)">
                                    <span class="slider"></span>
                                </label>
                            </td>
                            <td>
                                <?php if ($menu['is_protected']): ?>
                                    <span class="protected-badge">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/>
                                        </svg>
                                        Bảo vệ
                                    </span>
                                <?php else: ?>
                                    <span class="normal-badge">Thường</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($menu['created_at'])); ?></td>
                            <td>
                                <div style="display: flex; gap: 5px;">
                                    <button class="btn-action btn-edit" onclick='editMenu(<?php echo json_encode($menu); ?>)' title="Sửa">
                                        <svg width="16" height="16" viewBox="0 0 24 24">
                                            <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z" fill="currentColor"/>
                                        </svg>
                                    </button>
                                    <button class="btn-action btn-submenu" onclick='openSubmenuModal(<?php echo $menu['id']; ?>, <?php echo json_encode($menu['name']); ?>)' title="Quản lý trang mục con">
                                        <svg width="16" height="16" viewBox="0 0 24 24">
                                            <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z" fill="currentColor"/>
                                        </svg>
                                    </button>
                                    <?php if (!$menu['is_protected']): ?>
                                    <button class="btn-action btn-delete" onclick="deleteMenu(<?php echo $menu['id']; ?>)" title="Xóa">
                                        <svg width="16" height="16" viewBox="0 0 24 24">
                                            <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z" fill="currentColor"/>
                                        </svg>
                                    </button>
                                    <?php endif; ?>
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
    <div id="navMenuModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Thêm mục menu</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form id="navMenuForm">
                <input type="hidden" id="menuId" name="id">
                
                <div class="form-grid">
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Tên menu *</label>
                        <input type="text" id="menuName" name="name" required placeholder="VD: Game mới, Nạp thẻ, Liên hệ">
                        <small style="color: var(--text-secondary); font-size: 12px; margin-top: 5px; display: block;">
                            Slug (URL) sẽ được tự động tạo từ tên menu
                        </small>
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Link/URL *</label>
                        <input type="text" id="menuLink" name="link" required placeholder="VD: ?page=home, ?page=search, https://example.com">
                        <small style="color: var(--text-secondary); font-size: 12px; margin-top: 5px; display: block;">
                            Đường dẫn khi click vào menu (có thể dùng ?page=... hoặc URL đầy đủ)
                        </small>
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Icon SVG</label>
                        <textarea id="menuIcon" name="icon" rows="3" placeholder="Dán code SVG hoặc tên file: home.svg"></textarea>
                        <small style="color: var(--text-secondary); font-size: 12px; margin-top: 5px; display: block;">
                            Paste toàn bộ code SVG (VD: &lt;svg&gt;...&lt;/svg&gt;) hoặc tên file SVG
                        </small>
                    </div>

                    <div class="form-group">
                        <label>Màu chữ</label>
                        <input type="color" id="menuColor" name="text_color" value="#ffffff" style="width: 100%; height: 45px; cursor: pointer; border: 1px solid var(--border-color); border-radius: 8px; background: transparent;">
                        <small style="color: var(--text-secondary); font-size: 12px; margin-top: 5px; display: block;">
                            Chọn màu hiển thị cho menu
                        </small>
                    </div>

                    <div class="form-group">
                        <label>Thứ tự hiển thị</label>
                        <input type="number" id="menuOrder" name="display_order" min="0" value="0">
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="checkbox-label">
                            <input type="checkbox" id="menuActive" name="is_active" checked>
                            <span>Kích hoạt (hiển thị trên website)</span>
                        </label>
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="checkbox-label">
                            <input type="checkbox" id="menuProtected" name="is_protected">
                            <span>Bảo vệ (không cho phép xóa)</span>
                        </label>
                        <small id="protectWarning" style="color: #f59e0b; font-size: 12px; margin-top: 5px; display: none;">
                            ⚠️ Trang chủ luôn được bảo vệ và không thể bỏ bảo vệ
                        </small>
                    </div>

                    <div id="submenuNote" style="grid-column: 1 / -1; padding: 12px; background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 8px; color: var(--text-secondary); font-size: 13px; display: none;">
                        💡 Nhấn nút <strong>"Quản lý trang mục con"</strong> ở bảng trên để tạo trang mục con cho menu này
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal()">Hủy</button>
                    <button type="submit" class="btn-primary">Lưu</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Submenu Modal -->
    <div id="submenuModal" class="modal">
        <div class="modal-content submenu-modal-content">
            <div class="modal-header">
                <h2 id="submenuTitle">Quản lý trang mục con - <span id="parentMenuName"></span></h2>
                <button class="modal-close" onclick="closeSubmenuModal()">&times;</button>
            </div>
            
            <div class="submenu-modal-body">
                <!-- Add Form Section -->
                <div class="submenu-section submenu-section-form">
                    <button class="btn-primary btn-add-submenu" onclick="openSubmenuForm()">
                        <svg width="18" height="18" viewBox="0 0 24 24">
                            <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" fill="currentColor"/>
                        </svg>
                        <span>Thêm trang mục con</span>
                    </button>

                    <div id="submenuForm" class="submenu-form">
                        <form id="addSubmenuForm">
                            <input type="hidden" id="parentMenuId" name="parent_id">
                            
                            <div class="form-group">
                                <label>Tên trang mục con *</label>
                                <input type="text" id="submenuName" name="name" required placeholder="VD: Game TOP, Game Mới, Khuyến Mãi">
                            </div>
                            
                            <div class="form-group">
                                <label>Link *</label>
                                <input type="text" id="submenuLink" name="link" required placeholder="VD: ?page=new, ?page=hot">
                            </div>

                            <div class="form-group">
                                <label>SVG Icon</label>
                                <input type="text" id="submenuIcon" name="icon" placeholder="VD: heart.svg hoặc inline SVG">
                            </div>

                            <div class="form-group">
                                <label>Màu chữ</label>
                                <div class="color-picker-wrapper">
                                    <input type="color" id="submenuTextColor" name="text_color" value="#ffffff">
                                    <span id="submenuTextColorValue">#ffffff</span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Thứ tự hiển thị</label>
                                <input type="number" id="submenuOrder" name="display_order" min="0" value="0">
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-primary">Lưu</button>
                                <button type="button" class="btn-secondary" onclick="closeSubmenuForm()">Hủy</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- List Section -->
                <div class="submenu-section submenu-section-list">
                    <div class="submenu-list-header">
                        <h3>Danh sách trang mục con</h3>
                        <span class="submenu-count" id="submenuCount">0</span>
                    </div>
                    <div id="submenuListContainer" class="submenu-list-container">
                        <div id="submenuList"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* ============ SUBMENU MODAL STYLES ============ */
        
        /* Modal Container */
        .submenu-modal-content {
            max-width: 740px;
            max-height: 85vh;
            display: flex !important;
            flex-direction: column !important;
            border: 1px solid rgba(124, 58, 237, 0.35);
            background: linear-gradient(180deg, #1b2036, #171a2b);
            box-shadow: 0 24px 70px rgba(0, 0, 0, 0.55);
            backdrop-filter: blur(12px);
        }

        .submenu-modal-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 16px;
            padding: 22px 26px 26px;
            overflow-y: auto;
        }

        /* Submenu Sections */
        .submenu-section {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .submenu-section-form {
            padding: 14px;
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.12), rgba(59, 130, 246, 0.03));
            border: 1px solid rgba(59, 130, 246, 0.25);
            border-radius: 12px;
        }

        .btn-add-submenu {
            width: 100%;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-weight: 600;
            letter-spacing: 0.2px;
            box-shadow: 0 6px 18px rgba(59, 130, 246, 0.25);
        }

        /* Submenu Form */
        .submenu-form {
            display: none;
            margin-top: 12px;
            padding: 16px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 10px;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
        }

        .submenu-form.show {
            display: block;
        }

        #addSubmenuForm {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        #addSubmenuForm .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        #addSubmenuForm .form-group label {
            font-size: 13px;
            font-weight: 500;
            color: var(--text-primary);
        }

        #addSubmenuForm .form-group input {
            padding: 10px 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: var(--text-primary);
            font-size: 13px;
            transition: all 0.2s ease;
        }

        #addSubmenuForm .form-group input:focus {
            outline: none;
            border-color: var(--accent-color);
            background: rgba(var(--accent-rgb), 0.05);
            box-shadow: 0 0 0 2px rgba(var(--accent-rgb), 0.1);
        }

        /* Color Picker Wrapper */
        .color-picker-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        #submenuTextColor {
            width: 50px;
            height: 38px;
            padding: 2px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        #submenuTextColor:hover {
            border-color: var(--accent-color);
        }

        #submenuTextColorValue {
            font-size: 12px;
            font-family: 'Courier New', monospace;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 4px;
        }

        .form-actions button {
            flex: 1;
            padding: 10px 12px;
            font-size: 13px;
            font-weight: 500;
        }

        /* Submenu List Section */
        .submenu-section-list {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .submenu-list-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 4px 6px 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .submenu-list-header h3 {
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .submenu-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 26px;
            height: 26px;
            background: rgba(59, 130, 246, 0.2);
            border-radius: 13px;
            font-size: 12px;
            font-weight: 700;
            color: #93c5fd;
            box-shadow: 0 4px 10px rgba(59, 130, 246, 0.2);
        }

        /* Submenu List Container */
        #submenuListContainer {
            flex: 1;
            overflow-y: auto;
            padding-right: 4px;
            min-height: 150px;
        }

        #submenuListContainer::-webkit-scrollbar {
            width: 6px;
        }

        #submenuListContainer::-webkit-scrollbar-track {
            background: transparent;
        }

        #submenuListContainer::-webkit-scrollbar-thumb {
            background: rgba(var(--accent-rgb), 0.2);
            border-radius: 3px;
        }

        #submenuListContainer::-webkit-scrollbar-thumb:hover {
            background: rgba(var(--accent-rgb), 0.4);
        }

        /* Submenu List Items */
        .submenu-item {
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: center;
            padding: 12px 14px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 10px;
            margin-bottom: 10px;
            transition: all 0.2s ease;
            gap: 12px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        }

        .submenu-item:hover {
            background: rgba(59, 130, 246, 0.08);
            border-color: rgba(59, 130, 246, 0.45);
            transform: translateX(2px);
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.25);
        }

        .submenu-item-info {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .submenu-item-name {
            font-weight: 600;
            font-size: 14px;
            color: var(--text-primary);
            word-break: break-word;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .submenu-item-order {
            margin-left: 6px;
            font-size: 11px;
            color: var(--text-secondary);
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 2px 6px;
            border-radius: 999px;
            font-weight: 600;
        }

        .submenu-item-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 16px;
            height: 16px;
            flex-shrink: 0;
            color: currentColor;
        }

        .submenu-item-icon-svg svg {
            width: 16px;
            height: 16px;
            fill: currentColor;
        }

        .submenu-item-icon-img {
            width: 16px;
            height: 16px;
            display: inline-block;
        }

        .submenu-item-link {
            font-size: 11px;
            font-family: 'Courier New', monospace;
            background: rgba(52, 211, 153, 0.14);
            color: #34d399;
            padding: 4px 8px;
            border-radius: 6px;
            display: inline-block;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            border: 0.5px solid rgba(52, 211, 153, 0.25);
        }

        .submenu-item-actions {
            display: flex;
            gap: 6px;
            flex-shrink: 0;
        }

        .btn-submenu-edit,
        .btn-submenu-delete {
            width: 34px;
            height: 34px;
            padding: 0;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s ease;
            background: rgba(255, 255, 255, 0.04);
            font-size: 15px;
            font-weight: 700;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .btn-submenu-edit {
            color: #60a5fa;
        }

        .btn-submenu-edit:hover {
            background: rgba(96, 165, 250, 0.15);
            color: #93c5fd;
            transform: scale(1.05);
        }

        .btn-submenu-delete {
            color: #f87171;
        }

        .btn-submenu-delete:hover {
            background: rgba(248, 113, 113, 0.15);
            color: #fca5a5;
            transform: scale(1.05);
        }

        /* Empty State */
        .submenu-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 20px;
            color: var(--text-secondary);
            font-size: 14px;
            min-height: 200px;
            text-align: center;
        }

        .submenu-empty::before {
            content: attr(data-icon);
            font-size: 48px;
            margin-bottom: 12px;
            opacity: 0.5;
        }

        /* Responsive */
        @media (max-width: 600px) {
            .submenu-modal-content {
                max-width: 95vw;
                max-height: 90vh;
            }

            .submenu-modal-body {
                padding: 16px;
                gap: 16px;
            }

            .submenu-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .submenu-item-actions {
                width: 100%;
                justify-content: flex-end;
            }
        }
    </style>

    <script src="../../public/assets/js/admin-nav-menu.js"></script>
    <style>
        /* Info Banner */
        .info-banner {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(37, 99, 235, 0.05));
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 8px;
            margin-bottom: 24px;
            color: #60a5fa;
        }

        .info-banner svg {
            flex-shrink: 0;
        }

        /* Order Badge */
        .order-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            padding: 6px 12px;
            background: linear-gradient(135deg, var(--accent-color), #667eea);
            color: white;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
        }

        /* Menu Icon */
        .menu-icon-svg {
            width: 24px;
            height: 24px;
            display: inline-block;
            vertical-align: middle;
        }

        .menu-icon-svg img {
            filter: brightness(0) saturate(100%) invert(100%);
        }

        .menu-icon-svg svg {
            width: 24px;
            height: 24px;
            fill: currentColor;
            color: var(--text-primary);
        }

        .menu-icon-fallback {
            font-size: 24px;
            display: inline-block;
        }

        /* Slug Code */
        .slug-code {
            font-family: 'Courier New', monospace;
            background: rgba(52, 211, 153, 0.1);
            color: #34d399;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        /* Link Code */
        .link-code {
            font-family: 'Courier New', monospace;
            background: rgba(59, 130, 246, 0.1);
            color: #60a5fa;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        /* Protected Badge */
        .protected-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(220, 38, 38, 0.1));
            color: #f87171;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .normal-badge {
            display: inline-flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-secondary);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        /* Table hover effect */
        .data-table tbody tr {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
        }

        .data-table tbody tr:hover {
            background: linear-gradient(90deg, rgba(124, 58, 237, 0.15), rgba(102, 126, 234, 0.1)) !important;
            transform: translateX(4px);
            box-shadow: -4px 0 0 0 var(--accent-color), 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        /* Switch Toggle */
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

        /* Modal */
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

        .modal-content {
            position: relative;
            background: linear-gradient(145deg, #1a1d2e, #161929);
            margin: 3% auto;
            padding: 0;
            border: 1px solid rgba(124, 58, 237, 0.3);
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            animation: slideDown 0.3s ease;
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

        .modal-close {
            background: transparent;
            border: none;
            color: var(--text-secondary);
            font-size: 32px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .modal-close:hover {
            color: #ff3b30;
            transform: rotate(90deg);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            padding: 30px;
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
        }

        .form-group input:focus,
        .form-group select:focus,
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

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding: 20px 30px;
            border-top: 1px solid var(--border-color);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
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
