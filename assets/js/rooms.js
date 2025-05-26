$(document).ready(function() {
    $('.alert').delay(3000).fadeOut('slow');

    $('.modal').modal({
        show: false,
        backdrop: 'static'
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

    $(document).on('click', '.edit-room', function() {
        const roomId = $(this).data('id');
        const roomName = $(this).data('name');
        
        $('#editRoomId').val(roomId);
        $('#editRoomName').val(roomName);
        $('#editRoomModal').modal('show');
    });

    $(document).on('click', '.delete-room', function() {
        const roomId = $(this).data('id');
        $('#deleteRoomId').val(roomId);
        $('#deleteRoomModal').modal('show');
    });

    $(document).on('click', '.view-room', function() {
        const roomId = $(this).data('id');
        window.location.href = phpVars.baseUrl + 'room_details.php?id=' + roomId;
    });

    $('#addRoomButton').click(function() {
        $('#addRoomModal').modal('show');
    });

    $('#addRoomModal').on('hidden.bs.modal', function () {
        $('#addRoomForm')[0].reset();
    });

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
                        loadRooms($('#searchInput').val(), 1);
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

    handleFormSubmission($('#addRoomForm'));
    handleFormSubmission($('#editRoomForm'));
    handleFormSubmission($('#deleteRoomForm'));

    function loadRooms(search = phpVars.searchTerm, page = phpVars.currentPage) {
        const isMobile = $(window).width() < 768;
        const limit = isMobile ? 5 : 7;
        
        if (isMobile) {
            $('#mobileRoomsList').html('<div class="list-group-item text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
        } else {
            $('#roomsTableBody').html('<tr><td colspan="3" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>');
        }
        
        $.ajax({
            url: phpVars.baseUrl + 'fetch_rooms.php',
            type: 'GET',
            data: { 
                search: search, 
                page: page,
                limit: limit,
                mobile: isMobile
            },
            success: function(response) {
                if (isMobile) {
                    $('#mobileRoomsList').html(response.mobile_html);
                    
                    const total_pages = Math.ceil(response.total_rooms / 5);
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
                                <i class="fas fa-door-open mr-1"></i> 
                                Total Room${response.total_rooms !== 1 ? 's' : ''}: 
                                <strong>${response.total_rooms}</strong>
                            </span>
                        </div>
                        ${paginationHtml}
                    `);
                } else {
                    $('#roomsTableBody').html(response.desktop_html);
    
                    const total_pages = Math.ceil(response.total_rooms / 7);
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
                    
                    $('#roomsTableBody').append(`<tr><td colspan="3">${paginationHtml}</td></tr>`);
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", status, error);
                window.location.href = 'rooms.php?page=' + page + 
                    (search ? '&search=' + encodeURIComponent(search) : '');
            }
        });
    }

    const searchVal = $('#searchInput').val();
    const urlParams = new URLSearchParams(window.location.search);
    const currentPage = urlParams.get('page') || 1;
    loadRooms(searchVal, currentPage);

    let resizeTimer;
    $(window).on('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            const searchVal = $('#searchInput').val();
            const currentPage = $('.page-item.active .page-link').data('page') || 1;
            loadRooms(searchVal, currentPage);
        }, 200);
    });

    let searchTimer;
    $('#searchInput').on('input', function() {
        clearTimeout(searchTimer);
        const searchVal = $(this).val();
        searchTimer = setTimeout(() => {
            loadRooms(searchVal, 1);
        }, 300);
    });

    $('#searchButton').click(function() {
        const searchVal = $('#searchInput').val();
        loadRooms(searchVal, 1);
    });

    $(document).on('click', '.page-link', function(e) {
        e.preventDefault();
        const page = $(this).data('page') || $(this).text().trim();
        const searchVal = $('#searchInput').val();
        loadRooms(searchVal, page);
    });
});