<?php
session_start();

// Kode Penjaga Admin
if (!isset($_SESSION['loggedin']) || ($_SESSION['userrole'] ?? 'user') !== 'admin') {
    header("Location: index.php");
    exit();
}

include 'connect.php';

$message = $_SESSION['admin_message'] ?? '';
unset($_SESSION['admin_message']);

// Logika untuk mode edit
$edit_mode = false;
$genre_to_edit = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $id_to_edit = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM genre WHERE idgenre = ?");
    $stmt->bind_param("i", $id_to_edit);
    $stmt->execute();
    $genre_to_edit = $stmt->get_result()->fetch_assoc();
    if ($genre_to_edit) {
        $edit_mode = true;
    }
    $stmt->close();
}

// Ambil semua genre untuk ditampilkan di tabel
$all_genres = $conn->query("SELECT * FROM genre ORDER BY namagenre ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Genre - Admin Panel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Gaya tambahan untuk memastikan konsistensi */
        .action-buttons .btn-sm { width: 40px; height: 40px; }
    </style>
</head>
<body>
    <?php include 'admin_navbar.php'; ?>

    <div class="container admin-container py-4">
        <div class="text-center mb-4">
            <h1 class="admin-title-page">Kelola Genre</h1>
        </div>
        <div class="text-center mb-4">
            <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#genreFormCollapse" aria-expanded="<?php echo $edit_mode ? 'true' : 'false'; ?>" aria-controls="genreFormCollapse">
                <i class="fas fa-plus me-2"></i>
                <?php echo $edit_mode ? 'Edit Genre' : 'Tambah Genre Baru'; ?>
            </button>
        </div>
        <div class="collapse <?php echo $edit_mode ? 'show' : ''; ?>" id="genreFormCollapse">
            <div class="admin-form-card mb-4 mx-auto" style="max-width: 500px;">
                <h4 class="fw-bold text-center"><?php echo $edit_mode ? 'Form Edit Genre' : 'Form Tambah Genre'; ?></h4>
                <hr class="border-secondary opacity-50">
                <form action="admin_process_genre.php" method="POST">
                    <?php if ($edit_mode): ?>
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="idgenre" value="<?php echo $genre_to_edit['idgenre']; ?>">
                    <?php else: ?>
                        <input type="hidden" name="action" value="add">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="namagenre" class="form-label">Nama Genre</label>
                        <input type="text" class="form-control" id="namagenre" name="namagenre" value="<?php echo htmlspecialchars($genre_to_edit['namagenre'] ?? ''); ?>" required>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary"><?php echo $edit_mode ? 'Simpan Perubahan' : 'Tambah Genre'; ?></button>
                        <?php if ($edit_mode): ?>
                            <a href="admin_genres.php" class="btn btn-secondary ms-2">Batal</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="admin-table-container">
            <table class="table professional-table table-hover align-middle">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 15%;">ID</th>
                        <th>Nama Genre</th>
                        <th class="text-center" style="width: 5%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($all_genres->num_rows > 0): ?>
                        <?php while ($genre = $all_genres->fetch_assoc()): ?>
                            <tr>
                                <td class="text-center text-secondary">#<?php echo $genre['idgenre']; ?></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($genre['namagenre']); ?></td>
                                <td>
                                    <div class="d-flex gap-3 justify-content-end">
                                        <a href="?action=edit&id=<?php echo $genre['idgenre']; ?>" class="btn btn-warning btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a href="admin_process_genre.php?action=delete&idgenre=<?php echo $genre['idgenre']; ?>" class="btn btn-danger btn-sm" title="Hapus" onclick="return confirm('Anda yakin ingin menghapus genre \'<?php echo addslashes($genre['namagenre']); ?>\'? Film terkait akan kehilangan genre ini.')"><i class="fas fa-trash-alt"></i></a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3" class="text-center p-4">Belum ada genre.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>