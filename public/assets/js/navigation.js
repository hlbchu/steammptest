// ==============================================
// NAVIGATION - JavaScript cho menu điều hướng
// ==============================================

document.addEventListener('DOMContentLoaded', function() {
    initNavigation();
});

function initNavigation() {
    // Highlight active menu item
    const currentPage = new URLSearchParams(window.location.search).get('page') || 'home';
    const navLinks = document.querySelectorAll('nav.navbar a');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href && href.includes(`page=${currentPage}`)) {
            link.classList.add('active');
        }
    });
}
