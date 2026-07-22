<?php
include '../config/config.php';

// Ambil data kategori berdasarkan id
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM categories WHERE category_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
}

// Update nama kategori
if (isset($_POST['submit'])) {
    $new_name = trim($_POST['name']);
    if (!empty($new_name)) {
        $stmt = $conn->prepare("UPDATE categories SET name = ? WHERE category_id = ?");
        $stmt->bind_param("si", $new_name, $id);  // Bind sesuai tipe data
        if ($stmt->execute()) {
            header("Location: kategori.php?success=Kategori berhasil diperbarui");
        } else {
            $error = "Gagal memperbarui kategori (mungkin nama sudah ada).";
        }
    } else {
        $error = "Nama kategori tidak boleh kosong!";
    }
}
?>
