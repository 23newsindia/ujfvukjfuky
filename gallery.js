class VanillaZoom {
  constructor(element) {
    this.container = element;
    this.image = element.querySelector('img');
    this.link = element.querySelector('a');
    
    if (!this.image || !this.link) {
      console.warn('VanillaZoom: Missing image or link element');
      return;
    }
    
    this.flyout = document.createElement('div');
    this.flyout.className = 'vanilla-zoom-flyout';
    
    this.zoomImageLoaded = false;
    this.ratiosCalculated = false;
    this.containerRect = null;
    this.imageBounds = null;
    
    this.zoomImage = new Image();
    
    this.zoomImage.onload = () => {
      this.zoomImageLoaded = true;
      this.zoomImage.style.position = 'absolute';
      
      if (this.imageBounds) {
        this.calculateZoomRatios();
      }
    };
    
    this.zoomImage.src = this.link.href;
    this.flyout.appendChild(this.zoomImage);
    
    this.bindEvents();
  }

  bindEvents() {
    if (window.matchMedia('(min-width: 768px)').matches) {
      this.container.addEventListener('mouseenter', this.onEnter.bind(this));
      this.container.addEventListener('mouseleave', this.onLeave.bind(this));
      this.container.addEventListener('mousemove', this.onMove.bind(this));
    }
    
    this.link.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      openGallery(this.image);
    });
    
    if ('ontouchstart' in window) {
      this.container.addEventListener('touchstart', this.onTouchStart.bind(this));
      this.container.addEventListener('touchend', this.onTouchEnd.bind(this));
      this.container.addEventListener('touchmove', this.onTouchMove.bind(this));
    }
  }

  calculateZoomRatios() {
    if (!this.zoomImage || !this.flyout || !this.imageBounds) return false;
    if (this.imageBounds.width === 0 || this.imageBounds.height === 0) return false;
    if (this.zoomImage.width === 0 || this.zoomImage.height === 0) return false;

    this.xRatio = (this.zoomImage.width - this.flyout.offsetWidth) / this.imageBounds.width;
    this.yRatio = (this.zoomImage.height - this.flyout.offsetHeight) / this.imageBounds.height;

    this.ratiosCalculated = true;
    return true;
  }

  onEnter(e) {
    if (!this.image) return;
    this.containerRect = this.container.getBoundingClientRect();
    this.imageBounds = this.image.getBoundingClientRect();

    if (!this.imageBounds || this.imageBounds.width === 0) return;

    this.container.appendChild(this.flyout);

    if (this.zoomImageLoaded) {
      this.calculateZoomRatios();
    }
  }

  onLeave() {
    if (this.flyout.parentElement) {
      this.flyout.remove();
    }
    this.ratiosCalculated = false;
  }

  onMove(e) {
    if (!this.flyout.parentElement || !this.ratiosCalculated) return;
    
    const rect = this.containerRect;
    const x = e.pageX - rect.left - window.pageXOffset;
    const y = e.pageY - rect.top - window.pageYOffset;
    
    let zoomX = -1 * Math.min(
      Math.max(0, (x * this.xRatio)),
      this.zoomImage.width - this.flyout.offsetWidth
    );
    
    let zoomY = -1 * Math.min(
      Math.max(0, (y * this.yRatio)),
      this.zoomImage.height - this.flyout.offsetHeight
    );
    
    this.zoomImage.style.transform = `translate(${zoomX}px, ${zoomY}px)`;
  }

  onTouchStart(e) {
    if (e.touches.length === 1) {
      this.touchStartX = e.touches[0].pageX;
      this.touchStartY = e.touches[0].pageY;
      this.touchStartTime = Date.now();
      this.isSwiping = false;
      this.hasMoved = false;
    }
  }

  onTouchMove(e) {
    if (e.touches.length === 1) {
      this.hasMoved = true;
      const touch = e.touches[0];
      const deltaX = touch.pageX - this.touchStartX;
      const deltaY = touch.pageY - this.touchStartY;

      if (Math.abs(deltaX) > Math.abs(deltaY)) {
        e.preventDefault();
        this.isSwiping = true;
      }
    }
  }

  onTouchEnd(e) {
    const touchDuration = Date.now() - this.touchStartTime;
    
    if (!this.hasMoved && touchDuration < 300) {
      openGallery(this.image);
    }
  }
}

class ModernLightbox {
  constructor() {
    this.currentIndex = 0;
    this.create();
    this.bindEvents();
  }

