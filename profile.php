<?php
session_start();
include 'connect.php';

// Jika tidak login, tendang ke halaman login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== TRUE) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$user_role = $_SESSION['userrole'] ?? 'user';

// Ambil detail pengguna
$stmt_user = $conn->prepare("SELECT email, tanggaldaftar FROM pengguna WHERE idpengguna = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_detail = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

// Ambil semua daftar tontonan pengguna
$sql_films = "SELECT f.idfilm, f.judul, f.posterurl, f.ratingimdb, f.tahunproduksi, dt.statustontonan, dt.favorit
            FROM film f JOIN daftartontonan dt ON f.idfilm = dt.idfilm
            WHERE dt.idpengguna = ? ORDER BY dt.tanggalditambahkan DESC";
$stmt_films = $conn->prepare($sql_films);
$stmt_films->bind_param("i", $user_id);
$stmt_films->execute();
$result_films = $stmt_films->get_result();

$favorite_films = [];
$watchlist_films = [];
$watched_films = [];

while ($row = $result_films->fetch_assoc()) {
    if ($row['favorit']) {
        $favorite_films[] = $row;
    }
    if ($row['statustontonan'] == 'ditonton') {
        $watched_films[] = $row;
    } else {
        $watchlist_films[] = $row;
    }
}
$stmt_films->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil <?php echo htmlspecialchars($username); ?> - Katalog Film</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container my-5">
        <div class="text-center mb-5">
            <h1 class="display-5 fw-bold">Halo, <?php echo htmlspecialchars($username); ?>!</h1>
            <p class="fs-5 text-secondary">Selamat datang di halaman profil Anda.</p>
        </div>

        <div class="profile-card">
            <div class="profile-header">
                <img src="davy.jpg" alt="Foto Profil" class="profile-img">
                <div class="profile-details">
                    <h2 class="profile-username"><?php echo htmlspecialchars($username); ?></h2>
                    <span class="role-badge"><?php echo ucfirst(htmlspecialchars($user_role)); ?></span>
                </div>
            </div>
            <div class="profile-body">
                <div class="profile-info-item">
                    <span class="label">Email</span>
                    <span class="value"><?php echo htmlspecialchars($user_detail['email']); ?></span>
                </div>
                <div class="profile-info-item">
                    <span class="label">Tanggal Daftar</span>
                    <span class="value"><?php echo date("d F Y", strtotime($user_detail['tanggaldaftar'])); ?></span>
                </div>
            </div>
            <?php if ($user_role === 'admin'): ?>
                <div class="profile-footer">
                    <a href="admin_dashboard.php" class="btn btn-primary"><i class="fas fa-user-shield me-2"></i> Akses Admin Panel</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="profile-tabs mt-5">
            <ul class="nav nav-tabs justify-content-center" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="favorites-tab" data-bs-toggle="tab" data-bs-target="#favorites-pane" type="button" role="tab">Film Favorit (<?php echo count($favorite_films); ?>)</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="watchlist-tab" data-bs-toggle="tab" data-bs-target="#watchlist-pane" type="button" role="tab">Akan Ditonton (<?php echo count($watchlist_films); ?>)</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="watched-tab" data-bs-toggle="tab" data-bs-target="#watched-pane" type="button" role="tab">Riwayat Tontonan (<?php echo count($watched_films); ?>)</button>
                </li>
            </ul>
            <div class="tab-content pt-4" id="myTabContent">
                <div class="tab-pane fade show active" id="favorites-pane" role="tabpanel">
                    <?php if (!empty($favorite_films)): ?>
                        <div class="movie-grid"><?php foreach ($favorite_films as $film) include 'movie_card_template.php'; ?></div>
                    <?php else: ?>
                        <p class="text-center text-secondary">Anda belum memiliki film favorit.</p>
                    <?php endif; ?>
                </div>
                <div class="tab-pane fade" id="watchlist-pane" role="tabpanel">
                    <?php if (!empty($watchlist_films)): ?>
                        <div class="movie-grid"><?php foreach ($watchlist_films as $film) include 'movie_card_template.php'; ?></div>
                    <?php else: ?>
                        <p class="text-center text-secondary">Daftar akan ditonton Anda kosong.</p>
                    <?php endif; ?>
                </div>
                <div class="tab-pane fade" id="watched-pane" role="tabpanel">
                    <?php if (!empty($watched_films)): ?>
                        <div class="movie-grid"><?php foreach ($watched_films as $film) include 'movie_card_template.php'; ?></div>
                    <?php else: ?>
                        <p class="text-center text-secondary">Anda belum menonton film apapun.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>