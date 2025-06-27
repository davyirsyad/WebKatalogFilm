<?php
session_start();
include 'connect.php';

// Kode Penjaga Admin
if (!isset($_SESSION['loggedin']) || $_SESSION['userrole'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$action = $_POST['action'] ?? null;
$idgenre = $_POST['idgenre'] ?? null;
$namagenre = trim($_POST['namagenre'] ?? '');

if (empty($namagenre)) {
    $_SESSION['admin_message'] = "Nama genre tidak boleh kosong.";
    header("Location: admin_genres.php");
    exit();
}

try {
    if ($action === 'add') {
        // Aksi Tambah Genre
        $stmt = $conn->prepare("INSERT INTO genre (namagenre) VALUES (?)");
        $stmt->bind_param("s", $namagenre);
        $stmt->execute();
        $_SESSION['admin_message'] = "Genre baru berhasil ditambahkan.";
    } elseif ($action === 'edit' && !empty($idgenre)) {
        // Aksi Edit Genre
        $stmt = $conn->prepare("UPDATE genre SET namagenre = ? WHERE idgenre = ?");
        $stmt->bind_param("si", $namagenre, $idgenre);
        $stmt->execute();
        $_SESSION['admin_message'] = "Genre berhasil diperbarui.";
    } else {
        $_SESSION['admin_message'] = "Aksi tidak valid.";
    }
} catch (Exception $e) {
    // Menangkap error jika genre sudah ada (UNIQUE constraint)
    if ($conn->errno == 1062) { // 1062 adalah kode error untuk duplicate entry
        $_SESSION['admin_message'] = "Error: Genre dengan nama '$namagenre' sudah ada.";
    } else {
        $_SESSION['admin_message'] = "Terjadi error pada database: " . $e->getMessage();
    }
}

$stmt->close();
header("Location: admin_genres.php");
exit();