<?php
session_start();

// Kode Penjaga Admin
if (!isset($_SESSION['loggedin']) || $_SESSION['userrole'] !== 'admin') {
    header("Location: index.php");
    exit();
}

include 'connect.php';

// Logika untuk mode Tambah atau Edit
$film_id = $_GET['id'] ?? null;
$film_data = null;
$selected_genres = [];
$form_title = "Tambah Film Baru";
$form_action = "add_edit";

if ($film_id) {
    $form_title = "Edit Film";
    $stmt_film = $conn->prepare("SELECT * FROM film WHERE idfilm = ?");
    $stmt_film->bind_param("i", $film_id);
    $stmt_film->execute();
    $film_data = $stmt_film->get_result()->fetch_assoc();
    $stmt_film->close();

    $stmt_genres = $conn->prepare("SELECT idgenre FROM filmgenre WHERE idfilm = ?");
    $stmt_genres->bind_param("i", $film_id);
    $stmt_genres->execute();
    $result_genres = $stmt_genres->get_result();
    while ($row = $result_genres->fetch_assoc()) {
        $selected_genres[] = $row['idgenre'];
    }
    $stmt_genres->close();
}

$all_genres = $conn->query("SELECT idgenre, namagenre FROM genre ORDER BY namagenre")->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $form_title; ?> - Admin Panel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'admin_navbar.php'; ?>

    <div class="container admin-container py-4">
        <div class="admin-form-container mx-auto">
            <h1 class="admin-title-page text-center mb-4"><?php echo $form_title; ?></h1>
            <form action="admin_process_film.php" method="POST">
                <input type="hidden" name="idfilm" value="<?php echo htmlspecialchars($film_data['idfilm'] ?? ''); ?>">
                <input type="hidden" name="action" value="<?php echo $form_action; ?>">
                
                <div class="mb-3">
                    <label for="judul" class="form-label">Judul Film</label>
                    <input type="text" class="form-control" id="judul" name="judul" value="<?php echo htmlspecialchars($film_data['judul'] ?? ''); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="deskripsi" class="form-label">Deskripsi</label>
                    <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4" required><?php echo htmlspecialchars($film_data['deskripsi'] ?? ''); ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="tahunproduksi" class="form-label">Tahun</label>
                        <input type="number" class="form-control" id="tahunproduksi" name="tahunproduksi" value="<?php echo htmlspecialchars($film_data['tahunproduksi'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="ratingimdb" class="form-label">Rating</label>
                        <input type="number" step="0.1" class="form-control" id="ratingimdb" name="ratingimdb" value="<?php echo htmlspecialchars($film_data['ratingimdb'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="durasi" class="form-label">Durasi (menit)</label>
                        <input type="number" class="form-control" id="durasi" name="durasi" value="<?php echo htmlspecialchars($film_data['durasi'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="posterurl" class="form-label">URL Poster</label>
                    <input type="url" class="form-control" id="posterurl" name="posterurl" value="<?php echo htmlspecialchars($film_data['posterurl'] ?? ''); ?>" placeholder="https://..." required>
                </div>

                <div class="mb-4">
                    <label class="form-label d-block">Genre</label>
                    <div class="genre-checkbox-container">
                        <?php foreach ($all_genres as $genre): ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="genres[]" value="<?php echo $genre['idgenre']; ?>" id="genre_<?php echo $genre['idgenre']; ?>"
                                    <?php echo in_array($genre['idgenre'], $selected_genres) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="genre_<?php echo $genre['idgenre']; ?>">
                                    <?php echo htmlspecialchars($genre['namagenre']); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="d-flex justify-content-end pt-3">
                    <a href="admin_films.php" class="btn btn-secondary me-2">Batal</a>
                    <button type="submit" class="btn btn-primary px-4">Simpan Film</button>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>