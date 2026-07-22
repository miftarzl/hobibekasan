<?php
// Periksa session timeout sebelum memulai output apapun
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Periksa session timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    // Jika lebih dari 30 menit tidak aktif
    $_SESSION = array();
    session_unset();
    session_destroy();
    
    // Mulai session baru untuk pesan error
    session_start();
    $_SESSION['error_message'] = "Sesi Anda telah berakhir karena tidak ada aktivitas. Silakan login kembali.";
    
    // Pastikan tidak ada output sebelum ini
    if (!headers_sent()) {
        // Redirect ke login
        $redirect_url = (basename(dirname($_SERVER['PHP_SELF'])) == "admin" ? "../pengguna/login.php" : "login.php");
        header("Location: " . $redirect_url);
        exit();
    } else {
        // Fallback jika headers sudah terkirim
        echo '<script>window.location.href="' . $redirect_url . '";</script>';
        exit();
    }
}

// Perbarui waktu aktivitas terakhir
if (isset($_SESSION['user'])) {
    $_SESSION['last_activity'] = time();
    
    // Ambil data user untuk foto profil
    require_once dirname(__DIR__) . '/config/config.php';
    $conn = new mysqli($host, $db_user, $db_pass, $database);
    
    // Ambil user ID dengan pengecekan yang aman
    $user_id = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : 0;
    
    // Hanya query jika user ID valid
    if ($user_id > 0) {
        $user_query = "SELECT id, username, email, profile_photo FROM users WHERE id = ?";
        $user_stmt = $conn->prepare($user_query);
        $user_stmt->bind_param("i", $user_id);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();
        $current_user = $user_result->fetch_assoc();
        
        $_SESSION['current_user'] = $current_user;
    }
}

