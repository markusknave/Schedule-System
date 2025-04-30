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
                    <a href="dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'office_dashboard.php' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-users"></i>
                        <p>Teachers Management</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="schedule.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'office_schedule.php' ? 'active' : '' ?>">
                        <i class="nav-icon fa fa-calendar"></i>
                        <p>Schedules</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="rooms.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'office_rooms.php' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-grip-horizontal"></i>
                        <p>Rooms</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="subjects.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'office_subjects.php' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-book"></i>
                        <p>Subjects</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="announcements.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'office_announcements.php' ? 'active' : '' ?>">
                        <i class="nav-icon fa fa-exclamation-circle"></i>
                        <p>Announcements</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="archived.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'office_archived.php' ? 'active' : '' ?>">
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
            </div>
        </aside>