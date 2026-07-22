<?php
require '../config/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/phpmailer/src/Exception.php';
require '../vendor/phpmailer/src/PHPMailer.php';
require '../vendor/phpmailer/src/SMTP.php';

$error = $_SESSION["error"] ?? '';
$message = $_SESSION["message"] ?? '';
$google_error = $_SESSION["google_error"] ?? '';

// Bersihkan setelah ditampilkan
unset($_SESSION["error"], $_SESSION["message"], $_SESSION["google_error"]);

// Variabel untuk menampung status dan pesan
$status = '';
$response_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $phone_number = $_POST['phone_number'];
    $address = $_POST['address'];

    // Cek apakah email sudah terdaftar
    $emailCheckStmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $emailCheckStmt->bind_param("s", $email);
    $emailCheckStmt->execute();
    $emailCheckResult = $emailCheckStmt->get_result();

    if ($emailCheckResult->num_rows > 0) {
        $status = 'error';
        $response_message = 'Email sudah terdaftar. Silakan gunakan email lain.';
    } else {
        // Simpan data ke database
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, phone_number, address, verification_token, is_verified, role) VALUES (?, ?, ?, ?, ?, '', 1, 'user')");
        $stmt->bind_param("sssss", $username, $email, $password, $phone_number, $address);
        if ($stmt->execute()) {
            // Kirim email verifikasi
            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'hobibekasan@gmail.com';
                 $mail->setFrom('hobibekasan@gmail.com', 'hobibekasan');
                $mail->Password = 'axzt ogvl udmp qmne'; // App password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port = 465;

                $mail->setFrom('hobibekasan@gmail.com', 'Hobi Bekasan Official');
                $mail->addAddress($email);
                $mail->Subject = 'Verifikasi Akun Anda';

                // Link verifikasi, sesuaikan URL dengan sistemmu
                    $mail->Body = "Halo $username,

                    Akun Anda telah berhasil dibuat dan sudah terverifikasi secara otomatis ✅
                    
                    Sekarang Anda sudah bisa login ke website kami.
                    
                    Silakan login menggunakan email dan password yang telah Anda daftarkan.
                    
                    Terima kasih 🙏
                    Hobi Bekasan";
                $mail->send();

                $status = 'success';
                $response_message = 'Pendaftaran berhasil! Silakan cek email Anda.';
            } catch (Exception $e) {
                $status = 'error';
                $response_message = "Pendaftaran gagal! Error: {$mail->ErrorInfo}";
            }
        } else {
            $status = 'error';
            $response_message = "Gagal menyimpan ke database.";
        }
    }
}

