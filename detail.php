<?php
session_start();
include 'connect.php';

$film_id = $_GET['id'] ?? null;
$film = null;
$genres = []; // PERBAIKAN: Pastikan variabel $genres selalu ada sebagai array kosong
$is_loggedin = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === TRUE;
$user_id = $is_loggedin ? $_SESSION['user_id'] : null;
$user_film_status = ['favorit' => 0, 'download' => 0, 'statustontonan' => 'akanditonton'];

// Handle POST request untuk update status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $is_loggedin && isset($_POST['film_id'])) {
    $film_id_post = (int)$_POST['film_id'];
    $action = $_POST['action'] ?? '';

    // Cek apakah entri sudah ada
    $stmt_check = $conn->prepare("SELECT iddaftartontonan, favorit, download FROM daftartontonan WHERE idpengguna = ? AND idfilm = ?");
    $stmt_check->bind_param("ii", $user_id, $film_id_post);
    $stmt_check->execute();
    $existing_entry = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    if ($existing_entry) { // Jika entri sudah ada, UPDATE
        $query = "UPDATE daftartontonan SET ";
        if ($action == 'toggle_favorit') {
            $new_fav_status = $existing_entry['favorit'] ? 0 : 1;
            $query .= "favorit = $new_fav_status";
        } elseif ($action == 'toggle_download') {
            $new_dl_status = $existing_entry['download'] ? 0 : 1;
            $query .= "download = $new_dl_status";
        } elseif ($action == 'set_statustontonan') {
            $new_status = $_POST['status'];
            $query .= "statustontonan = '$new_status'";
        }
        $query .= " WHERE iddaftartontonan = " . $existing_entry['iddaftartontonan'];
        $conn->query($query);
    } else { // Jika belum ada, INSERT
        $statustontonan = 'akanditonton'; $favorit = 0; $download = 0;
        if ($action == 'toggle_favorit') $favorit = 1;
        if ($action == 'toggle_download') $download = 1;
        if ($action == 'set_statustontonan') $statustontonan = $_POST['status'];
        
        $stmt_insert = $conn->prepare("INSERT INTO daftartontonan (idpengguna, idfilm, statustontonan, favorit, download) VALUES (?, ?, ?, ?, ?)");
        $stmt_insert->bind_param("iissi", $user_id, $film_id_post, $statustontonan, $favorit, $download);
        $stmt_insert->execute();
        $stmt_insert->close();
    }
    header("Location: detail.php?id=" . $film_id_post);
    exit();
}

