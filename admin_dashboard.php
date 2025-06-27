<?php
session_start();

// Kode Penjaga Admin yang sudah kita standarisasi
if (!isset($_SESSION['loggedin']) || ($_SESSION['userrole'] ?? 'user') !== 'admin') {
    header("Location: index.php");
    exit();
}

include 'connect.php';

$message = $_SESSION['admin_message'] ?? '';
unset($_SESSION['admin_message']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Katalog Film</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    
    <?php include 'admin_navbar.php'; // Memanggil navbar admin yang benar ?>

    <div class="container admin-container py-5">
        
        <div class="admin-header text-center">
            <h1 class="admin-title">Admin Dashboard</h1>
            <p class="admin-subtitle">Selamat datang, <?php echo htmlspecialchars($_SESSION['username']); ?>. Kelola konten aplikasi dari sini.</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="row g-4 mt-4">
            <div class="col-lg-4 col-md-6">
                <div class="dashboard-card">
                    <div class="card-icon"><i class="fas fa-film"></i></div>
                    <h4 class="card-title">Kelola Film</h4>
                    <p class="card-text">Tambah, edit, dan hapus data film di dalam katalog.</p>
                    <a href="admin_films.php" class="btn btn-primary stretched-link">Masuk</a>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="dashboard-card">
                    <div class="card-icon"><i class="fas fa-tags"></i></div>
                    <h4 class="card-title">Kelola Genre</h4>
                    <p class="card-text">Atur semua genre yang tersedia untuk film.</p>
                    <a href="admin_genres.php" class="btn btn-primary stretched-link">Masuk</a>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="dashboard-card">
                    <div class="card-icon"><i class="fas fa-users"></i></div>
                    <h4 class="card-title">Kelola Pengguna</h4>
                    <p class="card-text">Lihat daftar pengguna dan atur role mereka.</p>
                    <a href="admin_users.php" class="btn btn-primary stretched-link">Masuk</a>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>