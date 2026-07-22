<?php
session_start();
require '../config/config.php';

$message = null;
$message_type = null;
$admin_reset_secret = getenv('ADMIN_RESET_SECRET') ?: 'admin_reset_default_secret';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $secret = trim($_POST['secret'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($secret === '' || $secret !== $admin_reset_secret) {
        $message = 'Secret reset admin tidak valid. Periksa kembali token reset Anda.';
        $message_type = 'danger';
    } elseif ($username === '' || $new_password === '' || $confirm_password === '') {
        $message = 'Semua field wajib diisi.';
        $message_type = 'danger';
    } elseif ($new_password !== $confirm_password) {
        $message = 'Konfirmasi password tidak cocok.';
        $message_type = 'danger';
    } elseif (strlen($new_password) < 6) {
        $message = 'Password minimal 6 karakter.';
        $message_type = 'danger';
    } else {

    if ($username === '' || $new_password === '' || $confirm_password === '') {
        $message = 'Semua field wajib diisi.';
        $message_type = 'danger';
    } elseif ($new_password !== $confirm_password) {
        $message = 'Konfirmasi password tidak cocok.';
        $message_type = 'danger';
    } elseif (strlen($new_password) < 6) {
        $message = 'Password minimal 6 karakter.';
        $message_type = 'danger';
    } else {
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND role = 'admin' LIMIT 1");
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows === 0) {
            $message = 'Username admin tidak ditemukan.';
            $message_type = 'danger';
        } else {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ? AND role = 'admin'");
            $update_stmt->bind_param("ss", $password_hash, $username);

            if ($update_stmt->execute()) {
                $message = "Password admin untuk username '{$username}' berhasil direset.";
                $message_type = 'success';
            } else {
                $message = 'Gagal mereset password admin.';
                $message_type = 'danger';
            }

            $update_stmt->close();
        }

        $check_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h4 class="mb-3">Reset Password Admin</h4>
                        <p class="text-muted small mb-4">
                            Gunakan ini hanya saat lupa password admin. Setelah selesai, sebaiknya hapus file ini.
                        </p>

                        <?php if ($message): ?>
                            <div class="alert alert-<?= htmlspecialchars($message_type); ?>">
                                <?= htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Admin Reset Secret</label>
                                <input type="password" name="secret" class="form-control" required>
                                <div class="form-text">Masukkan token reset yang ada di .env (ADMIN_RESET_SECRET).</div>
                            </div>
                        <div class="mb-3">
                                <label class="form-label">Username Admin</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Password Baru</label>
                                <input type="password" name="new_password" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Konfirmasi Password Baru</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Reset Password Admin</button>
                        </form>

                        <a href="../pengguna/login.php" class="btn btn-link w-100 mt-2">Kembali ke Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
