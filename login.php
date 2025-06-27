<?php
session_start(); // Mulai session
include 'connect.php'; // Include file koneksi database

// Cek apakah ada pesan dari halaman lain (misalnya setelah registrasi)
$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']); // Hapus pesan setelah ditampilkan
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Username dan password harus diisi!";
    } else {
        $stmt = $conn->prepare("SELECT idpengguna, username, password, userrole FROM pengguna WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            // Verifikasi password yang dimasukkan dengan hash di database
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['idpengguna'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['userrole'] = $user['userrole']; // Simpan role pengguna di session
                $_SESSION['loggedin'] = TRUE;
                
                header("Location: index.php"); // Arahkan ke halaman utama setelah login
                exit();
            } else {
                $error = "Username atau password salah.";
            }
        } else {
            $error = "Username atau password salah.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Katalog Film</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-container">
        <h2>Login</h2>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger" style="background-color: #5c2c2c; border-color: #e50914; color: #fff;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success" style="background-color: #1e4d2b; border-color: #28a745; color: #fff;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Username:</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password:</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
        <p class="link-text">Belum punya akun? <a href="register.php">Daftar di sini</a></p>
    </div>
</body>
</html>