$(document).ready(function() {
    $('.alert').delay(3000).fadeOut('slow');

    $('.modal').modal({
        show: false,
    });

    function toggleScrollbar(container) {
    // if content is taller than container → scrolling needed
    if (container.scrollHeight > container.clientHeight) {
        container.classList.add('needs-scroll');
    } else {
        container.classList.remove('needs-scroll');
    }
    }

    document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.overflow-container').forEach(el => {
        toggleScrollbar(el);
        window.addEventListener('resize', () => toggleScrollbar(el));
    });
    });

    function showAlert(message, type = 'success') {
        $('#messageContainer').html(`
            <div class="alert alert-${type} alert-dismissible fade show float-right">
                ${message}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        `).delay(3000).fadeOut('slow', function() { $(this).remove(); });
    }

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

    $(document).on('click', '.delete-teacher', function() {
        const teacherId = $(this).data('id');
        $('#deleteTeacherId').val(teacherId);
        $('#deleteTeacherModal').modal('show');
    });

    $('#addTeacherButton').click(function() {
        $('#addTeacherModal').modal('show');
    });

    $('#addTeacherModal').on('hidden.bs.modal', function () {
        $(this).find('form')[0].reset();
    });

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
                    form[0].reset();
                    showAlert(response.message);
                    loadTeachers($('#searchInput').val(), 1);
                } else {
                    showAlert(response.message, 'danger');
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
                    showAlert(response.message);
                    loadTeachers($('#searchInput').val(), 1);
                } else {
                    showAlert(response.message, 'danger');
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

    $('#deleteTeacherForm').submit(function(e) {
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
                    $('#deleteTeacherModal').modal('hide');
                    showAlert(response.message);
                    loadTeachers($('#searchInput').val(), 1);
                } else {
                    showAlert(response.message, 'danger');
                }
            },
            error: function(xhr, status, error) {
                $('#deleteTeacherModal .modal-body').prepend(
                    '<div class="alert alert-danger">Error: ' + error + '</div>'
                );
                setTimeout(function() {
                    $('#deleteTeacherModal .alert-danger').fadeOut('slow', function() {
                        $(this).remove();
                    });
                }, 5000);
            }
        });
    });

    function loadTeachers(search = "", page = 1) {
    const isMobile = $(window).width() < 768;
    const limit = isMobile ? 5 : 10;
        
    if (isMobile) {
        $('#mobileTeachersList').html('<div class="list-group-item text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
    } else {
        $('#teachersTableBody').html('<tr><td colspan="4" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>');
    }
    
    $.ajax({
        url: '/myschedule/components/admin_comp/user_comp/fetch_user.php',
        type: 'GET',
        data: { 
            search: search, 
            page: page,
            limit: limit,
            mobile: isMobile
        },
        success: function(response) {
            if (isMobile) {
                $('#mobileTeachersList').html(response.mobile_html);
                
                const total_pages = Math.ceil(response.total_teachers / 5);
                let paginationHtml = `
                    <nav aria-label="Page navigation" class="mt-2">
                        <ul class="pagination pagination-sm justify-content-center">
                            <li class="page-item ${page <= 1 ? 'disabled' : ''}">
                                <a class="page-link" href="#" data-page="${Math.max(1, page - 1)}" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                                </li>
                                ${Array.from({length: Math.min(5, total_pages)}, (_, i) => {
                                    const pageNum = Math.max(1, Math.min(page - 2, total_pages - 4)) + i;
                                    return `
                                        <li class="page-item ${page === pageNum ? 'active' : ''}">
                                            <a class="page-link" href="#" data-page="${pageNum}">${pageNum}</a>
                                        </li>
                                    `;
                                }).join('')}
                                <li class="page-item ${page >= total_pages ? 'disabled' : ''}">
                                    <a class="page-link" href="#" data-page="${Math.min(total_pages, page + 1)}" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    `;
                    
                    let cardFooter = $('.card-footer');
                    if (!cardFooter.length) {
                        cardFooter = $('<div class="card-footer bg-light"></div>');
                        $('.card').append(cardFooter);
                    }
                    cardFooter.html(`
                        <div class="d-flex justify-content-center mb-3">
                            <span class="badge bg-primary p-2">
                                <i class="fas fa-book mr-1"></i> 
                                Total Subject${response.total_subjects !== 1 ? 's' : ''}: 
                                <strong>${response.total_subjects}</strong>
                            </span>
                        </div>
                        ${response.mobile_pagination}
                    `);
            } else {
                $('#teachersTableBody').html(response.desktop_html);
                
                const total_pages = Math.ceil(response.total_teachers / 7);
                let paginationHtml = `
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item ${page <= 1 ? 'disabled' : ''}">
                                <a class="page-link" href="#" data-page="${Math.max(1, page - 1)}" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            ${Array.from({length: Math.min(5, total_pages)}, (_, i) => {
                                const pageNum = Math.max(1, Math.min(page - 2, total_pages - 4)) + i;
                                return `
                                    <li class="page-item ${page === pageNum ? 'active' : ''}">
                                        <a class="page-link" href="#" data-page="${pageNum}">${pageNum}</a>
                                    </li>
                                `;
                            }).join('')}
                            <li class="page-item ${page >= total_pages ? 'disabled' : ''}">
                                <a class="page-link" href="#" data-page="${Math.min(total_pages, page + 1)}" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                    </nav>`;
                
                $('#teachersTableBody').append(`<tr><td colspan="4">${paginationHtml}</td></tr>`);
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error:", status, error);
            window.location.href = 'dashboard.php?page=' + page + 
                (search ? '&search=' + encodeURIComponent(search) : '');
        }
    });
}

const searchVal = $('#searchInput').val();
const urlParams = new URLSearchParams(window.location.search);
const currentPage = urlParams.get('page') || 1;
loadTeachers(searchVal, currentPage);

let resizeTimer;
$(window).on('resize', function() {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function() {
        const searchVal = $('#searchInput').val();
        const currentPage = $('.page-item.active .page-link').data('page') || 1;
        loadTeachers(searchVal, currentPage);
    }, 200);
});

let searchTimer;
$('#searchInput').on('input', function() {
    clearTimeout(searchTimer);
    const searchVal = $(this).val();
    searchTimer = setTimeout(() => {
        loadTeachers(searchVal, 1);
    }, 300);
});

$('#searchButton').click(function() {
    const searchVal = $('#searchInput').val();
    loadTeachers(searchVal, 1);
});

$(document).on('click', '.page-link', function(e) {
    e.preventDefault();
    const page = $(this).data('page') || $(this).text().trim();
    const searchVal = $('#searchInput').val();
    loadTeachers(searchVal, page);
});

    let selectedStatus = null;
    let selectedUserId = null;

    $('#adminStatusSelect').change(function() {
        selectedStatus = $(this).val();
        selectedUserId = $('.edit-teacher.active').data('id');
        
        if (!selectedUserId) {
            alert('Please select a teacher first');
            $(this).val('');
            return;
        }

        if (selectedStatus === 'OL' || selectedStatus === 'OT') {
            $('#adminDateModal').modal('show');
        } else {
            updateTeacherStatus(selectedStatus);
        }
    });

    $('#adminSaveDates').click(function() {
        const startDate = $('#adminStartDate').val();
        const endDate = $('#adminEndDate').val();
        
        if (!startDate || !endDate) {
            alert('Please fill both dates');
            return;
        }
        
        if (new Date(startDate) > new Date(endDate)) {
            alert('End date must be after start date');
            return;
        }
        
        updateTeacherStatus(selectedStatus, startDate, endDate);
        $('#adminDateModal').modal('hide');
    });

    $('.edit-teacher').click(function() {
        $('.edit-teacher').removeClass('active');
        $(this).addClass('active');
        selectedUserId = $(this).data('id');
    });

    function updateTeacherStatus(status, startDate = null, endDate = null) {
        $.ajax({
            url: '../../components/prof_comp/update_status.php',
            method: 'POST',
            dataType: 'json',
            data: { 
                status: status,
                start_date: startDate,
                end_date: endDate,
                for_user_id: selectedUserId
            },
            success: function(response) {
                if (response.success) {
                    alert('Status updated successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + response.error);
                }
            },
            error: function() {
                alert('Request failed. Please try again.');
            }
        });
    }

});