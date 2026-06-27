document.addEventListener ('DOMContentLoaded', function () {

    
    var track = document.getElementById('carousel-track');
    var items = document.querySelectorAll('.carousel-item');
    var prevBtn = document.getElementById('prev-btn');
    var nextBtn = document.getElementById('next-btn'); 
    var indicatorContainer = document.getElementById('indicator-container');
    var carouselContainer = document.getElementById('book-carousel');
    
    
    if (!track || !prevBtn || !nextBtn || items.length === 0) {

        console.error('Carousel elements not found!');
        return;

    }

    var totalItems = items.length;
    var itemsPerView;
    var totalSlides ;
    var currentSlide = 0;
    var autoPlayInterval ;

    
    function initializeCarousel() {
        var screenWidth = window.innerWidth;
        
        
        if (screenWidth <  700) {
            itemsPerView = 1; 
        } 
        
        else if (screenWidth <  1000) {
            itemsPerView = 2; 
        } 
        
        else {
            itemsPerView = 3; 
        }
        
        
        if (itemsPerView ===  1) {
            totalSlides =  totalItems;
        } 
        
        else {
            totalSlides =  totalItems - itemsPerView + 1;
        }

        if (currentSlide >=  totalSlides) {
            currentSlide = 0;
        }
        
        
        
        indicatorContainer.innerHTML = '';

        for (var i = 0; i  < totalSlides; i++) {

            var button = document.createElement('button');

            button.className = 'w-2 h-2 rounded-full transition-all duration-300';
            button.setAttribute('data-slide-to', i);
            indicatorContainer.appendChild(button);

        }
        
        
        var indicators = document.querySelectorAll('#indicator-container button');

        for(var i = 0; i  <  indicators.length; i++) {

            indicators[i].addEventListener('click', function (e) {

                currentSlide = parseInt(e.target.getAttribute('data-slide-to'));

                 updateCarousel();
                startAutoPlay();

            });
        }
        


        updateCarousel();

    }


    function updateCarousel() {

        var translateValue ;
        
        if (window.innerWidth < 650) {
            translateValue = currentSlide * 100; 
        }
        
        else if (window.innerWidth < 1000) {
            translateValue = currentSlide * 50; 
        } 
        
        else {
             translateValue = currentSlide * 33.333; 
        }
        
        
        track.style.transform = "translateX(-" + translateValue + "%)";

        
        
        var  indicators = document.querySelectorAll('#indicator-container button');

        for (var i = 0; i < indicators.length; i++) {

            if (i === currentSlide) {
                indicators[i].className = 'h-2 rounded-full transition-all duration-300 w-8 bg-white';
            } 
            
            else {

                 indicators[i].className = 'h-2 rounded-full transition-all duration-300 w-2 bg-white/50';
            }
        }
    }

    
    function moveToNextSlide() {

        currentSlide = currentSlide + 1;

        if (currentSlide >= totalSlides) {

             currentSlide = 0;
        }


         updateCarousel();
    }

    
    function moveToPrevSlide() {

        currentSlide = currentSlide - 1;

        if ( currentSlide < 0 ) {
             currentSlide = totalSlides - 1; 
        }

          updateCarousel();
    }

    function startAutoPlay() {

         clearInterval( autoPlayInterval );
        autoPlayInterval = setInterval( moveToNextSlide, 5000 );

    }

    function stopAutoPlay() {

         clearInterval(autoPlayInterval);

    }
    
    
    var touchStartX =  0;
    
    track.addEventListener('touchstart', function(e) {

        touchStartX = e.touches[0].screenX;
        stopAutoPlay();


    });

    track.addEventListener('touchend',  function(e) {

        var touchEndX = e.changedTouches[0].screenX;

        if (touchEndX < touchStartX -  50) {
            moveToNextSlide();
        }

        if (touchEndX > touchStartX  + 50) {
            moveToPrevSlide();
        }

        startAutoPlay();

    });

    
    nextBtn.addEventListener ('click', function() {

        moveToNextSlide();
        startAutoPlay();

    });

    prevBtn.addEventListener ('click', function() {

        moveToPrevSlide() ;
        startAutoPlay() ;

    });

    
    carouselContainer.addEventListener('mouseenter',  stopAutoPlay);
    carouselContainer.addEventListener('mouseleave',  startAutoPlay);

    
    document.addEventListener('keydown', function(e) {

        if (e.key === 'ArrowLeft') {
            moveToPrevSlide() ;
            startAutoPlay();
        }

        if (e.key === 'ArrowRight') {
            moveToNextSlide();
            startAutoPlay() ;
        }


    });

    
    window.addEventListener ('resize', function() {
        

        initializeCarousel() ;

    });

    
    
    initializeCarousel () ;
    startAutoPlay () ;

});