$(document).ready(function() {
    let autoScrollEnabled = true;
    let scrollInterval;
    const scrollSpeed = 30; 
    const pauseBetweenSlides = 3000; 
    
    const carousel = new bootstrap.Carousel('#announcementsCarousel', {
        interval: false,
        ride: false
    });
    
    function startAutoScroll() {
        const activeIndex = $('.carousel-item.active').index();
        const contentElement = $(`#scroll-content-${activeIndex}`);
        const containerHeight = contentElement.parent().height();
        const contentHeight = contentElement[0].scrollHeight;
        
        contentElement.scrollTop(0);
        
        clearInterval(scrollInterval);
        
        const scrollTime = ((contentHeight - containerHeight) / scrollSpeed) * 2500;
        
        if (contentHeight > containerHeight) {
            const startTime = Date.now();
            
            scrollInterval = setInterval(() => {
                if (!autoScrollEnabled) return;
                
                const elapsed = Date.now() - startTime;
                const progress = Math.min(elapsed / scrollTime, 1);
                const scrollPosition = progress * (contentHeight - containerHeight);
                
                contentElement.scrollTop(scrollPosition);
                
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
            setTimeout(() => {
                if (autoScrollEnabled) {
                    carousel.next();
                }
            }, pauseBetweenSlides + 2000);
        }
    }
    
    window.toggleAutoScroll = function() {
        autoScrollEnabled = !autoScrollEnabled;
        $('.pause-btn i').toggleClass('fa-pause fa-play');
        
        if (autoScrollEnabled) {
            startAutoScroll();
        } else {
            clearInterval(scrollInterval);
        }
    };
    
    $('#announcementsCarousel').on('slid.bs.carousel', function() {
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
        window.location.href = '/myschedule/components/announ_comp/delete_announcement.php?id=' + id;
    }
}