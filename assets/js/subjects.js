$(document).ready(function() {
    $('.alert').delay(3000).fadeOut('slow');

    $('.modal').modal({
        show: false,
        backdrop: 'static'
    });

    $(document).on('click', '.edit-subject', function() {
        const subjectId = $(this).data('id');
        const subjectCode = $(this).data('code');
        const subjectName = $(this).data('name');
        
        $('#editSubjectId').val(subjectId);
        $('#editSubjectCode').val(subjectCode);
        $('#editSubjectName').val(subjectName);
        $('#editSubjectModal').modal('show');
    });

    $(document).on('click', '.delete-subject', function() {
        const subjectId = $(this).data('id');
        $('#deleteSubjectId').val(subjectId);
        $('#deleteSubjectModal').modal('show');
    });

    $(document).on('click', '.view-subject', function() {
        const subjectId = $(this).data('id');
        window.location.href = '/myschedule/components/subj_comp/subject_details.php?id=' + subjectId;
    });

    $('#addSubjectButton').click(function() {
        $('#addSubjectModal').modal('show');
    });

    function showAlert(message, type = 'success') {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        `;
        $('#messageContainer').html(alertHtml);
        $('.alert').delay(3000).fadeOut('slow');
    }

    function handleFormSubmission(form, callback) {
        form.on('submit', function(e) {
            e.preventDefault();
            const formData = form.serialize();
            
            $.ajax({
                url: form.attr('action'),
                method: form.attr('method'),
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        form[0].reset();
                        form.closest('.modal').modal('hide');
                        showAlert(response.message);
                        const searchVal = $('#searchInput').val();
                        const currentPage = $('.page-item.active .page-link').data('page') || 1;
                        loadSubjects(searchVal, currentPage);
                    } else {
                        showAlert(response.message, 'danger');
                    }
                    if (typeof callback === 'function') {
                        callback(response);
                    }
                },
                error: function(xhr, status, error) {
                    showAlert('Error: ' + error, 'danger');
                }
            });
        });
    }

    handleFormSubmission($('#addSubjectForm'));
    handleFormSubmission($('#editSubjectForm'));
    handleFormSubmission($('#deleteSubjectForm'));

    function loadSubjects(search = "", page = 1) {
        const isMobile = $(window).width() < 768;
        const limit = isMobile ? 5 : 7;
        
        if (isMobile) {
            $('#mobileSubjectsList').html('<div class="list-group-item text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
        } else {
            $('#subjectsTableBody').html('<tr><td colspan="4" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>');
        }
        
        $.ajax({
            url: '/myschedule/components/subj_comp/fetch_subjects.php',
            type: 'GET',
            dataType: 'json',
            data: { 
                search: search, 
                page: page,
                limit: limit,
                mobile: isMobile
            },
            success: function(response) {
                const total_pages = Math.ceil(response.total_subjects / 5);
                
                if (isMobile) {
                    $('#mobileSubjectsList').html(response.mobile_html);
                    
                    const mobilePagination = `
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
                        ${mobilePagination}
                    `);
                } else {
                    $('#subjectsTableBody').html(response.desktop_html);
                    
                    const desktopPagination = `
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <li class="page-item ${page <= 1 ? 'disabled' : ''}">
                                    <a class="page-link" href="#" data-page="${Math.max(1, page - 1)}" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                ${Array.from({length: Math.min(7, total_pages)}, (_, i) => {
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
                    
                    $('#subjectsTableBody').append(`<tr><td colspan="4">${desktopPagination}</td></tr>`);
                }
                
                const queryParams = new URLSearchParams();
                if (page > 1) queryParams.set('page', page);
                if (search) queryParams.set('search', search);
                
                const newUrl = window.location.pathname + (queryParams.toString() ? '?' + queryParams.toString() : '');
                window.history.pushState({ path: newUrl }, '', newUrl);
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", status, error);
                window.location.href = 'subjects.php?page=' + page + 
                    (search ? '&search=' + encodeURIComponent(search) : '');
            }
        });
    }

    let resizeTimer;
    $(window).on('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            const searchVal = $('#searchInput').val();
            const currentPage = $('.page-item.active .page-link').data('page') || 1;
            loadSubjects(searchVal, currentPage);
        }, 200);
    });

    const searchVal = $('#searchInput').val();
    const currentPage = $('#current-page').val() || 1;
    loadSubjects(searchVal, currentPage);

    let searchTimer;
    $('#searchInput').on('input', function() {
        clearTimeout(searchTimer);
        const searchVal = $(this).val();
        searchTimer = setTimeout(() => {
            loadSubjects(searchVal, 1);
        }, 300);
    });

    $('#searchButton').click(function() {
        const searchVal = $('#searchInput').val();
        loadSubjects(searchVal, 1);
    });

    $(document).on('click', '.page-link', function(e) {
        e.preventDefault();
        const page = $(this).data('page') || $(this).text().trim();
        const searchVal = $('#searchInput').val();
        loadSubjects(searchVal, page);
    });
});