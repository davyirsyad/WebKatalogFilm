<?php
session_start();

// Kode Penjaga Admin
if (!isset($_SESSION['loggedin']) || $_SESSION['userrole'] !== 'admin') {
    header("Location: index.php");
    exit();
}

include 'connect.php';

$message = $_SESSION['admin_message'] ?? '';
unset($_SESSION['admin_message']);

// --- LOGIKA PENCARIAN & PAGINASI ---
$search_term = $_GET['search'] ?? '';
$search_param = "%" . $search_term . "%";

$sql_base = " FROM film WHERE judul LIKE ?";
$sql_count = "SELECT COUNT(idfilm) AS total" . $sql_base;
$sql_data = "SELECT idfilm, judul, deskripsi, tahunproduksi, ratingimdb, posterurl" . $sql_base . " ORDER BY idfilm DESC";

$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page > 0) ? ($page - 1) * $limit : 0;

$stmt_count = $conn->prepare($sql_count);
$stmt_count->bind_param("s", $search_param);
$stmt_count->execute();
$total_films = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_films / $limit);
$stmt_count->close();

$stmt = $conn->prepare($sql_data . " LIMIT ? OFFSET ?");
$stmt->bind_param("sii", $search_param, $limit, $offset);
$stmt->execute();
$result_films = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Film - Admin Panel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">

    <style>
        /* ... Style yang sudah ada sebelumnya ... */
        .admin-table-container { background-color: var(--bg-color-dark); border: 1px solid var(--border-color); border-radius: 8px; padding: 1rem; margin-top: 1.5rem; width: 100%; }
        .admin-table-container .table { --bs-table-bg: var(--bg-color-dark); --bs-table-color: var(--text-primary); --bs-table-border-color: var(--border-color); --bs-table-hover-bg: #2a3b50; }
        .admin-table-container td, .admin-table-container th { vertical-align: middle !important; border-bottom: 1px solid var(--border-color); }
        .admin-table-container thead th { border-bottom-width: 2px; color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase; }
        .admin-table-container tbody tr:last-child td { border-bottom: none; }
        .table-poster { width: 70px; height: 105px; object-fit: cover; border-radius: 4px; }
        .table-description { font-size: 0.9em; color: var(--text-secondary); max-width: 350px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .action-buttons { display: flex; gap: 0.75rem; justify-content: center; }
        .action-buttons .btn-sm { width: 40px; height: 40px; }
        .pagination { --bs-pagination-color: var(--primary-accent); --bs-pagination-bg: var(--bg-color-darkest); --bs-pagination-border-color: var(--border-color); --bs-pagination-hover-color: #fff; --bs-pagination-hover-bg: var(--primary-accent-hover); --bs-pagination-hover-border-color: var(--primary-accent-hover); --bs-pagination-active-color: #fff; --bs-pagination-active-bg: var(--primary-accent); --bs-pagination-active-border-color: var(--primary-accent); --bs-pagination-disabled-color: var(--text-secondary); --bs-pagination-disabled-bg: #2a3b50; --bs-pagination-disabled-border-color: var(--border-color); }

        /* === CSS BARU UNTUK KONTROL ADMIN === */
        .admin-controls {
            background-color: var(--bg-color-dark);
            padding: 1.25rem;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }
        .search-wrapper {
            position: relative;
        }
        .search-wrapper .fa-search {
            position: absolute;
            top: 50%;
            left: 1rem;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }
        .search-wrapper .form-control {
            background-color: var(--bg-color-darkest);
            border-color: var(--border-color);
            color: var(--text-primary);
            padding-left: 2.5rem; /* Memberi ruang untuk ikon */
        }
        .search-wrapper .form-control:focus {
            background-color: var(--bg-color-darkest);
            border-color: var(--primary-accent);
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.2);
        }
        /* Memperbaiki warna placeholder */
        .search-wrapper .form-control::placeholder {
            color: var(--text-secondary);
            opacity: 0.7;
        }
    </style>
</head>
<body>
    <?php include 'admin_navbar.php'; ?>

    <div class="container admin-container py-4">
        <div class="text-center mb-4">
            <h1 class="admin-title-page">Kelola Film</h1>
        </div>
        
        <div class="admin-controls p-3 mb-4">
            <div class="row g-3 align-items-center">
                <div class="col-md-8">
                    <form action="admin_films.php" method="GET" class="admin-search-form">
                        <div class="search-wrapper">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" class="form-control" placeholder="Cari film berdasarkan judul..." value="<?php echo htmlspecialchars($search_term); ?>">
                        </div>
                    </form>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="admin_film_form.php" class="btn btn-primary w-100 w-md-auto"><i class="fas fa-plus me-2"></i> Tambah Film Baru</a>
                </div>
            </div>
        </div>
        <?php if (!empty($message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="admin-table-container">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th style="width: 10%;">Poster</th>
                        <th style="width: 25%;">Judul</th>
                        <th class="text-center" style="width: 35%;">Deskripsi</th>
                        <th class="text-center" style="width: 10%;">Tahun</th>
                        <th class="text-center" style="width: 10%;">Rating</th>
                        <th class="text-center" style="width: 15%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_films->num_rows > 0): ?>
                        <?php while ($row = $result_films->fetch_assoc()): ?>
                            <tr style="vertical-align: middle;">
                                <td><img src="<?php echo htmlspecialchars($row['posterurl']); ?>" alt="Poster" class="table-poster"></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($row['judul']); ?></td>
                                <td class="table-description text-center" title="<?php echo htmlspecialchars($row['deskripsi']); ?>"><?php echo htmlspecialchars($row['deskripsi']); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($row['tahunproduksi']); ?></td>
                                <td class="text-center"><span class="badge bg-warning text-dark"><i class="fas fa-star"></i> <?php echo htmlspecialchars($row['ratingimdb']); ?></span></td>
                                <td style="text-align: center;">
                                    <div style="display: inline-flex; gap: 0.75rem;">
                                        <a href="admin_film_form.php?id=<?php echo $row['idfilm']; ?>" class="btn btn-warning btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a href="admin_process_film.php?action=delete&id=<?php echo $row['idfilm']; ?>" class="btn btn-danger btn-sm" title="Hapus" onclick="return confirm('Anda yakin ingin menghapus film ini?')"><i class="fas fa-trash-alt"></i></a>
                                    </div>
                                </td>
                            </tr>
                    <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center p-4">
                            <?php if (!empty($search_term)): ?>
                                Tidak ada film yang cocok dengan pencarian "<?php echo htmlspecialchars($search_term); ?>".
                            <?php else: ?>
                                Belum ada film di database.
                            <?php endif; ?>
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
        <nav aria-label="Navigasi Halaman Film" class="d-flex justify-content-center mt-4">
            <ul class="pagination">
                <li class="page-item <?php if($page <= 1){ echo 'disabled'; } ?>">
                    <a class="page-link" href="?search=<?php echo urlencode($search_term); ?>&page=<?php echo $page - 1; ?>">Previous</a>
                </li>
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php if($page == $i) {echo 'active'; } ?>">
                    <a class="page-link" href="?search=<?php echo urlencode($search_term); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?php if($page >= $total_pages) { echo 'disabled'; } ?>">
                    <a class="page-link" href="?search=<?php echo urlencode($search_term); ?>&page=<?php echo $page + 1; ?>">Next</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>