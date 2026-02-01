document.addEventListener('DOMContentLoaded', function () {
    let currentGalleryIndex = 0;
    let galleryImages = [];

    try {
        const raw = document.body.dataset.galleryImages;
        if (raw) {
            galleryImages = JSON.parse(raw);
        }
    } catch (e) {
        galleryImages = [];
    }

    function openGalleryModal(index) {
        currentGalleryIndex = index;
        const modal = document.getElementById('galleryModal');
        const modalImage = document.getElementById('modalImage');
        if (!modal || !modalImage) return;
        modalImage.src = galleryImages[currentGalleryIndex] || '';
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeGalleryModal() {
        const modal = document.getElementById('galleryModal');
        if (!modal) return;
        modal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }

    function nextGalleryImage() {
        if (!galleryImages.length) return;
        currentGalleryIndex = (currentGalleryIndex + 1) % galleryImages.length;
        const modalImage = document.getElementById('modalImage');
        if (modalImage) {
            modalImage.src = galleryImages[currentGalleryIndex];
        }
    }

    function prevGalleryImage() {
        if (!galleryImages.length) return;
        currentGalleryIndex = (currentGalleryIndex - 1 + galleryImages.length) % galleryImages.length;
        const modalImage = document.getElementById('modalImage');
        if (modalImage) {
            modalImage.src = galleryImages[currentGalleryIndex];
        }
    }

    window.openGalleryModal = openGalleryModal;
    window.closeGalleryModal = closeGalleryModal;
    window.nextGalleryImage = nextGalleryImage;
    window.prevGalleryImage = prevGalleryImage;

    document.querySelectorAll('.gallery-item').forEach(function (item) {
        item.addEventListener('click', function () {
            const index = parseInt(this.dataset.galleryIndex, 10);
            if (!Number.isNaN(index)) {
                openGalleryModal(index);
            }
        });
    });

    const modal = document.getElementById('galleryModal');
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === this) {
                closeGalleryModal();
            }
        });
    }

    document.addEventListener('keydown', function (e) {
        if (!modal || !modal.classList.contains('active')) return;
        if (e.key === 'ArrowRight') nextGalleryImage();
        if (e.key === 'ArrowLeft') prevGalleryImage();
        if (e.key === 'Escape') closeGalleryModal();
    });

    (function () {
        const frame = document.getElementById('productEmbedFrame');
        if (!frame) return;

        function resizeFrame() {
            try {
                const doc = frame.contentDocument || frame.contentWindow.document;
                if (!doc || !doc.body) return;
                const height = Math.max(doc.body.scrollHeight, doc.documentElement.scrollHeight);
                frame.style.height = height + 'px';
            } catch (e) {
                // ignore cross-origin errors
            }
        }

        frame.addEventListener('load', function () {
            resizeFrame();
            setTimeout(resizeFrame, 300);
        });

        window.addEventListener('resize', function () {
            resizeFrame();
        });
    })();
});
