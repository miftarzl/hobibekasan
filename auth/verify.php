<?php
require '../config/config.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Periksa token di database
    $stmt = $conn->prepare("SELECT id FROM users WHERE verification_token = ? AND is_verified = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Update status menjadi terverifikasi
        $update_stmt = $conn->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE verification_token = ?");
        $update_stmt->bind_param("s", $token);
        if ($update_stmt->execute()) {
            $message = "Akun berhasil diverifikasi! Silakan <a href='../pengguna/login.php' class='btn btn-success'>Login</a>.";
            $alert_class = "alert-success";
        } else {
            $message = "Terjadi kesalahan saat memverifikasi akun.";
            $alert_class = "alert-danger";
        }
    } else {
        $message = "Token tidak valid atau akun sudah diverifikasi.";
        $alert_class = "alert-warning";
    }
} else {
    $message = "Token tidak ditemukan.";
    $alert_class = "alert-danger";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Akun</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container d-flex justify-content-center align-items-center" style="height: 100vh;">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body">
                    <div class="alert <?php echo $alert_class; ?>" role="alert">
                        <h4 class="alert-heading">Status Verifikasi</h4>
                        <p><?php echo $message; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>