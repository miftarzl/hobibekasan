<?php
session_start();
include('../config/config.php');

// Mengecek apakah data dikirim dari form
if (isset($_POST['transaction_id']) && isset($_POST['shipping_status']) && isset($_POST['tracking_number'])) {
    $transaction_id = $_POST['transaction_id'];
    $shipping_status = $_POST['shipping_status'];
    $tracking_number = $_POST['tracking_number'];  // Ambil nomor resi dari form

    // Query untuk memperbarui status pengiriman dan nomor resi
    $query = "UPDATE transactions 
              SET status = '$shipping_status', tracking_number = '$tracking_number' 
              WHERE transaction_id = '$transaction_id'";
    
    if (mysqli_query($conn, $query)) {
        // Status dan nomor resi berhasil diupdate
        $_SESSION['message'] = "Status pengiriman dan nomor resi berhasil diperbarui!";
        header("Location: transaksi_pembayaran.php?id=$transaction_id"); // Redirect ke halaman transaksi
    } else {
        // Jika terjadi error
        $_SESSION['message'] = "Gagal memperbarui status pengiriman atau nomor resi!";
        header("Location: transaksi_pembayaran.php?id=$transaction_id"); // Redirect kembali dengan pesan error
    }
} else {
    // Jika form tidak lengkap
    $_SESSION['message'] = "Data tidak lengkap!";
    header("Location: transaksi_pembayaran.php"); // Redirect ke halaman sebelumnya
}
?>
