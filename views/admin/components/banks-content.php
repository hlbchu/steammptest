<?php
/**
 * Banks Content - Modular include file
 * Quản lý tài khoản ngân hàng và mã QR
 */
?>

<div class="admin-banks-section">
    <div class="section-header">
        <h3>Quản Lý Tài Khoản Ngân Hàng</h3>
        <button class="btn btn-success" onclick="openBankModal()">+ Thêm Tài Khoản Ngân Hàng</button>
    </div>

    <div class="filter-section" style="margin-bottom: 20px;">
        <button class="btn btn-primary" onclick="loadBanks()">Refresh</button>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tên Ngân Hàng</th>
                <th>Số TK</th>
                <th>Chủ TK</th>
                <th>Mã QR</th>
                <th>Đã Nhận / Hạn Mức</th>
                <th>Trạng Thái</th>
                <th>Hành Động</th>
            </tr>
        </thead>
        <tbody id="banks-list">
            <tr><td colspan="8" style="text-align: center; color: #999;">Loading...</td></tr>
        </tbody>
    </table>
</div>

<!-- ========== MODAL: Add/Edit Bank Account ========== -->
<?php
$modal = [
    'id' => 'modal-bank',
    'title' => 'Thêm/Sửa Tài Khoản Ngân Hàng',
    'body' => '
    <form id="form-bank">
        <input type="hidden" id="bank-id" value="">
        <div class="form-group">
            <label>Chọn nhanh ngân hàng:</label>
            <select id="bank-preset">
                <option value="">-- Chọn ngân hàng --</option>
            </select>
        </div>
        <div class="form-group">
            <label>Tên Ngân Hàng:</label>
            <input type="text" id="bank-name" required placeholder="VD: Vietcombank, Techcombank">
        </div>
        <div class="form-group">
            <label>Mã Ngân Hàng:</label>
            <input type="text" id="bank-code" required placeholder="VD: VCB, TCB">
        </div>
        <div class="form-group">
            <label>Số Tài Khoản:</label>
            <input type="text" id="bank-account-number" required>
        </div>
        <div class="form-group">
            <label>Chủ Tài Khoản:</label>
            <input type="text" id="bank-account-holder" required>
        </div>
        <div class="form-group">
            <label>Icon URL:</label>
            <input type="text" id="bank-icon-url" placeholder="VD: https://example.com/icon.png">
        </div>
        <div class="form-group">
            <label>Nội dung thanh toán:</label>
            <textarea id="bank-description" rows="3" placeholder="VD: NAP [username] hoặc THANH TOAN DON HANG"></textarea>
        </div>
        <div class="form-group">
            <label>Ngưỡng Tối Đa (VND):</label>
            <input type="number" id="bank-max-threshold" placeholder="VD: 9900000">
        </div>
        <div class="form-group">
            <label>Độ Ưu Tiên:</label>
            <input type="number" id="bank-sort-order" value="0">
        </div>
        <div class="form-group">
            <label>
                <input type="checkbox" id="bank-is-active"> Kích Hoạt
            </label>
        </div>
    </form>
    ',
    'buttons' => [
        ['label' => 'Lưu', 'class' => 'btn-success', 'onclick' => 'saveBank()'],
        ['label' => 'Hủy', 'class' => 'btn-secondary', 'onclick' => 'closeModal("modal-bank")'],
    ]
];
include 'components/modal.php';
?>

<!-- ========== MODAL: QR Code Display ========== -->
<?php
$modal = [
    'id' => 'modal-qr',
    'title' => 'Mã QR Ngân Hàng',
    'body' => '
    <div id="qr-container" style="text-align: center; padding: 20px;">
        <img id="qr-image" src="" alt="QR Code" style="max-width: 300px; margin: 20px 0;">
        <p id="qr-info" style="margin-top: 20px; color: var(--text-secondary);"></p>
        <button class="btn btn-primary" id="btn-download-qr" style="margin-top: 20px;">Tải Xuống QR</button>
        <button class="btn btn-success" id="btn-regenerate-qr" style="margin-top: 20px;">Tạo Lại Mã QR</button>
    </div>
    ',
    'buttons' => [
        ['label' => 'Đóng', 'class' => 'btn-secondary', 'onclick' => 'closeModal("modal-qr")'],
    ]
];
include 'components/modal.php';
?>