session_start();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - hobibekasan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Gaya utama yang diperbarui - konsisten dengan login */
        body {
            background: linear-gradient(135deg, #b3e5fc, #64b5f6, #1e88e5);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 80px 20px; /* Ditambahkan padding atas dan bawah */
            margin: 40px 0; /* Ditambahkan margin untuk jarak dari navbar dan footer */
        }

        .register-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            padding: 40px;
            max-width: 600px;
            width: 100%;
            position: relative;
            overflow: hidden;
            transition: all 0.4s ease;
        }

        .register-container:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .register-container::before {
            content: "";
            position: absolute;
            top: -50px;
            right: -50px;
            width: 100px;
            height: 100px;
            background: rgba(255, 204, 0, 0.3);
            border-radius: 50%;
            z-index: 0;
        }

        .register-container::after {
            content: "";
            position: absolute;
            bottom: -50px;
            left: -50px;
            width: 100px;
            height: 100px;
            background: rgba(0, 51, 102, 0.2);
            border-radius: 50%;
            z-index: 0;
        }

        .register-logo {
            max-width: 150px;
            margin-bottom: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 5px;
            background: white;
            transition: transform 0.3s ease;
        }

        .register-logo:hover {
            transform: rotate(5deg);
        }

        .form-title {
            color: #003366;
            font-weight: 700;
            margin-bottom: 25px;
            position: relative;
            padding-bottom: 15px;
        }

        .form-title::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: linear-gradient(to right, #ffcc00, #003366);
            border-radius: 3px;
        }

        .form-subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 25px;
            font-size: 16px;
            line-height: 1.5;
        }

        .custom-input-group {
            position: relative;
            margin-bottom: 20px;
        }

        .custom-input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            z-index: 2;
            width: 20px;
            text-align: center;
        }

        .custom-form-control {
            display: block;
            width: 100%;
            padding: 12px 12px 12px 45px;
            font-size: 16px;
            font-weight: 400;
            line-height: 1.5;
            color: #212529;
            background-color: #f8f9fa;
            background-clip: padding-box;
            border: 2px solid #e0e0e0;
            border-radius: 30px;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .custom-form-control:focus {
            border-color: #ffcc00;
            box-shadow: 0 0 0 3px rgba(255, 204, 0, 0.25);
            background-color: #fff;
            outline: 0;
        }

        /* Spesial untuk textarea */
        textarea.custom-form-control {
            min-height: 100px;
            padding-top: 40px;
        }

        .textarea-icon {
            top: 15px;
            transform: none;
        }

        .btn-register {
            background: linear-gradient(to right, #ffcc00, #ffa000);
            color: #003366;
            font-weight: 700;
            padding: 12px 30px;
            font-size: 18px;
            border-radius: 30px;
            transition: all 0.4s ease;
            border: none;
            box-shadow: 0 5px 15px rgba(255, 204, 0, 0.4);
            position: relative;
            overflow: hidden;
        }

        .btn-register:hover {
            background: linear-gradient(to right, #ffa000, #ffcc00);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 204, 0, 0.6);
        }

        .btn-register:active {
            transform: translateY(0);
            box-shadow: 0 3px 10px rgba(255, 204, 0, 0.4);
        }

        .btn-register::after {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.5s, height 0.5s;
        }

        .btn-register:hover::after {
            width: 200px;
            height: 200px;
        }

        .btn-success-custom {
            background: linear-gradient(to right, #28a745, #20c997);
            color: white;
            font-weight: 700;
            padding: 12px 30px;
            font-size: 16px;
            border-radius: 30px;
            transition: all 0.4s ease;
            border: none;
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
            text-decoration: none;
            display: inline-block;
            margin: 10px 5px;
        }

        .btn-success-custom:hover {
            background: linear-gradient(to right, #20c997, #28a745);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(40, 167, 69, 0.6);
            color: white;
        }

        .btn-primary-custom {
            background: linear-gradient(to right, #007bff, #0056b3);
            color: white;
            font-weight: 700;
            padding: 12px 30px;
            font-size: 16px;
            border-radius: 30px;
            transition: all 0.4s ease;
            border: none;
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.4);
            text-decoration: none;
            display: inline-block;
            margin: 10px 5px;
        }

        .btn-primary-custom:hover {
            background: linear-gradient(to right, #0056b3, #007bff);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 123, 255, 0.6);
            color: white;
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 25px 0;
        }

        .divider::before,
        .divider::after {
            content: "";
            flex: 1;
            height: 2px;
            background: linear-gradient(to right, transparent, #dee2e6, transparent);
        }

        .divider-text {
            padding: 0 15px;
            color: #6c757d;
            font-weight: 600;
            font-size: 14px;
        }

        .register-links {
            margin-top: 20px;
            text-align: center;
        }

        .register-link {
            color: #003366;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 15px;
            display: inline-block;
            margin: 5px 0;
            position: relative;
        }

        .register-link:hover {
            color: #ffcc00;
            transform: translateX(5px);
        }

        .register-link::after {
            content: "";
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: #ffcc00;
            transition: width 0.3s ease;
        }

        .register-link:hover::after {
            width: 100%;
        }

        .alert {
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            font-weight: 500;
            animation: fadeIn 0.5s ease;
            border: none;
            position: relative;
            overflow: hidden;
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.2);
        }

        .alert-danger {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.2);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Element animasi */
        .user-icon {
            font-size: 3.5rem;
            color: #ffcc00;
            margin-bottom: 20px;
            display: block;
            text-align: center;
            animation: float 3s ease-in-out infinite;
            text-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .success-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 20px;
            display: block;
            text-align: center;
            animation: bounce 2s ease-in-out infinite;
            text-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
        }

        .error-icon {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 20px;
            display: block;
            text-align: center;
            animation: shake 0.5s ease-in-out;
            text-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        @keyframes shake {
            0% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            50% { transform: translateX(5px); }
            75% { transform: translateX(-5px); }
            100% { transform: translateX(0); }
        }

        .navigation-buttons {
            text-align: center;
            margin-top: 25px;
        }

        /* Responsive styles */
        @media (max-width: 576px) {
            .main-content {
                padding: 60px 15px;
                margin: 20px 0;
            }

            .register-container {
                padding: 30px 20px;
            }

            .register-logo {
                max-width: 100px;
            }
            
            .form-title {
                font-size: 20px;
            }

            .user-icon, .success-icon, .error-icon {
                font-size: 2rem;
            }

            .btn-success-custom, .btn-primary-custom {
                padding: 8px 16px;
                font-size: 12px;
                margin: 3px 1px;
            }
        }
        
        /* Social register styles */
        .social-register {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 2px solid #e9ecef;
        }
        
        .social-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .social-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .btn-google {
            background: linear-gradient(135deg, #ea4335, #4285f4);
            color: white;
            border: none;
            border-radius: 30px;
            padding: 12px 20px;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(234, 67, 53, 0.2);
        }
        
        .btn-google:hover {
            background: linear-gradient(135deg, #d33b4a, #1a73e8);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(234, 67, 53, 0.3);
        }
        
        .btn-facebook {
            background: linear-gradient(135deg, #1877f2, #0d6efd);
            color: white;
            border: none;
            border-radius: 30px;
            padding: 12px 20px;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(24, 119, 242, 0.2);
        }
        
        .btn-facebook:hover {
            background: linear-gradient(135deg, #1557c3, #084298);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(24, 119, 242, 0.3);
        }
        
        .register-links {
            margin-top: 20px;
            text-align: center;
        }
        
        .register-links p {
            margin-bottom: 10px;
        }
        
        .register-link {
            color: #61b2ff;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        
        .register-link:hover {
            color: #1e7fd6;
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <?php include '../assets/navbar.php'; ?>

    <main class="main-content">
        <div class="container">
            <div class="register-container mx-auto">
                <img src="../assets/img/logo.jpg" alt="Logo" class="img-logo register-logo d-block mx-auto">
                
                <?php if ($status == 'success'): ?>
                    <!-- Tampilan Sukses -->
                    <i class="fas fa-check-circle success-icon"></i>
                    <h2 class="text-center form-title">Pendaftaran Berhasil!</h2>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= htmlspecialchars($response_message); ?>
                    </div>
                    <p class="form-subtitle">
                        Kami telah mengirimkan email verifikasi ke alamat email yang Anda daftarkan. 
                        <strong>Silakan cek kotak masuk atau folder spam</strong> dan ikuti instruksi di email tersebut untuk mengaktifkan akun Anda.
                    </p>
                    
                    <div class="navigation-buttons">
                        <a href="login.php" class="btn-success-custom">
                            <i class="fas fa-sign-in-alt me-2"></i>Login Sekarang
                        </a>
                        <a href="register.php" class="btn-primary-custom">
                            <i class="fas fa-user-plus me-2"></i>Daftar Lagi
                        </a>
                    </div>

                <?php elseif ($status == 'error'): ?>
                    <!-- Tampilan Error -->
                    <i class="fas fa-exclamation-triangle error-icon"></i>
                    <h2 class="text-center form-title">Pendaftaran Gagal!</h2>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= htmlspecialchars($response_message); ?>
                    </div>
                    
                    <div class="navigation-buttons">
                        <a href="register.php" class="btn-primary-custom">
                            <i class="fas fa-redo me-2"></i>Coba Lagi
                        </a>
                        <a href="login.php" class="btn-success-custom">
                            <i class="fas fa-arrow-left me-2"></i>Kembali ke Login
                        </a>
                    </div>

                <?php else: ?>
                    <!-- Form Default -->
                    <i class="fas fa-user-plus user-icon"></i>
                    <h2 class="text-center form-title">Buat Akun Baru</h2>

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?= htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($message): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <?= htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($google_error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?= htmlspecialchars($google_error); ?>
                        </div>
                    <?php endif; ?>

                    <form action="register.php" method="POST">
                        <div class="custom-input-group">
                            <i class="fas fa-user custom-input-icon"></i>
                            <input type="text" name="username" class="custom-form-control" placeholder="Username" required
                                minlength="3" maxlength="50" pattern="[a-zA-Z0-9_]+" title="Hanya huruf, angka, dan underscore">
                        </div>

                        <div class="custom-input-group">
                            <i class="fas fa-envelope custom-input-icon"></i>
                            <input type="email" name="email" class="custom-form-control" placeholder="Email" required>
                        </div>

                        <div class="custom-input-group">
                            <i class="fas fa-lock custom-input-icon"></i>
                            <input type="password" name="password" class="custom-form-control" placeholder="Password" required
                                minlength="6" pattern="^(?=.*[A-Za-z])(?=.*\d).+$"
                                title="Minimal 6 karakter dengan kombinasi huruf dan angka">
                        </div>

                        <div class="custom-input-group">
                            <i class="fas fa-key custom-input-icon"></i>
                            <input type="password" name="confirm_password" class="custom-form-control" placeholder="Ulangi Password" required>
                        </div>

                        <div class="custom-input-group">
                            <i class="fas fa-phone custom-input-icon"></i>
                            <input type="tel" name="phone_number" class="custom-form-control" placeholder="Nomor Telepon"
                                pattern="[0-9]{10,15}" title="10-15 digit angka" required>
                        </div>

                        <div class="custom-input-group">
                            <i class="fas fa-home custom-input-icon textarea-icon"></i>
                            <textarea name="address" class="custom-form-control" placeholder="Alamat Lengkap" required
                                minlength="10" maxlength="255"></textarea>
                        </div>

                        <button type="submit" class="btn btn-register w-100 mb-4">
                            <i class="fas fa-user-plus me-2"></i>Daftar Sekarang
                        </button>
                        
                        <div class="divider">
                            <span class="divider-text">ATAU</span>
                        </div>
                        
                        <div class="social-register">
                            <p class="social-title">Daftar dengan:</p>
                            <div class="social-buttons">
                                <a href="https://accounts.google.com/signup" class="btn btn-google w-100 mb-3">
                                    <i class="fab fa-google me-2"></i>
                                    Daftar dengan Google
                                </a>
                                <a href="https://www.facebook.com/" class="btn btn-facebook w-100 mb-3">
                                    <i class="fab fa-facebook-f me-2"></i>
                                    Daftar dengan Facebook
                                </a>
                            </div>
                        </div>
                        
                        <div class="register-links">
                            <p>Sudah punya akun? <a href="login.php" class="register-link">Masuk disini</a></p>
                            <p>Belum punya akun? <a href="kategori.php" class="register-link">Lihat produk dulu</a></p>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include '../assets/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>