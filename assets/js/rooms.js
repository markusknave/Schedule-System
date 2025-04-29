$(document).ready(function() {
    // Show success/error messages
    $('.alert').delay(3000).fadeOut('slow');

    // Initialize all modals properly
    $('.modal').modal({
        show: false,
        backdrop: 'static'
    });

    // Export to CSV
    $('#exportToCsv').click(function() {
        window.location.href = phpVars.baseUrl + 'export_rooms.php';
    });

    // Edit Room Button Click
    $(document).on('click', '.edit-room', function() {
        const roomId = $(this).data('id');
        const roomName = $(this).data('name');
        
        $('#editRoomId').val(roomId);
        $('#editRoomName').val(roomName);
        $('#editRoomModal').modal('show');
    });

    // Delete Room Button Click
    $(document).on('click', '.delete-room', function() {
        const roomId = $(this).data('id');
        $('#deleteRoomId').val(roomId);
        $('#deleteRoomModal').modal('show');
    });

    // View Room Button Click
    $(document).on('click', '.view-room', function() {
        const roomId = $(this).data('id');
        window.location.href = phpVars.baseUrl + 'room_details.php?id=' + roomId;
    });

    // Add Room Button Click
    $('#addRoomButton').click(function() {
        $('#addRoomModal').modal('show');
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
    handleFormSubmission($('#addRoomForm'));
    handleFormSubmission($('#editRoomForm'));
    handleFormSubmission($('#deleteRoomForm'));

    // Dynamic search functionality
    function loadRooms(search = phpVars.searchTerm, page = phpVars.currentPage) {
        const isMobile = $(window).width() < 768;
        
        // Show loading state
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
                mobile: isMobile
            },
            success: function(response) {
                if (isMobile) {
                    $('#mobileRoomsList').html(response);
                } else {
                    $('#roomsTableBody').html(response);
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
                window.location.href = 'rooms.php?page=' + page + 
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
            const currentPage = $('.page-item.active .page-link').data('page') || phpVars.currentPage;
            loadRooms(searchVal, currentPage);
        }, 200);
    });

    // Initial load based on current view
    loadRooms();

    // Live search with debounce
    let searchTimer;
    $('#searchInput').on('input', function() {
        clearTimeout(searchTimer);
        const searchVal = $(this).val();
        searchTimer = setTimeout(() => {
            loadRooms(searchVal, 1);
        }, 300);
    });

    // Search button click
    $('#searchButton').click(function() {
        const searchVal = $('#searchInput').val();
        loadRooms(searchVal, 1);
    });

    // Handle pagination click
    $(document).on('click', '.page-link', function(e) {
        e.preventDefault();
        const page = $(this).data('page') || $(this).text().trim();
        const searchVal = $('#searchInput').val();
        loadRooms(searchVal, page);
    });
});