<?php
session_start();
@include '../../components/links.php';

if (!isset($_SESSION['office_id'])) {
    header("Location: /myschedule/login.html");
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    
    $office_id = $_SESSION['office_id'];
    
    if (empty($title) || empty($content)) {
        $error = 'All fields are required!';
    } elseif (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
        $error = 'Please select an image for the announcement!';
    } else {
        $target_dir = $_SERVER['DOCUMENT_ROOT'] . "/myschedule/uploads/announcements/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $filename;
        
        $check = getimagesize($_FILES['image']['tmp_name']);
        if ($check === false) {
            $error = 'File is not an image.';
        } elseif ($_FILES['image']['size'] > 25000000) { // 25MB max
            $error = 'Sorry, your file is too large. Max size is 25MB.';
        } elseif (!move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $error = 'Sorry, there was an error uploading your file.';
        } else {
            $img_path = "/myschedule/uploads/announcements/" . $filename;
            $stmt = $conn->prepare("INSERT INTO announcements (office_id, title, img, content, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("isss", $office_id, $title, $img_path, $content);
            
            if ($stmt->execute()) {
                $success = 'Announcement created successfully!';
                $title = $content = '';

                header('Location: ../../public/office/announcements.php');
                exit();
            } else {
                $error = 'Error saving announcement: ' . $conn->error;
                unlink($target_file);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Announcement</title>
    <script src="/myschedule/assets/tinymce/js/tinymce/tinymce.min.js"></script>

    <style>
        .image-preview {
            max-width: 100%;
            max-height: 300px;
            margin-top: 10px;
        }
        .required-field::after {
            content: " *";
            color: red;
        }
        
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<?php ?>
    <div class="wrapper">
    <?php include '../../components/header.php'; ?>
    <?php include '../../components/sidebar.php'; ?>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>Create New Announcement</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="../../public/office/announcements.php">Announcements</a></li>
                                <li class="breadcrumb-item active">Create</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </section>

            <section class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-8 offset-md-2">
                            <div class="card card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">Announcement Details</h3>
                                </div>
                                
                                <?php if ($error): ?>
                                    <div class="alert alert-danger alert-dismissible m-3">
                                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                                        <h5><i class="icon fas fa-ban"></i> Error!</h5>
                                        <?= htmlspecialchars($error) ?>
                                    </div>
                                <?php elseif ($success): ?>
                                    <div class="alert alert-success alert-dismissible m-3">
                                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                                        <h5><i class="icon fas fa-check"></i> Success!</h5>
                                        <?= htmlspecialchars($success) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="title" class="required-field">Title</label>
                                            <input type="text" class="form-control" id="title" name="title" 
                                                placeholder="Enter announcement title" required
                                                value="<?= isset($title) ? htmlspecialchars($title) : '' ?>">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="image" class="required-field">Thumbnail Image</label>
                                            <div class="input-group">
                                                <div class="custom-file">
                                                    <input type="file" class="custom-file-input" id="image" name="image" accept="image/*" required>
                                                    <label class="custom-file-label" for="image">Choose file</label>
                                                </div>
                                            </div>
                                            <img id="imagePreview" src="#" alt="Preview" class="image-preview img-fluid">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="content" class="required-field">Content</label>
                                            <textarea class="form-control" id="content" name="content" rows="8" 
                                                placeholder="Enter announcement content" required><?= isset($content) ? htmlspecialchars($content) : '' ?></textarea>
                                        </div>
                                    </div>
                                    
                                    <div class="card-footer">
                                        <button type="submit" class="btn btn-primary">Publish Announcement</button>
                                        <a href="/myschedule/public/office/announcements.php" class="btn btn-default float-right">Cancel</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <script>
        
    $(document).ready(function() {
        $('#image').change(function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#imagePreview').attr('src', e.target.result).show();
                    $('.custom-file-label').text(file.name);
                }
                reader.readAsDataURL(file);
            }
        });
        
        $('form').submit(function() {
            let valid = true;
            
            $('.required-field').each(function() {
                const fieldId = $(this).attr('for');
                const $field = $('#' + fieldId);
                
                if ($field.val().trim() === '') {
                    valid = false;
                    $field.addClass('is-invalid');
                } else {
                    $field.removeClass('is-invalid');
                }
            });
            
            // Check if image is selected
            if ($('#image').val() === '') {
                valid = false;
                $('#image').addClass('is-invalid');
            } else {
                $('#image').removeClass('is-invalid');
            }
            
            if (!valid) {
                return false;
            }
        });
    });


    $(document).ready(function() {
    tinymce.init({
        selector: '#content',
        height: 300,
        menubar: false,
        plugins: [
            'advlist autolink lists link image charmap print preview anchor',
            'searchreplace visualblocks code fullscreen',
            'insertdatetime media table paste code help wordcount'
        ],
        toolbar: 'undo redo | formatselect | ' +
        'bold italic underline strikethrough | forecolor backcolor | ' +
        'alignleft aligncenter alignright alignjustify | ' +
        'bullist numlist outdent indent | removeformat | help',
        content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
        setup: function(editor) {
            editor.on('change', function() {
                editor.save();
            });
        }
    });

    $('form').submit(function(e) {
        let valid = true;
        
        $('.required-field').each(function() {
            const fieldId = $(this).attr('for');
            const $field = $('#' + fieldId);
            
            if (fieldId === 'content') {
                const content = tinymce.get('content').getContent({format: 'text'});
                if (!content.trim()) {
                    valid = false;
                    $field.addClass('is-invalid');
                    tinymce.get('content').focus();
                } else {
                    $field.removeClass('is-invalid');
                }
            } else if ($field.val().trim() === '') {
                valid = false;
                $field.addClass('is-invalid');
            } else {
                $field.removeClass('is-invalid');
            }
        });
        
        if ($('#image').val() === '') {
            valid = false;
            $('#image').addClass('is-invalid');
        } else {
            $('#image').removeClass('is-invalid');
        }
        
        if (!valid) {
            e.preventDefault();
            return false;
        }
    });

    $('#image').change(function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#imagePreview').attr('src', e.target.result).show();
                $('.custom-file-label').text(file.name);
            }
            reader.readAsDataURL(file);
        }
    });
});
    </script>
</body>
</html>