if ($film_id) {
    // Ambil detail film
    $stmt = $conn->prepare("SELECT * FROM film WHERE idfilm = ?");
    $stmt->bind_param("i", $film_id);
    $stmt->execute();
    $film = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($film) {
        // Ambil genre film
        $stmt_genre = $conn->prepare("SELECT g.namagenre FROM genre g JOIN filmgenre fg ON g.idgenre = fg.idgenre WHERE fg.idfilm = ?");
        $stmt_genre->bind_param("i", $film_id);
        $stmt_genre->execute();
        $result_genre = $stmt_genre->get_result();
        while ($row_genre = $result_genre->fetch_assoc()) {
            $genres[] = $row_genre['namagenre'];
        }
        $stmt_genre->close();

        // Jika pengguna login, cek status film di daftartontonan
        if ($is_loggedin) {
            $stmt_status = $conn->prepare("SELECT statustontonan, favorit, download FROM daftartontonan WHERE idpengguna = ? AND idfilm = ?");
            $stmt_status->bind_param("ii", $user_id, $film_id);
            $stmt_status->execute();
            $result_status = $stmt_status->get_result()->fetch_assoc();
            if($result_status) $user_film_status = $result_status;
            $stmt_status->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $film ? htmlspecialchars($film['judul']) : 'Film Tidak Ditemukan'; ?> - Katalog Film</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container my-5">
        <?php if ($film): ?>
            <div class="row g-5">
                <div class="col-lg-4 text-center text-lg-start">
                    <img src="<?php echo htmlspecialchars($film['posterurl']); ?>" alt="Poster <?php echo htmlspecialchars($film['judul']); ?>" class="film-detail-poster img-fluid rounded shadow-lg">
                </div>
                <div class="col-lg-8 film-detail-info">
                    <h1 class="display-4 fw-bold"><?php echo htmlspecialchars($film['judul']); ?></h1>
                    <div class="d-flex flex-wrap align-items-center film-detail-meta mb-3 gap-2">
                        <span><i class="fas fa-calendar-alt me-2 opacity-75"></i><?php echo htmlspecialchars($film['tahunproduksi']); ?></span>
                        <span class="d-none d-md-inline mx-2">·</span>
                        <span><i class="fas fa-clock me-2 opacity-75"></i><?php echo htmlspecialchars($film['durasi']); ?> menit</span>
                        <span class="d-none d-md-inline mx-2">·</span>
                        <span class="rating"><i class="fas fa-star me-1"></i><?php echo htmlspecialchars($film['ratingimdb']); ?></span>
                    </div>
                    <div class="film-genres mb-4">
                        <?php foreach ($genres as $genre): ?>
                            <span class="badge rounded-pill text-bg-primary me-1 px-3 py-2"><?php echo $genre; ?></span>
                        <?php endforeach; ?>
                    </div>
                    <p class="fs-5 lh-base film-detail-description"><?php echo nl2br(htmlspecialchars($film['deskripsi'])); ?></p>

                    <?php if ($is_loggedin): ?>
                    <div class="action-buttons-container mt-4 pt-4">
                        <h4 class="text-secondary mb-3">Aksi Saya</h4>
                        <form action="detail.php?id=<?php echo $film_id; ?>" method="POST" class="d-inline-block me-2 mb-2">
                            <input type="hidden" name="film_id" value="<?php echo $film_id; ?>">
                            <input type="hidden" name="action" value="toggle_favorit">
                            <button type="submit" class="btn btn-action <?php echo ($user_film_status['favorit'] ?? 0) ? 'favorited' : ''; ?>">
                                <i class="fa-<?php echo ($user_film_status['favorit'] ?? 0) ? 'solid' : 'regular'; ?> fa-heart me-2"></i>
                                <?php echo ($user_film_status['favorit'] ?? 0) ? 'Difavoritkan' : 'Favoritkan'; ?>
                            </button>
                        </form>
                        <form action="detail.php?id=<?php echo $film_id; ?>" method="POST" class="d-inline-block me-2 mb-2">
                            <input type="hidden" name="film_id" value="<?php echo $film_id; ?>">
                            <input type="hidden" name="action" value="toggle_download">
                            <button type="submit" class="btn btn-action <?php echo ($user_film_status['download'] ?? 0) ? 'active' : ''; ?>">
                                <i class="fas fa-download me-2"></i>
                                <?php echo ($user_film_status['download'] ?? 0) ? 'Tersimpan' : 'Simpan Offline'; ?>
                            </button>
                        </form>
                        <form action="detail.php?id=<?php echo $film_id; ?>" method="POST" class="d-inline-block mb-2">
                            <input type="hidden" name="film_id" value="<?php echo $film_id; ?>">
                            <input type="hidden" name="action" value="set_statustontonan">
                            <select name="status" class="form-select btn-action <?php echo ($user_film_status['statustontonan'] ?? '') == 'ditonton' ? 'active' : ''; ?>" onchange="this.form.submit()" style="width: auto; display: inline-block;">
                                <option value="akanditonton" <?php if (($user_film_status['statustontonan'] ?? '') == 'akanditonton') echo 'selected'; ?>>Akan Ditonton</option>
                                <option value="ditonton" <?php if (($user_film_status['statustontonan'] ?? '') == 'ditonton') echo 'selected'; ?>>Sudah Ditonton</option>
                            </select>
                        </form>
                    </div>
                    <?php else: ?>
                        <div class="alert alert-info mt-4" style="background-color: var(--bg-color-dark); border-color: var(--primary-accent);">
                            Silakan <a href="login.php">login</a> atau <a href="register.php">daftar</a> untuk menyimpan film ini.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <h1 class="display-1 text-secondary"><i class="fas fa-film fa-2x"></i></h1>
                <h2 class="display-4">404 - Film Tidak Ditemukan</h2>
                <p class="fs-4 text-secondary">Maaf, film yang Anda cari tidak ada di database kami.</p>
                <a href="index.php" class="btn btn-primary mt-3">Kembali ke Beranda</a>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>