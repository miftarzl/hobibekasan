<?php
// Include koneksi database
include('../config/config.php');

// Hitung jumlah transaksi dengan status 'pending'
$countQuery = "SELECT COUNT(*) AS total_pending FROM transactions WHERE status = 'pending'";
$countResult = mysqli_query($conn, $countQuery);
$countData = mysqli_fetch_assoc($countResult);
$totalPending = $countData['total_pending'];

// Ambil detail notifikasi (5 transaksi terbaru dengan status 'pending')
$notifQuery = "SELECT customer_name, total_price, created_at 
               FROM transactions 
               WHERE status = 'pending' 
               ORDER BY created_at DESC LIMIT 5";
$notifResult = mysqli_query($conn, $notifQuery);

// Siapkan array untuk menyimpan data notifikasi
$notifications = [];
if (mysqli_num_rows($notifResult) > 0) {
    while ($row = mysqli_fetch_assoc($notifResult)) {
        $notifications[] = $row;
    }
}
?>
