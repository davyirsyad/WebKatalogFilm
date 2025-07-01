<?php
session_start();
include 'connect.php';

// Kode Penjaga Admin
if (!isset($_SESSION['loggedin']) || ($_SESSION['userrole'] ?? 'user') !== 'admin') {
    header("Location: index.php");
    exit();
}

// Ambil action dari POST atau GET
$action = $_POST['action'] ?? $_GET['action'] ?? null;

// Ambil ID genre dari POST atau GET
$idgenre = $_POST['idgenre'] ?? $_GET['idgenre'] ?? null;

// Ambil nama genre dari POST (hanya untuk add/edit)
$namagenre = trim($_POST['namagenre'] ?? ''); 

// Inisialisasi pesan
$message = "";
$msg_type = "";

// Validasi input nama genre untuk 'add' dan 'edit'
if (($action === 'add' || $action === 'edit') && empty($namagenre)) {
    $_SESSION['admin_message'] = "Nama genre tidak boleh kosong.";
    $_SESSION['msg_type'] = "danger";
    header("Location: admin_genres.php");
    exit();
}

try {
    if ($action === 'add') {
        // Aksi Tambah Genre
        $stmt = $conn->prepare("INSERT INTO genre (namagenre) VALUES (?)");
        if (!$stmt) {
            throw new Exception("Prepared statement untuk ADD genre gagal: " . $conn->error);
        }
        $stmt->bind_param("s", $namagenre);
        if ($stmt->execute()) {
            $_SESSION['admin_message'] = "Genre baru berhasil ditambahkan.";
            $_SESSION['msg_type'] = "success";
        } else {
            // Menangkap error jika genre sudah ada (UNIQUE constraint)
            if ($conn->errno == 1062) { // 1062 adalah kode error untuk duplicate entry
                throw new Exception("Error: Genre dengan nama '$namagenre' sudah ada.");
            } else {
                throw new Exception("Gagal menambahkan genre: " . $stmt->error);
            }
        }
        $stmt->close();

    } elseif ($action === 'edit' && !empty($idgenre)) {
        // Aksi Edit Genre
        $stmt = $conn->prepare("UPDATE genre SET namagenre = ? WHERE idgenre = ?");
        if (!$stmt) {
            throw new Exception("Prepared statement untuk EDIT genre gagal: " . $conn->error);
        }
        $stmt->bind_param("si", $namagenre, $idgenre);
        if ($stmt->execute()) {
            $_SESSION['admin_message'] = "Genre berhasil diperbarui.";
            $_SESSION['msg_type'] = "success";
        } else {
             // Menangkap error jika genre sudah ada (UNIQUE constraint)
            if ($conn->errno == 1062) {
                throw new Exception("Error: Genre dengan nama '$namagenre' sudah ada.");
            } else {
                throw new Exception("Gagal memperbarui genre: " . $stmt->error);
            }
        }
        $stmt->close();

    } elseif ($action === 'delete' && !empty($idgenre)) {
        // Aksi Delete Genre
        $idgenre = (int)$idgenre; // Pastikan ID adalah integer

        $conn->begin_transaction(); // Mulai transaksi

        try {
            // --- PENTING: Penanganan Foreign Key Constraint ---
            // Hapus entri terkait di tabel 'filmgenre' terlebih dahulu.
            // Ini WAJIB dilakukan jika foreign key di tabel 'filmgenre'
            // TIDAK memiliki ON DELETE CASCADE untuk 'idgenre'.
            $stmt_film_genre = $conn->prepare("DELETE FROM filmgenre WHERE idgenre = ?");
            if (!$stmt_film_genre) {
                throw new Exception("Prepared statement untuk DELETE filmgenre gagal: " . $conn->error);
            }
            $stmt_film_genre->bind_param("i", $idgenre);
            if (!$stmt_film_genre->execute()) {
                throw new Exception("Eksekusi DELETE dari filmgenre gagal: " . $stmt_film_genre->error);
            }
            $stmt_film_genre->close();

            // Kemudian baru hapus genre dari tabel 'genre'
            $stmt = $conn->prepare("DELETE FROM genre WHERE idgenre = ?");
            if (!$stmt) {
                throw new Exception("Prepared statement untuk DELETE genre gagal: " . $conn->error);
            }
            $stmt->bind_param("i", $idgenre);
            
            if ($stmt->execute()) {
                $conn->commit(); // Commit transaksi jika semua berhasil
                $_SESSION['admin_message'] = "Genre berhasil dihapus.";
                $_SESSION['msg_type'] = "success";
            } else {
                throw new Exception("Gagal menghapus genre utama: " . $stmt->error);
            }
            $stmt->close();

        } catch (Exception $e) {
            $conn->rollback(); // Batalkan transaksi jika ada error
            $_SESSION['admin_message'] = "Terjadi kesalahan saat menghapus genre: " . $e->getMessage();
            $_SESSION['msg_type'] = "danger";
        }

    } else {
        $_SESSION['admin_message'] = "Aksi tidak valid atau parameter tidak lengkap.";
        $_SESSION['msg_type'] = "warning";
    }

} catch (Exception $e) {
    // Menangkap error umum dari operasi ADD/EDIT yang mungkin belum tertangani
    // seperti masalah koneksi atau query lainnya.
    $_SESSION['admin_message'] = "Terjadi kesalahan sistem: " . $e->getMessage();
    $_SESSION['msg_type'] = "danger";
}

$conn->close(); // Tutup koneksi database
header("Location: admin_genres.php"); // Arahkan selalu kembali ke halaman daftar genre
exit();
?>