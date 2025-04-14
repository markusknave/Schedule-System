<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['office_id'])) {
    header("Location: /myschedule/components/login.html");
    exit();
}

// Include database connection
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

$error = '';
$success = '';
$announcement = [];

// Get announcement ID from URL
$announcement_id = $_GET['id'] ?? 0;

// Fetch announcement data
if ($announcement_id) {
    $stmt = $conn->prepare("SELECT * FROM announcements WHERE id = ? AND office_id = ?");
    $stmt->bind_param("ii", $announcement_id, $_SESSION['office_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $announcement = $result->fetch_assoc();
    
    if (!$announcement) {
        header("Location: announcements.php");
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $current_image = $announcement['img'] ?? '';
    
    // Validate inputs
    if (empty($title) || empty($content)) {
        $error = 'All fields are required!';
    } else {
        $image_changed = isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE;
        
        if ($image_changed) {
            // Handle file upload
            $target_dir = $_SERVER['DOCUMENT_ROOT'] . "/myschedule/uploads/announcements/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $filename;
            
            // Check if image file is an actual image
            $check = getimagesize($_FILES['image']['tmp_name']);
            if ($check === false) {
                $error = 'File is not an image.';
            } elseif ($_FILES['image']['size'] > 25000000) { // 25MB max
                $error = 'Sorry, your file is too large. Max size is 25MB.';
            } elseif (!move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $error = 'Sorry, there was an error uploading your file.';
            } else {
                $img_path = "/myschedule/uploads/announcements/" . $filename;
                // Delete old image if it exists
                if ($current_image && file_exists($_SERVER['DOCUMENT_ROOT'] . $current_image)) {
                    unlink($_SERVER['DOCUMENT_ROOT'] . $current_image);
                }
            }
        } else {
            $img_path = $current_image;
        }
        
        if (!$error) {
            // Update database
            $stmt = $conn->prepare("UPDATE announcements SET title = ?, img = ?, content = ? WHERE id = ? AND office_id = ?");
            $stmt->bind_param("sssii", $title, $img_path, $content, $announcement_id, $_SESSION['office_id']);
            
            if ($stmt->execute()) {
                $success = 'Announcement updated successfully!';
                // Refresh announcement data
                $stmt = $conn->prepare("SELECT * FROM announcements WHERE id = ? AND office_id = ?");
                $stmt->bind_param("ii", $announcement_id, $_SESSION['office_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $announcement = $result->fetch_assoc();
            } else {
                $error = 'Error updating announcement: ' . $conn->error;
                // Delete the uploaded file if database update failed
                if ($image_changed && isset($target_file)) {
                    unlink($target_file);
                }
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
    <title>Edit Announcement</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.1/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.css">
    <script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.1/dist/js/adminlte.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
        .current-image {
            margin-top: 10px;
        }
        
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<?php include COMPONENTS_PATH . '/loading_screen.php'; ?>
    <div class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
                </li>
            </ul>
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <span class="nav-link">Logged in as, <?php echo htmlspecialchars($_SESSION['office_name'] ?? 'User'); ?></span>
                </li>
            </ul>
        </nav>
        
        <!-- Sidebar -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4" style="background-color:rgb(5, 29, 160);">
            <div class="container overflow-hidden">
                <a href="#" class="brand-link">
                    <img src="/myschedule/assets/img/favicon.png" width="35" height="35" alt="" class="ml-2">
                    <span class="brand-text font-weight-light">LNU Teacher's Board</span>
                </a>
            </div>
            <div class="sidebar">
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                        <li class="nav-item">
                            <a href="dashboard.php" class="nav-link">
                                <i class="nav-icon fas fa-users"></i>
                                <p>Teachers Management</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="schedule.php" class="nav-link">
                                <i class="nav-icon fa fa-calendar"></i>
                                <p>Schedules</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="rooms.php" class="nav-link">
                                <i class="nav-icon fas fa-grip-horizontal"></i>
                                <p>Rooms</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="announcements.php" class="nav-link">
                                <i class="nav-icon fa fa-exclamation-circle"></i>
                                <p>Announcements</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="archived.php" class="nav-link">
                                <i class="nav-icon fa fa-archive"></i>
                                <p>Archived</p>
                            </a>
                        </li>
                    </ul>
                </nav>
                <div style="position: absolute; bottom: 0;" class="nav-item overflow-hidden">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                        <li class="nav-item">
                            <a href="/myschedule/components/logout.php" class="nav-link">
                                <i class="nav-icon fas fa-sign-out-alt"></i>
                                <p>Logout</p>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </aside>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>Edit Announcement</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="announcements.php">Announcements</a></li>
                                <li class="breadcrumb-item active">Edit</li>
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
                                                value="<?= htmlspecialchars($announcement['title'] ?? '') ?>">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="image">Thumbnail Image</label>
                                            <div class="input-group">
                                                <div class="custom-file">
                                                    <input type="file" class="custom-file-input" id="image" name="image" accept="image/*">
                                                    <label class="custom-file-label" for="image">Choose new image (optional)</label>
                                                </div>
                                            </div>
                                            <?php if (!empty($announcement['img'])): ?>
                                                <div class="current-image">
                                                    <p>Current Image:</p>
                                                    <img src="<?= htmlspecialchars($announcement['img']) ?>" alt="Current Announcement Image" class="img-fluid" style="max-height: 200px;">
                                                </div>
                                            <?php endif; ?>
                                            <img id="imagePreview" src="#" alt="Preview" class="image-preview img-fluid" style="display: none;">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="content" class="required-field">Content</label>
                                            <textarea class="form-control" id="content" name="content" rows="8" 
                                                placeholder="Enter announcement content" required><?= htmlspecialchars($announcement['content'] ?? '') ?></textarea>
                                        </div>
                                    </div>
                                    
                                    <div class="card-footer">
                                        <button type="submit" class="btn btn-primary">Update Announcement</button>
                                        <a href="/myschedule/public/admin/announcements.php" class="btn btn-default float-right">Cancel</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <script src="/myschedule/assets/js/loading_screen.js"></script>
    <script>
    $(document).ready(function() {
        // Show image preview when file is selected
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
        
        // Initialize CKEditor
        CKEDITOR.replace('content', {
            toolbar: [
                { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat'] },
                { name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote'] },
                { name: 'align', items: ['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'] },
                { name: 'styles', items: ['Styles', 'Format', 'Font', 'FontSize'] },
                { name: 'colors', items: ['TextColor', 'BGColor'] },
                { name: 'links', items: ['Link', 'Unlink'] },
                { name: 'insert', items: ['Image', 'Table', 'HorizontalRule', 'SpecialChar'] },
                { name: 'document', items: ['Source'] }
            ],
            height: 300,
            extraAllowedContent: '*(*);*{*}',
            removePlugins: 'resize',
            removeButtons: ''
        });

        // Form validation
        $('form').submit(function() {
            let valid = true;
            
            // Check required fields
            $('.required-field').each(function() {
                const fieldId = $(this).attr('for');
                const $field = $('#' + fieldId);
                
                if (fieldId === 'content') {
                    // Special handling for CKEditor
                    const content = CKEDITOR.instances.content.getData().replace(/<[^>]*>/g, '').trim();
                    if (content === '') {
                        valid = false;
                        $field.addClass('is-invalid');
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
            
            if (!valid) {
                return false;
            }
        });
    });
    </script>
</body>
</html>