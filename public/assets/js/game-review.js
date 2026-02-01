/* ==============================================
   GAME REVIEW JAVASCRIPT
   ============================================== */

document.addEventListener('DOMContentLoaded', function() {
    initReviewAnimations();
    initPosterFadeOut();
});

// Fade-in animation on scroll
function initReviewAnimations() {
    const reviewItems = document.querySelectorAll('.review-item');
    
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.classList.add('fade-in');
                }, index * 100);
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    reviewItems.forEach(item => {
        observer.observe(item);
    });
}

// Poster fade out after 10 seconds
function initPosterFadeOut() {
    const reviewItems = document.querySelectorAll('.review-item');
    
    reviewItems.forEach(item => {
        const poster = item.querySelector('.review-poster');
        if (poster) {
            // Fade out after 10 seconds
            setTimeout(() => {
                poster.classList.add('fade-out');
            }, 10000);
        }
    });
}

// Add to cart function
function addToCart(gameId) {
    console.log('Adding game to cart:', gameId);
    
    // Animation effect
    const button = event.target.closest('.btn-cart');
    button.style.transform = 'scale(0.95)';
    
    setTimeout(() => {
        button.style.transform = '';
        showNotification('Đã thêm vào giỏ hàng!', 'success');
    }, 150);

    // TODO: Implement actual cart functionality
    // Example: Send AJAX request to add to cart
    /*
    fetch('?action=add-to-cart', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ game_id: gameId })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            showNotification('Đã thêm vào giỏ hàng!', 'success');
        }
    });
    */
}

// Play now function
function playNow(gameId) {
    console.log('Launching game:', gameId);
    
    // Animation effect
    const button = event.target.closest('.btn-play');
    button.style.transform = 'scale(0.95)';
    
    setTimeout(() => {
        button.style.transform = '';
        // TODO: Redirect to game page or demo
        window.location.href = `?page=game&id=${gameId}`;
    }, 150);
}

// Show notification (simple version)
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        background: ${type === 'success' ? 'linear-gradient(135deg, #10b981, #059669)' : 'linear-gradient(135deg, #3b82f6, #2563eb)'};
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        z-index: 10000;
        animation: slideIn 0.3s ease;
        font-size: 14px;
        font-weight: 600;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Add animation styles
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
