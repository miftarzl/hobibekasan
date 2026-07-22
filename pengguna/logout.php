<?php
// Mulai session
session_start();

// Hapus semua data session
$_SESSION = [];
session_unset();

// Hancurkan session
session_destroy();

// Hapus cookie session (opsional tapi direkomendasikan)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Redirect ke halaman login
header("Location: login.php");
exit();
?>