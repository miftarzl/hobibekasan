<?php
session_start();
include '../config/config.php';

if (isset($_GET['id'])) {
    $product_id = $_GET['id'];

    // Ambil data produk dari database
    $result = mysqli_query($conn, "SELECT image FROM products WHERE product_id = $product_id");
    $data = mysqli_fetch_assoc($result);

    // Jika ada gambar, hapus gambar produk
    if ($data['image']) {
        $images = explode(",", $data['image']);
        foreach ($images as $image) {
            // Hapus setiap gambar
            $image_path = "../assets/img/" . $image;
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
    }

    // Hapus produk dari database
    mysqli_query($conn, "DELETE FROM products WHERE product_id = $product_id");

    $_SESSION['message'] = "Produk berhasil dihapus!";
    header("Location: produk.php");  // Mengarahkan ke halaman produk setelah penghapusan
    exit();
}
?>
