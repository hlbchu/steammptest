/**
 * Admin Navigation Menu Management
 */

// Open modal
function openModal() {
    document.getElementById('navMenuModal').style.display = 'block';
    document.getElementById('modalTitle').textContent = 'Thêm mục menu';
    document.getElementById('navMenuForm').reset();
    document.getElementById('menuId').value = '';
    document.getElementById('menuProtected').disabled = false;
    document.getElementById('protectWarning').style.display = 'none';
    document.getElementById('submenuNote').style.display = 'none';
}

// Close modal
function closeModal() {
    document.getElementById('navMenuModal').style.display = 'none';
}

// Open submenu modal
function openSubmenuModal(parentId, parentName) {
    document.getElementById('submenuModal').style.display = 'block';
    document.getElementById('parentMenuId').value = parentId;
    document.getElementById('parentMenuName').textContent = parentName;
    loadSubmenus(parentId);
    closeSubmenuForm();
}

// Close submenu modal
function closeSubmenuModal() {
    document.getElementById('submenuModal').style.display = 'none';
}

// Open submenu form
function openSubmenuForm(reset = true) {
    const form = document.getElementById('submenuForm');
    form.classList.add('show');
    if (reset) {
        document.getElementById('addSubmenuForm').reset();
        const colorInput = document.getElementById('submenuTextColor');
        if (colorInput) {
            colorInput.value = '#ffffff';
            const colorValue = document.getElementById('submenuTextColorValue');
            if (colorValue) {
                colorValue.textContent = '#ffffff';
            }
        }
    }
    document.getElementById('submenuName').focus();
}

// Close submenu form
function closeSubmenuForm() {
    const form = document.getElementById('submenuForm');
    form.classList.remove('show');
    document.getElementById('addSubmenuForm').reset();
    delete document.getElementById('addSubmenuForm').dataset.submenuId;
}

// Load submenus
async function loadSubmenus(parentId) {
    try {
        const response = await fetch('../../app/Controllers/AdminNavMenuRouter.php?action=getSubmenus&parent_id=' + parentId);
        const result = await response.json();
        
        if (result.success) {
            console.log('📋 Submenu data:', result.data); // Debug
            let html = '';
            const count = result.data.length;
            
            // Update count badge
            document.getElementById('submenuCount').textContent = count;
            
            if (count === 0) {
                html = '<div class="submenu-empty" data-icon="📭"><span>Chưa có trang mục con nào.<br>Hãy thêm mục con đầu tiên!</span></div>';
            } else {
                result.data.forEach(submenu => {
                    console.log('🔹 Rendering submenu:', submenu.name, submenu); // Debug
                    const icon = (submenu.icon || '').trim();
                    const isHexIcon = /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(icon);
                    const textColor = submenu.text_color || '#ffffff';
                    const displayOrder = typeof submenu.display_order !== 'undefined' ? submenu.display_order : 0;
                    let iconHtml = '';
                    if (icon && !isHexIcon) {
                        if (icon.startsWith('<svg')) {
                            iconHtml = `<span class="submenu-item-icon submenu-item-icon-svg">${icon}</span>`;
                        } else if (icon.match(/\.(svg|png|jpg|jpeg|webp)$/i)) {
                            iconHtml = `<img src="../../public/assets/svg/${icon}" class="submenu-item-icon submenu-item-icon-img" alt="">`;
                        } else {
                            iconHtml = `<span class="submenu-item-icon submenu-item-icon-fallback">${icon}</span>`;
                        }
                    }

                    const dataName = encodeURIComponent(submenu.name || '');
                    const dataLink = encodeURIComponent(submenu.link || '');
                    const dataIcon = encodeURIComponent(submenu.icon || '');
                    const dataColor = encodeURIComponent(submenu.text_color || '#ffffff');
                    
                    // Escape HTML for safe rendering
                    const safeName = (submenu.name || 'Không có tên').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                    const safeLink = (submenu.link || 'Không có link').replace(/</g, '&lt;').replace(/>/g, '&gt;');

                    html += `<div class="submenu-item">
                        <div class="submenu-item-info">
                            <div class="submenu-item-name" style="color: ${textColor};">${iconHtml}<span class="submenu-name-text">${safeName}</span><span class="submenu-item-order">#${displayOrder}</span></div>
                            <span class="submenu-item-link" title="${safeLink}">${safeLink}</span>
                        </div>
                        <div class="submenu-item-actions">
                            <button type="button" class="btn-submenu-edit" data-id="${submenu.id}" data-name="${dataName}" data-link="${dataLink}" data-icon="${dataIcon}" data-color="${dataColor}" data-order="${displayOrder}" title="Sửa">✎</button>
                            <button type="button" class="btn-submenu-delete" data-id="${submenu.id}" title="Xóa">✕</button>
                        </div>
                    </div>`;
                });
            }
            document.getElementById('submenuList').innerHTML = html;
        }
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('submenuList').innerHTML = '<div class="submenu-empty" data-icon="❌"><span>Lỗi khi tải dữ liệu</span></div>';
    }
}

