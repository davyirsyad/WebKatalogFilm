<?php
session_start();
include 'connect.php'; // Pastikan path ke file koneksi database Anda benar

// Fitur laporan ini seharusnya hanya bisa diakses oleh admin
if (!isset($_SESSION['loggedin']) || ($_SESSION['userrole'] ?? 'user') !== 'admin') {
    header("Location: index.php");
    exit();
}

// --- PENGATURAN FILTER & LAPORAN DEFAULT ---
$report_type = $_GET['type'] ?? 'film_per_tahun_produksi';
$tahun_produksi_filter = $_GET['tahun_produksi'] ?? '';
$genre_filter = $_GET['genre'] ?? '';
$min_users_filter = $_GET['min_users'] ?? '';

$report_data = [];
$report_title = '';
$genres_list = [];

// Ambil daftar genre untuk dropdown
$stmt_genres = $conn->prepare("SELECT idgenre, namagenre FROM genre ORDER BY namagenre");
$stmt_genres->execute();
$result_genres = $stmt_genres->get_result();
while ($row_genre = $result_genres->fetch_assoc()) {
    $genres_list[] = $row_genre;
}
$stmt_genres->close();

$is_searched = !empty($_GET['type']); // Laporan dianggap dicari jika 'type' diset

if ($is_searched) {
    if ($report_type == 'film_per_tahun_produksi') {
        $report_title = "Laporan Film Berdasarkan Tahun Produksi";
        if (!empty($tahun_produksi_filter)) {
            $report_title .= " " . htmlspecialchars($tahun_produksi_filter);
        }
        $sql_report = "
            SELECT f.judul, f.tahunproduksi, GROUP_CONCAT(DISTINCT g.namagenre SEPARATOR ', ') AS genres,
                COUNT(DISTINCT dt.idpengguna) AS jumlah_pengguna_interaksi,
                COUNT(CASE WHEN dt.favorit = 1 THEN 1 END) AS total_favorit,
                COUNT(CASE WHEN dt.download = 1 THEN 1 END) AS total_download
            FROM film f
            LEFT JOIN filmgenre fg ON f.idfilm = fg.idfilm
            LEFT JOIN genre g ON fg.idgenre = g.idgenre
            LEFT JOIN daftartontonan dt ON f.idfilm = dt.idfilm ";
        
        $params = [];
        $types = '';
        if (!empty($tahun_produksi_filter)) {
            $sql_report .= " WHERE f.tahunproduksi = ?";
            $params[] = $tahun_produksi_filter;
            $types .= 'i';
        }
        $sql_report .= " GROUP BY f.idfilm ORDER BY f.tahunproduksi DESC, f.judul";
        $stmt_report = $conn->prepare($sql_report);
        if(!empty($types)) $stmt_report->bind_param($types, ...$params);

    } elseif ($report_type == 'favorit_download_per_periode') {
        $report_title = "Laporan Interaksi Pengguna per Genre";
        $sql_report = "
            SELECT f.judul, f.tahunproduksi, GROUP_CONCAT(DISTINCT g.namagenre SEPARATOR ', ') AS genres,
                COUNT(DISTINCT dt.idpengguna) AS jumlah_pengguna_interaksi,
                COUNT(CASE WHEN dt.favorit = 1 THEN 1 END) AS total_favorit,
                COUNT(CASE WHEN dt.download = 1 THEN 1 END) AS total_download
            FROM film f
            LEFT JOIN daftartontonan dt ON f.idfilm = dt.idfilm
            LEFT JOIN filmgenre fg ON f.idfilm = fg.idfilm
            LEFT JOIN genre g ON fg.idgenre = g.idgenre ";
        
        $params = [];
        $types = '';
        $where_clauses = [];
        if (!empty($genre_filter)) {
            $where_clauses[] = "fg.idgenre = ?";
            $params[] = $genre_filter;
            $types .= 'i';
        }
        if (!empty($where_clauses)) {
            $sql_report .= " WHERE " . implode(' AND ', $where_clauses);
        }

        $min_users_val = !empty($min_users_filter) ? (int)$min_users_filter : 0;
        $sql_report .= " GROUP BY f.idfilm HAVING COUNT(DISTINCT dt.idpengguna) >= ?";
        $params[] = $min_users_val;
        $types .= 'i';
        $sql_report .= " ORDER BY jumlah_pengguna_interaksi DESC, total_favorit DESC";
        
        $stmt_report = $conn->prepare($sql_report);
        if(!empty($types)) $stmt_report->bind_param($types, ...$params);
    }
    
    if (isset($stmt_report)) {
        $stmt_report->execute();
        $report_data = $stmt_report->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_report->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Katalog Film</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css"> <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>
<body>
    <?php include 'admin_navbar.php'; ?>

    <div class="container admin-container py-4">
        <div class="text-center mb-5">
            <h1 class="admin-title-page">Halaman Laporan</h1>
        </div>

        <div class="report-filters">
            <form action="report.php" method="GET">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="report_type" class="form-label">Jenis Laporan:</label>
                        <select class="form-select" id="report_type" name="type" onchange="this.form.submit()">
                            <option value="film_per_tahun_produksi" <?php echo ($report_type == 'film_per_tahun_produksi') ? 'selected' : ''; ?>>Film per Tahun</option>
                            <option value="favorit_download_per_periode" <?php echo ($report_type == 'favorit_download_per_periode') ? 'selected' : ''; ?>>Interaksi per Genre</option>
                        </select>
                    </div>

                    <?php if ($report_type == 'film_per_tahun_produksi'): ?>
                        <div class="col-md-5">
                            <label for="tahun_produksi" class="form-label">Filter Tahun Produksi:</label>
                            <input type="number" class="form-control" id="tahun_produksi" name="tahun_produksi" placeholder="Masukan Tahun Produksi Film" value="<?php echo htmlspecialchars($tahun_produksi_filter); ?>">
                        </div>
                        <div class="col-md-3 d-grid">
                            <button type="submit" class="btn btn-primary">Tampilkan</button>
                        </div>
                    <?php else: // favorit_download_per_periode ?>
                        <div class="col-md-3">
                            <label for="genre" class="form-label">Filter Genre:</label>
                            <select class="form-select" id="genre" name="genre">
                                <option value="">Semua Genre</option>
                                <?php foreach ($genres_list as $genre_item): ?>
                                    <option value="<?php echo htmlspecialchars($genre_item['idgenre']); ?>" <?php echo ($genre_filter == $genre_item['idgenre']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($genre_item['namagenre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="min_users" class="form-label">Min. Pengguna:</label>
                            <input type="number" class="form-control" id="min_users" name="min_users" placeholder="0" value="<?php echo htmlspecialchars($min_users_filter); ?>">
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="submit" class="btn btn-primary">Tampilkan</button>
                        </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <?php if ($is_searched): ?>
            <div class="report-results mt-5">
                <h2 class="report-title"><?php echo htmlspecialchars($report_title); ?></h2>
                
                <div class="text-end mb-3 print-button-container">
                    <button type="button" class="btn btn-success" onclick="printPDF()">
                        <i class="fas fa-file-pdf me-2"></i> Unduh Laporan PDF
                    </button>
                </div>

                <div id="print-area" class="pdf-content">
                    <h1 style="margin-bottom: 0;"><?php echo htmlspecialchars($report_title); ?></h1>
                    <?php if ($report_type == 'film_per_tahun_produksi' && !empty($tahun_produksi_filter)): ?>
                        <h2 style="margin-top: 5px;">Tahun Produksi: <?php echo htmlspecialchars($tahun_produksi_filter); ?></h2>
                    <?php endif; ?>
                    <p>Data per tanggal: <?php echo date('d M Y, H:i:s'); ?></p>

                    <?php if (empty($report_data)): ?>
                        <p class="text-center text-muted mt-4">Tidak ada data untuk laporan ini dengan filter yang diberikan.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table professional-table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Judul Film</th>
                                        <th class="text-center">Tahun</th>
                                        <th>Genre</th>
                                        <th class="text-center">Jml. Interaksi</th>
                                        <th class="text-center">Total Favorit</th>
                                        <th class="text-center">Total Download</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($report_data as $row): ?>
                                        <tr>
                                            <td class="fw-bold"><?php echo htmlspecialchars($row['judul']); ?></td>
                                            <td class="text-center"><?php echo htmlspecialchars($row['tahunproduksi']); ?></td>
                                            <td><?php echo htmlspecialchars($row['genres'] ?? 'N/A'); ?></td>
                                            <td class="text-center"><?php echo htmlspecialchars($row['jumlah_pengguna_interaksi']); ?></td>
                                            <td class="text-center"><?php echo htmlspecialchars($row['total_favorit']); ?></td>
                                            <td class="text-center"><?php echo htmlspecialchars($row['total_download']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center mt-5 p-4" style="background-color: var(--bg-color-dark); border-radius: 8px;">
                <p class="fs-5 text-secondary">Pilih jenis laporan dan klik "Tampilkan" untuk melihat data.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function printPDF() {
            const element = document.getElementById('print-area'); // Area HTML yang akan diubah jadi PDF

            const opt = {
                margin:       [15, 15, 15, 15], // Margin: top, right, bottom, left (dalam satuan mm)
                filename:     'Laporan_Film_<?php echo date('Ymd_His'); ?>.pdf', // Nama file PDF
                image:        { type: 'jpeg', quality: 0.98 }, // Kualitas gambar
                html2canvas:  { scale: 2, dpi: 192 }, // Skala rendering HTML ke kanvas, DPI
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'landscape' } // Unit, format kertas, orientasi
            };

            // Panggil html2pdf untuk mengonversi dan menyimpan
            html2pdf().set(opt).from(element).save();
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>