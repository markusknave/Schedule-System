<!-- Sidebar -->
<aside class="main-sidebar sidebar-dark-primary elevation-4" style="background-color:rgb(5, 29, 160);">
    <div class="container overflow-hidden">
        <a href="/myschedule/public/office/dashboard.php" class="brand-link">
            <img src="/myschedule/assets/img/favicon.png" width="35" height="35" alt="" class="ml-2">
            <span class="brand-text font-weight-light">LNU Admin's Board</span>
        </a>
    </div>
    <div class="sidebar">
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                <li class="nav-item">
                    <a href="/myschedule/public/office/dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-users"></i>
                        <p>Teachers Management</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/myschedule/public/office/schedule.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'schedule.php' ? 'active' : '' ?>">
                        <i class="nav-icon fa fa-calendar"></i>
                        <p>Schedules</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/myschedule/public/office/rooms.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'rooms.php' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-grip-horizontal"></i>
                        <p>Rooms</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/myschedule/public/office/subjects.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'subjects.php' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-book"></i>
                        <p>Subjects</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/myschedule/public/office/sections.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'sections.php' ? 'active' : '' ?>">
                    <i class="nav-icon fa-solid fa-people-line"></i>
                        <p>Sections</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/myschedule/public/office/announcements.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'announcements.php' ? 'active' : '' ?>">
                        <i class="nav-icon fa fa-exclamation-circle"></i>
                        <p>Announcements</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/myschedule/public/office/archived.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'archived.php' ? 'active' : '' ?>">
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