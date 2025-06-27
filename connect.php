<?php
// includes/db_connect.php
$servername = "localhost"; // Ganti jika hosting
$username = "root";       // Ganti dengan username DB Anda
$password = "";           // Ganti dengan password DB Anda
$dbname = "katalogfilm"; // Nama database Anda

// Membuat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// Mengecek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
// Tidak perlu echo "Koneksi berhasil"; di produksi
?>