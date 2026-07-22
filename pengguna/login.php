<?php
session_start();

// Cek apakah ada pesan error dari session timeout
$error_message = null;
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']); // Hapus pesan setelah ditampilkan
}

require '../config/config.php';

// Function untuk memproses item keranjang yang tertunda
function processPendingCartItem() {
    // Implementasi proses untuk item keranjang yang tertunda
    // Contoh implementasi:
    if (isset($_SESSION['pending_cart_item'])) {
        // Proses item keranjang tertunda
        // Misalnya: tambahkan ke database keranjang
        
        // Setelah diproses, hapus dari session
        unset($_SESSION['pending_cart_item']);
    }
}

// Function untuk mendapatkan URL redirect setelah login
function getRedirectAfterLogin() {
    // Cek jika ada halaman yang diminta sebelumnya
    if (isset($_SESSION['requested_page'])) {
        $redirect = $_SESSION['requested_page'];
        unset($_SESSION['requested_page']);
        return $redirect;
    }
    
    // Default redirect ke beranda
    return "index.php";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login_type = isset($_POST['login_type']) ? $_POST['login_type'] : 'user';
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($identifier === '' || $password === '') {
        $error_message = "Mohon lengkapi data login.";
    } else {
        if ($login_type === 'admin') {
            // Admin login menggunakan username atau email
            $stmt = $conn->prepare("SELECT id, username, email, password, is_verified, role FROM users WHERE (username = ? OR email = ?) AND role = 'admin' LIMIT 1");
            $stmt->bind_param("ss", $identifier, $identifier);
        } else {
            // Pengguna login menggunakan email atau username
            $stmt = $conn->prepare("SELECT id, username, email, password, is_verified, role FROM users WHERE (email = ? OR username = ?) AND role <> 'admin' LIMIT 1");
            $stmt->bind_param("ss", $identifier, $identifier);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $is_verified = (int)$user['is_verified'];
            $role = $user['role'];

            if ($is_verified || $role === 'admin') {
                if (password_verify($password, $user['password'])) {
                    // Simpan ke dalam session
                    $_SESSION['user'] = [
                        'user_id' => $user['id'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'role' => $role
                    ];

                    // Set waktu aktivitas terakhir
                    $_SESSION['last_activity'] = time();

                    // Proses item keranjang yang tertunda (jika ada)
                    processPendingCartItem();

                    // Redirect berdasarkan role
                    if ($role === 'admin') {
                        header("Location: ../admin/admin_dashboard.php");
                    } else {
                        // Redirect ke halaman yang diminta sebelumnya atau ke beranda
                        $redirect_url = getRedirectAfterLogin();
                        header("Location: $redirect_url");
                    }
                    exit;
                } else {
                    $error_message = "Password salah.";
                }
            } else {
                $error_message = "Akun Anda belum diverifikasi. Silakan cek email.";
            }
        } else {
            $error_message = $login_type === 'admin'
                ? "Username admin tidak ditemukan."
                : "Email pengguna tidak ditemukan.";
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
    <title>Login - Hobibekasan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Gaya utama yang diperbarui */
        body {
            background: url('../assets/img/login-bg.jpg') center center no-repeat fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 51, 102, 0.7);
            z-index: -1;
        }

        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 80px 20px; /* Ditambahkan padding atas dan bawah */
            margin: 40px 0; /* Ditambahkan margin untuk jarak dari navbar dan footer */
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            padding: 40px;
            max-width: 500px;
            width: 100%;
            position: relative;
            overflow: hidden;
            transition: all 0.4s ease;
        }

        .login-container:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .login-container::before {
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

        .login-container::after {
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

        .login-logo {
            max-width: 150px;
            margin-bottom: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 5px;
            background: white;
            transition: transform 0.3s ease;
        }

        .login-logo:hover {
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

        /* Gunakan pendekatan baru untuk input group */
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

        .btn-login-masuk {
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

        .btn-login-masuk:hover {
            background: linear-gradient(to right, #ffa000, #ffcc00);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 204, 0, 0.6);
        }

        .btn-login-masuk:active {
            transform: translateY(0);
            box-shadow: 0 3px 10px rgba(255, 204, 0, 0.4);
        }

        .btn-login-masuk::after {
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

        .btn-login-masuk:hover::after {
            width: 200px;
            height: 200px;
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

        .login-links {
            margin-top: 20px;
            text-align: center;
        }

        .login-link {
            color: #003366;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 15px;
            display: inline-block;
            margin: 5px 0;
            position: relative;
        }

        .login-link:hover {
            color: #ffcc00;
            transform: translateX(5px);
        }

        .login-link::after {
            content: "";
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: #ffcc00;
            transition: width 0.3s ease;
        }

        .login-link:hover::after {
            width: 100%;
        }

        .alert {
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 25px;
            font-weight: 500;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsive styles */
        @media (max-width: 576px) {
            .main-content {
                padding: 60px 15px;
                margin: 20px 0;
            }

            .login-container {
                padding: 30px 20px;
            }

            .login-logo {
                max-width: 120px;
            }
            
            .form-title {
                font-size: 24px;
            }
        }
    </style>
</head>

<body>

    <?php include '../assets/navbar.php'; ?>

    <main class="main-content">
        <div class="container">
            <div class="login-container mx-auto">
                <img src="../assets/img/logo.jpg" alt="Logo" class="img-logo login-logo d-block mx-auto">
                <h2 class="text-center form-title">Masuk ke Akun Anda</h2>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    <div class="custom-input-group">
                        <i class="fas fa-user-shield custom-input-icon"></i>
                        <select name="login_type" id="login_type" class="custom-form-control" required>
                            <option value="user">Login Pengguna (Email)</option>
                            <option value="admin">Login Admin (Username)</option>
                        </select>
                    </div>

                    <div class="custom-input-group">
                        <i class="fas fa-envelope custom-input-icon" id="identifier_icon"></i>
                        <input type="text" name="identifier" id="identifier" class="custom-form-control" placeholder="Email pengguna" required>
                    </div>

                    <div class="custom-input-group">
                        <i class="fas fa-lock custom-input-icon"></i>
                        <input type="password" name="password" class="custom-form-control" placeholder="Password" required>
                    </div>

                    <button type="submit" class="btn btn-login-masuk w-100 mb-4">
                        <i class="fas fa-sign-in-alt me-2"></i>Masuk
                    </button>

                    <div class="divider">
                        <span class="divider-text">ATAU</span>
                    </div>

                    <div class="login-links">
                        <p>Belum punya akun? <a href="register.php" class="login-link">Daftar sekarang</a></p>
                        <p><a href="lupa_password.php" class="login-link">Lupa password?</a></p>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <?php include '../assets/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const loginTypeEl = document.getElementById('login_type');
        const identifierInputEl = document.getElementById('identifier');
        const identifierIconEl = document.getElementById('identifier_icon');

        function updateLoginModeUI() {
            if (loginTypeEl.value === 'admin') {
                identifierInputEl.placeholder = 'Username admin';
                identifierIconEl.classList.remove('fa-envelope');
                identifierIconEl.classList.add('fa-user');
            } else {
                identifierInputEl.placeholder = 'Email pengguna';
                identifierIconEl.classList.remove('fa-user');
                identifierIconEl.classList.add('fa-envelope');
            }
        }

        loginTypeEl.addEventListener('change', updateLoginModeUI);
        updateLoginModeUI();
    </script>
</body>

</html>