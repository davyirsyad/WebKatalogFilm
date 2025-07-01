<?php
// Pastikan semua error ditampilkan untuk debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Sertakan file koneksi database Anda
include "connect.php"; // Sesuaikan dengan nama file koneksi Anda (contoh: connect.php)

// --- PENGATURAN FILTER & LAPORAN DEFAULT (HARUS SAMA DENGAN report.php) ---
$report_type = $_GET['type'] ?? 'film_per_tahun_produksi';
$tahun_produksi_filter = $_GET['tahun_produksi'] ?? '';
$genre_filter = $_GET['genre'] ?? '';
$min_users_filter = $_GET['min_users'] ?? '';

$report_data = [];
$report_title = '';

// Logika pengambilan data dari database (HARUS SESUAI IDENTIK DENGAN report.php)
switch ($report_type) {
    case 'film_per_tahun_produksi':
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
        $where_clauses = []; // Perbaikan: inisialisasi $where_clauses di sini
        if (!empty($tahun_produksi_filter)) {
            $where_clauses[] = "f.tahunproduksi = ?";
            $params[] = $tahun_produksi_filter;
            $types .= 'i';
        }
        if (!empty($where_clauses)) { // Perbaikan: tambahkan WHERE jika ada klausa
            $sql_report .= " WHERE " . implode(" AND ", $where_clauses);
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
$conn->close(); // Tutup koneksi database
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Cetak Laporan - <?php echo htmlspecialchars($report_title); ?></title>
  
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
  <link rel="stylesheet" href="style.css"> </head>
<body class="print-only"> <div id="print-area" class="report-print-content"> <h1><?php echo htmlspecialchars($report_title); ?></h1>
    <?php if ($report_type == 'film_per_tahun_produksi' && !empty($tahun_produksi_filter)): ?>
        <h2>Tahun Produksi: <?php echo htmlspecialchars($tahun_produksi_filter); ?></h2>
    <?php endif; ?>
    <p class="print-date">Data per tanggal: <?php echo date('d M Y, H:i:s'); ?></p>

    <?php if (!empty($report_data)): ?>
        <table class="table-print"> <thead>
                <tr>
                    <th>Judul Film</th>
                    <th>Tahun</th>
                    <th>Genre</th>
                    <th>Jml. Interaksi</th>
                    <th>Total Favorit</th>
                    <th>Total Download</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($report_data as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['judul']); ?></td>
                        <td><?php echo htmlspecialchars($row['tahunproduksi']); ?></td>
                        <td><?php echo htmlspecialchars($row['genres'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($row['jumlah_pengguna_interaksi']); ?></td>
                        <td><?php echo htmlspecialchars($row['total_favorit']); ?></td>
                        <td><?php echo htmlspecialchars($row['total_download']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="text-align: center; color: #555;">Tidak ada data laporan untuk kriteria yang dipilih.</p>
    <?php endif; ?>
  </div>

  <br>
  <div class="print-button-container">
    <button onclick="printPDF()" style="padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
        UNDUH LAPORAN PDF
    </button>
  </div>

<script>
  // Fungsi yang akan dipanggil saat tombol "UNDUH LAPORAN PDF" diklik
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

  // Opsi: Jika Anda ingin PDF langsung diunduh saat halaman print_report.php dimuat,
  // aktifkan baris di bawah ini dan sembunyikan tombol di HTML.
  window.onload = function() {
      printPDF();
  };
</script>

</body>
</html>