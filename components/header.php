<?php
@include '/myschedule/components/links.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myschedule/constants.php';
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
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <?php if (basename($_SERVER['PHP_SELF']) == 'disp_announ_sched.php'): ?>
                <a class="nav-link" href="/myschedule/public/office/announcements.php"><i class="fas fa-arrow-left"></i></a>
            <?php else: ?>
                <a class="nav-link" href="#" data-widget="pushmenu" role="button" onclick="return false;"><i class="fas fa-bars"></i></a>
            <?php endif; ?>
        </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        <!-- User Info Display -->
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
        
        <!-- Dropdown Menu -->
        <li class="nav-item dropdown">
            <a href="#"class="nav-link dropdown-toggle" id="navbarDropdown" role="button" 
            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-cog"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                    <i class="fas fa-key mr-2"></i> Change Password
                </a>
            </div>
        </li>
    </ul>
</nav>

<!-- Change Password Modal -->
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

<!-- Confirm Change Password Modal -->
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

<script src="../../assets/js/pass.js"></script>