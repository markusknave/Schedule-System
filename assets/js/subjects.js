$(document).ready(function() {
    // Show success/error messages
    $('.alert').delay(3000).fadeOut('slow');

    // Initialize all modals properly
    $('.modal').modal({
        show: false,
        backdrop: 'static'
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

    // Form Submissions (Add, Edit, Delete)
    function handleFormSubmission(form, callback) {
        form.on('submit', function(e) {
            e.preventDefault();
            const formData = form.serialize();
            
            $.ajax({
                url: form.attr('action'),
                method: form.attr('method'),
                data: formData,
                success: function(response) {
                    if (typeof callback === 'function') {
                        callback(response);
                    }
                    location.reload();
                },
                error: function(xhr, status, error) {
                    alert('Error: ' + error);
                }
            });
        });
    }

    // Initialize form handlers
    handleFormSubmission($('#addSubjectForm'));
    handleFormSubmission($('#editSubjectForm'));
    handleFormSubmission($('#deleteSubjectForm'));

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
            dataType: 'json',
            data: { 
                search: search, 
                page: page,
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
                    
                    // Append pagination to the table
                    $('#subjectsTableBody').append(`<tr><td colspan="4">${desktopPagination}</td></tr>`);
                }
                
                    // Update URL without reloading
                const queryParams = new URLSearchParams();
                if (page > 1) queryParams.set('page', page);
                if (search) queryParams.set('search', search);
                
                const newUrl = window.location.pathname + (queryParams.toString() ? '?' + queryParams.toString() : '');
                window.history.pushState({ path: newUrl }, '', newUrl);
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", status, error);
                // Fallback to regular page reload if AJAX fails
                window.location.href = 'subjects.php?page=' + page + 
                    (search ? '&search=' + encodeURIComponent(search) : '');
            }
        });
    }

    // Handle window resize to switch between mobile and desktop views
    let resizeTimer;
    $(window).on('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            const searchVal = $('#searchInput').val();
            const currentPage = $('.page-item.active .page-link').data('page') || 1;
            loadSubjects(searchVal, currentPage);
        }, 200);
    });

    // Initial load based on current view
    const searchVal = $('#searchInput').val();
    const currentPage = $('#current-page').val() || 1;
    loadSubjects(searchVal, currentPage);

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
    $(document).on('click', '.page-link', function(e) {
        e.preventDefault();
        const page = $(this).data('page') || $(this).text().trim();
        const searchVal = $('#searchInput').val();
        loadSubjects(searchVal, page);
    });
});