<!-- ========== JAVASCRIPT ========== -->
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';
const API_GATEWAY = BASE_URL + '/app/Controllers/AdminBankController.php';
const BANK_PRESETS = [
    { code: 'VCB', name: 'Vietcombank', icon: `${BASE_URL}/public/assets/svg/banks/VCB.svg` },
    { code: 'TCB', name: 'Techcombank', icon: `${BASE_URL}/public/assets/svg/banks/TCB.svg` },
    { code: 'BIDV', name: 'BIDV', icon: `${BASE_URL}/public/assets/svg/banks/BIDV.svg` },
    { code: 'ACB', name: 'ACB', icon: `${BASE_URL}/public/assets/svg/banks/ACB.svg` },
    { code: 'MBB', name: 'MB Bank', icon: `${BASE_URL}/public/assets/svg/banks/MBB.svg` },
    { code: 'VPB', name: 'VPBank', icon: `${BASE_URL}/public/assets/svg/banks/VPB.svg` },
    { code: 'STB', name: 'Sacombank', icon: `${BASE_URL}/public/assets/svg/banks/STB.svg` },
    { code: 'VIB', name: 'VIB', icon: `${BASE_URL}/public/assets/svg/banks/VIB.svg` },
    { code: 'SHB', name: 'SHB', icon: `${BASE_URL}/public/assets/svg/banks/SHB.svg` },
    { code: 'HDB', name: 'HDBank', icon: `${BASE_URL}/public/assets/svg/banks/HDB.svg` },
    { code: 'TPB', name: 'TPBank', icon: `${BASE_URL}/public/assets/svg/banks/TPB.svg` }
];
let currentBankId = null;

// ========== Initialization ==========
document.addEventListener('DOMContentLoaded', function() {
    loadBanks();
    loadBankPresets();
});

function loadBankPresets() {
    const select = document.getElementById('bank-preset');
    if (!select) return;
    const options = BANK_PRESETS.map(b => `<option value="${b.code}">${b.name} (${b.code})</option>`).join('');
    select.innerHTML = '<option value="">-- Chọn ngân hàng --</option>' + options;
}

function applyBankPreset(code) {
    const preset = BANK_PRESETS.find(b => b.code === code);
    if (!preset) return;
    document.getElementById('bank-name').value = preset.name;
    document.getElementById('bank-code').value = preset.code;
    document.getElementById('bank-icon-url').value = preset.icon;
}

// ========== Bank Account Management ==========
function loadBanks() {
    const url = API_GATEWAY + '?action=get_banks';
    
    fetch(url)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderBanks(data.data);
            }
        })
        .catch(err => console.error('Load banks error:', err));
}

