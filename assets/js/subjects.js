$(document).ready(function() {
    // Show success/error messages
    $('.alert').delay(3000).fadeOut('slow');

    // Initialize all modals properly
    $('.modal').modal({
        show: false,
    });

    // Edit Subject Button Click
    $(document).on('click', '.edit-subject', function() {
        const subjectId = $(this).data('id');
        const subjectCode = $(this).data('code');
        const subjectName = $(this).data('name');
        
        $('#editSubjectId').val(subjectId);
        $('#editSubjectCode').val(subjectCode);
        $('#editSubjectName').val(subjectName);
        $('#editSubjectModal').modal('show');
    });

    // Delete Subject Button Click
    $(document).on('click', '.delete-subject', function() {
        const subjectId = $(this).data('id');
        $('#deleteSubjectId').val(subjectId);
        $('#deleteSubjectModal').modal('show');
    });

    // View Subject Button Click
    $(document).on('click', '.view-subject', function() {
        const subjectId = $(this).data('id');
        window.location.href = '/myschedule/components/subj_comp/subject_details.php?id=' + subjectId;
    });

    // Add Subject Button Click
    $('#addSubjectButton').click(function() {
        $('#addSubjectModal').modal('show');
    });

    // Add Subject Form Submission
    $('#addSubjectForm').submit(function(e) {
        e.preventDefault();
        const form = $(this);
        const formData = form.serialize();
        
        $.ajax({
            url: form.attr('action'),
            method: form.attr('method'),
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#addSubjectModal').modal('hide');
                    // Show success message
                    $('<div class="alert alert-success alert-dismissible fade show float-right">' + 
                    response.message + 
                    '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                    '<span aria-hidden="true">&times;</span></button></div>')
                    .insertBefore('.content-header .container-fluid .row .col-sm-6')
                    .delay(3000).fadeOut('slow', function() { $(this).remove(); });
                    loadSubjects($('#searchInput').val(), 1);
                } else {
                    // Show error message in modal
                    $('#addSubjectModal .modal-body').prepend(
                        '<div class="alert alert-danger">' + response.message + '</div>'
                    );
                    // Remove error after 5 seconds
                    setTimeout(function() {
                        $('#addSubjectModal .alert-danger').fadeOut('slow', function() {
                            $(this).remove();
                        });
                    }, 5000);
                }
            },
            error: function(xhr, status, error) {
                $('#addSubjectModal .modal-body').prepend(
                    '<div class="alert alert-danger">Error: ' + error + '</div>'
                );
                setTimeout(function() {
                    $('#addSubjectModal .alert-danger').fadeOut('slow', function() {
                        $(this).remove();
                    });
                }, 5000);
            }
        });
    });

    // Edit Subject Form Submission
    $('#editSubjectForm').submit(function(e) {
        e.preventDefault();
        const form = $(this);
        const formData = form.serialize();
        
        $.ajax({
            url: form.attr('action'),
            method: form.attr('method'),
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#editSubjectModal').modal('hide');
                    // Show success message
                    $('<div class="alert alert-success alert-dismissible fade show float-right">' + 
                    response.message + 
                    '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                    '<span aria-hidden="true">&times;</span></button></div>')
                    .insertBefore('.content-header .container-fluid .row .col-sm-6')
                    .delay(3000).fadeOut('slow', function() { $(this).remove(); });
                    loadSubjects($('#searchInput').val(), 1);
                } else {
                    // Show error message in modal
                    $('#editSubjectModal .modal-body').prepend(
                        '<div class="alert alert-danger">' + response.message + '</div>'
                    );
                    // Remove error after 5 seconds
                    setTimeout(function() {
                        $('#editSubjectModal .alert-danger').fadeOut('slow', function() {
                            $(this).remove();
                        });
                    }, 5000);
                }
            },
            error: function(xhr, status, error) {
                $('#editSubjectModal .modal-body').prepend(
                    '<div class="alert alert-danger">Error: ' + error + '</div>'
                );
                setTimeout(function() {
                    $('#editSubjectModal .alert-danger').fadeOut('slow', function() {
                        $(this).remove();
                    });
                }, 5000);
            }
        });
    });

    // Delete Subject Form Submission
    $('#deleteSubjectForm').submit(function(e) {
        e.preventDefault();
        const form = $(this);
        const formData = form.serialize();
        
        $.ajax({
            url: form.attr('action'),
            method: form.attr('method'),
            data: formData,
            success: function(response) {
                loadSubjects($('#searchInput').val(), 1);
            },
            error: function(xhr, status, error) {
                alert('Error: ' + error);
            }
        });
    });

    // Dynamic search functionality
    function loadSubjects(search = "", page = 1) {
        const isMobile = $(window).width() < 768;
        
        // Show loading state
        if (isMobile) {
            $('#mobileSubjectsList').html('<div class="list-group-item text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
        } else {
            $('#subjectsTableBody').html('<tr><td colspan="4" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>');
        }
        
        $.ajax({
            url: '/myschedule/components/subj_comp/fetch_subjects.php',
            type: 'GET',
            data: { 
                search: search, 
                page: page,
                mobile: isMobile
            },
            dataType: 'json',
            success: function(response) {
                if (isMobile) {
                    $('#mobileSubjectsList').html(response.mobile_html);
                    $('.card-footer .pagination').replaceWith(response.mobile_pagination);
                } else {
                    $('#subjectsTableBody').html(response.desktop_html);
                    if ($('#subjectsTableBody tr').last().find('.pagination').length) {
                        $('#subjectsTableBody tr').last().remove();
                    }
                    $('#subjectsTableBody').append('<tr><td colspan="4">' + response.desktop_pagination + '</td></tr>');
                }
                
                // Update total subjects count
                $('.total-subjects-count').text(response.total_subjects);
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", status, error);
                // Fallback to regular page reload if AJAX fails
                window.location.href = 'subjects.php?page=' + page + 
                    (search ? '&search=' + encodeURIComponent(search) : '');
            }
        });
    }
    
    // Initial load
    const searchVal = $('#searchInput').val();
    const currentPage = $('#current-page').val() || 1;
    loadSubjects(searchVal, currentPage);

    // Handle window resize to switch between mobile and desktop views
    let resizeTimer;
    $(window).on('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            const searchVal = $('#searchInput').val();
            const currentPage = $('.page-item.active .page-link-ajax').data('page') || 1;
            loadSubjects(searchVal, currentPage);
        }, 200);
    });

    // Live search with debounce
    let searchTimer;
    $('#searchInput').on('input', function() {
        clearTimeout(searchTimer);
        const searchVal = $(this).val();
        searchTimer = setTimeout(() => {
            loadSubjects(searchVal, 1);
        }, 300);
    });

    // Search button click
    $('#searchButton').click(function() {
        const searchVal = $('#searchInput').val();
        loadSubjects(searchVal, 1);
    });

    // Handle pagination click
    $(document).on('click', '.page-link-ajax', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        const searchVal = $('#searchInput').val();
        loadSubjects(searchVal, page);
    });
});