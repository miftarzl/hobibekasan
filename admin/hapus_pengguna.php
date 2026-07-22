<?php
// Mulai session
session_start();

// Include config dan validasi admin
require '../config/config.php';
require 'validate_admin.php'; // Anda bisa pindahkan fungsi validasi ke file terpisah

// Validasi parameter ID
if (!isset($_GET['id'])) {  // Perhatikan penambahan tanda kurung penutup
    $_SESSION['error_message'] = "ID pengguna tidak valid";
    header("Location: pengguna.php");
    exit;
}

// Sanitasi dan validasi ID
$user_id = intval($_GET['id']);
if ($user_id <= 0) {
    $_SESSION['error_message'] = "ID pengguna tidak valid";
    header("Location: pengguna.php");
    exit;
}

// Gunakan prepared statement untuk keamanan
$stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $_SESSION['message'] = "Pengguna berhasil dihapus";
    } else {
        $_SESSION['error_message'] = "Tidak ada pengguna yang dihapus (ID tidak ditemukan)";
    }
} else {
    $_SESSION['error_message'] = "Gagal menghapus pengguna: " . $conn->error;
}

$stmt->close();
header("Location: pengguna.php");
exit;
?>