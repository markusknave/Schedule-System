$(document).ready(function() {
    // Restore button click
    $(document).on('click', '.restore-btn', function() {
        currentType = $(this).data('type');
        currentId = $(this).data('id');
        currentAction = 'restore';
        
        $('#modalTitle').text('Confirm Restoration');
        $('#modalBody').html(`Are you sure you want to restore this ${currentType}?`);
        $('#confirmModal').modal('show');
    });
    
    // Delete button click
    $(document).on('click', '.delete-btn', function() {
        currentType = $(this).data('type');
        currentId = $(this).data('id');
        currentAction = 'delete';
        
        $('#modalTitle').text('Confirm Permanent Deletion');
        $('#modalBody').html(`Are you sure you want to permanently delete this ${currentType}? This action cannot be undone.`);
        $('#confirmModal').modal('show');
    });
    
    // Confirm action
    $('#confirmAction').click(function() {
        $('#confirmModal').modal('hide');
        
        let url = '';
        if (currentAction === 'restore') {
            url = '/myschedule/components/del_comp/restore_item.php';
        } else if (currentAction === 'delete') {
            url = '/myschedule/components/del_comp/delete_item_permanently.php';
        }
        
        $.ajax({
            url: url,
            type: 'POST',
            data: {
                id: currentId,
                type: currentType,
                permanent: true
            },
            success: function(response) {
                response = JSON.parse(response);
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                alert('Error: ' + error);
            }
        });
    });
});    