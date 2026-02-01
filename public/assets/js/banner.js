// ==============================================
// BANNER - JavaScript cho banner slider
// ==============================================

document.addEventListener('DOMContentLoaded', function() {
    initBannerSlider();
});

function initBannerSlider() {
    const bannerSlides = document.querySelectorAll('.banner-slide');
    const bannerLinks = document.querySelectorAll('.banner-slide-link');
    const dots = document.querySelectorAll('.dot');
    let currentSlide = 0;
    
    if (bannerSlides.length === 0) return;
    
    // Auto slide
    setInterval(() => {
        changeSlide((currentSlide + 1) % bannerSlides.length);
    }, 5000);
    
    // Dot navigation
    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            changeSlide(index);
        });
    });
    
    function changeSlide(index) {
        bannerSlides[currentSlide].classList.remove('active');
        bannerLinks[currentSlide]?.classList.remove('active');
        dots[currentSlide]?.classList.remove('active');
        
        currentSlide = index;
        
        bannerSlides[currentSlide].classList.add('active');
        bannerLinks[currentSlide]?.classList.add('active');
        dots[currentSlide]?.classList.add('active');
    }
}
