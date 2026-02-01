/**
 * ADMIN CATEGORIES JS - JavaScript cho quản lý danh mục
 */

function openCategoryModal(category = null) {
    const modal = document.getElementById('categoryModal');
    const form = document.getElementById('categoryForm');
    const title = document.getElementById('modalTitle');

    if (category) {
        title.textContent = 'Sửa danh mục';
        document.getElementById('categoryId').value = category.id;
        document.getElementById('categoryName').value = category.name || '';
        document.getElementById('categorySlug').value = category.slug || '';
        document.getElementById('categoryIcon').value = category.icon || '';
        document.getElementById('categoryOrder').value = category.sort_order || 0;
        document.getElementById('categoryStatus').value = category.is_active ? '1' : '0';
    } else {
        title.textContent = 'Thêm danh mục';
        form.reset();
        document.getElementById('categoryId').value = '';
        document.getElementById('categoryOrder').value = 0;
        document.getElementById('categoryStatus').value = '1';
    }

    modal.style.display = 'flex';
}

function closeCategoryModal() {
    document.getElementById('categoryModal').style.display = 'none';
    document.getElementById('categoryForm').reset();
}

function editCategory(category) {
    openCategoryModal(category);
}

function deleteCategory(id) {
    if (!confirm('Bạn có chắc chắn muốn xóa danh mục này?')) {
        return;
    }

    fetch('../../app/Controllers/AdminCategoryController.php?action=delete', {
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
    .catch(() => {
        showNotification('Có lỗi xảy ra', 'error');
    });
}

// Form submit
const categoryForm = document.getElementById('categoryForm');
if (categoryForm) {
    categoryForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const categoryId = document.getElementById('categoryId').value;
        const action = categoryId ? 'update' : 'create';

        fetch(`../../app/Controllers/AdminCategoryController.php?action=${action}`, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                closeCategoryModal();
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(() => {
            showNotification('Có lỗi xảy ra', 'error');
        });
    });
}

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
    const modal = document.getElementById('categoryModal');
    if (event.target === modal) {
        closeCategoryModal();
    }
};
