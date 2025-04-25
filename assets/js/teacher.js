$(document).ready(function() {
    // Show success/error messages
    $('.alert').delay(3000).fadeOut('slow');

    // Initialize all modals properly
    $('.modal').modal({
        show: false,
        backdrop: 'static'
    });

    // Edit Teacher Button Click
    $(document).on('click', '.edit-teacher', function() {
        const teacherId = $(this).data('id');
        const firstname = $(this).data('firstname');
        const middlename = $(this).data('middlename');
        const lastname = $(this).data('lastname');
        const extension = $(this).data('extension');
        const email = $(this).data('email');
        const unit = $(this).data('unit');
        
        $('#editTeacherId').val(teacherId);
        $('#editFirstname').val(firstname);
        $('#editMiddlename').val(middlename);
        $('#editLastname').val(lastname);
        $('#editExtension').val(extension);
        $('#editEmail').val(email);
        $('#editUnit').val(unit);
        $('#editTeacherModal').modal('show');
    });

    // Delete Teacher Button Click
    $(document).on('click', '.delete-teacher', function() {
        const teacherId = $(this).data('id');
        $('#deleteTeacherId').val(teacherId);
        $('#deleteTeacherModal').modal('show');
    });

    // View Teacher Button Click
    $(document).on('click', '.view-teacher', function() {
        const teacherId = $(this).data('id');
        window.location.href = '/myschedule/components/teach_comp/teacher_details.php?id=' + teacherId;
    });

    // Add Teacher Button Click
    $('#addTeacherButton').click(function() {
        $('#addTeacherModal').modal('show');
    });

    // Add Teacher Form Submission
    $('#addTeacherForm').submit(function(e) {
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
                    $('#addTeacherModal').modal('hide');
                    // Show success message
                    $('<div class="alert alert-success alert-dismissible fade show float-right">' + 
                    response.message + 
                    '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                    '<span aria-hidden="true">&times;</span></button></div>')
                    .insertBefore('.content-header .container-fluid .row .col-sm-6')
                    .delay(3000).fadeOut('slow', function() { $(this).remove(); });
                    loadTeachers($('#searchInput').val(), 1);
                } else {
                    // Show error message in modal
                    $('#addTeacherModal .modal-body').prepend(
                        '<div class="alert alert-danger">' + response.message + '</div>'
                    );
                    // Remove error after 5 seconds
                    setTimeout(function() {
                        $('#addTeacherModal .alert-danger').fadeOut('slow', function() {
                            $(this).remove();
                        });
                    }, 5000);
                }
            },
            error: function(xhr, status, error) {
                $('#addTeacherModal .modal-body').prepend(
                    '<div class="alert alert-danger">Error: ' + error + '</div>'
                );
                setTimeout(function() {
                    $('#addTeacherModal .alert-danger').fadeOut('slow', function() {
                        $(this).remove();
                    });
                }, 5000);
            }
        });
    });

    // Edit Teacher Form Submission
    $('#editTeacherForm').submit(function(e) {
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
                    $('#editTeacherModal').modal('hide');
                    // Show success message
                    $('<div class="alert alert-success alert-dismissible fade show float-right">' + 
                    response.message + 
                    '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                    '<span aria-hidden="true">&times;</span></button></div>')
                    .insertBefore('.content-header .container-fluid .row .col-sm-6')
                    .delay(3000).fadeOut('slow', function() { $(this).remove(); });
                    loadTeachers($('#searchInput').val(), 1);
                } else {
                    // Show error message in modal
                    $('#editTeacherModal .modal-body').prepend(
                        '<div class="alert alert-danger">' + response.message + '</div>'
                    );
                    // Remove error after 5 seconds
                    setTimeout(function() {
                        $('#editTeacherModal .alert-danger').fadeOut('slow', function() {
                            $(this).remove();
                        });
                    }, 5000);
                }
            },
            error: function(xhr, status, error) {
                $('#editTeacherModal .modal-body').prepend(
                    '<div class="alert alert-danger">Error: ' + error + '</div>'
                );
                setTimeout(function() {
                    $('#editTeacherModal .alert-danger').fadeOut('slow', function() {
                        $(this).remove();
                    });
                }, 5000);
            }
        });
    });

    // Delete Teacher Form Submission
    $('#deleteTeacherForm').submit(function(e) {
        e.preventDefault();
        const form = $(this);
        const formData = form.serialize();
        
        $.ajax({
            url: form.attr('action'),
            method: form.attr('method'),
            data: formData,
            success: function(response) {
                loadTeachers($('#searchInput').val(), 1);
            },
            error: function(xhr, status, error) {
                alert('Error: ' + error);
            }
        });
    });

    // Dynamic search functionality
    function loadTeachers(search = "", page = 1) {
        const isMobile = $(window).width() < 768;
        
        // Show loading state
        if (isMobile) {
            $('#mobileTeachersList').html('<div class="list-group-item text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
        } else {
            $('#teachersTableBody').html('<tr><td colspan="4" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>');
        }
        
        $.ajax({
            url: '/myschedule/components/teach_comp/fetch_teachers.php',
            type: 'GET',
            data: { 
                search: search, 
                page: page,
                mobile: isMobile
            },
            dataType: 'json',
            success: function(response) {
                if (isMobile) {
                    $('#mobileTeachersList').html(response.mobile_html);
                    $('.card-footer .pagination').replaceWith(response.mobile_pagination);
                } else {
                    $('#teachersTableBody').html(response.desktop_html);
                    if ($('#teachersTableBody tr').last().find('.pagination').length) {
                        $('#teachersTableBody tr').last().remove();
                    }
                    $('#teachersTableBody').append('<tr><td colspan="4">' + response.desktop_pagination + '</td></tr>');
                }
                
                // Update total teachers count
                $('.total-teachers-count').text(response.total_teachers);
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", status, error);
                // Fallback to regular page reload if AJAX fails
                window.location.href = 'dashboard.php?page=' + page + 
                    (search ? '&search=' + encodeURIComponent(search) : '');
            }
        });
    }
    
    // Initial load
    const searchVal = $('#searchInput').val();
    const currentPage = $('#current-page').val() || 1;
    loadTeachers(searchVal, currentPage);

    // Handle window resize to switch between mobile and desktop views
    let resizeTimer;
    $(window).on('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            const searchVal = $('#searchInput').val();
            const currentPage = $('.page-item.active .page-link-ajax').data('page') || 1;
            loadTeachers(searchVal, currentPage);
        }, 200);
    });

    // Live search with debounce
    let searchTimer;
    $('#searchInput').on('input', function() {
        clearTimeout(searchTimer);
        const searchVal = $(this).val();
        searchTimer = setTimeout(() => {
            loadTeachers(searchVal, 1);
        }, 300);
    });

    // Search button click
    $('#searchButton').click(function() {
        const searchVal = $('#searchInput').val();
        loadTeachers(searchVal, 1);
    });

    // Handle pagination click
    $(document).on('click', '.page-link-ajax', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        const searchVal = $('#searchInput').val();
        loadTeachers(searchVal, page);
    });
});