<?php
include('../assets/config.php');

// Ambil 10 transaksi terbaru yang statusnya masih pending
$query = "SELECT customer_name, total_price, created_at 
          FROM transactions 
          WHERE status = 'pending' 
          ORDER BY created_at DESC LIMIT 10";

$result = mysqli_query($conn, $query);

// Jika ada notifikasi
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<li>";
        echo "<strong>" . $row['customer_name'] . "</strong> melakukan pembelian sebesar Rp" . number_format($row['total_price'], 2, ',', '.') . "<br>";
        echo "<small>Pada " . date("d M Y H:i", strtotime($row['created_at'])) . "</small>";
        echo "</li>";
    }
} else {
    // Jika tidak ada notifikasi
    echo "<li class='notif-empty'>Tidak ada pemberitahuan baru.</li>";
}

?>
