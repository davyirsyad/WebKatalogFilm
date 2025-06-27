<?php
session_start();
include 'connect.php';

// Ambil semua genre untuk dropdown filter
$genres = $conn->query("SELECT idgenre, namagenre FROM genre ORDER BY namagenre ASC")->fetch_all(MYSQLI_ASSOC);

// Ambil semua parameter filter dari URL
$search_query = trim($_GET['q'] ?? '');
$genre_filter = isset($_GET['genre']) && $_GET['genre'] !== '' ? (int)$_GET['genre'] : null;
$year_from_filter = isset($_GET['year_from']) && $_GET['year_from'] !== '' ? (int)$_GET['year_from'] : null;
$year_to_filter = isset($_GET['year_to']) && $_GET['year_to'] !== '' ? (int)$_GET['year_to'] : null;
$sort_by_filter = $_GET['sort_by'] ?? 'rating_desc';

// --- LOGIKA PEMBUATAN QUERY DINAMIS ---
$sql_select = "SELECT DISTINCT f.idfilm, f.judul, f.posterurl, f.ratingimdb, f.tahunproduksi";
$sql_from = " FROM film f LEFT JOIN filmgenre fg ON f.idfilm = fg.idfilm LEFT JOIN genre g ON fg.idgenre = g.idgenre";
$sql_where = " WHERE 1=1";
$params = [];
$types = '';

if (!empty($search_query)) {
    // REGEXP '[[:<:]]...[[:>:]]' adalah cara SQL untuk mencari kata utuh (whole word)
    $sql_where .= " AND (f.judul REGEXP ? OR f.deskripsi REGEXP ?)";
    $search_regexp = '[[:<:]]' . $search_query . '[[:>:]]';
    array_push($params, $search_regexp, $search_regexp);
    $types .= 'ss';
}
if (!is_null($genre_filter) && $genre_filter > 0) {
    $sql_where .= " AND g.idgenre = ?";
    $params[] = $genre_filter;
    $types .= 'i';
}
if (!is_null($year_from_filter) && $year_from_filter > 0) {
    $sql_where .= " AND f.tahunproduksi >= ?";
    $params[] = $year_from_filter;
    $types .= 'i';
}
if (!is_null($year_to_filter) && $year_to_filter > 0) {
    $sql_where .= " AND f.tahunproduksi <= ?";
    $params[] = $year_to_filter;
    $types .= 'i';
}

$sql_order_by = " ORDER BY ";
switch ($sort_by_filter) {
    case 'year_desc': $sql_order_by .= "f.tahunproduksi DESC, f.ratingimdb DESC"; break;
    case 'title_asc': $sql_order_by .= "f.judul ASC"; break;
    default: $sql_order_by .= "f.ratingimdb DESC, f.tahunproduksi DESC"; break;
}

$final_sql = $sql_select . $sql_from . $sql_where . $sql_order_by;

$search_results = [];
$is_searched = !empty($_GET); 

if ($is_searched) {
    $stmt = $conn->prepare($final_sql);
    if (!empty($types) && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $search_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pencarian Lanjutan - Katalog Film</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">

    <style>
        .search-form-container {
            background-color: var(--bg-color-dark);
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }
        .search-form-container .input-group-text,
        .search-form-container .form-control,
        .search-form-container .form-select {
            background-color: var(--bg-color-darkest);
            border-color: var(--border-color);
            color: var(--text-primary);
        }
        
        /* === PERBAIKAN WARNA PLACEHOLDER ADA DI SINI === */
        .search-form-container .form-control::placeholder,
        .search-form-container input.form-select::placeholder {
            color: var(--text-secondary) !important;
            opacity: 0.9 !important; /* Dibuat hampir tidak transparan */
        }

        .filter-bar .form-label {
            font-size: 0.85em;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }
        
        /* Trik untuk input tahun */
        input.form-select {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: none !important;
            text-align: center;
        }
        input[type=number]::-webkit-inner-spin-button, 
        input[type=number]::-webkit-outer-spin-button { 
            -webkit-appearance: none; 
            margin: 0; 
        }
        input[type=number] {
            -moz-appearance: textfield;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4 mb-5">
        <div class="search-form-container">
            <h2 class="mb-4 text-center">Temukan Film Favorit Anda</h2>
            <form action="search.php" method="GET">
                <div class="input-group input-group-lg mb-4">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" name="q" placeholder="Cari berdasarkan judul atau deskripsi..." value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
                
                <div class="row g-3 filter-bar">
                    <div class="col-md-3">
                        <label for="genre" class="form-label">Genre</label>
                        <select name="genre" id="genre" class="form-select">
                            <option value="">Semua Genre</option>
                            <?php foreach ($genres as $genre): ?>
                                <option value="<?php echo $genre['idgenre']; ?>" <?php if($genre_filter == $genre['idgenre']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($genre['namagenre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="year_from" class="form-label">Dari Tahun</label>
                        <input type="number" name="year_from" id="year_from" class="form-select" placeholder="Contoh: 2010" value="<?php if($year_from_filter) echo $year_from_filter; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="year_to" class="form-label">Hingga Tahun</label>
                        <input type="number" name="year_to" id="year_to" class="form-select" placeholder="Contoh: 2024" value="<?php if($year_to_filter) echo $year_to_filter; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="sort_by" class="form-label">Urutkan</label>
                        <select name="sort_by" id="sort_by" class="form-select">
                            <option value="rating_desc" <?php if($sort_by_filter == 'rating_desc') echo 'selected'; ?>>Rating Tertinggi</option>
                            <option value="year_desc" <?php if($sort_by_filter == 'year_desc') echo 'selected'; ?>>Tahun Terbaru</option>
                            <option value="title_asc" <?php if($sort_by_filter == 'title_asc') echo 'selected'; ?>>Judul (A-Z)</option>
                        </select>
                    </div>
                </div>
                
                <div class="d-grid mt-4">
                    <button class="btn btn-primary btn-lg" type="submit">Terapkan Filter & Cari</button>
                </div>
            </form>
        </div>

        <?php if ($is_searched): ?>
            <h3 class="my-4">Hasil Pencarian (<?php echo count($search_results); ?> film ditemukan)</h3>
            <div class="movie-grid">
                <?php if (!empty($search_results)): ?>
                    <?php foreach ($search_results as $film): ?>
                        <a href="detail.php?id=<?php echo $film['idfilm']; ?>" class="movie-card">
                            <img src="<?php echo htmlspecialchars($film['posterurl']); ?>" alt="<?php echo htmlspecialchars($film['judul']); ?>">
                            <div class="movie-card-body">
                                <div class="movie-card-title"><?php echo htmlspecialchars($film['judul']); ?></div>
                                <div class="movie-card-meta">
                                    Tahun: <?php echo htmlspecialchars($film['tahunproduksi']); ?><br>
                                    Rating: <span class="rating">★ <?php echo htmlspecialchars($film['ratingimdb']); ?></span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <p class="fs-4 text-secondary">Tidak ada film yang cocok dengan kriteria pencarian Anda.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <p class="fs-4 text-secondary">Silakan gunakan filter di atas untuk memulai pencarian film.</p>
            </div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>