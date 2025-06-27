<?php
session_start();
include 'connect.php';

// Kode Penjaga Admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== TRUE || ($_SESSION['userrole'] ?? 'user') !== 'admin') {
    header("Location: login.php");
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? null;

if ($action === 'add_edit') {
    // Ambil semua data dari form
    $idfilm = !empty($_POST['idfilm']) ? (int)$_POST['idfilm'] : null;
    $judul = trim($_POST['judul'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $tahunproduksi = (int)($_POST['tahunproduksi'] ?? 0);
    $ratingimdb = (float)str_replace(',', '.', ($_POST['ratingimdb'] ?? 0)); // Ganti koma jadi titik
    $durasi = (int)($_POST['durasi'] ?? 0);
    $posterurl = trim($_POST['posterurl'] ?? '');
    $genres = $_POST['genres'] ?? [];

    // Validasi sederhana
    if (empty($judul) || empty($tahunproduksi) || empty($ratingimdb) || empty($durasi) || empty($posterurl)) {
        $_SESSION['admin_message'] = "Error: Semua field wajib diisi.";
        header("Location: admin_film_form.php" . ($idfilm ? "?id=$idfilm" : ""));
        exit();
    }
    
    $conn->begin_transaction();

    try {
        if ($idfilm) {
            // Mode Edit Film
            $stmt = $conn->prepare("UPDATE film SET judul=?, deskripsi=?, tahunproduksi=?, ratingimdb=?, durasi=?, posterurl=? WHERE idfilm=?");
            // PERBAIKAN KRUSIAL: Urutan tipe data yang benar adalah s-s-i-d-i-s-i
            $stmt->bind_param("ssidisi", $judul, $deskripsi, $tahunproduksi, $ratingimdb, $durasi, $posterurl, $idfilm);
            $message_text = "Film berhasil diperbarui.";
        } else {
            // Mode Tambah Film Baru
            $stmt = $conn->prepare("INSERT INTO film (judul, deskripsi, tahunproduksi, ratingimdb, durasi, posterurl) VALUES (?, ?, ?, ?, ?, ?)");
            // PERBAIKAN KRUSIAL: Urutan tipe data yang benar adalah s-s-i-d-i-s
            $stmt->bind_param("ssidis", $judul, $deskripsi, $tahunproduksi, $ratingimdb, $durasi, $posterurl);
            $message_text = "Film baru berhasil ditambahkan.";
        }
        $stmt->execute();
        $film_id_affected = $idfilm ? $idfilm : $conn->insert_id;
        $stmt->close();

        // Hapus relasi genre lama
        $stmt_delete_genres = $conn->prepare("DELETE FROM filmgenre WHERE idfilm = ?");
        $stmt_delete_genres->bind_param("i", $film_id_affected);
        $stmt_delete_genres->execute();
        $stmt_delete_genres->close();

        // Masukkan relasi genre yang baru
        if (!empty($genres)) {
            $stmt_insert_genre = $conn->prepare("INSERT INTO filmgenre (idfilm, idgenre) VALUES (?, ?)");
            foreach ($genres as $genre_id) {
                $genre_id_int = (int)$genre_id;
                $stmt_insert_genre->bind_param("ii", $film_id_affected, $genre_id_int);
                $stmt_insert_genre->execute();
            }
            $stmt_insert_genre->close();
        }

        $conn->commit();
        $_SESSION['admin_message'] = $message_text;

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['admin_message'] = "Terjadi error pada database: " . $e->getMessage();
    }
    
    header("Location: admin_films.php");
    exit();

} elseif ($action === 'delete') {
    // Logika untuk menghapus film...
    $film_id = $_GET['id'] ?? null;
    if ($film_id) {
        // ... (kode hapus Anda sudah cukup baik)
        $conn->query("DELETE FROM filmgenre WHERE idfilm = $film_id");
        $conn->query("DELETE FROM daftartontonan WHERE idfilm = $film_id");
        $conn->query("DELETE FROM film WHERE idfilm = $film_id");
        $_SESSION['admin_message'] = "Film berhasil dihapus.";
    }
    header("Location: admin_films.php");
    exit();
}

header("Location: admin_dashboard.php");
exit();