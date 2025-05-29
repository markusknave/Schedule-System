function showNotification(message, type = 'info') {
  const $n = $('#notification');
  $n
    .removeClass('alert-success alert-danger alert-warning alert-info')
    .addClass('alert-' + type)
    .text(message)
    .stop(true,true)
    .fadeIn(200)
    .delay(2500)
    .fadeOut(400);
}

let tempPasswordData = {};

$(document).ready(function() {

    $('.toggle-password').click(function() {
        const targetSelector = $(this).data('target');
        const $input = $(targetSelector);
        const type = $input.attr('type') === 'password' ? 'text' : 'password';
        $input.attr('type', type);
        $(this).find('i').toggleClass('fa-eye fa-eye-slash');
    });

    $('#savePasswordChanges').click(function() {
        const currentPassword = $('#currentPassword').val();
        const newPassword = $('#newPassword').val();
        const confirmPassword = $('#confirmPassword').val();

        if (!currentPassword || !newPassword || !confirmPassword) {
            showNotification('All fields are required!', 'warning');
            return;
        }

        if (newPassword.length < 8) {
            showNotification('Password must be at least 8 characters long!', 'warning');
            return;
        }

        if (newPassword !== confirmPassword) {
            showNotification('New password and confirmation password do not match!', 'warning');
            return;
        }

        tempPasswordData = { currentPassword, newPassword };
        $('#changePasswordModal').modal('hide');
        $('#confirmChangePasswordModal').modal('show');
    });

    $('#confirmPasswordChange').click(function() {
        const $btn = $('#savePasswordChanges');
        $btn.prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

        $.ajax({
            url: '../../components/pass_comp/change_pass.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(tempPasswordData),
            success: function(response) {
                showNotification(response.success ? response.message : 'Error: ' + response.message,
                                 response.success ? 'success' : 'danger');
                $('#confirmChangePasswordModal').modal('hide');
                $('#passwordChangeForm')[0].reset();
            },
            error: function(xhr) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    showNotification('Error: ' + (response.message || 'Failed to change password'), 'danger');
                } catch (e) {
                    showNotification('An unexpected error occurred. Please try again.', 'danger');
                }
            },
            complete: function() {
                $btn.prop('disabled', false).text('Save Changes');
                tempPasswordData = {};
            }
        });
    });

    $('#changePasswordModal').on('hidden.bs.modal', function() {
        $('#passwordChangeForm')[0].reset();
        $('#savePasswordChanges').prop('disabled', false).text('Save Changes');
    });

    $('[data-widget="pushmenu"]').click(function(e) {
        e.preventDefault();
        if (typeof AdminLTEOptions !== 'undefined' && $.fn.pushMenu) {
            $('body').pushMenu('toggle');
        } else {
            $('body').toggleClass('sidebar-collapse');
        }
        return false;
    });

    $('.dropdown-toggle').dropdown();
});

$('#uploadImageBtn').click(function() {
    const formData = new FormData();
    const fileInput = document.getElementById('profileImage');
    const $btn = $(this);
    
    if (fileInput.files.length === 0) {
        showNotification('Please select an image.', 'warning');
        return;
    }

    formData.append('profileImage', fileInput.files[0]);

    $btn.prop('disabled', true)
       .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Uploading...');

    $.ajax({
        url: '/myschedule/components/upload_image.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                showNotification('Image uploaded successfully!', 'success');
                $('#addImageModal').modal('hide');
                location.reload();
            } else {
                showNotification(response.error || 'Upload failed', 'danger');
            }
        },
        error: function(xhr) {
            try {
                const response = JSON.parse(xhr.responseText);
                showNotification('Error: ' + (response.error || 'Upload failed'), 'danger');
            } catch (e) {
                showNotification('An unexpected error occurred. Please try again.', 'danger');
            }
        },
        complete: function() {
            $btn.prop('disabled', false).text('Upload');
            $('#imageUploadForm')[0].reset();
        }
    });
});

$('#addImageModal').on('hidden.bs.modal', function() {
    $('#imageUploadForm')[0].reset();
});

document.getElementById('profileImage').addEventListener('change', function(event) {
    const preview = document.getElementById('imagePreview');
    const file = event.target.files[0];

    if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        preview.src = '#';
        preview.style.display = 'none';
    }
});