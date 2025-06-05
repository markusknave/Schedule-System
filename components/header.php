<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';

$imgUrl = '';
$imgFound = false;

if (!empty($_SESSION['img_filename'])) {
    $filePath = IMAGE_DIR . $_SESSION['img_filename'];
    if (file_exists($filePath)) {
        $imgUrl = IMAGE_BASE . UPLOAD_REL_PATH . $_SESSION['img_filename'];
        $imgFound = true;
    }
} elseif (!empty($_SESSION['img'])) {
    $filename = basename($_SESSION['img']);
    $filePath = IMAGE_DIR . $filename;
    if (file_exists($filePath)) {
        $imgUrl = IMAGE_BASE . UPLOAD_REL_PATH . $filename;
        $imgFound = true;
    }
}
?>
<div id="notification" 
     class="alert" 
     role="alert"
     style="display:none;
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1060;
            min-width: 200px;">
</div>

<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
        <li class="nav-item">
            <?php if (basename($_SERVER['PHP_SELF']) == 'disp_announ_sched.php'): ?>
                <a class="nav-link" href="/myschedule/public/office/schedule.php"><i class="fas fa-arrow-left"></i></a>
            <?php else: ?>
                <a class="nav-link" href="#" data-widget="pushmenu" role="button" onclick="return false;"><i class="fas fa-bars"></i></a>
            <?php endif; ?>
        </li>
    </ul>

    <ul class="navbar-nav ml-auto">
        <li class="nav-item d-flex align-items-center">
            <div class="d-inline-flex align-items-center">
                <?php if ($imgFound): ?>
                    <img src="<?= htmlspecialchars($imgUrl) ?>" 
                        alt="Profile" 
                        style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 1px solid #ddd;">
                <?php else: ?>
                    <i class="fa-solid fa-user" style="font-size: 1.25rem;"></i>
                <?php endif; ?>
            </div>
        </li>
        <li class="nav-item d-flex align-items-center">
                <span class="nav-link">
                    Logged in as, 
                    <?php
                    if (isset($_SESSION['role'])) {
                        if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher') {
                            echo htmlspecialchars($_SESSION['user_name'] ?? 'User');
                        } elseif ($_SESSION['role'] === 'office') {
                            echo htmlspecialchars($_SESSION['office_name'] ?? 'Office');
                        }
                    } else {
                        echo 'Guest';
                    }
                    ?>
                </span>
            </li>
        
        <li class="nav-item dropdown">
            <a href="#"class="nav-link dropdown-toggle" id="navbarDropdown" role="button" 
            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-cog"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#changePasswordModal" onclick="">
                    <i class="fas fa-key mr-2"></i> Change Password
                </a>
                <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#addImageModal" onclick="">
                    <i class="fa-solid fa-image-portrait mr-2"></i> Add Image
                </a>
            </div>
        </li>
    </ul>
</nav>

<div class="modal fade" id="changePasswordModal" tabindex="-1" role="dialog" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="passwordChangeForm">
                    <div class="form-group">
                        <label for="currentPassword">Current Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="currentPassword" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#currentPassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="newPassword">New Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="newPassword" required minlength="8">
                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#newPassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="form-text text-muted">Minimum 8 characters</small>
                    </div>
                    <div class="form-group">
                        <label for="confirmPassword">Confirm Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirmPassword" required minlength="8">
                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#confirmPassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <small>Tip: Use a strong password with a mix of letters, numbers, and symbols.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="savePasswordChanges">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmChangePasswordModal" tabindex="-1" role="dialog" aria-labelledby="confirmChangePasswordLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="confirmChangePasswordLabel">Confirm Password Change</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to change your password?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="confirmPasswordChange" class="btn btn-primary">Yes, Change Password</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="addImageModal" tabindex="-1" role="dialog" aria-labelledby="addImageModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addImageModalLabel">Upload Profile Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="imageUploadForm" enctype="multipart/form-data">
                    <div class="form-group mb-3">
                        <label for="profileImage">Choose Image</label>
                        <input type="file" class="form-control-file" id="profileImage" name="profileImage" accept="image/*" required>
                        <small class="form-text text-muted">Allowed formats: JPG, PNG, GIF. Max size: 5MB</small>
                    </div>

                    <div class="form-group">
                        <?php if ($imgFound): ?>
                            <img id="imagePreview" src="<?= htmlspecialchars($imgUrl) ?>" alt="Current Profile Image" style="max-width: 100%; height: auto; border: 1px solid #ccc; padding: 5px; margin-bottom: 10px;" />
                        <?php else: ?>
                            <div style="margin-bottom: 10px; text-align: center;">
                                <i class="fa-solid fa-user" style="font-size: 3rem; color: #6c757d;"></i>
                                <p>No profile image</p>
                            </div>
                        <?php endif; ?>
                        <img id="newImagePreview" src="#" alt="New Image Preview" style="display:none; max-width: 100%; height: auto; border: 1px solid #ccc; padding: 5px;" />
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="uploadImageBtn">Upload</button>
            </div>
        </div>
    </div>
</div>

<script src="../../assets/js/pass.js"></script>