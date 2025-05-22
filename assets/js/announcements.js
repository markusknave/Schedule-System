$(document).ready(function() {
    let autoScrollEnabled = true;
    let scrollInterval;
    const scrollSpeed = 30;
    const pauseBetweenSlides = 10000;

    const carousel = new bootstrap.Carousel('#announcementsCarousel', {
        interval: pauseBetweenSlides,
        ride: 'carousel'
    });

    
    window.toggleAutoScroll = function() {
        autoScrollEnabled = !autoScrollEnabled;
        $('.pause-btn i').toggleClass('fa-pause fa-play');
        
        if (autoScrollEnabled) {
            startAutoScroll();
        } else {
            clearInterval(scrollInterval);
        }
    };
    
    window.toggleAutoScroll = function() {
        autoScrollEnabled = !autoScrollEnabled;
        $('.pause-btn i').toggleClass('fa-pause fa-play');
        
        if (autoScrollEnabled) {
            carousel.cycle();
        } else {
            carousel.pause();
        }
    };

    $('#announcementsCarousel').on('slid.bs.carousel', function() {
        const activeIndex = $('.carousel-item.active').index();
        $('.announcement-content-container').hide();
        $(`#content-${activeIndex}`).show();
    });
});

function confirmDelete(id) {
    if (confirm('Are you sure you want to delete this announcement?')) {
        window.location.href = '/myschedule/components/announ_comp/delete_announcement.php?id=' + id;
    }
}