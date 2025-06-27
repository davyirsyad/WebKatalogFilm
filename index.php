<?php
session_start();
include 'connect.php';

$sql_terbaru = "SELECT idfilm, judul, posterurl, ratingimdb, tahunproduksi FROM film ORDER BY idfilm DESC LIMIT 15";
$result_terbaru = $conn->query($sql_terbaru);

$sql_rekomendasi = "SELECT idfilm, judul, posterurl, ratingimdb, tahunproduksi FROM film ORDER BY ratingimdb DESC LIMIT 15";
$result_rekomendasi = $conn->query($sql_rekomendasi);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Film - Beranda</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container py-5">
        <section class="mb-5">
            <h2 class="section-title">Film Terbaru</h2>
            <div class="movie-grid">
                <?php while ($row = $result_terbaru->fetch_assoc()): ?>
                    <a href="detail.php?id=<?php echo $row['idfilm']; ?>" class="movie-card">
                        <img src="<?php echo htmlspecialchars($row['posterurl']); ?>" alt="<?php echo htmlspecialchars($row['judul']); ?>">
                        <div class="movie-card-body">
                            <div class="movie-card-title"><?php echo htmlspecialchars($row['judul']); ?></div>
                            <div class="movie-card-meta">
                                <span><?php echo htmlspecialchars($row['tahunproduksi']); ?></span> | 
                                <span class="rating">★ <?php echo htmlspecialchars($row['ratingimdb']); ?></span>
                            </div>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>
        </section>

        <section>
            <h2 class="section-title">Rekomendasi Rating Tertinggi</h2>
            <div class="movie-grid">
                <?php while ($row = $result_rekomendasi->fetch_assoc()): ?>
                    <a href="detail.php?id=<?php echo $row['idfilm']; ?>" class="movie-card">
                        <img src="<?php echo htmlspecialchars($row['posterurl']); ?>" alt="<?php echo htmlspecialchars($row['judul']); ?>">
                        <div class="movie-card-body">
                            <div class="movie-card-title"><?php echo htmlspecialchars($row['judul']); ?></div>
                            <div class="movie-card-meta">
                                <span><?php echo htmlspecialchars($row['tahunproduksi']); ?></span> | 
                                <span class="rating">★ <?php echo htmlspecialchars($row['ratingimdb']); ?></span>
                            </div>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>
        </section>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>