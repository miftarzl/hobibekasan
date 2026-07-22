<?php

include '../config/config.php'; // Sesuaikan dengan file koneksi database Anda

if (isset($_GET['id'])) {
    $transaction_id = $_GET['id'];

    // Hapus data di tabel purchase_details terlebih dahulu
    $query_delete_details = "DELETE FROM purchase_details WHERE transaction_id = ?";
    $stmt1 = $conn->prepare($query_delete_details);
    $stmt1->bind_param("i", $transaction_id);
    $stmt1->execute();

    // Hapus data transaksi di tabel transactions
    $query_delete_transaction = "DELETE FROM transactions WHERE transaction_id = ?";
    $stmt2 = $conn->prepare($query_delete_transaction);
    $stmt2->bind_param("i", $transaction_id);
    $stmt2->execute();

    if ($stmt2->affected_rows > 0) {
        echo "<script>alert('Transaksi berhasil dihapus!'); window.location.href='pembelian.php';</script>";
    } else {
        echo "<script>alert('Gagal menghapus transaksi!'); window.history.back();</script>";
    }

    $stmt1->close();
    $stmt2->close();
    $conn->close();
} else {
    echo "<script>alert('ID transaksi tidak ditemukan!'); window.history.back();</script>";
}
?>
