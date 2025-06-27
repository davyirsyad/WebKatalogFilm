<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <div class="d-flex flex-column align-items-start">
            <a class="navbar-brand" href="admin_dashboard.php">
                <i class="fas fa-user-shield me-2"></i>Admin Panel
            </a>
            <span class="navbar-greeting">Halo, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
        </div>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNavbar">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php') ? 'active' : ''; ?>" href="admin_dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_films.php' || basename($_SERVER['PHP_SELF']) == 'admin_film_form.php') ? 'active' : ''; ?>" href="admin_films.php">Film</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_genres.php') ? 'active' : ''; ?>" href="admin_genres.php">Genre</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_users.php') ? 'active' : ''; ?>" href="admin_users.php">Pengguna</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'report.php') ? 'active' : ''; ?>" href="report.php">Laporan</a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php" title="Ke Aplikasi Utama"><i class="fas fa-home"></i></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
                </li>
            </ul>
        </div>
    </div>
</nav>