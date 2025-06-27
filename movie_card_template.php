<a href="detail.php?id=<?php echo $film['idfilm']; ?>" class="movie-card">
    <img src="<?php echo htmlspecialchars($film['posterurl']); ?>" alt="<?php echo htmlspecialchars($film['judul']); ?>">
    <div class="movie-card-body">
        <div class="movie-card-title"><?php echo htmlspecialchars($film['judul']); ?></div>
        <div class="movie-card-meta">
            <span><?php echo htmlspecialchars($film['tahunproduksi']); ?></span> | 
            <span class="rating">★ <?php echo htmlspecialchars($film['ratingimdb']); ?></span>
        </div>
    </div>
</a>