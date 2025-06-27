<?php
session_start();

// Kode Penjaga Admin
if (!isset($_SESSION['loggedin']) || ($_SESSION['userrole'] ?? 'user') !== 'admin') {
    header("Location: index.php");
    exit();
}

include 'connect.php';

$message = $_SESSION['admin_message'] ?? '';
unset($_SESSION['admin_message']);

$current_admin_id = $_SESSION['user_id'];

// Logika untuk mode edit (agar form terbuka saat mengedit)
$edit_mode = false;
$user_to_edit = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $id_to_edit = (int)$_GET['id'];
    if ($id_to_edit != $current_admin_id) {
        $stmt = $conn->prepare("SELECT idpengguna, username, email, userrole FROM pengguna WHERE idpengguna = ?");
        $stmt->bind_param("i", $id_to_edit);
        $stmt->execute();
        $user_to_edit = $stmt->get_result()->fetch_assoc();
        if ($user_to_edit) {
            $edit_mode = true;
        }
        $stmt->close();
    }
}

// Ambil semua pengguna
$result_users = $conn->query("SELECT idpengguna, username, email, userrole FROM pengguna ORDER BY idpengguna ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - Admin Panel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'admin_navbar.php'; ?>

    <div class="container admin-container py-4">
        <div class="text-center mb-4">
            <h1 class="admin-title-page">Kelola Pengguna</h1>
        </div>
        <div class="text-center mb-4">
            <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#userFormCollapse" aria-expanded="<?php echo $edit_mode ? 'true' : 'false'; ?>" aria-controls="userFormCollapse">
                <i class="fas fa-user-plus me-2"></i>
                <?php echo $edit_mode ? 'Edit Pengguna' : 'Tambah Pengguna Baru'; ?>
            </button>
        </div>

        <div class="collapse <?php echo $edit_mode ? 'show' : ''; ?>" id="userFormCollapse">
            <div class="admin-form-card mb-4 mx-auto" style="max-width: 800px;">
                <h4 class="fw-bold text-center"><?php echo $edit_mode ? 'Form Edit Pengguna' : 'Form Tambah Pengguna Baru'; ?></h4>
                <p class="text-secondary small text-center"><?php echo $edit_mode ? 'Kosongkan password jika tidak ingin mengubahnya.' : 'Role default adalah "user".'; ?></p>
                <hr class="border-secondary opacity-50">
                <form action="admin_process_user.php" method="POST">
                    <?php if ($edit_mode): ?>
                        <input type="hidden" name="action" value="edit_user">
                        <input type="hidden" name="idpengguna" value="<?php echo $user_to_edit['idpengguna']; ?>">
                    <?php else: ?>
                        <input type="hidden" name="action" value="add_user">
                    <?php endif; ?>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label for="username" class="form-label">Username</label><input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user_to_edit['username'] ?? ''); ?>" required></div>
                        <div class="col-md-6 mb-3"><label for="email" class="form-label">Email</label><input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user_to_edit['email'] ?? ''); ?>" required></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label for="password" class="form-label">Password</label><input type="password" class="form-control" id="password" name="password" placeholder="<?php echo $edit_mode ? '(Tidak diubah)' : ''; ?>" <?php echo !$edit_mode ? 'required' : ''; ?>></div>
                        <div class="col-md-6 mb-3"><label for="userrole" class="form-label">Role</label><select name="userrole" id="userrole" class="form-select"><option value="user" <?php if (($user_to_edit['userrole'] ?? 'user') == 'user') echo 'selected'; ?>>User</option><option value="admin" <?php if (($user_to_edit['userrole'] ?? 'user') == 'admin') echo 'selected'; ?>>Admin</option></select></div>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary"><?php echo $edit_mode ? 'Simpan Perubahan' : 'Tambah Pengguna'; ?></button>
                        <?php if ($edit_mode): ?><a href="admin_users.php" class="btn btn-secondary ms-2">Batal</a><?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="admin-table-container">
            <table class="table professional-table table-hover align-middle">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 5%;">ID</th>
                        <th style="width: 45%;">Username & Email</th>
                        <th class="text-center" style="width: 5%;">Role</th>
                        <th class="text-center" style="width: 5%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_users->num_rows > 0): ?>
                        <?php while ($user = $result_users->fetch_assoc()): ?>
                            <tr>
                                <td class="text-center text-secondary">#<?php echo $user['idpengguna']; ?></td>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($user['username']); ?></div>
                                    <div class="text-secondary small"><?php echo htmlspecialchars($user['email']); ?></div>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?php echo ($user['userrole'] == 'admin') ? 'text-bg-primary' : 'text-bg-secondary'; ?>">
                                        <?php echo ucfirst(htmlspecialchars($user['userrole'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-3 justify-content-center">
                                        <?php if($user['idpengguna'] != $current_admin_id): ?>
                                            <a href="?action=edit&id=<?php echo $user['idpengguna']; ?>" class="btn btn-warning btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
                                            <a href="admin_process_user.php?action=delete_user&id=<?php echo $user['idpengguna']; ?>" class="btn btn-danger btn-sm" title="Hapus Pengguna" onclick="return confirm('PERINGATAN: Menghapus pengguna ini akan menghapus semua data terkait. Anda yakin?')">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="badge text-bg-success">Ini Anda</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center p-4">Belum ada pengguna lain.</td></tr>
                    <?php endif; ?>
                </tbody>
                </table>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>