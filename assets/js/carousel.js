document.addEventListener('DOMContentLoaded', function () {
    const track = document.getElementById('carousel-track');
    const items = document.querySelectorAll('.carousel-item');
    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');
    const indicatorContainer = document.getElementById('indicator-container');
    const carouselContainer = document.getElementById('book-carousel');

    if (!track || !prevBtn || !nextBtn || items.length === 0) {
        return;
    }

    const totalItems = items.length;
    let itemsPerView = 3;
    let totalSlides = 1;
    let currentSlide = 0;
    let autoPlayInterval = null;

    const reducedMotionQuery = window.matchMedia
        ? window.matchMedia('(prefers-reduced-motion: reduce)')
        : { matches: false };

    function getItemsPerView() {
        const screenWidth = window.innerWidth;
        if (screenWidth < 640) {
            return 1;
        } else if (screenWidth < 1024) {
            return 2;
        }
        return 3;
    }

    function initializeCarousel() {
        itemsPerView = getItemsPerView();

        if (itemsPerView === 1) {
            totalSlides = totalItems;
        } else {
            totalSlides = Math.max(1, totalItems - itemsPerView + 1);
        }

        if (currentSlide >= totalSlides) {
            currentSlide = 0;
        }

        if (indicatorContainer) {
            indicatorContainer.textContent = '';
            for (let i = 0; i < totalSlides; i++) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'w-2.5 h-2.5 rounded-full transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-purple-400';
                button.setAttribute('data-slide-to', String(i));
                button.setAttribute('aria-label', `Go to slide ${i + 1} of ${totalSlides}`);
                button.addEventListener('click', function () {
                    currentSlide = i;
                    updateCarousel();
                    startAutoPlay();
                });
                indicatorContainer.appendChild(button);
            }
        }

        updateCarousel();
    }

    function updateCarousel() {
        const stepPercent = 100 / itemsPerView;
        const translateValue = currentSlide * stepPercent;
        track.style.transform = `translateX(-${translateValue}%)`;

        if (indicatorContainer) {
            const indicators = indicatorContainer.querySelectorAll('button');
            indicators.forEach((btn, i) => {
                if (i === currentSlide) {
                    btn.className = 'h-2.5 rounded-full transition-all duration-200 w-7 bg-white focus:outline-none focus:ring-2 focus:ring-purple-400';
                    btn.setAttribute('aria-current', 'true');
                } else {
                    btn.className = 'h-2.5 rounded-full transition-all duration-200 w-2.5 bg-white/50 hover:bg-white/80 focus:outline-none focus:ring-2 focus:ring-purple-400';
                    btn.removeAttribute('aria-current');
                }
            });
        }
    }

    function moveToNextSlide() {
        currentSlide = (currentSlide + 1) % totalSlides;
        updateCarousel();
    }

    function moveToPrevSlide() {
        currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
        updateCarousel();
    }

    function startAutoPlay() {
        clearInterval(autoPlayInterval);
        if (reducedMotionQuery.matches) return;
        autoPlayInterval = setInterval(moveToNextSlide, 5000);
    }

    function stopAutoPlay() {
        clearInterval(autoPlayInterval);
    }

    let touchStartX = 0;
    track.addEventListener('touchstart', function (e) {
        touchStartX = e.touches[0].screenX;
        stopAutoPlay();
    }, { passive: true });

    track.addEventListener('touchend', function (e) {
        const touchEndX = e.changedTouches[0].screenX;
        if (touchEndX < touchStartX - 50) {
            moveToNextSlide();
        } else if (touchEndX > touchStartX + 50) {
            moveToPrevSlide();
        }
        startAutoPlay();
    }, { passive: true });

    nextBtn.addEventListener('click', function () {
        moveToNextSlide();
        startAutoPlay();
    });

    prevBtn.addEventListener('click', function () {
        moveToPrevSlide();
        startAutoPlay();
    });

    if (carouselContainer) {
        carouselContainer.addEventListener('mouseenter', stopAutoPlay);
        carouselContainer.addEventListener('mouseleave', startAutoPlay);
        carouselContainer.addEventListener('focusin', stopAutoPlay);
        carouselContainer.addEventListener('focusout', startAutoPlay);
        carouselContainer.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowLeft') {
                e.preventDefault();
                moveToPrevSlide();
                stopAutoPlay();
            } else if (e.key === 'ArrowRight') {
                e.preventDefault();
                moveToNextSlide();
                stopAutoPlay();
            }
        });
    }

    window.addEventListener('resize', function () {
        initializeCarousel();
    });

    initializeCarousel();
    startAutoPlay();
});
