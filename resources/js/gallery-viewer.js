/**
 * Gallery Viewer with Lazy Loading and Lightbox
 */

// Lazy loading with Intersection Observer
class LazyImageLoader {
    constructor() {
        this.observer = null;
        this.init();
    }

    init() {
        if ('IntersectionObserver' in window) {
            this.observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.loadImage(entry.target);
                        this.observer.unobserve(entry.target);
                    }
                });
            }, {
                rootMargin: '50px', // Start loading 50px before entering viewport
            });

            // Observe all lazy images
            document.querySelectorAll('img[data-src]').forEach(img => {
                this.observer.observe(img);
            });
        } else {
            // Fallback for browsers without Intersection Observer
            document.querySelectorAll('img[data-src]').forEach(img => {
                this.loadImage(img);
            });
        }
    }

    loadImage(img) {
        const src = img.getAttribute('data-src');
        if (src) {
            img.src = src;
            img.addEventListener('load', () => {
                img.classList.remove('opacity-0');
                img.classList.add('opacity-100');
                // Hide loading skeleton
                const skeleton = img.parentElement.querySelector('.animate-pulse');
                if (skeleton) {
                    skeleton.style.display = 'none';
                }
            });
        }
    }
}

// Lightbox with Zoom and Pan
class Lightbox {
    constructor() {
        this.currentIndex = 0;
        this.photos = [];
        this.zoomLevel = 1;
        this.panX = 0;
        this.panY = 0;
        this.isPanning = false;
        this.startX = 0;
        this.startY = 0;
        this.init();
    }

    init() {
        this.setupElements();
        this.setupEvents();
        this.loadPhotos();
    }

    setupElements() {
        this.lightbox = document.getElementById('lightbox');
        this.image = document.getElementById('lightbox-image');
        this.container = document.getElementById('lightbox-image-container');
        this.closeBtn = document.getElementById('lightbox-close');
        this.prevBtn = document.getElementById('lightbox-prev');
        this.nextBtn = document.getElementById('lightbox-next');
        this.zoomInBtn = document.getElementById('zoom-in');
        this.zoomOutBtn = document.getElementById('zoom-out');
        this.zoomFitBtn = document.getElementById('zoom-fit');
        this.counter = document.getElementById('lightbox-counter');
    }

    loadPhotos() {
        const photoItems = document.querySelectorAll('.photo-item');
        this.photos = Array.from(photoItems).map(item => ({
            id: item.dataset.photoId,
            url: item.dataset.photoUrl,
            name: item.dataset.photoName,
        }));
    }

    setupEvents() {
        // Open lightbox on photo click
        document.querySelectorAll('.photo-item').forEach((item, index) => {
            item.addEventListener('click', (e) => {
                if (e.target.type !== 'checkbox') {
                    this.currentIndex = index;
                    this.open();
                }
            });
        });

        // Close lightbox
        this.closeBtn.addEventListener('click', () => this.close());
        this.lightbox.addEventListener('click', (e) => {
            if (e.target === this.lightbox) {
                this.close();
            }
        });

        // Navigation
        this.prevBtn.addEventListener('click', () => this.prev());
        this.nextBtn.addEventListener('click', () => this.next());

        // Zoom controls
        this.zoomInBtn.addEventListener('click', () => this.zoomIn());
        this.zoomOutBtn.addEventListener('click', () => this.zoomOut());
        this.zoomFitBtn.addEventListener('click', () => this.zoomFit());

        // Keyboard navigation
        document.addEventListener('keydown', (e) => this.handleKeyboard(e));

        // Mouse wheel zoom
        this.container.addEventListener('wheel', (e) => {
            e.preventDefault();
            if (e.deltaY < 0) {
                this.zoomIn();
            } else {
                this.zoomOut();
            }
        }, { passive: false });

        // Pan with mouse drag
        this.setupPanning();
    }