require_once dirname(__DIR__) . '/config/config.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navbar - itsyourthriftt.id</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        body {
            margin: 0;
            padding: 0;
        }
        
        .custom-navbar {
            background: linear-gradient(135deg, #003366 0%, #005b99 100%);
            padding: 15px 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: relative; /* Penting: Pastikan posisi relatif */
            z-index: 1000; /* Tambahkan z-index yang lebih tinggi */
        }
        
        .navbar-brand {
            font-family: 'Arial', sans-serif;
            font-weight: 800;
            letter-spacing: 0.5px;
            color: #ffffff !important;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease;
        }
        
        .navbar-brand:hover {
            transform: scale(1.05);
        }
        
        .hover-effect {
            position: relative;
            transition: all 0.3s ease;
            padding-bottom: 3px;
        }
        
        .hover-effect::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 0;
            background-color: #66b2ff;
            transition: width 0.3s ease;
        }
        
        .hover-effect:hover {
            color: #ffffff !important;
        }
        
        .hover-effect:hover::after {
            width: 100%;
        }
        
        .navbar-toggler {
            border: none !important;
            padding: 10px;
            transition: transform 0.3s ease;
        }
        
        .navbar-toggler:focus {
            box-shadow: none;
        }
        
        .navbar-toggler:hover {
            transform: rotate(90deg);
        }
        
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3E%3Cpath stroke='white' stroke-width='2' stroke-linecap='round' stroke-miterlimit='10' d='M4 7h22M4 15h22M4 23h22'/%3E%3C/svg%3E") !important;
        }
        
        /* Standardized Button Styles */
        .nav-btn {
            font-size: 16px !important;  /* Standardized font size */
            font-weight: 600 !important;
            padding: 8px 16px !important; /* Standardized padding */
            border-radius: 6px !important;
            transition: all 0.3s ease-in-out;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            min-width: 120px; /* Standardized width */
            text-align: center;
        }
        
        .nav-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;
            color: white !important;
            border: none !important;
        }
        
        .btn-register {
            background: linear-gradient(135deg, #ffcc00 0%, #ffa500 100%) !important;
            color: #333 !important;
            border: none !important;
        }
        
        .btn-profile {
            background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%) !important;
            color: white !important;
            border: none !important;
        }
        
        .btn-logout {
            background: linear-gradient(135deg, #dc3545 0%, #bd2130 100%) !important;
            color: white !important;
            border: none !important;
        }
        
        /* Update untuk tampilan mobile juga */
        @media (max-width: 991px) {
            .auth-logged-in {
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: space-between;
            }
            
            .nav-btn {
                margin: 5px 0;
                flex-grow: 1;
            }
        }
        
        /* Profile Photo Styles */
        .profile-photo-nav {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }
        
        .profile-photo-nav:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        
        .profile-photo-placeholder-nav {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #61b2ff, #1e7fd6);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }
        
        .profile-photo-placeholder-nav:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        
        .profile-photo-placeholder-nav i {
            font-size: 14px;
            color: white;
        }
        
        /* Active link styling */
        .nav-link.active {
            color: #ffcc00 !important;
            font-weight: bold;
        }

        /* Icon sizing to match text */
        .nav-link i,
        .nav-btn i {
            font-size: 0.9em;
            vertical-align: middle;
        }

        .navbar-brand i {
            font-size: 1.2em;
            vertical-align: middle;
        }
        
        /* Responsive adjustments */
        @media (max-width: 991px) {
            .custom-navbar {
                padding: 10px 0;
            }

            .navbar-brand {
                font-size: 1.2rem !important;
            }

            .navbar-collapse {
                background: rgba(0, 51, 102, 0.98);
                border-radius: 12px;
                padding: 20px;
                margin-top: 15px;
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                z-index: 1000;
            }

            .navbar-nav {
                padding: 10px 0;
                flex-direction: column;
                width: 100%;
            }

            .nav-item {
                width: 100%;
                margin-bottom: 5px;
            }

            .nav-link {
                padding: 12px 15px !important;
                border-radius: 8px;
                width: 100%;
                display: block;
            }

            .nav-link:hover {
                background-color: rgba(255, 255, 255, 0.15);
            }

            .nav-btn {
                margin: 8px 0;
                width: 100%;
                padding: 10px 16px !important;
                font-size: 14px !important;
            }

            .auth-buttons {
                display: flex;
                flex-direction: column;
                width: 100%;
                margin-top: 15px;
                padding-top: 15px;
                border-top: 1px solid rgba(255, 255, 255, 0.1);
            }

            .auth-logged-in {
                display: flex;
                flex-direction: column;
                width: 100%;
                margin-top: 15px;
                padding-top: 15px;
                border-top: 1px solid rgba(255, 255, 255, 0.1);
            }
        }

        @media (max-width: 576px) {
            .navbar-brand {
                font-size: 1rem !important;
            }

            .navbar-brand i {
                font-size: 1em !important;
            }

            .nav-link {
                font-size: 14px !important;
            }

            .nav-btn {
                font-size: 13px !important;
                padding: 8px 12px !important;
            }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg custom-navbar">
    <div class="container">
        <!-- Brand with icon -->
        <a class="navbar-brand d-flex align-items-center" href="../pengguna/index.php">
        <i class="fas fa-shoe-prints"></i>
            hobiBekasan
        </a>

        <!-- Navbar Toggle -->
        <button class="navbar-toggler" type="button" id="navbarToggle" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Menu -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto gap-3">
                <li class="nav-item">
                    <a class="nav-link fw-semibold text-white hover-effect <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="../pengguna/index.php">
                        <i class="fas fa-home me-1"></i> Beranda
                    </a>
                    <li class="nav-item">
                    <a class="nav-link fw-semibold text-white hover-effect <?php echo basename($_SERVER['PHP_SELF']) == 'tentangkita.php' ? 'active' : ''; ?>" href="../pengguna/tentangkita.php">
                        <i class="fas fa-info-circle me-1"></i> Tentang Kita
                    </a>
                </li>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold text-white hover-effect <?php echo basename($_SERVER['PHP_SELF']) == 'kategori.php' ? 'active' : ''; ?>" href="../pengguna/kategori.php">
                        <i class="fas fa-tags me-1"></i> Produk
                    </a>
                </li>
                <?php if (isset($_SESSION['user'])): ?>
                <li class="nav-item">
                    <a class="nav-link fw-semibold text-white hover-effect <?php echo basename($_SERVER['PHP_SELF']) == 'riwayat_pesanan.php' ? 'active' : ''; ?>" href="../pengguna/riwayat_pesanan.php">
                        <i class="fas fa-history me-1"></i> Riwayat Pesanan
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <?php if (isset($_SESSION['user'])): ?>
                <!-- Logged-in user controls (Updated) -->
                <div class="d-flex align-items-center gap-2 auth-logged-in">
                    <a class="btn nav-btn btn-profile" href="../pengguna/profile.php">
                        <i class="fas fa-user me-1"></i> Profil
                    </a>
                    <a class="btn nav-btn btn-logout" href="../pengguna/logout.php">
                        <i class="fas fa-sign-out-alt me-1"></i> Keluar
                    </a>
                </div>
            <?php else: ?>
                <!-- Guest user controls -->
                <div class="d-flex gap-2 auth-buttons">
                    <a class="btn nav-btn btn-login" href="../pengguna/login.php">
                        <i class="fas fa-sign-in-alt me-1"></i> Login
                    </a>
                    <a class="btn nav-btn btn-register" href="../pengguna/register.php">
                        <i class="fas fa-user-plus me-1"></i> Daftar
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Enhanced Navbar Toggle Script -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const navbarToggle = document.getElementById('navbarToggle');
        const navbarCollapse = document.getElementById('navbarNav');
        
        // Toggle navbar with animation
        navbarToggle.addEventListener('click', function() {
            const isExpanded = navbarToggle.getAttribute('aria-expanded') === 'true';
            navbarToggle.setAttribute('aria-expanded', !isExpanded);
            
            if (!isExpanded) {
                navbarCollapse.classList.add('show');
                navbarCollapse.style.maxHeight = navbarCollapse.scrollHeight + 'px';
            } else {
                navbarCollapse.classList.remove('show');
                navbarCollapse.style.maxHeight = '0';
            }
        });
        
        // Highlight active page
        const currentPage = window.location.pathname.split('/').pop();
        const navLinks = document.querySelectorAll('.nav-link');
        
        navLinks.forEach(link => {
            const href = link.getAttribute('href').split('/').pop();
            if (href === currentPage) {
                link.classList.add('active');
            }
        });
    });
</script>

</body>
</html>