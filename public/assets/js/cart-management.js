/**
 * CART MANAGEMENT - Xử lý giỏ hàng (xoá, thay đổi số lượng)
 */

document.addEventListener('DOMContentLoaded', function() {
    // Delete button handlers
    document.querySelectorAll('.btn-remove').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            removeCartItem(this);
        });
    });

    // Quantity increase/decrease buttons
    document.querySelectorAll('.qty-increase').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            updateQuantity(this, 1);
        });
    });

    document.querySelectorAll('.qty-decrease').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            updateQuantity(this, -1);
        });
    });

    // Quantity input change
    document.querySelectorAll('.qty-input').forEach(input => {
        input.addEventListener('change', function(e) {
            const newQty = parseInt(this.value) || 1;
            const productId = this.closest('.cart-item').getAttribute('data-product-id');
            updateCartQuantity(productId, newQty);
        });
    });
});

/**
 * Xoá sản phẩm khỏi giỏ
 */
function removeCartItem(button) {
    const cartItem = button.closest('.cart-item');
    const productId = cartItem.getAttribute('data-product-id');
    
    if (!confirm('Bạn có chắc chắn muốn xoá sản phẩm này?')) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'remove');
    formData.append('product_id', productId);

    fetch('/steamweb/app/Controllers/CartController.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Animate item removal
            cartItem.style.animation = 'slideOut 0.3s ease-out forwards';
            setTimeout(() => {
                cartItem.remove();
                
                // Update cart count
                updateHeaderCartCount(data.cartCount || 0);
                updateCartSummary(data.cartTotal || 0, data.itemCount || 0);
                
                // Check if cart is empty
                const cartList = document.querySelector('.cart-list');
                if (cartList && cartList.children.length === 0) {
                    location.reload();
                }
                
                showNotification('Đã xoá khỏi giỏ hàng', 'success');
            }, 300);
        } else {
            showNotification(data.message || 'Không thể xoá sản phẩm', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Có lỗi xảy ra', 'error');
    });
}

/**
 * Thay đổi số lượng (+/-)
 */
function updateQuantity(button, change) {
    const container = button.closest('.item-quantity');
    const input = container.querySelector('.qty-input');
    let newQty = parseInt(input.value) + change;
    
    if (newQty < 1) newQty = 1;
    if (newQty > 100) newQty = 100;
    
    input.value = newQty;
    
    const productId = button.closest('.cart-item').getAttribute('data-product-id');
    updateCartQuantity(productId, newQty);
}

/**
 * Cập nhật số lượng sản phẩm trong giỏ
 */
function updateCartQuantity(productId, quantity) {
    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('product_id', productId);
    formData.append('quantity', quantity);

    fetch('/steamweb/app/Controllers/CartController.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update item total price
            const cartItem = document.querySelector(`.cart-item[data-product-id="${productId}"]`);
            if (cartItem) {
                const unitPrice = parseFloat(cartItem.querySelector('.unit-price').textContent.replace(/[^0-9.]/g, ''));
                const totalPrice = unitPrice * quantity;
                cartItem.querySelector('.total-price').textContent = formatCurrency(totalPrice);
            }
            
            // Update header cart count
            updateHeaderCartCount(data.cartCount || 0);
            
            // Update cart summary
            updateCartSummary(data.cartTotal || 0, data.itemCount || 0);
            
            showNotification('Cập nhật giỏ hàng thành công', 'success');
        } else {
            showNotification(data.message || 'Không thể cập nhật', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Có lỗi xảy ra', 'error');
    });
}

/**
 * Cập nhật số lượng giỏ hàng trong header
 */
function updateHeaderCartCount(count) {
    const badge = document.querySelector('.cart-badge');
    if (badge) {
        if (count > 0) {
            badge.textContent = count;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }
}

/**
 * Cập nhật tóm tắt giỏ hàng
 */
function updateCartSummary(total, itemCount) {
    const itemCountEl = document.querySelector('.item-count');
    if (itemCountEl) {
        itemCountEl.textContent = itemCount + ' sản phẩm';
    }
    
    const totalPriceEl = document.querySelector('.cart-total-price');
    if (totalPriceEl) {
        totalPriceEl.textContent = formatCurrency(total);
    }
}

/**
 * Format tiền tệ
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount).replace('₫', '').trim() + '₫';
}

/**
 * Hiển thị notification
 */
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Add slideOut animation
const style = document.createElement('style');
style.textContent = `
@keyframes slideOut {
    from {
        opacity: 1;
        transform: translateX(0);
    }
    to {
        opacity: 0;
        transform: translateX(100%);
    }
}

.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 12px 20px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    z-index: 9999;
    opacity: 0;
    transform: translateY(-20px);
    transition: all 0.3s ease;
}

.notification.show {
    opacity: 1;
    transform: translateY(0);
}

.notification-success {
    background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(46, 204, 113, 0.3);
}

.notification-error {
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
}

.notification-warning {
    background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(243, 156, 18, 0.3);
}

.notification-info {
    background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
}
`;
document.head.appendChild(style);
