$(document).ready(function() {
    let currentType, currentId, currentAction;
    
    const showModal = (title, message) => {
        $('#modalTitle').text(title);
        $('#modalBody').html(message);
        $('#confirmModal').modal('show');
    };
    
    const handleApiResponse = (data) => {
        if (data.success) {
            // Refresh the page to show updated data
            location.reload();
        } else {
            showModal('Operation Failed', data.message || 'An error occurred');
            if (data.errors) {
                console.error('Validation errors:', data.errors);
            }
        }
    };
    
    $(document).on('click', '.restore-btn', function() {
        currentType = $(this).data('type');
        currentId = $(this).data('id');
        currentAction = 'restore';
        
        showModal('Confirm Restoration', `Are you sure you want to restore this ${currentType}?`);
    });
    
    $(document).on('click', '.delete-btn', function() {
        currentType = $(this).data('type');
        currentId = $(this).data('id');
        currentAction = 'delete';
        
        showModal('Confirm Permanent Deletion', 
            `Are you sure you want to permanently delete this ${currentType}?<br><br>
             <strong>This action cannot be undone.</strong>`);
    });
    
    $('#confirmAction').click(function() {
        $('#confirmModal').modal('hide');
        
        const endpoint = currentAction === 'restore' 
            ? '/myschedule/components/del_comp/restore_item.php' 
            : '/myschedule/components/del_comp/delete_item_permanently.php';
        
        const formData = new FormData();
        formData.append('id', currentId);
        formData.append('type', currentType);
        
        fetch(endpoint, {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => {
                    throw new Error(err.message || 'Network response was not ok');
                });
            }
            return response.json();
        })
        .then(handleApiResponse)
        .catch(error => {
            console.error('Error:', error);
            showModal('Error', error.message || 'An unexpected error occurred');
        });
    });
});