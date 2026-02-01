// ==============================================
// GAME CARD - JavaScript cho thẻ game
// ==============================================

document.addEventListener('DOMContentLoaded', function() {
    initGameCards();
});

function initGameCards() {
    // Note: Add to cart buttons are handled by add-to-cart.js
    // Do not add listeners here to avoid duplicate calls
    
    // Xử lý click vào game card
    const gameCards = document.querySelectorAll('.game-card');
    gameCards.forEach(card => {
        card.addEventListener('click', function(e) {
            if (!e.target.closest('.btn-buy')) {
                const detailUrl = this.dataset.detailUrl;
                const gameId = this.dataset.id || 1;
                window.location.href = detailUrl || `?page=product&id=${gameId}`;
            }
        });
    });
}