  create() {
    this.container = document.createElement('div');
    this.container.className = 'modern-lightbox';
    Object.assign(this.container.style, {
      position: 'fixed',
      top: 0,
      left: 0,
      width: '100%',
      height: '100%',
      backgroundColor: 'rgba(0, 0, 0, 0.9)',
      opacity: 0,
      transition: 'opacity 0.3s ease',
      zIndex: 999999,
      display: 'none',
      userSelect: 'none'
    });

    this.wrapper = document.createElement('div');
    Object.assign(this.wrapper.style, {
      position: 'absolute',
      top: '50%',
      left: '50%',
      transform: 'translate(-50%, -50%)',
      width: '100%',
      height: '100%',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center'
    });

    this.image = document.createElement('img');
    Object.assign(this.image.style, {
      maxWidth: '100%',
      maxHeight: '100%',
      objectFit: 'contain',
      transform: 'scale(0.9)',
      transition: 'transform 0.3s ease',
      touchAction: 'none'
    });

    this.prevBtn = this.createNavButton('prev', `
      <svg width="42" height="42" viewBox="0 0 32 32" fill="currentColor">
        <path d="M12.792 15.233l-0.754 0.754 6.035 6.035 0.754-0.754-5.281-5.281 5.256-5.256-0.754-0.754-3.013 3.013z" transform="rotate(180 16 16)"/>
      </svg>
    `);
    
    this.nextBtn = this.createNavButton('next', `
      <svg width="42" height="42" viewBox="0 0 32 32" fill="currentColor">
        <path d="M19.159 16.767l0.754-0.754-6.035-6.035-0.754 0.754 5.281 5.281-5.256 5.256 0.754 0.754 3.013-3.013z"/>
      </svg>
    `);

    this.closeBtn = document.createElement('button');
    Object.assign(this.closeBtn.style, {
      position: 'absolute',
      top: '20px',
      right: '20px',
      background: 'transparent',
      border: 'none',
      color: 'white',
      fontSize: '40px',
      cursor: 'pointer',
      width: '40px',
      height: '40px',
      padding: 0,
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      opacity: 0.8,
      transition: 'opacity 0.2s ease',
      zIndex: 1000
    });
    this.closeBtn.innerHTML = '×';

    this.counter = document.createElement('div');
    Object.assign(this.counter.style, {
      position: 'absolute',
      bottom: '20px',
      left: '50%',
      transform: 'translateX(-50%)',
      color: 'white',
      fontSize: '14px',
      padding: '8px 16px',
      backgroundColor: 'rgba(0, 0, 0, 0.5)',
      borderRadius: '20px',
      zIndex: 1000
    });

    this.wrapper.appendChild(this.image);
    this.container.appendChild(this.wrapper);
    this.container.appendChild(this.prevBtn);
    this.container.appendChild(this.nextBtn);
    this.container.appendChild(this.closeBtn);
    this.container.appendChild(this.counter);
    document.body.appendChild(this.container);
  }

  createNavButton(direction, svg) {
    const btn = document.createElement('button');
    Object.assign(btn.style, {
      position: 'absolute',
      top: '50%',
      transform: 'translateY(-50%)',
      [direction === 'prev' ? 'left' : 'right']: '20px',
      background: 'rgba(255, 255, 255, 0.1)',
      border: 'none',
      color: 'white',
      width: '48px',
      height: '48px',
      borderRadius: '50%',
      cursor: 'pointer',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      transition: 'background-color 0.3s ease',
      zIndex: 1000,
      padding: 0
    });
    btn.innerHTML = svg;
    return btn;
  }