    setupPanning() {
        this.container.addEventListener('mousedown', (e) => {
            if (this.zoomLevel > 1) {
                this.isPanning = true;
                this.startX = e.clientX - this.panX;
                this.startY = e.clientY - this.panY;
            }
        });

        document.addEventListener('mousemove', (e) => {
            if (this.isPanning) {
                this.panX = e.clientX - this.startX;
                this.panY = e.clientY - this.startY;
                this.updateTransform();
            }
        });

        document.addEventListener('mouseup', () => {
            this.isPanning = false;
        });

        // Touch events for mobile
        let touchStartDistance = 0;
        let touchStartZoom = 1;

        this.container.addEventListener('touchstart', (e) => {
            if (e.touches.length === 2) {
                // Pinch zoom
                touchStartDistance = this.getDistance(e.touches[0], e.touches[1]);
                touchStartZoom = this.zoomLevel;
            } else if (e.touches.length === 1 && this.zoomLevel > 1) {
                // Pan
                this.isPanning = true;
                this.startX = e.touches[0].clientX - this.panX;
                this.startY = e.touches[0].clientY - this.panY;
            }
        });

        this.container.addEventListener('touchmove', (e) => {
            if (e.touches.length === 2) {
                e.preventDefault();
                const currentDistance = this.getDistance(e.touches[0], e.touches[1]);
                const zoomChange = currentDistance / touchStartDistance;
                this.zoomLevel = Math.max(1, Math.min(5, touchStartZoom * zoomChange));
                this.updateTransform();
            } else if (e.touches.length === 1 && this.isPanning) {
                this.panX = e.touches[0].clientX - this.startX;
                this.panY = e.touches[0].clientY - this.startY;
                this.updateTransform();
            }
        });

        this.container.addEventListener('touchend', () => {
            this.isPanning = false;
        });

        // Double tap to zoom
        let lastTap = 0;
        this.container.addEventListener('touchend', (e) => {
            const currentTime = Date.now();
            const tapLength = currentTime - lastTap;
            if (tapLength < 300 && tapLength > 0) {
                if (this.zoomLevel > 1) {
                    this.zoomFit();
                } else {
                    this.zoomTo(2);
                }
            }
            lastTap = currentTime;
        });
    }

    getDistance(touch1, touch2) {
        const dx = touch1.clientX - touch2.clientX;
        const dy = touch1.clientY - touch2.clientY;
        return Math.sqrt(dx * dx + dy * dy);
    }

    handleKeyboard(e) {
        if (!this.lightbox.classList.contains('hidden')) {
            switch(e.key) {
                case 'Escape':
                    this.close();
                    break;
                case 'ArrowLeft':
                    this.prev();
                    break;
                case 'ArrowRight':
                    this.next();
                    break;
                case '+':
                case '=':
                    this.zoomIn();
                    break;
                case '-':
                    this.zoomOut();
                    break;
                case '0':
                    this.zoomFit();
                    break;
            }
        }
    }

    open() {
        this.lightbox.classList.remove('hidden');
        this.showPhoto(this.currentIndex);
        document.body.style.overflow = 'hidden';
    }

    close() {
        this.lightbox.classList.add('hidden');
        this.zoomFit();
        document.body.style.overflow = '';
    }

    prev() {
        this.currentIndex = (this.currentIndex - 1 + this.photos.length) % this.photos.length;
        this.showPhoto(this.currentIndex);
    }

    next() {
        this.currentIndex = (this.currentIndex + 1) % this.photos.length;
        this.showPhoto(this.currentIndex);
    }

    showPhoto(index) {
        const photo = this.photos[index];
        this.image.src = photo.url;
        this.counter.textContent = `${index + 1} / ${this.photos.length}`;
        this.zoomFit();
    }

    zoomIn() {
        this.zoomTo(Math.min(this.zoomLevel + 0.5, 5));
    }

    zoomOut() {
        this.zoomTo(Math.max(this.zoomLevel - 0.5, 1));
    }

    zoomTo(level) {
        this.zoomLevel = level;
        this.updateTransform();
    }

    zoomFit() {
        this.zoomLevel = 1;
        this.panX = 0;
        this.panY = 0;
        this.updateTransform();
    }

    updateTransform() {
        this.image.style.transform = `scale(${this.zoomLevel}) translate(${this.panX}px, ${this.panY}px)`;
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new LazyImageLoader();
    window.lightbox = new Lightbox();
});

