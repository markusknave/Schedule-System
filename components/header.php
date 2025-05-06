<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
    </ul>
    <ul class="navbar-nav ml-auto">
        <li class="nav-item">
            <span class="nav-link">
                Logged in as, 
                <?php
                if (isset($_SESSION['role'])) {
                    if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher') {
                        echo $_SESSION['user_name'] ?? 'User';
                    } elseif ($_SESSION['role'] === 'office') {
                        echo $_SESSION['office_name'] ?? 'Office';
                    }
                } else {
                    echo 'Guest';
                }
                ?>
            </span>
        </li>
    </ul>
</nav>
