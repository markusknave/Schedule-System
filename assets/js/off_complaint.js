$(document).ready(function() {
    $('#searchInput').on('keyup', function(e) {
        if (e.key === 'Enter') {
            const search = $(this).val();
            window.location.href = `?search=${encodeURIComponent(search)}`;
        }
    });

    $('.complaint-row').on('click', function() {
        const complaintId = $(this).data('id');
        const detailRow = $(`#detail-${complaintId}`);
        
        if (detailRow.is(':visible')) {
            detailRow.slideUp();
        } else {
            $('.complaint-detail').slideUp();
            detailRow.slideDown();
            
            $('html, body').animate({
                scrollTop: detailRow.offset().top - 100
            }, 500);
        }
    });
    
    $('.close-detail').on('click', function(e) {
        e.stopPropagation();
        const complaintId = $(this).data('id');
        $(`#detail-${complaintId}`).slideUp();
    });
});

$(document).on('click', '.btn-mark-resolved', function(e) {
    e.stopPropagation();
    const complaintId = $(this).data('id');
    
    $.ajax({
        url: '/myschedule/components/complaint_comp/mark_resolved.php',
        type: 'POST',
        data: { id: complaintId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('.complaint-row[data-id="' + complaintId + '"] .status-badge')
                    .text('Resolved')
                    .removeClass('status-pending')
                    .addClass('status-resolved');
                
                $('#detail-' + complaintId).slideUp();
                
                showNotification('Complaint marked as resolved successfully!', 'success');
            } else {
                showNotification('Error: ' + response.error, 'danger');
            }
        },
        error: function() {
            showNotification('An error occurred. Please try again.', 'danger');
        }
    });
});

$(document).on('click', '.btn-delete-complaint', function(e) {
    e.stopPropagation();
    const complaintId = $(this).data('id');
    
    if (!confirm('Are you sure you want to delete this complaint? This action cannot be undone.')) {
        return;
    }
    
    $.ajax({
        url: '/myschedule/components/complaint_comp/del_complaint.php',
        type: 'POST',
        data: { id: complaintId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('.complaint-row[data-id="' + complaintId + '"]').remove();
                $('#detail-' + complaintId).remove();
                
                const totalBadge = $('.badge.lnu-badge strong');
                const currentTotal = parseInt(totalBadge.text());
                totalBadge.text(currentTotal - 1);
                
                showNotification('Complaint deleted successfully!', 'success');
            } else {
                showNotification('Error: ' + response.error, 'danger');
            }
        },
        error: function() {
            showNotification('An error occurred. Please try again.', 'danger');
        }
    });
});

if (typeof showNotification === 'undefined') {
    function showNotification(message, type = 'info') {
        const $n = $('#notification');
        $n
            .removeClass('alert-success alert-danger alert-warning alert-info')
            .addClass('alert-' + type)
            .text(message)
            .stop(true, true)
            .fadeIn(200)
            .delay(2500)
            .fadeOut(400);
    }
}