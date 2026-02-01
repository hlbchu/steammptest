/* ==============================================
   ADMIN JAVASCRIPT UTILITIES
   ============================================== */

// ==============================================
// MODAL FUNCTIONS
// ==============================================

/**
 * Mở modal
 * @param {string} modalId - ID của modal
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
    }
}

/**
 * Đóng modal
 * @param {string} modalId - ID của modal
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
    }
}

// Đóng modal khi click bên ngoài
document.addEventListener('click', function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('show');
    }
});

// ==============================================
// ALERT FUNCTIONS
// ==============================================

/**
 * Hiển thị thông báo thành công
 * @param {string} message - Nội dung tin nhắn
 * @param {number} duration - Thời gian hiển thị (ms)
 */
function showSuccess(message, duration = 3000) {
    const alert = createAlert(message, 'alert-success', '✓');
    document.body.appendChild(alert);
    setTimeout(() => alert.remove(), duration);
}

/**
 * Hiển thị thông báo lỗi
 * @param {string} message - Nội dung tin nhắn
 * @param {number} duration - Thời gian hiển thị (ms)
 */
function showError(message, duration = 3000) {
    const alert = createAlert(message, 'alert-danger', '✗');
    document.body.appendChild(alert);
    setTimeout(() => alert.remove(), duration);
}

/**
 * Hiển thị thông báo cảnh báo
 * @param {string} message - Nội dung tin nhắn
 * @param {number} duration - Thời gian hiển thị (ms)
 */
function showWarning(message, duration = 3000) {
    const alert = createAlert(message, 'alert-warning', '⚠️');
    document.body.appendChild(alert);
    setTimeout(() => alert.remove(), duration);
}

/**
 * Tạo phần tử alert
 * @param {string} message - Nội dung tin nhắn
 * @param {string} type - Loại alert (alert-success, alert-danger, etc.)
 * @param {string} icon - Icon
 * @returns {HTMLElement}
 */
