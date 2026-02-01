// ==============================================
// TOP RECHARGE - JavaScript cho bảng xếp hạng
// ==============================================

document.addEventListener('DOMContentLoaded', function() {
    initTopRecharge();
});

function initTopRecharge() {
    const topUsers = document.querySelectorAll('.top-user');
    
    topUsers.forEach(user => {
        user.addEventListener('click', function() {
            const userId = this.querySelector('.user-id').textContent;
            console.log('Clicked user:', userId);
            // Có thể thêm modal hoặc redirect đến trang profile
        });
    });
    
    // Animation khi scroll vào view
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animation = 'fadeInUp 0.6s ease-out forwards';
                }
            });
        }, { threshold: 0.1 });
        
        topUsers.forEach((user, index) => {
            user.style.opacity = '0';
            user.style.animationDelay = `${index * 0.2}s`;
            observer.observe(user);
        });
    }
}

// CSS Animation
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
`;
document.head.appendChild(style);
