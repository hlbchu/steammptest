/**
 * CART.JS - JavaScript cho trang giỏ hàng
 */

document.addEventListener('DOMContentLoaded', function() {
    // Update quantity
    document.querySelectorAll('.qty-decrease, .qty-increase').forEach(btn => {
        btn.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            const input = this.closest('.item-quantity').querySelector('.qty-input');
            let qty = parseInt(input.value);

            if (this.classList.contains('qty-decrease')) {
                qty = Math.max(1, qty - 1);
            } else {
                qty = Math.min(100, qty + 1);
            }

            input.value = qty;
            updateCart(productId, qty);
        });
    });

    // Direct quantity input change
    document.querySelectorAll('.qty-input').forEach(input => {
        input.addEventListener('change', function() {
            const productId = this.closest('.cart-item').getAttribute('data-product-id');
            let qty = parseInt(this.value);

            if (isNaN(qty) || qty < 1) {
                qty = 1;
            }
            if (qty > 100) {
                qty = 100;
            }

            this.value = qty;
            updateCart(productId, qty);
        });
    });

    // Remove item from cart
    document.querySelectorAll('.btn-remove').forEach(btn => {
        btn.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            removeFromCart(productId);
        });
    });

    // Apply promo code
    document.getElementById('btnApplyPromo')?.addEventListener('click', function() {
        const promoCode = document.getElementById('promoCode').value;
        if (promoCode.trim()) {
            applyPromoCode(promoCode);
        }
    });

    // Continue shopping button
    document.querySelector('.btn-continue')?.addEventListener('click', function() {
        window.location.href = 'index.php';
    });

    // Checkout button
    document.querySelector('.btn-checkout')?.addEventListener('click', function() {
        const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
        if (isLoggedIn) {
            window.location.href = 'checkout.php';
        } else {
            window.location.href = 'login.php';
        }
    });
});

/**
 * Update cart quantity
 */
function updateCart(productId, quantity) {
    fetch('../app/Controllers/CartController.php?action=update', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload page to update all prices correctly
            location.reload();
        } else {
            console.error('Error updating cart:', data.message);
        }
    })
    .catch(error => console.error('Error:', error));
}

/**
 * Remove item from cart
 */
function removeFromCart(productId) {
    if (confirm('Bạn có chắc chắn muốn xóa sản phẩm này?')) {
        fetch('../app/Controllers/CartController.php?action=remove', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `product_id=${productId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove item element
                document.querySelector(`[data-product-id="${productId}"]`).remove();
                location.reload();
            } else {
                alert('Lỗi: ' + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    }
}

/**
 * Apply promo code
 */
function applyPromoCode(code) {
    const messageDiv = document.getElementById('promoMessage');

    fetch('../../app/Controllers/CartController.php?action=applyPromo', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `promo_code=${code}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            messageDiv.className = 'promo-message success';
            messageDiv.textContent = `${data.message} (-${data.discount}đ)`;
            messageDiv.style.display = 'block';
            recalculateTotal();
        } else {
            messageDiv.className = 'promo-message error';
            messageDiv.textContent = data.message;
            messageDiv.style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        messageDiv.className = 'promo-message error';
        messageDiv.textContent = 'Có lỗi xảy ra, vui lòng thử lại';
        messageDiv.style.display = 'block';
    });
}

/**
 * Recalculate total price
 */
function recalculateTotal() {
    let total = 0;
    document.querySelectorAll('.cart-item').forEach(item => {
        const priceText = item.querySelector('.unit-price').textContent;
        const price = parseFloat(priceText.replace(/[^\d]/g, ''));
        const qty = parseInt(item.querySelector('.qty-input').value);
        const itemTotal = price * qty;
        total += itemTotal;
        
        console.log('Price text:', priceText, 'Parsed:', price, 'Qty:', qty, 'Item total:', itemTotal);
        
        // Update item total price display
        const totalPriceElement = item.querySelector('.total-price');
        if (totalPriceElement) {
            totalPriceElement.textContent = formatCurrency(itemTotal);
        }
    });

    const subtotal = document.getElementById('subtotal');
    const totalElement = document.getElementById('total');
    
    console.log('Total:', total, 'Formatted:', formatCurrency(total));
    
    if (subtotal) subtotal.textContent = formatCurrency(total);
    if (totalElement) totalElement.textContent = formatCurrency(total);
}

/**
 * Format currency
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN').format(amount) + 'đ';
}
