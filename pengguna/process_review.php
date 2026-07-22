<?php

session_start(); // Mulai session

// Cek apakah user login atau belum
$isLoggedIn = isset($_SESSION['user']['user_id']);

include '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = intval($_POST['product_id']);
    $user = $_SESSION['user'];
    $user_id = $user['user_id'];    
    $rating = intval($_POST['rating']);
    $review_text = mysqli_real_escape_string($conn, $_POST['review_text']);
    $action = $_POST['action'];
    
    // Validasi rating
    if ($rating < 1 || $rating > 5) {
        $_SESSION['error'] = "Rating harus antara 1 sampai 5";
        header("Location: produk_detail.php?id=$product_id");
        exit;
    }
    
    // Proses foto ulasan jika ada
    $review_photo = '';
    $upload_success = true;
    
    if (!empty($_FILES['review_photo']['name'])) {
        $target_dir = "uploads/";
        
        // Buat direktori jika belum ada
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['review_photo']['name'], PATHINFO_EXTENSION));
        $new_filename = "review_" . $user_id . "_" . $product_id . "_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        // Cek apakah file adalah gambar
        $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
        if (!in_array($file_extension, $allowed_types)) {
            $_SESSION['error'] = "Hanya file JPG, JPEG, PNG, dan GIF yang diperbolehkan.";
            $upload_success = false;
        }
        
        // Cek ukuran file (max 2MB)
        else if ($_FILES['review_photo']['size'] > 2000000) {
            $_SESSION['error'] = "Ukuran file terlalu besar. Maksimal 2MB.";
            $upload_success = false;
        }
        
        // Upload file
        else if (move_uploaded_file($_FILES['review_photo']['tmp_name'], $target_file)) {
            $review_photo = $new_filename;
        } else {
            $_SESSION['error'] = "Gagal mengupload foto ulasan.";
            $upload_success = false;
        }
    }
    
    if ($upload_success) {
        if ($action == 'add') {
            // Tambah ulasan baru
            $query = "INSERT INTO product_reviews (product_id, user_id, rating, review) 
                      VALUES ($product_id, $user_id, $rating, '$review_text')";
                      
            if (mysqli_query($conn, $query)) {
                $_SESSION['success'] = "Ulasan berhasil ditambahkan!";
            } else {
                $_SESSION['error'] = "Gagal menambahkan ulasan: " . mysqli_error($conn);
            }
        } 
        else if ($action == 'update') {
            $review_id = intval($_POST['review_id']);
            
            // Ambil data ulasan lama
            $get_review = mysqli_query($conn, "SELECT * FROM product_reviews WHERE id = $review_id AND user_id = $user_id");
            if (mysqli_num_rows($get_review) > 0) {
                $old_review = mysqli_fetch_assoc($get_review);
                
                // Jika user ingin menghapus foto
                if (isset($_POST['delete_photo']) && $old_review['review_photo']) {
                    $old_photo_path = "uploads/" . $old_review['review_photo'];
                    if (file_exists($old_photo_path)) {
                        unlink($old_photo_path);
                    }
                    $review_photo = NULL;
                } 
                // Jika tidak upload foto baru, gunakan foto lama
                else if (empty($review_photo)) {
                    $review_photo = $old_review['review_photo'];
                } 
                // Jika upload foto baru, hapus foto lama
                else if (!empty($old_review['review_photo'])) {
                    $old_photo_path = "uploads/" . $old_review['review_photo'];
                    if (file_exists($old_photo_path)) {
                        unlink($old_photo_path);
                    }
                }
                
                // Update ulasan
                $query = "UPDATE product_reviews SET 
                          rating = $rating, 
                          review = '$review_text' 
                          WHERE id = $review_id AND user_id = $user_id";
                          
                if (mysqli_query($conn, $query)) {
                    $_SESSION['success'] = "Ulasan berhasil diperbarui!";
                } else {
                    $_SESSION['error'] = "Gagal memperbarui ulasan: " . mysqli_error($conn);
                }
            } else {
                $_SESSION['error'] = "Ulasan tidak ditemukan.";
            }
        }
    }
    
    // Redirect kembali ke halaman detail produk
    header("Location: produk_detail.php?id=$product_id");
    exit;
}
else {
    // Jika bukan POST request, redirect ke halaman utama
    header("Location: index.php");
    exit;
}
?>