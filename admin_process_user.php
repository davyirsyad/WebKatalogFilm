<?php
session_start();
include 'connect.php';

// Kode Penjaga Admin
if (!isset($_SESSION['loggedin']) || $_SESSION['userrole'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? null;
$idpengguna = $_POST['idpengguna'] ?? $_GET['id'] ?? null;

// Keamanan: Admin tidak bisa memproses dirinya sendiri
if ($idpengguna == $_SESSION['user_id'] && $action != 'change_role') {
    $_SESSION['admin_message'] = "Error: Anda tidak dapat memodifikasi akun Anda sendiri melalui form ini.";
    header("Location: admin_users.php");
    exit();
}

// Aksi untuk menambah atau mengedit pengguna dari form
if ($action === 'add_user' || $action === 'edit_user') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $userrole = $_POST['userrole'];

    if (empty($username) || empty($email) || !in_array($userrole, ['user', 'admin'])) {
        $_SESSION['admin_message'] = "Error: Username, Email, dan Role wajib diisi.";
        header("Location: admin_users.php");
        exit();
    }

    if ($action === 'add_user') {
        if (empty($password)) {
            $_SESSION['admin_message'] = "Error: Password wajib diisi untuk pengguna baru.";
            header("Location: admin_users.php");
            exit();
        }
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO pengguna (username, email, password, userrole) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $email, $hashed_password, $userrole);
        $_SESSION['admin_message'] = "Pengguna baru berhasil ditambahkan.";

    } elseif ($action === 'edit_user' && !empty($idpengguna)) {
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE pengguna SET username = ?, email = ?, password = ?, userrole = ? WHERE idpengguna = ?");
            $stmt->bind_param("ssssi", $username, $email, $hashed_password, $userrole, $idpengguna);
        } else {
            $stmt = $conn->prepare("UPDATE pengguna SET username = ?, email = ?, userrole = ? WHERE idpengguna = ?");
            $stmt->bind_param("sssi", $username, $email, $userrole, $idpengguna);
        }
        $_SESSION['admin_message'] = "Data pengguna berhasil diperbarui.";
    }

    try {
        $stmt->execute();
    } catch (Exception $e) {
        if ($conn->errno == 1062) $_SESSION['admin_message'] = "Error: Username atau Email sudah terdaftar.";
        else $_SESSION['admin_message'] = "Error Database: " . $e->getMessage();
    }
    $stmt->close();

} elseif ($action === 'delete_user' && !empty($idpengguna)) {
    $stmt = $conn->prepare("DELETE FROM pengguna WHERE idpengguna = ?");
    $stmt->bind_param("i", $idpengguna);
    $stmt->execute();
    $stmt->close();
    $_SESSION['admin_message'] = "Pengguna berhasil dihapus.";
} else {
    $_SESSION['admin_message'] = "Aksi tidak valid.";
}

header("Location: admin_users.php");
exit();