function createAlert(message, type, icon) {
    const alert = document.createElement('div');
    alert.className = `alert ${type}`;
    alert.textContent = `${icon} ${message}`;
    alert.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        animation: slideIn 0.3s ease-in-out;
    `;
    return alert;
}

// ==============================================
// TABLE FUNCTIONS
// ==============================================

/**
 * Lấy dữ liệu từ form
 * @param {string} formId - ID của form
 * @returns {Object}
 */
function getFormData(formId) {
    const form = document.getElementById(formId);
    const formData = new FormData(form);
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    return data;
}

/**
 * Reset form
 * @param {string} formId - ID của form
 */
function resetForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.reset();
    }
}

/**
 * Validate form
 * @param {string} formId - ID của form
 * @returns {boolean}
 */
function validateForm(formId) {
    const form = document.getElementById(formId);
    return form ? form.checkValidity() : false;
}

// ==============================================
// CONFIRM DIALOG
// ==============================================

/**
 * Hiển thị hộp xác nhận
 * @param {string} message - Nội dung tin nhắn
 * @param {Function} onConfirm - Callback khi xác nhận
 * @param {Function} onCancel - Callback khi hủy bỏ
 */
function confirmDelete(message, onConfirm, onCancel) {
    if (confirm(message || 'Bạn có chắc chắn muốn xóa?')) {
        onConfirm && onConfirm();
    } else {
        onCancel && onCancel();
    }
}

// ==============================================
// API FUNCTIONS (Mock)
// ==============================================

/**
 * Gọi API (Giả lập)
 * @param {string} endpoint - Đường dẫn API
 * @param {string} method - Phương thức HTTP
 * @param {Object} data - Dữ liệu gửi
 * @returns {Promise}
 */
async function apiCall(endpoint, method = 'GET', data = null) {
    try {
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            }
        };

        if (data && (method === 'POST' || method === 'PUT')) {
            options.body = JSON.stringify(data);
        }

        const response = await fetch(endpoint, options);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return await response.json();
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}

// ==============================================
// UTILITY FUNCTIONS
// ==============================================

/**
 * Format tiền tệ
 * @param {number} amount - Số tiền
 * @returns {string}
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount);
}

/**
 * Format ngày tháng
 * @param {string|Date} date - Ngày
 * @returns {string}
 */
function formatDate(date) {
    return new Date(date).toLocaleDateString('vi-VN');
}

/**
 * Highlight text trong bảng
 * @param {string} searchText - Từ tìm kiếm
 * @param {string} tableId - ID của bảng
 */
function highlightSearch(searchText, tableId) {
    const table = document.getElementById(tableId);
    if (!table) return;

    const cells = table.querySelectorAll('td');
    cells.forEach(cell => {
        const text = cell.textContent;
        const regex = new RegExp(`(${searchText})`, 'gi');
        cell.innerHTML = text.replace(regex, '<mark>$1</mark>');
    });
}

// ==============================================
// PAGINATION
// ==============================================

/**
 * Chuyển trang
 * @param {number} page - Số trang
 */
function goToPage(page) {
    console.log(`Chuyển đến trang: ${page}`);
    // Sẽ implement thực tế
}

// ==============================================
// EXPORT FUNCTIONS
// ==============================================

/**
 * Export dữ liệu thành CSV
 * @param {string} tableId - ID của bảng
 * @param {string} filename - Tên file
 */
function exportToCSV(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;

    let csv = [];
    const rows = table.querySelectorAll('tr');

    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        let csvRow = [];
        cols.forEach(col => {
            csvRow.push('"' + col.textContent.trim() + '"');
        });
        csv.push(csvRow.join(','));
    });

    downloadFile(csv.join('\n'), filename, 'text/csv');
}

/**
 * Export dữ liệu thành JSON
 * @param {Object} data - Dữ liệu
 * @param {string} filename - Tên file
 */
function exportToJSON(data, filename = 'export.json') {
    const json = JSON.stringify(data, null, 2);
    downloadFile(json, filename, 'application/json');
}

/**
 * Download file
 * @param {string} content - Nội dung file
 * @param {string} filename - Tên file
 * @param {string} type - MIME type
 */
function downloadFile(content, filename, type) {
    const blob = new Blob([content], { type: type });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    link.click();
    window.URL.revokeObjectURL(url);
}

// ==============================================
// THEME FUNCTIONS
// ==============================================

/**
 * Thay đổi theme
 * @param {string} theme - Tên theme (light, dark)
 */
function switchTheme(theme) {
    if (theme === 'light') {
        document.documentElement.style.setProperty('--primary-bg', '#f5f5f5');
        document.documentElement.style.setProperty('--text-primary', '#333333');
    } else {
        document.documentElement.style.setProperty('--primary-bg', '#0a0e27');
        document.documentElement.style.setProperty('--text-primary', '#ffffff');
    }
    localStorage.setItem('theme', theme);
}

// ==============================================
// INITIALIZATION
// ==============================================

document.addEventListener('DOMContentLoaded', function() {
    // Khôi phục theme từ localStorage
    const savedTheme = localStorage.getItem('theme') || 'dark';
    switchTheme(savedTheme);

    // Thiết lập các event listeners
    setupEventListeners();
});

/**
 * Thiết lập event listeners
 */
function setupEventListeners() {
    // Event cho các button xóa
    document.querySelectorAll('[data-action="delete"]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.dataset.id;
            confirmDelete('Bạn có chắc chắn muốn xóa?', () => {
                deleteItem(id);
            });
        });
    });

    // Event cho các form submit
    document.querySelectorAll('form[data-api]').forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const endpoint = this.dataset.api;
            const data = getFormData(this.id);
            
            try {
                const result = await apiCall(endpoint, 'POST', data);
                showSuccess('Lưu thành công!');
                this.reset();
            } catch (error) {
                showError('Lỗi: ' + error.message);
            }
        });
    });
}
