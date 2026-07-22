<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user']['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $conn->real_escape_string($_POST['phone_number']);
    $address = $conn->real_escape_string($_POST['address']);

    $update = $conn->query("UPDATE users SET 
                          phone_number = '$phone', 
                          address = '$address' 
                          WHERE user_id = " . $_SESSION['user_id']);

    if ($update) {
        $_SESSION['message'] = 'Profil berhasil diperbarui!';
        header('Location: ../auth/user/index.php');
        exit;
    } else {
        $error = 'Gagal memperbarui profil: ' . $conn->error;
    }
}

// Ambil data user
$userData = $conn->query("SELECT * FROM users WHERE user_id = " . $_SESSION['user_id'])->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lengkapi Profil</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>
    <div class="container mt-5">
        <div class="card mx-auto" style="max-width: 500px;">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Lengkapi Profil Anda</h4>
            </div>
            <div class="card-body">
                <?php if (isset($_GET['new_google_user'])): ?>
                    <div class="alert alert-info">
                        Silakan lengkapi data profil Anda sebelum melanjutkan
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Nomor Telepon</label>
                        <input type="tel" name="phone_number" class="form-control"
                            value="<?= htmlspecialchars($userData['phone_number'] ?? '') ?>" required
                            pattern="[0-9]{10,15}" title="10-15 digit angka">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Alamat Lengkap</label>
                        <textarea name="address" class="form-control" required
                            minlength="10" maxlength="255"><?= htmlspecialchars($userData['address'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Simpan Profil</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>