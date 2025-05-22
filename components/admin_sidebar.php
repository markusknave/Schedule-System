<!-- Sidebar -->
<aside class="main-sidebar sidebar-dark-primary elevation-4" style="background-color:rgb(5, 29, 160);">
    <div class="container overflow-hidden">
        <a href="/myschedule/public/admin/dashboard.php" class="brand-link">
            <img src="/myschedule/assets/img/favicon.png" width="35" height="35" alt="" class="ml-2">
            <span class="brand-text font-weight-light">LNU Teacher's Board</span>
        </a>
    </div>
    <div class="sidebar">
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                <li class="nav-item">
                    <a href="/myschedule/public/admin/dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-users"></i>
                        <p>Teachers Management</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/myschedule/public/admin/office.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'office.php' ? 'active' : '' ?>">
                    <i class="nav-icon fa-solid fa-building"></i>
                        <p>Office Management</p>
                    </a>
                </li>
                    <li class="nav-item">
                    <a href="/myschedule/public/admin/ad_archive.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'ad_archive.php' ? 'active' : '' ?>">
                    <i class="nav-icon fa fa-archive"></i>
                        <p>Archived</p>
                    </a>
                </li>
                
            </ul>
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
                </nav>
            </div>
        </aside>