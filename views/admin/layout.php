<?php
/**
 * Admin Layout Template
 * Main wrapper for all admin pages
 */

session_start();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

// Check admin role
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../client/login.php');
    exit();
}

// Get current page
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Admin Panel'; ?></title>
    <link rel="stylesheet" href="../../public/assets/css/base.css">
    <link rel="stylesheet" href="../../public/assets/css/admin.css">
    <?php include '../../public/assets/svg/icons.svg'; ?>
</head>
<body class="admin-body">
    <!-- Admin Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="admin-content">
        <div class="content-header">
            <h1><?php echo $page_title ?? 'Admin Panel'; ?></h1>
        </div>

        <div class="page-content">
            <?php 
            // Include page content
            if (isset($page_content)) {
                $content_path = __DIR__ . '/components/' . $page_content;
                if (file_exists($content_path)) {
                    include $content_path;
                } else {
                    echo '<p style="color: #ff4444;">Content file not found: ' . htmlspecialchars($page_content) . '</p>';
                }
            }
            ?>
        </div>
    </main>

    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
        
        // Global helper function for API requests with credentials
        function apiCall(url, options = {}) {
            const fetchOptions = {
                credentials: 'include',
                ...options
            };
            return fetch(url, fetchOptions);
        }

        // Tab switching helper
        function setupTabs(containerSelector = '.bank-tabs') {
            const container = document.querySelector(containerSelector);
            if (!container) return;

            container.querySelectorAll('.tab-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    container.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                    
                    this.classList.add('active');
                    const tabId = this.dataset.tab + '-tab';
                    const tabElement = document.getElementById(tabId);
                    if (tabElement) {
                        tabElement.classList.add('active');
                    }
                });
            });
        }

        // Modal helpers
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) modal.style.display = 'flex';
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) modal.style.display = 'none';
        }

        // Close modal on close button click
        document.addEventListener('click', function(e) {
            console.log('Click:', e.target, 'Class:', e.target.className);
            
            if (e.target.classList.contains('close') || e.target.classList.contains('close-btn')) {
                console.log('Close button clicked!');
                const modal = e.target.closest('.modal');
                if (modal) {
                    console.log('Closing modal:', modal.id);
                    modal.style.display = 'none';
                }
            }
            
            // Handle data-dismiss attribute
            if (e.target.hasAttribute('data-dismiss') && e.target.getAttribute('data-dismiss') === 'modal') {
                console.log('Data-dismiss clicked!');
                const modal = e.target.closest('.modal');
                if (modal) {
                    console.log('Closing modal via data-dismiss:', modal.id);
                    modal.style.display = 'none';
                }
            }
        });

        // Close modal on outside click
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.style.display = 'none';
            }
        });
    </script>

    <style>
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.7);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: var(--card-bg);
            padding: 20px;
            border-radius: 8px;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            box-shadow: 0 4px 20px rgba(0, 180, 216, 0.3);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(0, 180, 216, 0.2);
            margin-bottom: 15px;
        }

        .modal-header h2 {
            margin: 0;
            color: #00d4ff;
            font-size: 1.3rem;
        }

        .modal-body {
            padding: 15px 0;
        }

        .modal-footer {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            padding-top: 15px;
            border-top: 1px solid rgba(0, 180, 216, 0.2);
            margin-top: 15px;
        }

        .modal .close-btn {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #b0bec5;
            transition: color 0.3s;
        }

        .modal .close-btn:hover {
            color: #00d4ff;
        }

        /* Tab Styles */
        .bank-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            border-bottom: 2px solid rgba(0, 180, 216, 0.2);
        }

        .tab-btn {
            background: none;
            border: none;
            padding: 12px 20px;
            color: #b0bec5;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 12px;
        }

        .tab-btn:hover {
            color: #00d4ff;
        }

        .tab-btn.active {
            color: #00d4ff;
            border-bottom-color: #00d4ff;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #b0bec5;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid rgba(0, 180, 216, 0.3);
            border-radius: 6px;
            background: rgba(0, 20, 40, 0.6);
            color: #e0e7ff;
            font-family: inherit;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #00d4ff;
            box-shadow: 0 0 0 5px rgba(0, 212, 255, 0.1);
        }

        /* Table Styles */
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .table thead {
            background: linear-gradient(135deg, rgba(0, 180, 216, 0.1) 0%, rgba(0, 180, 216, 0.05) 100%);
            border-bottom: 2px solid rgba(0, 180, 216, 0.3);
        }

        .table th {
            padding: 15px;
            text-align: left;
            color: #00d4ff;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 1px;
        }

        .table tbody tr {
            border-bottom: 1px solid rgba(0, 180, 216, 0.1);
            transition: background-color 0.3s;
        }

        .table tbody tr:hover {
            background-color: rgba(0, 180, 216, 0.05);
        }

        .table td {
            padding: 15px;
            color: #ccc;
        }

        /* Button Styles */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
            transition: all 0.3s;
        }

        .btn-primary {
            background-color: #00b8d4;
            color: white;
        }

        .btn-primary:hover {
            background-color: #00d4ff;
            box-shadow: 0 0 20px rgba(0, 212, 255, 0.3);
        }

        .btn-success {
            background-color: #28a745;
            color: white;
        }

        .btn-success:hover {
            background-color: #34c759;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #7a8288;
        }

        .btn-edit {
            background-color: #007bff;
            color: white;
            padding: 8px 16px;
            font-size: 11px;
        }

        .btn-edit:hover {
            background-color: #0056b3;
        }

        /* Content area */
        .page-content {
            padding: 20px;
        }

        .content-header {
            margin-bottom: 30px;
            border-bottom: 2px solid rgba(0, 180, 216, 0.2);
            padding-bottom: 15px;
        }

        .content-header h1 {
            color: #00d4ff;
            margin: 0;
            font-size: 2rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
    </style>
