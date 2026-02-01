/**
 * GLOBAL ADD TO CART - JavaScript xử lý thêm vào giỏ trên toàn site
 */

document.addEventListener('DOMContentLoaded', function() {
    // Add to cart buttons
    document.querySelectorAll('[data-add-to-cart]').forEach(btn => {
        if (btn.dataset.cartListenerAdded) return; // Prevent duplicate listeners
        btn.dataset.cartListenerAdded = 'true';
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            addToCart(this);
        });
    });
});

/**
 * Thêm sản phẩm vào giỏ
 */
function addToCart(button) {
    const productId = button.getAttribute('data-product-id');
    // Mỗi lần click chỉ thêm 1 sản phẩm
    const quantity = 1;

    if (!productId) {
        showNotification('Lỗi: Không thể xác định sản phẩm', 'error');
        return;
    }

    // Show loading state
    const originalHTML = button.innerHTML;
    button.innerHTML = '<span>Đang thêm...</span>';
    button.disabled = true;

    fetch('/steamweb/app/Controllers/AddToCartController.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Play sound effect
            if (typeof soundManager !== 'undefined') {
                soundManager.play('cart');
            } else {
                playAddToCartSound();
            }
            
            // Animate flying to cart
            animateFlyToCart(button);
            
            // Update button state with checkmark icon
            button.classList.add('added');
            button.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24"><path fill="currentColor" d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>';
            button.disabled = false;
            
            showNotification(data.message, 'success');
            // Update cart count in header
            updateCartCount(data.cartCount);
            
            // Reset button after 2 seconds
            setTimeout(() => {
                if (button.classList.contains('added')) {
                    button.classList.remove('added');
                    button.innerHTML = originalHTML;
                }
            }, 2000);
        } else if (data.requireLogin) {
            showNotification(data.message, 'warning');
            button.innerHTML = originalHTML;
            button.disabled = false;
            // Redirect to login after 1 second
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 1500);
        } else {
            showNotification(data.message, 'error');
            button.innerHTML = originalHTML;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Có lỗi xảy ra, vui lòng thử lại', 'error');
        button.innerHTML = originalHTML;
        button.disabled = false;
    });
}

/**
 * Animation bay giỏ hàng từ nút đến icon giỏ
 */
function animateFlyToCart(button) {
    const cartIcon = document.querySelector('.action-icon .icon');
    if (!cartIcon) return;
    
    const buttonRect = button.getBoundingClientRect();
    const cartRect = cartIcon.getBoundingClientRect();
    
    const translateX = cartRect.left - buttonRect.left;
    const translateY = cartRect.top - buttonRect.top;
    
    button.style.setProperty('--tx', translateX + 'px');
    button.style.setProperty('--ty', translateY + 'px');
    button.classList.add('flying');
    
    setTimeout(() => {
        button.classList.remove('flying');
        button.style.removeProperty('--tx');
        button.style.removeProperty('--ty');
    }, 800);
}

/**
 * Play sound effect
 */
function playAddToCartSound() {
    // Create audio context
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    
    // Create oscillator for "ting" sound
    const oscillator = audioContext.createOscillator();
    const gain = audioContext.createGain();
    
    oscillator.connect(gain);
    gain.connect(audioContext.destination);
    
    // Set frequency for a pleasant "ting" sound
    oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
    oscillator.frequency.exponentialRampToValueAtTime(600, audioContext.currentTime + 0.1);
    
    // Set gain envelope
    gain.gain.setValueAtTime(0.3, audioContext.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);
    
    oscillator.start(audioContext.currentTime);
    oscillator.stop(audioContext.currentTime + 0.1);
}

/**
 * Cập nhật số lượng giỏ hàng trong header
 */
function updateCartCount(count) {
    const cartIcon = document.querySelector('.user-actions .action-icon');
    if (cartIcon) {
        let badge = cartIcon.querySelector('.cart-badge');
        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'cart-badge';
            cartIcon.appendChild(badge);
        }
        
        if (count > 0) {
            badge.textContent = count;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }
}

/**
 * Hiển thị notification
 */
function showNotification(message, type = 'info') {
    // Remove existing notification
    const existing = document.querySelector('.notification');
    if (existing) existing.remove();

    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <svg class="notification-icon" viewBox="0 0 24 24">
                ${getNotificationIcon(type)}
            </svg>
            <span>${message}</span>
        </div>
    `;

    document.body.appendChild(notification);

    // Auto remove after 3 seconds
    setTimeout(() => {
        notification.classList.add('fade-out');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

/**
 * Get notification icon SVG
 */
function getNotificationIcon(type) {
    const icons = {
        success: '<path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z" fill="currentColor"/>',
        error: '<path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z" fill="currentColor"/>',
        warning: '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z" fill="currentColor"/>',
        info: '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" fill="currentColor"/>'
    };
    return icons[type] || icons.info;
}