function renderBanks(banks) {
    const tbody = document.getElementById('banks-list');
    tbody.innerHTML = banks.map(bank => {
        const totalReceived = parseFloat(bank.total_received || 0);
        const maxThreshold = parseFloat(bank.max_amount_threshold || 0);
        const percentage = maxThreshold > 0 ? (totalReceived / maxThreshold * 100).toFixed(1) : 0;
        
        let progressColor = 'green';
        if (percentage >= 90) progressColor = 'red';
        else if (percentage >= 70) progressColor = 'orange';
        
        const progressBar = maxThreshold > 0 ? `
            <div style="font-size: 12px; color: #999;">
                ${totalReceived.toLocaleString('vi-VN')} / ${maxThreshold.toLocaleString('vi-VN')} đ
                <div style="background: #ddd; height: 6px; border-radius: 3px; margin-top: 4px; overflow: hidden;">
                    <div style="background: ${progressColor}; width: ${Math.min(percentage, 100)}%; height: 100%;"></div>
                </div>
                <span style="color: ${progressColor};">${percentage}%</span>
            </div>
        ` : `<span>${totalReceived.toLocaleString('vi-VN')} đ</span>`;

        return `
        <tr>
            <td>${bank.id}</td>
            <td><strong>${bank.bank_name}</strong></td>
            <td>${bank.account_number}</td>
            <td>${bank.account_holder || 'N/A'}</td>
            <td>
                ${bank.qr_code_data ? 
                    '<button class="btn btn-sm btn-info" onclick="viewQR(' + bank.id + ')">Xem</button>' :
                    '<button class="btn btn-sm btn-warning" onclick="generateQR(' + bank.id + ')">Tạo</button>'
                }
            </td>
            <td>${progressBar}</td>
            <td>
                <span class="badge ${bank.is_active ? 'badge-success' : 'badge-danger'}">
                    ${bank.is_active ? 'Hoạt Động' : 'Vô Hiệu'}
                </span>
            </td>
            <td>
                <button class="btn btn-sm btn-edit" onclick="editBank(${bank.id})">Sửa</button>
                <button class="btn btn-sm btn-danger" onclick="deleteBank(${bank.id})">Xóa</button>
            </td>
        </tr>
        `;
    }).join('') || '<tr><td colspan="8">Không có tài khoản ngân hàng</td></tr>';
}

function openBankModal() {
    document.getElementById('bank-id').value = '';
    document.getElementById('form-bank').reset();
    openModal('modal-bank');
}

function editBank(id) {
    fetch(API_GATEWAY + '?action=get_banks')
        .then(r => r.json())
        .then(data => {
            const bank = data.data.find(b => b.id == id);
            if (bank) {
                document.getElementById('bank-id').value = bank.id;
                document.getElementById('bank-name').value = bank.bank_name;
                document.getElementById('bank-code').value = bank.bank_code;
                document.getElementById('bank-account-number').value = bank.account_number || '';
                document.getElementById('bank-account-holder').value = bank.account_holder || '';
                document.getElementById('bank-icon-url').value = bank.icon_url || '';
                document.getElementById('bank-description').value = bank.description || '';
                document.getElementById('bank-max-threshold').value = bank.max_amount_threshold || '';
                document.getElementById('bank-sort-order').value = bank.sort_order;
                document.getElementById('bank-is-active').checked = bank.is_active;
                openModal('modal-bank');
            }
        });
}

function saveBank() {
    const id = document.getElementById('bank-id').value;
    const action = id ? 'update_bank' : 'create_bank';
    const formData = new FormData();
    
    if (id) formData.append('id', id);
    formData.append('bank_name', document.getElementById('bank-name').value);
    formData.append('bank_code', document.getElementById('bank-code').value);
    formData.append('account_number', document.getElementById('bank-account-number').value);
    formData.append('account_holder', document.getElementById('bank-account-holder').value);
    formData.append('icon_url', document.getElementById('bank-icon-url').value);
    formData.append('description', document.getElementById('bank-description').value);
    formData.append('max_amount_threshold', document.getElementById('bank-max-threshold').value);
    formData.append('sort_order', document.getElementById('bank-sort-order').value);
    formData.append('is_active', document.getElementById('bank-is-active').checked);

    fetch(API_GATEWAY + '?action=' + action, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                closeModal('modal-bank');
                loadBanks();
            } else {
                alert('Lỗi: ' + data.message);
            }
        })
        .catch(err => alert('Lỗi: ' + err.message));
}

function deleteBank(id) {
    if (!confirm('Bạn chắc chắn muốn xóa tài khoản ngân hàng này?')) return;
    
    const formData = new FormData();
    formData.append('id', id);
    
    fetch(API_GATEWAY + '?action=delete_bank', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                loadBanks();
            } else {
                alert('Lỗi: ' + data.message);
            }
        })
        .catch(err => alert('Lỗi: ' + err.message));
}

