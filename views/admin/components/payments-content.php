<?php
/**
 * Payments Content - Modular include file
 * Contains all payment dashboard logic
 */
?>

<div class="admin-payments-section">
    <!-- Webhook Section -->
    <div class="webhook-section">
        <div class="webhook-header">
            <h3>Webhook URL</h3>
            <p class="webhook-desc">Cấu hình URL này trong SeaPay để nhận thông báo thanh toán</p>
        </div>
        <div class="webhook-url-box">
            <input type="text" id="webhook-url" class="webhook-input" readonly value="<?php echo BASE_URL . '/public/webhook/seapay.php'; ?>">
            <button class="btn btn-primary" onclick="copyWebhookURL()">
                <svg width="16" height="16" viewBox="0 0 24 24" style="margin-right: 8px;">
                    <path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z" fill="currentColor"/>
                </svg>
                Copy
            </button>
        </div>
    </div>

    <!-- Payment Tabs -->
    <?php
    $tabs = [
        ['id' => 'history', 'label' => 'Lịch Sử Nạp Tiền', 'active' => true]
    ];
    include 'components/tabs.php';
    ?>

    <!-- Deposit History Tab -->
    <div id="history-tab" class="tab-content active">
        <div class="filter-section">
            <select id="filter-bank" style="padding: 10px; border: 2px solid rgba(0,180,216,0.3); border-radius: 6px; background: var(--card-bg); color: var(--text-primary);">
                <option value="">Tất cả ngân hàng</option>
            </select>

            <input type="text" id="search-content" placeholder="Tìm theo nội dung..." style="padding: 10px; border: 2px solid rgba(0,180,216,0.3); border-radius: 6px; background: var(--card-bg); color: var(--text-primary); margin-left: 10px; width: 200px;">

            <button class="btn btn-primary" id="btn-refresh-history" style="margin-left: 10px;">Tìm kiếm</button>
        </div>

        <div class="stats-summary">
            <div class="stat-item">
                <span class="stat-label">Ngân Hàng Hiện Tại:</span>
                <span class="stat-value" id="current-active-bank">-</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Tổng Đã Nhận:</span>
                <span class="stat-value" id="total-amount">0 đ</span>
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Ngân Hàng</th>
                    <th>Số Tiền</th>
                    <th>Nội dung</th>
                    <th>Status</th>
                    <th>Thời gian</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody id="history-list">
                <tr><td colspan="8" style="text-align: center; color: #999;">Loading...</td></tr>
            </tbody>
        </table>

        <div class="pagination-section" style="display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 20px;">
            <button class="btn btn-secondary" id="btn-prev-page" onclick="changePage(-1)">« Trang trước</button>
            <span id="page-info" style="color: var(--text-primary);">Trang 1</span>
            <button class="btn btn-secondary" id="btn-next-page" onclick="changePage(1)">Trang sau »</button>
        </div>
    </div>
</div>

<!-- Modal: Transaction Detail -->
<?php
$modal = [
    'id' => 'modal-transaction-detail',
    'title' => 'Chi Tiết Giao Dịch',
    'body' => '
    <div class="transaction-detail">
        <p><strong>Transaction ID:</strong> <span id="detail-transaction-id">-</span></p>
        <p><strong>User:</strong> <span id="detail-user">-</span></p>
        <p><strong>Số Tiền:</strong> <span id="detail-amount">-</span></p>
        <p><strong>Ngân Hàng:</strong> <span id="detail-bank">-</span></p>
        <p><strong>Trạng Thái:</strong> <span id="detail-status">-</span></p>
        <p><strong>Thời Gian:</strong> <span id="detail-created">-</span></p>
        <p><strong>Ghi Chú:</strong> <span id="detail-notes">-</span></p>
    </div>
    ',
    'buttons' => [
        ['label' => 'Đóng', 'class' => 'btn-secondary', 'onclick' => 'closeModal("modal-transaction-detail")']
    ]
];
include 'components/modal.php';
?>

