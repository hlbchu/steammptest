/**
 * ADMIN PRODUCTS JS - JavaScript cho quản lý sản phẩm
 */

// Open product modal
function openProductModal(product = null) {
    const modal = document.getElementById('productModal');
    const form = document.getElementById('productForm');
    const title = document.getElementById('modalTitle');
    const imagePreview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    
    if (product) {
        title.textContent = 'Sửa sản phẩm';
        document.getElementById('productId').value = product.id;
        document.getElementById('productName').value = product.name;
        document.getElementById('productCategory').value = product.category_id || '';
        
        // Set purchase type
        const purchaseTypeSelect = document.getElementById('productPurchaseType');
        if (purchaseTypeSelect && product.purchase_type_id) {
            purchaseTypeSelect.value = product.purchase_type_id;
        }
        
        document.getElementById('productPrice').value = product.price;
        document.getElementById('productSalePrice').value = product.sale_price || '';
        document.getElementById('productBadge').value = product.badge || '';
        document.getElementById('productRating').value = product.rating || '';
        document.getElementById('productImage').value = product.image || '';
        document.getElementById('productStatus').value = product.status;
        document.getElementById('productDescription').value = product.description || '';
        const receivedField = document.getElementById('productReceivedContent');
        if (receivedField) {
            receivedField.value = product.received_content || '';
        }
        document.getElementById('productContent').value = product.content || '';
        const galleryField = document.getElementById('productGallery');
        if (galleryField) {
            galleryField.value = product.gallery || '';
        }
        const videoField = document.getElementById('productVideo');
        if (videoField) {
            videoField.value = product.video_url || '';
        }
        
        // Show image preview if exists
        if (product.image) {
            previewImg.src = product.image;
            imagePreview.style.display = 'block';
        } else {
            imagePreview.style.display = 'none';
        }
    } else {
        title.textContent = 'Thêm sản phẩm';
        form.reset();
        document.getElementById('productId').value = '';
        imagePreview.style.display = 'none';
    }
    
    modal.style.display = 'flex';
}

// Image preview on URL input
document.addEventListener('DOMContentLoaded', function() {
    const imageInput = document.getElementById('productImage');
    const imagePreview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    
    if (imageInput) {
        imageInput.addEventListener('input', function() {
            const url = this.value.trim();
            if (url && (url.startsWith('http://') || url.startsWith('https://'))) {
                previewImg.src = url;
                imagePreview.style.display = 'block';
                
                previewImg.onerror = function() {
                    imagePreview.style.display = 'none';
                };
            } else {
                imagePreview.style.display = 'none';
            }
        });
    }
});

// Close product modal
function closeProductModal() {
    document.getElementById('productModal').style.display = 'none';
    document.getElementById('productForm').reset();
}

// Edit product
function editProduct(product) {
    openProductModal(product);
}

// Delete product
function deleteProduct(id) {
    if (!confirm('Bạn có chắc chắn muốn xóa sản phẩm này?')) {
        return;
    }

    fetch('../../app/Controllers/AdminProductController.php?action=delete', {
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

// Quick update field (category, purchase type)
function quickUpdateField(productId, field, value) {
    fetch('../../app/Controllers/AdminProductController.php?action=quickUpdate', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `id=${productId}&field=${field}&value=${value}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
        } else {
            showNotification(data.message, 'error');
            // Reload on error to revert dropdown
            setTimeout(() => location.reload(), 1000);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Có lỗi xảy ra', 'error');
        setTimeout(() => location.reload(), 1000);
    });
}

// Toggle multi-select dropdown
function toggleMultiSelect(event, productId) {
    event.stopPropagation();
    const wrapper = event.currentTarget.closest('.multi-select-wrapper');
    const isOpen = wrapper.classList.contains('open');
    
    // Close all other dropdowns
    document.querySelectorAll('.multi-select-wrapper.open').forEach(w => {
        if (w !== wrapper) w.classList.remove('open');
    });
    
    // Toggle current dropdown
    wrapper.classList.toggle('open');
}

// Update categories for a product
function updateCategories(productId) {
    const dropdown = document.getElementById(`category-dropdown-${productId}`);
    const checkboxes = dropdown.querySelectorAll('input[type="checkbox"]:checked');
    const categoryIds = Array.from(checkboxes).map(cb => cb.value);
    
    fetch('../../app/Controllers/AdminProductController.php?action=updateCategories', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `id=${productId}&category_ids=${JSON.stringify(categoryIds)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            // Update count display
            const trigger = dropdown.previousElementSibling;
            const countSpan = trigger.querySelector('.selected-count');
            const count = categoryIds.length;
            countSpan.textContent = count > 0 ? `${count} danh mục` : 'Chọn danh mục';
        } else {
            showNotification(data.message, 'error');
            setTimeout(() => location.reload(), 1000);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Có lỗi xảy ra', 'error');
        setTimeout(() => location.reload(), 1000);
    });
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.multi-select-wrapper')) {
        document.querySelectorAll('.multi-select-wrapper.open').forEach(wrapper => {
            wrapper.classList.remove('open');
        });
    }
});

// Form submit
document.getElementById('productForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const productId = document.getElementById('productId').value;
    const action = productId ? 'update' : 'create';
    
    fetch(`../../app/Controllers/AdminProductController.php?action=${action}`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            closeProductModal();
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
    const modal = document.getElementById('productModal');
    if (event.target === modal) {
        closeProductModal();
    }
}