// ========== QR Code Management ==========
function generateQR(bankId) {
    if (!confirm('Tạo mã QR cho tài khoản ngân hàng này?')) return;
    
    const formData = new FormData();
    formData.append('id', bankId);
    
    fetch(API_GATEWAY + '?action=generate_qr', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                loadBanks();
            } else {
                alert('Lỗi: ' + data.message);
            }
        })
        .catch(err => alert('Lỗi: ' + err.message));
}

function viewQR(bankId) {
    fetch(API_GATEWAY + '?action=get_qr&id=' + bankId)
        .then(r => r.json())
        .then(data => {
            if (data.success && data.qr_data) {
                currentBankId = bankId;
                document.getElementById('qr-image').src = data.qr_data;
                document.getElementById('qr-info').textContent = 'Mã QR cho tài khoản ngân hàng ID: ' + bankId;
                document.getElementById('btn-regenerate-qr').onclick = () => generateQR(bankId);
                document.getElementById('btn-download-qr').onclick = () => downloadQR(data.qr_data);
                openModal('modal-qr');
            } else {
                alert('Không thể tải mã QR');
            }
        })
        .catch(err => alert('Lỗi: ' + err.message));
}

function downloadQR(qrData) {
    const link = document.createElement('a');
    link.href = qrData;
    link.download = 'bank_qr_' + currentBankId + '.png';
    link.click();
}

// ========== Modal Functions ==========
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.classList.remove('show');
        }
    });
};

// ========== Event Listeners ==========
document.getElementById('bank-preset')?.addEventListener('change', function() {
    if (this.value) {
        applyBankPreset(this.value);
    }
});
</script>

<style>
.admin-banks-section {
    padding: 20px;
    background: var(--card-bg);
    border-radius: 8px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.section-header h3 {
    margin: 0;
    color: var(--text-primary);
}

.filter-section {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    color: var(--text-primary);
    font-weight: 500;
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 10px;
    border: 2px solid rgba(0,180,216,0.2);
    border-radius: 6px;
    background: var(--card-bg);
    color: var(--text-primary);
}

.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.badge-success {
    background: rgba(76, 175, 80, 0.3);
    color: #4caf50;
}

.badge-danger {
    background: rgba(244, 67, 54, 0.3);
    color: #f44336;
}

.badge-info {
    background: rgba(33, 150, 243, 0.3);
    color: #2196f3;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s ease;
}

.btn-primary {
    background: linear-gradient(135deg, #00b4d8, #0096c7);
    color: white;
}

.btn-success {
    background: linear-gradient(135deg, #4caf50, #45a049);
    color: white;
}

.btn-edit {
    background: rgba(33, 150, 243, 0.2);
    color: #2196f3;
}

.btn-danger {
    background: rgba(244, 67, 54, 0.2);
    color: #f44336;
}

.btn-info {
    background: rgba(33, 150, 243, 0.2);
    color: #2196f3;
}

.btn-warning {
    background: rgba(255, 152, 0, 0.2);
    color: #ff9800;
}

.btn-secondary {
    background: rgba(158, 158, 158, 0.2);
    color: #9e9e9e;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.btn-sm {
    padding: 4px 8px;
    font-size: 12px;
}

.table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.table thead {
    background: rgba(0, 180, 216, 0.1);
}

.table th {
    padding: 12px;
    text-align: left;
    color: var(--text-primary);
    font-weight: 600;
    border-bottom: 2px solid rgba(0,180,216,0.3);
}

.table td {
    padding: 12px;
    border-bottom: 1px solid rgba(0,180,216,0.2);
    color: var(--text-secondary);
}

.table tr:hover {
    background: rgba(0,180,216,0.05);
}

code {
    background: rgba(0,0,0,0.1);
    padding: 2px 6px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
    font-size: 12px;
}
</style>
