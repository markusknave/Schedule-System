$(document).ready(function() {
    // Variables for auto-scroll control
    let autoScrollEnabled = true;
    let scrollInterval;
    const scrollSpeed = 30; // pixels per second
    const pauseBetweenSlides = 3000; // 3 seconds
    
    // Initialize carousel without autoplay
    const carousel = new bootstrap.Carousel('#announcementsCarousel', {
        interval: false,
        ride: false
    });
    
    // Function to start auto-scrolling for current content
    function startAutoScroll() {
        const activeIndex = $('.carousel-item.active').index();
        const contentElement = $(`#scroll-content-${activeIndex}`);
        const containerHeight = contentElement.parent().height();
        const contentHeight = contentElement[0].scrollHeight;
        
        // Reset scroll position
        contentElement.scrollTop(0);
        
        // Clear any existing interval
        clearInterval(scrollInterval);
        
        // Calculate total scroll time (in ms)
        const scrollTime = ((contentHeight - containerHeight) / scrollSpeed) * 2500;
        
        // Start scrolling
        if (contentHeight > containerHeight) {
            const startTime = Date.now();
            
            scrollInterval = setInterval(() => {
                if (!autoScrollEnabled) return;
                
                const elapsed = Date.now() - startTime;
                const progress = Math.min(elapsed / scrollTime, 1);
                const scrollPosition = progress * (contentHeight - containerHeight);
                
                contentElement.scrollTop(scrollPosition);
                
                // When we reach the bottom, pause then go to next slide
                if (progress >= 1) {
                    clearInterval(scrollInterval);
                    setTimeout(() => {
                        if (autoScrollEnabled) {
                            carousel.next();
                        }
                    }, pauseBetweenSlides);
                }
            }, 16); // ~60fps
        } else {
            // Content fits without scrolling, just pause then move to next
            setTimeout(() => {
                if (autoScrollEnabled) {
                    carousel.next();
                }
            }, pauseBetweenSlides + 2000);
        }
    }
    
    // Toggle auto-scroll
    window.toggleAutoScroll = function() {
        autoScrollEnabled = !autoScrollEnabled;
        $('.pause-btn i').toggleClass('fa-pause fa-play');
        
        if (autoScrollEnabled) {
            startAutoScroll();
        } else {
            clearInterval(scrollInterval);
        }
    };
    
    // Start auto-scroll when slide changes
    $('#announcementsCarousel').on('slid.bs.carousel', function() {
        // Show the corresponding content
        const activeIndex = $('.carousel-item.active').index();
        $('.announcement-content-container').hide();
        $(`#content-${activeIndex}`).show();
        
        if (autoScrollEnabled) {
            startAutoScroll();
        }
    });
    
    startAutoScroll();
});

function confirmDelete(id) {
    if (confirm('Are you sure you want to delete this announcement?')) {
        window.location.href = '/myschedule/components/office_announ/delete_announcement.php?id=' + id;
    }
}