$(document).ready(function() {
    $('.alert').delay(3000).fadeOut('slow');

    $('.modal').modal({
        show: false,
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

    $(document).on('click', '.edit-office', function() {
        const officeId = $(this).data('id');
        const name = $(this).data('name');
        const email = $(this).data('email');
        
        $('#editOfficeId').val(officeId);
        $('#editName').val(name);
        $('#editEmail').val(email);
        $('#editOfficeModal').modal('show');
    });

    $(document).on('click', '.delete-office', function() {
        const officeId = $(this).data('id');
        $('#deleteOfficeId').val(officeId);
        $('#deleteOfficeModal').modal('show');
    });

    $('#addOfficeButton').click(function() {
        $('#addOfficeModal').modal('show');
    });

    $('#addOfficeModal').on('hidden.bs.modal', function () {
        $(this).find('form')[0].reset();
    });

    $('#addOfficeForm').submit(function(e) {
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
                    $('#addOfficeModal').modal('hide');
                    form[0].reset();
                    showAlert(response.message);
                    location.reload(); // Reload to show new office
                } else {
                    showAlert(response.message, 'danger');
                }
            },
            error: function(xhr, status, error) {
                $('#addOfficeModal .modal-body').prepend(
                    '<div class="alert alert-danger">Error: ' + error + '</div>'
                );
                setTimeout(function() {
                    $('#addOfficeModal .alert-danger').fadeOut('slow', function() {
                        $(this).remove();
                    });
                }, 5000);
            }
        });
    });

    $('#editOfficeForm').submit(function(e) {
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
                    $('#editOfficeModal').modal('hide');
                    showAlert(response.message);
                    location.reload(); // Reload to show updated data
                } else {
                    showAlert(response.message, 'danger');
                }
            },
            error: function(xhr, status, error) {
                $('#editOfficeModal .modal-body').prepend(
                    '<div class="alert alert-danger">Error: ' + error + '</div>'
                );
                setTimeout(function() {
                    $('#editOfficeModal .alert-danger').fadeOut('slow', function() {
                        $(this).remove();
                    });
                }, 5000);
            }
        });
    });

    $('#deleteOfficeForm').submit(function(e) {
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
                    $('#deleteOfficeModal').modal('hide');
                    showAlert(response.message);
                    location.reload(); // Reload to show updated list
                } else {
                    showAlert(response.message, 'danger');
                }
            },
            error: function(xhr, status, error) {
                $('#deleteOfficeModal .modal-body').prepend(
                    '<div class="alert alert-danger">Error: ' + error + '</div>'
                );
                setTimeout(function() {
                    $('#deleteOfficeModal .alert-danger').fadeOut('slow', function() {
                        $(this).remove();
                    });
                }, 5000);
            }
        });
    });

    let searchTimer;
    $('#searchInput').on('input', function() {
        clearTimeout(searchTimer);
        const searchVal = $(this).val();
        searchTimer = setTimeout(() => {
            window.location.href = '?search=' + encodeURIComponent(searchVal);
        }, 300);
    });

    $('#searchButton').click(function() {
        const searchVal = $('#searchInput').val();
        window.location.href = '?search=' + encodeURIComponent(searchVal);
    });
});