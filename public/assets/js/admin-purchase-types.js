/**
 * ADMIN PURCHASE TYPES JS - JavaScript cho quản lý loại mua
 */

// Open modal
function openModal(purchaseType = null) {
    const modal = document.getElementById('purchaseTypeModal');
    const form = document.getElementById('purchaseTypeForm');
    const title = document.getElementById('modalTitle');
    
    if (purchaseType) {
        title.textContent = 'Sửa loại mua';
        document.getElementById('purchaseTypeId').value = purchaseType.id;
        document.getElementById('purchaseTypeName').value = purchaseType.name;
        document.getElementById('purchaseTypeDescription').value = purchaseType.description || '';
        document.getElementById('purchaseTypeActive').checked = purchaseType.is_active == 1;
    } else {
        title.textContent = 'Thêm loại mua';
        form.reset();
        document.getElementById('purchaseTypeId').value = '';
        document.getElementById('purchaseTypeActive').checked = true;
    }
    
    modal.style.display = 'flex';
}

// Close modal
function closeModal() {
    document.getElementById('purchaseTypeModal').style.display = 'none';
    document.getElementById('purchaseTypeForm').reset();
}

// Edit purchase type
function editPurchaseType(purchaseType) {
    openModal(purchaseType);
}

// Delete purchase type
function deletePurchaseType(id) {
    if (!confirm('Bạn có chắc chắn muốn xóa loại mua này?')) {
        return;
    }

    fetch('../../app/Controllers/AdminPurchaseTypeController.php?action=delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `id=${id}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Có lỗi xảy ra', 'error');
    });
}

// Toggle active status
function toggleActive(id, is_active) {
    fetch('../../app/Controllers/AdminPurchaseTypeController.php?action=toggleActive', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `id=${id}&is_active=${is_active}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
        } else {
            showNotification(data.message, 'error');
            // Revert checkbox on error
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Có lỗi xảy ra', 'error');
        location.reload();
    });
}

// Form submit
document.getElementById('purchaseTypeForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const purchaseTypeId = document.getElementById('purchaseTypeId').value;
    const action = purchaseTypeId ? 'update' : 'create';
    
    fetch(`../../app/Controllers/AdminPurchaseTypeController.php?action=${action}`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            closeModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Có lỗi xảy ra', 'error');
    });
});

// Show notification
function showNotification(message, type = 'info') {
    const existing = document.querySelector('.notification');
    if (existing) existing.remove();

    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span>${message}</span>
        </div>
    `;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.classList.add('fade-out');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('purchaseTypeModal');
    if (event.target === modal) {
        closeModal();
    }
}
