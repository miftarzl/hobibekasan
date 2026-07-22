<?php
session_start();

function validateAdminSession() {
    // 1. Cek apakah user sudah login
    if (!isset($_SESSION['user'])) {
        redirectToLogin();
    }
    
    // 2. Cek role admin
    if ($_SESSION['user']['role'] !== 'admin') {
        redirectToLogin();
    }
    
    // 3. Cek session timeout (30 menit)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        session_unset();
        session_destroy();
        redirectToLogin();
    }
    
    // Perbarui last activity
    $_SESSION['last_activity'] = time();
}

function redirectToLogin() {
    $_SESSION['error_message'] = "Akses ditolak. Harap login sebagai admin.";
    header("Location: ../pengguna/login.php");
    exit();
}

// Panggil fungsi validasi
validateAdminSession();
?>