// Submenu list event delegation
document.addEventListener('DOMContentLoaded', function() {
    const submenuList = document.getElementById('submenuList');
    if (submenuList && !submenuList.dataset.bound) {
        submenuList.dataset.bound = 'true';
        submenuList.addEventListener('click', function(e) {
            const editBtn = e.target.closest('.btn-submenu-edit');
            const deleteBtn = e.target.closest('.btn-submenu-delete');

            if (editBtn) {
                const id = parseInt(editBtn.dataset.id || '0', 10);
                const name = decodeURIComponent(editBtn.dataset.name || '');
                const link = decodeURIComponent(editBtn.dataset.link || '');
                const icon = decodeURIComponent(editBtn.dataset.icon || '');
                const color = decodeURIComponent(editBtn.dataset.color || '#ffffff');
                const order = parseInt(editBtn.dataset.order || '0', 10);
                editSubmenu(id, name, link, icon, color, order);
                return;
            }

            if (deleteBtn) {
                const id = parseInt(deleteBtn.dataset.id || '0', 10);
                deleteSubmenu(id);
            }
        });
    }

    const colorPicker = document.getElementById('submenuTextColor');
    if (colorPicker) {
        colorPicker.addEventListener('change', function() {
            const colorValue = document.getElementById('submenuTextColorValue');
            if (colorValue) {
                colorValue.textContent = this.value;
            }
        });
        colorPicker.addEventListener('input', function() {
            const colorValue = document.getElementById('submenuTextColorValue');
            if (colorValue) {
                colorValue.textContent = this.value;
            }
        });
    }
});

// Edit submenu
function editSubmenu(id, name, link, icon = '', textColor = '#ffffff', displayOrder = 0) {
    openSubmenuForm(false);
    document.getElementById('submenuName').value = name;
    document.getElementById('submenuLink').value = link;
    document.getElementById('submenuIcon').value = icon;
    document.getElementById('submenuTextColor').value = textColor;
    document.getElementById('submenuTextColorValue').textContent = textColor;
    document.getElementById('submenuOrder').value = displayOrder;
    document.getElementById('addSubmenuForm').dataset.submenuId = id;
    document.getElementById('submenuName').focus();
}

// Delete submenu
async function deleteSubmenu(id) {
    if (!confirm('Bạn có chắc chắn muốn xóa trang mục con này?')) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('id', id);
        
        const response = await fetch('../../app/Controllers/AdminNavMenuRouter.php?action=deleteSubmenu', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        if (result.success) {
            const parentId = document.getElementById('parentMenuId').value;
            loadSubmenus(parentId);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Edit menu
function editMenu(menu) {
    document.getElementById('navMenuModal').style.display = 'block';
    document.getElementById('modalTitle').textContent = 'Sửa mục menu';
    document.getElementById('menuId').value = menu.id;
    document.getElementById('menuName').value = menu.name;
    document.getElementById('menuLink').value = menu.link || '';
    document.getElementById('menuIcon').value = menu.icon || '';
    document.getElementById('menuColor').value = menu.text_color || '#ffffff';
    document.getElementById('menuOrder').value = menu.display_order;
    document.getElementById('menuActive').checked = menu.is_active == 1;
    document.getElementById('menuProtected').checked = menu.is_protected == 1;
    document.getElementById('submenuNote').style.display = 'block';
    
    // Khóa checkbox bảo vệ nếu là Trang chủ (slug = 'index')
    if (menu.slug === 'index') {
        document.getElementById('menuProtected').disabled = true;
        document.getElementById('menuProtected').checked = true;
        document.getElementById('protectWarning').style.display = 'block';
    } else {
        document.getElementById('menuProtected').disabled = false;
        document.getElementById('protectWarning').style.display = 'none';
    }
}

// Delete menu
async function deleteMenu(id) {
    if (!confirm('Bạn có chắc chắn muốn xóa menu này?')) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);

        const response = await fetch('../../app/Controllers/AdminNavMenuRouter.php?action=delete', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showNotification(result.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(result.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Có lỗi xảy ra khi xóa menu!', 'error');
    }
}

// Toggle active status
async function toggleActive(id, isActive) {
    try {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('is_active', isActive);

        const response = await fetch('../../app/Controllers/AdminNavMenuRouter.php?action=toggleActive', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showNotification(result.message, 'success');
        } else {
            showNotification(result.message, 'error');
            // Revert checkbox if failed
            setTimeout(() => location.reload(), 1000);
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Có lỗi xảy ra!', 'error');
        setTimeout(() => location.reload(), 1000);
    }
}

// Handle form submission
document.getElementById('navMenuForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const menuId = document.getElementById('menuId').value;
    const action = menuId ? 'update' : 'create';

    try {
        const response = await fetch(`../../app/Controllers/AdminNavMenuRouter.php?action=${action}`, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showNotification(result.message, 'success');
            closeModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(result.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Có lỗi xảy ra khi lưu menu!', 'error');
    }
});

// Handle submenu form submission
document.getElementById('addSubmenuForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submenuId = this.dataset.submenuId;
    const action = submenuId ? 'updateSubmenu' : 'createSubmenu';
    
    if (submenuId) {
        formData.append('id', submenuId);
    }
    
    try {
        const response = await fetch(`../../app/Controllers/AdminNavMenuRouter.php?action=${action}`, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        if (result.success) {
            const parentId = document.getElementById('parentMenuId').value;
            loadSubmenus(parentId);
            closeSubmenuForm();
        }
    } catch (error) {
        console.error('Error:', error);
    }
});

// Show notification
function showNotification(message, type) {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notif => notif.remove());

    // Create notification
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    // Style notification
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 16px 24px;
        background: ${type === 'success' ? 'linear-gradient(135deg, #10b981, #059669)' : 'linear-gradient(135deg, #ef4444, #dc2626)'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        z-index: 10000;
        animation: slideInRight 0.3s ease;
        font-weight: 500;
    `;

    document.body.appendChild(notification);

    // Auto remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('navMenuModal');
    if (event.target == modal) {
        closeModal();
    }
}

// Add animation keyframes
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