<!-- Page-specific JavaScript -->
<script>
    const BASE_URL = '<?php echo BASE_URL; ?>';
    const PAYMENT_API = BASE_URL + '/app/Controllers/AdminPaymentController.php';

    let currentPage = 1;
    const itemsPerPage = 10;

    async function fetchJson(url, options = {}) {
        const res = await fetch(url, options);
        const data = await res.json();
        if (!res.ok) {
            throw new Error(data.message || 'Request failed');
        }
        return data;
    }

    function postForm(url, formData) {
        return fetchJson(url, { method: 'POST', body: formData });
    }

    // Load Deposit History (from deposit_transactions)
    function loadHistory() {
        const bankId = document.getElementById('filter-bank').value || '';
        const searchContent = document.getElementById('search-content').value || '';
        
        let url = PAYMENT_API + '?action=get_history';
        if (bankId) url += '&bank_id=' + encodeURIComponent(bankId);
        if (searchContent) url += '&search_content=' + encodeURIComponent(searchContent);
        url += '&page=' + currentPage + '&limit=' + itemsPerPage;

        fetchJson(url)
            .then(res => {
                if (res.success) {
                    const formatCurrency = (num) => {
                        return new Intl.NumberFormat('vi-VN').format(num) + 'đ';
                    };

                    document.getElementById('current-active-bank').textContent = res.current_bank || '-';
                    document.getElementById('total-amount').textContent = formatCurrency(res.total_amount || 0);

                    const filterSelect = document.getElementById('filter-bank');
                    if (res.banks && res.banks.length > 0) {
                        let options = '<option value="">Tất cả ngân hàng</option>';
                        res.banks.forEach(b => {
                            const status = b.is_active ? ' (Đang dùng)' : '';
                            options += `<option value="${b.id}">${b.bank_name}${status}</option>`;
                        });
                        filterSelect.innerHTML = options;
                    }

                    let html = '';
                    res.data.forEach(t => {
                        const statusText = {
                            'completed': '✓ Đã nhận',
                            'pending': '⏳ Chờ xử lý',
                            'failed': '✗ Thất bại',
                            'cancelled': '✗ Đã hủy'
                        };

                        const statusColor = {
                            'completed': 'green',
                            'pending': 'orange',
                            'failed': 'red',
                            'cancelled': 'gray'
                        };

                        const actionButtons = `<button class="btn btn-edit btn-sm" onclick="viewDepositDetail(${t.id})">Chi tiết</button>`;

                        // Extract content from notes field
                        const contentMatch = (t.notes || '').match(/Content: ([^|]+)/);
                        const content = contentMatch ? contentMatch[1].trim() : '-';

                        html += `
                        <tr>
                            <td>${t.id}</td>
                            <td>${t.username || 'N/A'} (ID: ${t.user_id})</td>
                            <td>${t.bank_name || 'N/A'}</td>
                            <td>${formatCurrency(t.amount || 0)}</td>
                            <td style="font-size: 12px;">${content}</td>
                            <td><span style="color: ${statusColor[t.status] || '#999'};">${statusText[t.status] || t.status}</span></td>
                            <td>${new Date(t.created_at).toLocaleString('vi-VN')}</td>
                            <td>${actionButtons}</td>
                        </tr>
                        `;
                    });
                    document.getElementById('history-list').innerHTML = html || '<tr><td colspan="8" style="text-align: center; color: #999;">Không có lịch sử</td></tr>';

                    // Update pagination info
                    const totalPages = Math.ceil((res.total_records || 0) / itemsPerPage);
                    document.getElementById('page-info').textContent = `Trang ${currentPage} / ${totalPages || 1} (${res.total_records || 0} records)`;
                    document.getElementById('btn-prev-page').disabled = currentPage <= 1;
                    document.getElementById('btn-next-page').disabled = currentPage >= totalPages;
                }
            })
            .catch(err => console.error('Error:', err));
    }

    function changePage(delta) {
        currentPage += delta;
        if (currentPage < 1) currentPage = 1;
        loadHistory();
    }

    // Bank management functions removed - use banks.php instead





    // Copy webhook URL
    function copyWebhookURL() {
        const input = document.getElementById('webhook-url');
        input.select();
        document.execCommand('copy');
        alert('Đã copy webhook URL!');
    }

    // View transaction detail
    function viewTransactionDetail(id) {
        fetchJson(PAYMENT_API + '?action=get_transaction_detail&id=' + id)
            .then(res => {
                if (res.success) {
                    const t = res.data;
                    document.getElementById('detail-transaction-id').textContent = t.transaction_code || '-';
                    document.getElementById('detail-user').textContent = `${t.username || 'N/A'} (ID: ${t.user_id || '-'})`;
                    document.getElementById('detail-amount').textContent = new Intl.NumberFormat('vi-VN').format(t.amount || 0) + 'đ';
                    document.getElementById('detail-bank').textContent = t.bank_name ? `${t.bank_name}` : 'N/A';
                    document.getElementById('detail-status').textContent = t.status || '-';
                    document.getElementById('detail-created').textContent = t.created_at ? new Date(t.created_at).toLocaleString('vi-VN') : '-';
                    document.getElementById('detail-notes').textContent = t.notes || '-';
                    openModal('modal-transaction-detail');
                }
            })
            .catch(err => console.error('Error:', err));
    }

    // Bank deposits are automatic (webhook) - no manual approval needed

    function viewDepositDetail(id) {
        viewTransactionDetail(id);
    }

    function loadSelectedBankDetail() {
        const bankId = document.getElementById('bank-selector').value;
        if (!bankId) {
            document.getElementById('bank-qr-body').innerHTML = '<div class="bank-qr-placeholder">Chọn ngân hàng để xem mã QR</div>';
            document.getElementById('bank-qr-info').textContent = '';
            return;
        }

        fetchJson(PAYMENT_API + '?action=get_bank_detail&id=' + bankId)
            .then(res => {
                if (res.success) {
                    const bank = res.data;
                    if (bank.qr_code_data) {
                        document.getElementById('bank-qr-body').innerHTML = `<img src="${bank.qr_code_data}" alt="QR" class="bank-qr-image">`;
                    } else {
                        document.getElementById('bank-qr-body').innerHTML = '<div class="bank-qr-placeholder">Chưa có mã QR. Nhấn "Tạo/Refresh QR"</div>';
                    }

                    const info = [];
                    if (bank.bank_name) info.push(bank.bank_name);
                    if (bank.account_number) info.push('STK: ' + bank.account_number);
                    if (bank.account_holder) info.push('CTK: ' + bank.account_holder);
                    document.getElementById('bank-qr-info').textContent = info.join(' | ');
                }
            })
            .catch(err => console.error('Error:', err));
    }

    function generateBankQR() {
        const bankId = document.getElementById('bank-selector').value;
        if (!bankId) {
            alert('Vui lòng chọn ngân hàng');
            return;
        }

        const formData = new FormData();
        formData.append('id', bankId);

        postForm(PAYMENT_API + '?action=generate_qr', formData)
            .then(res => {
                if (res.success) {
                    document.getElementById('bank-qr-body').innerHTML = `<img src="${res.qr_data}" alt="QR" class="bank-qr-image">`;
                    loadSelectedBankDetail();
                }
                alert(res.message || 'Tạo mã QR thành công');
            })
            .catch(err => alert('Lỗi: ' + err.message));
    }

    // Event listeners
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('btn-refresh-history').addEventListener('click', () => {
            currentPage = 1;
            loadHistory();
        });
        document.getElementById('filter-bank').addEventListener('change', () => {
            currentPage = 1;
            loadHistory();
        });
        document.getElementById('search-content').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                currentPage = 1;
                loadHistory();
            }
        });

        // Initial load
        loadHistory();
    });
