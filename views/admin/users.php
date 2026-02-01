<?php
/**
 * ADMIN USERS MANAGEMENT
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
$db->query("SELECT u.id, u.username, u.email, u.role, u.status, u.created_at, COALESCE(w.balance, 0) as balance
           FROM users u
           LEFT JOIN wallets w ON u.id = w.user_id
           ORDER BY u.id DESC");
$users = $db->fetchAll();
?>
<!-- ==============================================
     ADMIN USERS MANAGEMENT
     ============================================== -->

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Người dùng - Steam Web</title>
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
            <h1>Quản lý người dùng</h1>
            <div class="header-actions">
                <input type="text" placeholder="Tìm kiếm người dùng..." style="padding: 10px 15px; background: var(--tertiary-bg); border: 1px solid var(--border-color); color: var(--text-primary); border-radius: 4px; width: 250px;">
                <button type="button" class="btn btn-primary" onclick="openAddUserModal()">➕ Thêm người dùng mới</button>
            </div>
        </div>

        <div class="content-card">
            <div class="table-container">

            <!-- Users Table -->
            <table class="data-table">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Tên người dùng</th>
                        <th>Email</th>
                        <th>Vai trò</th>
                        <th>Số dư</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                        <?php $i = 1; foreach ($users as $user): ?>
                        <tr data-user-id="<?php echo $user['id']; ?>">
                            <td><?php echo $i++; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span style="background: rgba(<?php echo $user['role'] === 'admin' ? '255, 193, 7' : '40, 167, 69'; ?>, 0.3); color: <?php echo $user['role'] === 'admin' ? 'var(--warning)' : 'var(--success)'; ?>; padding: 4px 8px; border-radius: 2px; font-size: 11px;">
                                    <?php echo $user['role'] === 'admin' ? 'Admin' : 'User'; ?>
                                </span>
                            </td>
                            <td class="user-balance"><?php echo number_format($user['balance'], 0, ',', '.'); ?>₫</td>
                            <td>
                                <span style="background: rgba(40, 167, 69, 0.3); color: var(--success); padding: 4px 8px; border-radius: 2px; font-size: 11px;">
                                    <?php echo $user['status'] === 'active' ? '✓ Hoạt động' : '✗ Bị khóa'; ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                            <td style="display: flex; gap: 6px;">
                                <button type="button" class="btn btn-secondary btn-sm" onclick="openBalanceModal(this)" data-id="<?php echo $user['id']; ?>" data-username="<?php echo htmlspecialchars($user['username']); ?>" data-balance="<?php echo $user['balance']; ?>">💰 Số dư</button>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="editUser(this)" data-id="<?php echo $user['id']; ?>" data-username="<?php echo htmlspecialchars($user['username']); ?>" data-email="<?php echo htmlspecialchars($user['email']); ?>" data-role="<?php echo $user['role']; ?>" data-status="<?php echo $user['status']; ?>">✏️ Sửa</button>
                                <button type="button" class="btn btn-danger btn-sm" onclick="deleteUser(<?php echo $user['id']; ?>)">🗑️ Xóa</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: var(--text-secondary); padding: 40px;">Chưa có người dùng</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            </div>
        </div>
    </main>

    <!-- Modal: Add/Edit User -->
    <div class="modal" id="userModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Thêm người dùng mới</h2>
                <button class="modal-close" onclick="closeUserModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="userForm" class="admin-form">
                    <input type="hidden" name="id" id="userId">
                    <div class="form-group">
                        <label class="form-label">Tên người dùng *</label>
                        <input type="text" class="form-control" name="username" id="userName" placeholder="VD: Nguyễn Văn A" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="email" id="userEmail" placeholder="email@example.com" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Mật khẩu</label>
                        <input type="password" class="form-control" name="password" id="userPassword" placeholder="••••••••">
                        <small style="color: var(--text-secondary); font-size: 12px;">Để trống nếu không đổi mật khẩu</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Vai trò</label>
                        <select class="form-control" name="role" id="userRole">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Trạng thái</label>
                        <select class="form-control" name="status" id="userStatus">
                            <option value="active">Active</option>
                            <option value="banned">Banned</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeUserModal()">Hủy bỏ</button>
                <button type="button" class="btn btn-primary" onclick="saveUser()">Lưu người dùng</button>
            </div>
        </div>
    </div>

    <!-- Modal: Balance Adjust -->
    <div class="modal" id="balanceModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Chỉnh số dư người dùng</h2>
                <button class="modal-close" onclick="closeBalanceModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="balanceForm" class="admin-form">
                    <input type="hidden" name="user_id" id="balanceUserId">
                    <div class="form-group">
                        <label class="form-label">User</label>
                        <input type="text" class="form-control" id="balanceUsername" disabled>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Số dư hiện tại</label>
                        <input type="text" class="form-control" id="balanceCurrent" disabled>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Loại</label>
                        <select class="form-control" name="action_type" id="balanceType">
                            <option value="add">Cộng tiền</option>
                            <option value="subtract">Trừ tiền</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Số tiền *</label>
                        <input type="number" class="form-control" name="amount" id="balanceAmount" min="1" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ghi chú</label>
                        <input type="text" class="form-control" name="note" id="balanceNote" placeholder="Ví dụ: cộng thưởng, hoàn tiền...">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeBalanceModal()">Hủy bỏ</button>
                <button type="button" class="btn btn-primary" onclick="saveBalance()">Lưu số dư</button>
            </div>
        </div>
    </div>

    <script src="../../public/assets/admin.js"></script>
    <script>
        document.querySelectorAll('.nav-group-toggle').forEach(btn => {
            btn.addEventListener('click', () => {
                const group = btn.closest('.nav-group');
                const isOpen = group.classList.toggle('open');
                btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });
        });

        function openAddUserModal() {
            document.getElementById('userModal').classList.add('show');
            document.getElementById('userForm').reset();
            document.getElementById('userId').value = '';
            document.querySelector('.modal-title').textContent = 'Thêm người dùng mới';
            document.getElementById('userPassword').required = true;
        }

        function editUser(btn) {
            document.getElementById('userModal').classList.add('show');
            document.querySelector('.modal-title').textContent = 'Cập nhật người dùng';
            document.getElementById('userId').value = btn.getAttribute('data-id');
            document.getElementById('userName').value = btn.getAttribute('data-username') || '';
            document.getElementById('userEmail').value = btn.getAttribute('data-email') || '';
            document.getElementById('userRole').value = btn.getAttribute('data-role') || 'user';
            document.getElementById('userStatus').value = btn.getAttribute('data-status') || 'active';
            document.getElementById('userPassword').value = '';
            document.getElementById('userPassword').required = false;
        }

        function closeUserModal() {
            document.getElementById('userModal').classList.remove('show');
        }

        function openBalanceModal(btn) {
            const userId = btn.getAttribute('data-id');
            const username = btn.getAttribute('data-username');
            const balance = parseFloat(btn.getAttribute('data-balance') || '0');

            document.getElementById('balanceUserId').value = userId;
            document.getElementById('balanceUsername').value = username;
            document.getElementById('balanceCurrent').value = balance.toLocaleString('vi-VN') + '₫';
            document.getElementById('balanceAmount').value = '';
            document.getElementById('balanceNote').value = '';
            document.getElementById('balanceType').value = 'add';

            document.getElementById('balanceModal').classList.add('show');
        }

        function closeBalanceModal() {
            document.getElementById('balanceModal').classList.remove('show');
        }

        function saveBalance() {
            const form = document.getElementById('balanceForm');
            if (!form.checkValidity()) {
                showError('Vui lòng nhập số tiền hợp lệ');
                return;
            }

            const formData = new FormData(form);
            fetch('../../app/Controllers/AdminUserController.php?action=adjust_balance', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const row = document.querySelector(`tr[data-user-id="${formData.get('user_id')}"]`);
                    if (row) {
                        const balanceCell = row.querySelector('.user-balance');
                        balanceCell.textContent = Number(data.new_balance).toLocaleString('vi-VN') + '₫';
                        const balanceBtn = row.querySelector('button[data-balance]');
                        if (balanceBtn) {
                            balanceBtn.setAttribute('data-balance', data.new_balance);
                        }
                    }
                    showSuccess(data.message || 'Cập nhật số dư thành công');
                    closeBalanceModal();
                } else {
                    showError(data.message || 'Không thể cập nhật số dư');
                }
            })
            .catch(() => showError('Có lỗi xảy ra'));
        }

        function saveUser() {
            const form = document.getElementById('userForm');
            if (!form.checkValidity()) {
                showError('Vui lòng nhập đầy đủ thông tin');
                return;
            }

            const formData = new FormData(form);
            const userId = document.getElementById('userId').value;
            const action = userId ? 'update' : 'create';

            fetch(`../../app/Controllers/AdminUserController.php?action=${action}`, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess(data.message || 'Lưu thành công!');
                    closeUserModal();
                    setTimeout(() => location.reload(), 500);
                } else {
                    showError(data.message || 'Không thể lưu người dùng');
                }
            })
            .catch(() => showError('Có lỗi xảy ra'));
        }

        function deleteUser(id) {
            if (confirm('Bạn có chắc chắn muốn xóa người dùng này?')) {
                const formData = new FormData();
                formData.append('id', id);

                fetch('../../app/Controllers/AdminUserController.php?action=delete', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showSuccess(data.message || 'Xóa thành công!');
                        setTimeout(() => location.reload(), 500);
                    } else {
                        showError(data.message || 'Không thể xóa người dùng');
                    }
                })
                .catch(() => showError('Có lỗi xảy ra'));
            }
        }
    </script>
</body>
</html>
