<?php
include '../config/config.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
    $stmt->bind_param("i", $id);  // Tipe data integer
    
    if ($stmt->execute()) {
        header("Location: kategori.php?success=Kategori berhasil dihapus");
    } else {
        header("Location: kategori.php?error=Gagal menghapus kategori");
    }
}
?>