</script>

<style>
.webhook-section {
    background: linear-gradient(135deg, rgba(0, 180, 216, 0.1) 0%, rgba(0, 180, 216, 0.05) 100%);
    border: 2px solid rgba(0, 180, 216, 0.3);
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
}

.webhook-header h3 {
    color: #00d4ff;
    margin: 0 0 8px 0;
    font-size: 1.3rem;
}

.webhook-desc {
    color: #b0bec5;
    font-size: 13px;
    margin: 0 0 18px 0;
}

.webhook-url-box {
    display: flex;
    gap: 10px;
    align-items: stretch;
}

.webhook-input {
    flex: 1;
    padding: 14px 16px;
    border: 2px solid rgba(0, 180, 216, 0.3);
    border-radius: 8px;
    background: rgba(0, 20, 40, 0.6);
    color: #e0e7ff;
    font-size: 14px;
    font-family: 'Courier New', monospace;
}

.webhook-input:focus {
    outline: none;
    border-color: #00d4ff;
    box-shadow: 0 0 0 5px rgba(0, 212, 255, 0.2);
}

.bank-selector-section {
    background: linear-gradient(135deg, rgba(0, 180, 216, 0.05) 0%, rgba(0, 180, 216, 0) 100%);
    border: 1px solid rgba(0, 180, 216, 0.2);
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
}

.bank-selector-section label {
    display: block;
    color: #00d4ff;
    font-weight: 700;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    margin-bottom: 15px;
}

.filter-section {
    display: flex;
    gap: 10px;
    margin-bottom: 25px;
    flex-wrap: wrap;
}

.stats-summary {
    display: flex;
    gap: 30px;
    margin-bottom: 25px;
    padding: 20px;
    background: linear-gradient(135deg, rgba(0, 180, 216, 0.1) 0%, rgba(0, 180, 216, 0.05) 100%);
    border: 2px solid rgba(0, 180, 216, 0.3);
    border-radius: 8px;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-label {
    color: #b0bec5;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 600;
}

.stat-value {
    color: #00d4ff;
    font-size: 18px;
    font-weight: 700;
}

.bank-qr-section {
    margin: 20px 0 30px 0;
    padding: 20px;
    border: 2px solid rgba(0, 180, 216, 0.2);
    border-radius: 12px;
    background: rgba(0, 180, 216, 0.05);
}

.bank-qr-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.bank-qr-header h4 {
    margin: 0;
    color: #00d4ff;
    font-size: 1rem;
}

.bank-qr-body {
    min-height: 220px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 20, 40, 0.6);
    border-radius: 10px;
    border: 1px solid rgba(0, 180, 216, 0.2);
    padding: 16px;
}

.bank-qr-image {
    max-width: 220px;
    max-height: 220px;
}

.bank-qr-placeholder {
    color: #b0bec5;
    font-size: 14px;
    text-align: center;
}

.bank-qr-info {
    margin-top: 12px;
    color: #b0bec5;
    font-size: 13px;
}

.btn-sm {
    padding: 6px 10px;
    font-size: 12px;
}
</style>