  bindEvents() {
    this.closeBtn.addEventListener('click', () => this.close());
    this.container.addEventListener('click', (e) => {
      if (e.target === this.container) this.close();
    });

    this.prevBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      this.navigate('prev');
    });
    
    this.nextBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      this.navigate('next');
    });

    document.addEventListener('keydown', (e) => {
      if (this.container.style.display === 'block') {
        if (e.key === 'Escape') this.close();
        if (e.key === 'ArrowLeft') this.navigate('prev');
        if (e.key === 'ArrowRight') this.navigate('next');
      }
    });

    let startX = 0;
    let startY = 0;
    let initialScale = 1;
    let currentScale = 1;
    let lastTapTime = 0;

    const gestureStart = (e) => {
      const touch = e.touches[0];
      startX = touch.pageX;
      startY = touch.pageY;
      
      if (e.touches.length === 2) {
        initialScale = currentScale;
        const touch2 = e.touches[1];
        const distance = Math.hypot(
          touch2.pageX - touch.pageX,
          touch2.pageY - touch.pageY
        );
        this.initialPinchDistance = distance;
      }
    };

    const gestureMove = (e) => {
      e.preventDefault();
      
      if (e.touches.length === 2) {
        const touch1 = e.touches[0];
        const touch2 = e.touches[1];
        const distance = Math.hypot(
          touch2.pageX - touch1.pageX,
          touch2.pageY - touch1.pageY
        );
        
        currentScale = initialScale * (distance / this.initialPinchDistance);
        currentScale = Math.min(Math.max(1, currentScale), 3);
        
        this.image.style.transform = `scale(${currentScale})`;
      } else if (e.touches.length === 1) {
        const touch = e.touches[0];
        const deltaX = touch.pageX - startX;
        
        if (Math.abs(deltaX) > 30) {
          this.image.style.transform = `translateX(${deltaX}px) scale(${currentScale})`;
        }
      }
    };

    const gestureEnd = (e) => {
      const now = Date.now();
      const deltaX = e.changedTouches[0].pageX - startX;
      
      if (Math.abs(deltaX) > 100) {
        this.navigate(deltaX > 0 ? 'prev' : 'next');
      } else if (now - lastTapTime < 300) {
        // Double tap to zoom
        currentScale = currentScale > 1 ? 1 : 2;
        this.image.style.transform = `scale(${currentScale})`;
      } else {
        this.image.style.transform = `scale(${currentScale})`;
      }
      
      lastTapTime = now;
    };

    this.wrapper.addEventListener('touchstart', gestureStart, { passive: false });
    this.wrapper.addEventListener('touchmove', gestureMove, { passive: false });
    this.wrapper.addEventListener('touchend', gestureEnd);
  }

  navigate(direction) {
    if (!this.currentGalleryImages || !this.currentGalleryImages.length) return;

    const newIndex = direction === 'next' 
      ? (this.currentIndex + 1) % this.currentGalleryImages.length
      : (this.currentIndex - 1 + this.currentGalleryImages.length) % this.currentGalleryImages.length;

    this.currentIndex = newIndex;
    
    this.image.style.opacity = '0.5';
    this.image.style.transform = `scale(0.95) translateX(${direction === 'next' ? '-' : ''}30px)`;

    setTimeout(() => {
      this.image.src = this.currentGalleryImages[this.currentIndex];
      
      requestAnimationFrame(() => {
        this.image.style.opacity = '1';
        this.image.style.transform = 'scale(1) translateX(0)';
      });
      
      this.updateCounter();
      this.updateNavButtons();
      
      // Preload next image
      const preloadIndex = direction === 'next' 
        ? (newIndex + 1) % this.currentGalleryImages.length
        : (newIndex - 1 + this.currentGalleryImages.length) % this.currentGalleryImages.length;
      
      const preloadImage = new Image();
      preloadImage.src = this.currentGalleryImages[preloadIndex];
    }, 200);
  }

  updateCounter() {
    this.counter.textContent = `${this.currentIndex + 1} / ${this.currentGalleryImages.length}`;
  }

  updateNavButtons() {
    const showButtons = this.currentGalleryImages.length > 1;
    this.prevBtn.style.display = showButtons ? 'flex' : 'none';
    this.nextBtn.style.display = showButtons ? 'flex' : 'none';
  }

  open(imageUrl) {
    this.container.style.display = 'block';
    this.currentIndex = this.currentGalleryImages.indexOf(imageUrl);
    this.image.src = imageUrl;
    
    requestAnimationFrame(() => {
      this.container.style.opacity = '1';
      this.image.style.transform = 'scale(1)';
      this.updateCounter();
      this.updateNavButtons();
    });
  }

  close() {
    this.container.style.opacity = '0';
    this.image.style.transform = 'scale(0.9)';
    setTimeout(() => {
      this.container.style.display = 'none';
      this.image.src = '';
    }, 300);
  }
}

const lightbox = new ModernLightbox();

function openGallery(image) {
  const fullSizeUrl = image.dataset.largeImage || image.getAttribute('data-large-image') || image.src;
  
  const galleryImages = Array.from(document.querySelectorAll('.nasa-item-main-image-wrap:not(.nasa-item-main-video-wrap) img'))
    .map(img => img.dataset.largeImage || img.getAttribute('data-large-image') || img.src)
    .filter((url, index, self) => self.indexOf(url) === index);
  
  lightbox.currentGalleryImages = galleryImages;
  lightbox.open(fullSizeUrl);
}

function initializeZoom() {
  const zoomElements = document.querySelectorAll('.vanilla-zoom');
  
  zoomElements.forEach(element => {
    if (!element.dataset.vanillaZoomInitialized) {
      element.dataset.vanillaZoomInitialized = true;
      new VanillaZoom(element);
    }
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initializeZoom);
} else {
  initializeZoom();
}

window.addEventListener('load', initializeZoom);

let resizeTimer;
window.addEventListener('resize', () => {
  clearTimeout(resizeTimer);
  resizeTimer = setTimeout(() => {
    initializeZoom();
  }, 250);
});
