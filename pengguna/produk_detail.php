<?php
session_start();
include '../config/config.php';
require_once 'content_based_filtering.php'; // Pastikan ini mengarah ke file yang berisi fungsi CBF

// Cek apakah user sudah login
if (!isset($_SESSION['user'])) {
    $loggedIn = false;
    $userId = 0;
} else {
    $loggedIn = true;
    $userId = $_SESSION['user']['user_id'];
}

$product_id = isset($_GET['id']) ? $_GET['id'] : 0;

// Set default untuk sorting
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Ambil detail produk
$sql = "SELECT p.*, c.category_name 
        FROM products p
        JOIN categories c ON p.category_id = c.category_id
        WHERE p.product_id = ?";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

// Jika produk tidak ditemukan, redirect ke halaman utama
if (!$product) {
    header('Location: index.php');
    exit;
}

// Ambil rating dan review produk dengan sorting (hanya untuk produk yang sedang dilihat)
switch ($sort) {
    case 'oldest':
        $orderBy = "pr.created_at ASC";
        break;
    case 'highest':
        $orderBy = "pr.rating DESC, pr.created_at DESC";
        break;
    case 'lowest':
        $orderBy = "pr.rating ASC, pr.created_at DESC";
        break;
    default: // newest
        $orderBy = "pr.created_at DESC";
        break;
}

// Ambil review untuk produk yang sedang dilihat
$sql = "SELECT pr.*, u.username, u.profile_photo, p.name as product_name
        FROM product_reviews pr
        JOIN users u ON pr.user_id = u.id
        JOIN products p ON pr.product_id = p.product_id
        WHERE pr.product_id = ?
        ORDER BY $orderBy";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$reviews = $result->fetch_all(MYSQLI_ASSOC);

// Hitung rating rata-rata untuk produk yang sedang dilihat
$sql = "SELECT ROUND(AVG(rating), 1) as avg_rating, COUNT(*) as review_count
        FROM product_reviews
        WHERE product_id = ?";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$rating_data = $result->fetch_assoc();
$avg_rating = $rating_data['avg_rating'] ?: 0;
$review_count = $rating_data['review_count'] ?: 0;

// Total reviews - Add this line to set the variable properly
$product_total_reviews = $review_count;
$product_avg_rating = $avg_rating; // Also add this for consistency

// Total reviews
$total_reviews = $review_count;

// Cek apakah user sudah login dan produk sudah ada di keranjang
$in_cart = false;
$cart_quantity = 0;

if ($loggedIn) {
    $sql = "SELECT quantity FROM cart 
            WHERE user_id = ? AND product_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userId, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cart_item = $result->fetch_assoc();
    
    if ($cart_item) {
        $in_cart = true;
        $cart_quantity = $cart_item['quantity'];
    }
    
    // Cek apakah user sudah memberikan review untuk produk ini
    $sql = "SELECT * FROM product_reviews
            WHERE user_id = ? AND product_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userId, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing_review = $result->fetch_assoc();
    $has_reviewed = ($existing_review) ? true : false;
} else {
    $has_reviewed = false;
    $existing_review = null;
}

// Gunakan fungsi CBF untuk mendapatkan rekomendasi produk
$recommended_products = getWeightedProductRecommendations($conn, $product_id, 8);

$pageTitle = $product['name'];

// Function untuk menampilkan rating bintang
function displayRating($rating) {
    $html = '<div class="rating">';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $html .= '<i class="fas fa-star text-warning"></i>';
        } elseif ($i - 0.5 <= $rating) {
            $html .= '<i class="fas fa-star-half-alt text-warning"></i>';
        } else {
            $html .= '<i class="far fa-star text-warning"></i>';
        }
    }
    $html .= '</div>';
    return $html;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Owl Carousel CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css">
    <!-- Custom CSS -->
    <style>
              /* Product Detail Page CSS */

/* Main color scheme */
:root {
  --primary-color: #1e7fd6;
  --secondary-color: #61b2ff;
  --accent-color: #ff6b6b;
  --light-color: #f8f9fa;
  --dark-color: #343a40;
  --text-color: #495057;
  --border-color: #dee2e6;
  --success-color: #28a745;
  --warning-color: #ffc107;
  --danger-color: #dc3545;
  --white-color: #ffffff;
  --shadow-color: rgba(0, 0, 0, 0.1);
  --gradient-primary: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%);
}

/* General Styles */
body {
  font-family: 'Poppins', sans-serif;
  color: var(--text-color);
  background-color: #f5f7fa;
}

.btn-kembali {
  background-color: #f2f4f7 !important;  /* abu-abu terang */
  border: 1px solid #d0d7e2 !important;  /* abu-abu netral */
  color: #2b3e50 !important;             /* abu gelap kebiruan */
  transition: all 0.3s ease !important;
  font-weight: 500 !important;
  text-transform: uppercase !important;
  font-size: 0.9rem !important;
}

.btn-kembali:hover {
  background-color: #e2e8f0 !important;  /* efek hover lembut */
  color: #1e7fd6 !important;             /* biru sesuai gradasi tema */
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05) !important;
}

.btn-keranjang {
  background: var(--gradient-primary);
  border: none;
  color: white !important; /* Warna teks putih */
  transition: all 0.3s ease;
  font-weight: 600; /* Lebih tebal */
  letter-spacing: 0.5px;
  text-transform: uppercase;
  font-size: 0.9rem;
}

.btn-keranjang:hover {
  box-shadow: 0 5px 15px rgba(97, 178, 255, 0.4);
  transform: translateY(-2px);
}

.btn-add-review {
  background: var(--gradient-primary);
  border: none;
  color: white !important; /* Warna teks putih */
  transition: all 0.3s ease;
  font-weight: 600; /* Lebih tebal */
  letter-spacing: 0.5px;
  text-transform: uppercase;
  font-size: 0.9rem;
  padding: 10px 25px;
  border-radius: 5px;
}

.btn-add-review:hover {
  box-shadow: 0 5px 15px rgba(97, 178, 255, 0.4);
  transform: translateY(-2px);
  color: white !important;
}

/* Product Image Styles - Pembaruan */
.product-image-container {
  position: relative;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  background-color: white;
  height: 350px; /* Ukuran lebih kecil */
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.product-image {
  max-width: 90%;
  max-height: 90%;
  object-fit: contain; /* Pertahankan rasio aspek */
  transition: transform 0.5s ease;
}

.product-image:hover {
  transform: scale(1.02);
}

/* Product Details Styles */
.product-details {
  padding: 25px;
  background-color: white;
  border-radius: 10px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.product-title {
  font-size: 1.8rem;
  font-weight: 700;
  margin-bottom: 10px;
  color: var(--dark-color);
}

.product-category {
  display: inline-block;
  padding: 5px 15px;
  background: var(--gradient-primary);
  color: white;
  border-radius: 50px;
  font-size: 0.85rem;
  font-weight: 500;
}

.product-price {
  font-size: 1.6rem;
  font-weight: 700;
  color: var(--primary-color);
  margin: 15px 0;
}

/* Perbaikan untuk tombol Lihat Detail */
.product-card .btn-outline-primary {
  font-weight: 600;  /* Menebalkan teks */
  letter-spacing: 0.5px;
  text-transform: uppercase;
  font-size: 0.9rem;
  transition: all 0.3s ease;
  border: 1px solid var(--primary-color);
}

.product-card .btn-outline-primary:hover {
  background: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%);
  border-color: transparent;
  color: white !important;
  box-shadow: 0 3px 10px rgba(97, 178, 255, 0.3);
  transform: translateY(-2px);
}

/* Deskripsi Produk dengan "Baca Selengkapnya" */
.product-description {
  font-size: 0.95rem;
  line-height: 1.7;
  color: var(--text-color);
  padding: 15px 0;
  border-top: 1px solid var(--border-color);
  border-bottom: 1px solid var(--border-color);
  margin-bottom: 20px;
  position: relative;
  max-height: 150px;
  overflow: hidden;
  transition: max-height 0.5s ease;
}

.product-description.expanded {
  max-height: 1000px;
}

.read-more-btn {
  background: linear-gradient(to bottom, rgba(255,255,255,0), rgba(255,255,255,1));
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  text-align: center;
  padding-top: 50px;
  padding-bottom: 5px;
  cursor: pointer;
  color: var(--primary-color);
  font-weight: 500;
}

.read-more-btn.hidden {
  display: none;
}

.read-less-btn {
  text-align: center;
  width: 100%;
  color: var(--primary-color);
  cursor: pointer;
  font-weight: 500;
  margin-top: 10px;
  display: none;
}

.read-less-btn.visible {
  display: block;
}

/* Rating Stars */
.rating {
  display: inline-flex;
  align-items: center;
}

.rating i {
  color: #ffc107;
  font-size: 1rem;
  margin-right: 2px;
}

/* Quantity Control */
.quantity-control {
  display: flex;
  align-items: center;
  border: 1px solid var(--border-color);
  border-radius: 5px;
  overflow: hidden;
  width: 120px;
}

.quantity-btn {
  background-color: #f8f9fa;
  border: none;
  color: var(--primary-color);
  font-size: 1.2rem;
  width: 30px;
  height: 35px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.quantity-btn:hover {
  background-color: #e9ecef;
}

.quantity-input {
  width: 60px;
  text-align: center;
  border: none;
  height: 35px;
  color: var(--dark-color);
  font-weight: 500;
}

/* Review Section */
.review-section {
  background-color: white;
  border-radius: 10px;
  padding: 25px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  margin-top: 30px;
}

.review-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 15px;
  border-bottom: 1px solid var(--border-color);
}

.review-title {
  font-size: 1.4rem;
  font-weight: 600;
  color: var(--dark-color);
  margin-bottom: 0;
}

.review-toggle {
  cursor: pointer;
  color: var(--primary-color);
  font-weight: 500;
  transition: all 0.3s ease;
}

.review-toggle:hover {
  color: var(--secondary-color);
}

.reviews-container {
  max-height: 0;
  overflow: hidden;
  transition: max-height 0.5s ease;
}

.reviews-container.show {
  max-height: 1000px;
}

.reviews-list {
  max-height: 500px;
  overflow-y: auto;
  padding-right: 5px;
}

.reviews-list::-webkit-scrollbar {
  width: 5px;
}

.reviews-list::-webkit-scrollbar-thumb {
  background: var(--gradient-primary);
  border-radius: 10px;
}

.review-card {
  border: none;
  border-radius: 10px;
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
  transition: all 0.3s ease;
}

.review-card:hover {
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.review-user {
  font-weight: 600;
  color: var(--dark-color);
}

.review-photo {
  max-width: 100px;
  max-height: 100px;
  border-radius: 5px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.review-photo:hover {
  transform: scale(1.05);
}

.review-modal-img {
  max-width: 100%;
  max-height: 80vh;
}

/* Star Rating Form */
.star-rating {
  display: flex;
  flex-direction: row-reverse;
  justify-content: flex-end;
}

.star-rating input {
  display: none;
}

.star-rating label {
  cursor: pointer;
  font-size: 1.5rem;
  color: #ccc;
  margin-right: 5px;
  transition: color 0.3s ease;
}

.star-rating label:hover,
.star-rating label:hover ~ label,
.star-rating input:checked ~ label {
  color: #ffcc00;
}

.star-rating label.checked {
  color: #ffcc00;
}

.word-counter {
  text-align: right;
  font-size: 0.8rem;
  color: #6c757d;
  margin-top: 5px;
}

.preview-image {
  max-width: 150px;
  max-height: 150px;
  border-radius: 5px;
  margin-top: 10px;
}

/* Recommendation Section */
.recommendation-section {
  margin-top: 30px;
}

/* Pembaruan untuk Bagian Rekomendasi Produk - Dihilangkan animasi garis */
.recommendation-section h3 {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--white-color);
  margin-bottom: 25px;
  text-align: center;
  padding: 12px 20px;
  display: inline-block;
  background: var(--gradient-primary);
  border-radius: 50px;
  box-shadow: 0 5px 15px rgba(30, 127, 214, 0.2);
  max-width: fit-content;
  margin-left: auto;
  margin-right: auto;
}

/* Menghilangkan animasi garis setelah judul */
.recommendation-section h3::after {
  display: none;
}

/* Menghilangkan garis sebelum dan setelah judul */
.recommendation-section h3::before,
.recommendation-section h3::after {
  display: none;
}

.recommendation-section .recommendation-description {
  text-align: center;
  font-size: 1.1rem;
  max-width: 80%;
  margin: 0 auto 30px;
  color: var(--text-color);
  line-height: 1.7;
  position: relative;
  background: linear-gradient(to right, rgba(255,255,255,0) 0%, rgba(255,255,255,0.8) 20%, rgba(255,255,255,0.8) 80%, rgba(255,255,255,0) 100%);
  padding: 15px;
  border-radius: 8px;
}

.recommendation-section .recommendation-description::before {
  content: '"';
  font-family: Georgia, serif;
  font-size: 3rem;
  color: var(--primary-color);
  opacity: 0.3;
  position: absolute;
  top: -20px;
  left: 5%;
}

.recommendation-section .recommendation-description::after {
  content: '"';
  font-family: Georgia, serif;
  font-size: 3rem;
  color: var(--primary-color);
  opacity: 0.3;
  position: absolute;
  bottom: -40px;
  right: 5%;
}

.recommendation-section .recommendation-description .product-name {
  font-weight: 700;
  color: var(--primary-color);
  display: inline-block;
  position: relative;
}

.recommendation-section .recommendation-description .product-name::after {
  content: '';
  position: absolute;
  bottom: -2px;
  left: 0;
  width: 100%;
  height: 2px;
  background: var(--gradient-primary);
  border-radius: 5px;
}

.recommendation-section .recommendation-badge {
  position: absolute;
  top: 10px;
  right: 10px;
  background: var(--gradient-primary);
  color: white;
  padding: 5px 10px;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 600;
  box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
  z-index: 10;
}

/* AI Description Section - Creative & Unique */
.ai-description-section {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 20px;
  padding: 30px;
  position: relative;
  overflow: hidden;
  box-shadow: 0 20px 40px rgba(102, 126, 234, 0.3);
  margin-top: 30px;
  border: 3px solid rgba(255, 255, 255, 0.1);
}

.ai-description-section::before {
  content: '';
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
  animation: rotate 20s linear infinite;
}

.ai-description-section::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><text y="50" font-size="100" fill="rgba(255,255,255,0.03)">hobiBekasan</text></svg>');
  background-size: 100px 100px;
  animation: float 15s ease-in-out infinite;
}

@keyframes rotate {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

@keyframes float {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-20px); }
}

.ai-description-header {
  position: relative;
  z-index: 2;
  margin-bottom: 25px;
}

.ai-description-header h5 {
  color: white;
  font-weight: 800;
  margin: 0;
  display: flex;
  align-items: center;
  font-size: 1.3rem;
  text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
  letter-spacing: 0.5px;
}

.ai-highlight {
  background: linear-gradient(135deg, #ffd89b 0%, #19547b 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  font-weight: 900;
  font-size: 1.4rem;
  position: relative;
}

.ai-highlight::after {
  content: '';
  position: absolute;
  bottom: -5px;
  left: 0;
  width: 100%;
  height: 3px;
  background: linear-gradient(90deg, transparent, #ffd89b, transparent);
  animation: shine 3s ease-in-out infinite;
}

@keyframes shine {
  0%, 100% { opacity: 0.3; }
  50% { opacity: 1; }
}

.ai-description-content {
  position: relative;
  z-index: 2;
}

.ai-description-text {
  color: white;
  font-size: 0.95rem;
  line-height: 1.8;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  padding: 25px;
  border-radius: 15px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
  font-family: 'Georgia', serif;
  position: relative;
}

.ai-description-text::before {
  content: '"';
  position: absolute;
  top: 10px;
  left: 15px;
  font-size: 4rem;
  color: rgba(255, 255, 255, 0.2);
  font-family: Georgia, serif;
}

.ai-description-text::after {
  content: '"';
  position: absolute;
  bottom: -20px;
  right: 15px;
  font-size: 4rem;
  color: rgba(255, 255, 255, 0.2);
  font-family: Georgia, serif;
}

.ai-description-text strong {
  color: #ffd89b;
  font-weight: 700;
  text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
}

.ai-description-text ul {
  margin: 15px 0;
  padding-left: 20px;
}

.ai-description-text li {
  margin: 8px 0;
  position: relative;
  list-style: none;
}

.ai-description-text li::before {
  content: 'sparkle';
  position: absolute;
  left: -20px;
  color: #ffd89b;
}

.ai-description-footer {
  text-align: center;
  font-style: italic;
  color: rgba(255, 255, 255, 0.8);
  margin-top: 20px;
  font-size: 0.9rem;
  position: relative;
  z-index: 2;
}

.ai-description-error .alert {
  border-radius: 15px;
  border-left: 4px solid #ffd89b;
  margin-bottom: 0;
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(10px);
}

/* AI Icon Animation - Enhanced */
.ai-description-header i.fa-robot {
  color: #ffd89b;
  font-size: 1.5rem;
  animation: robotDance 3s ease-in-out infinite;
  margin-right: 10px;
  text-shadow: 0 0 10px rgba(255, 216, 155, 0.8);
}

@keyframes robotDance {
  0%, 100% { transform: rotate(0deg) scale(1); }
  25% { transform: rotate(-10deg) scale(1.1); }
  50% { transform: rotate(0deg) scale(1); }
  75% { transform: rotate(10deg) scale(1.1); }
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .ai-description-section {
    padding: 20px;
    margin-top: 20px;
  }
  
  .ai-description-text {
    padding: 20px;
    font-size: 0.9rem;
  }
  
  .ai-description-header h5 {
    font-size: 1.1rem;
  }
  
  .ai-highlight {
    font-size: 1.2rem;
  }
}

/* Kontainer carousel yang diperbarui */
.recommendation-carousel-container {
  position: relative;
  padding: 15px 40px; /* Menambah padding horizontal untuk tombol navigasi */
}

.recommendation-carousel .item {
  padding: 8px;
}

/* Perbaikan tombol navigasi */
.recommendation-carousel .owl-nav button {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 35px;
  height: 35px;
  border-radius: 50% !important;
  background: var(--gradient-primary) !important;
  color: white !important;
  font-size: 1rem !important;
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  display: flex !important;
  align-items: center;
  justify-content: center;
}

.recommendation-carousel .owl-nav button:hover {
  background: linear-gradient(135deg, #1e7fd6 30%, #61b2ff 70%) !important;
  box-shadow: 0 5px 15px rgba(97, 178, 255, 0.4);
}

/* Posisi tombol navigasi ke luar */
.recommendation-carousel .owl-nav button.owl-prev {
  left: -50px;
}

.recommendation-carousel .owl-nav button.owl-next {
  right: -50px;
}

.recommendation-carousel .owl-dots {
  margin-top: 15px;
}

.recommendation-carousel .owl-dots .owl-dot span {
  width: 10px;
  height: 10px;
  margin: 5px 5px;
  background: #d6d6d6;
  transition: all 0.3s ease;
}

.recommendation-carousel .owl-dots .owl-dot.active span,
.recommendation-carousel .owl-dots .owl-dot:hover span {
  background: var(--primary-color);
  width: 20px;
  border-radius: 5px;
}

/* Product Card in Carousel */
.product-card {
  border: none;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 5px 10px rgba(0, 0, 0, 0.05);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.product-card:hover {
  transform: translateY(-7px);
  box-shadow: 0 10px 25px rgba(30, 127, 214, 0.15);
}

.product-card .card-img-top {
    height: 180px;
    object-fit: contain; /* Mengubah dari cover menjadi contain */
    padding: 10px; /* Menambahkan padding agar gambar tidak terlalu dekat dengan tepi */
    background-color: #ffffff; /* Memastikan latar belakang putih */
}

.product-card .card-body {
  padding: 15px;
}

.product-card .card-title {
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 5px;
  color: var(--dark-color);
}

.product-card .card-text {
  font-size: 1.1rem;
  margin-bottom: 10px;
}

.product-card .rating-stars {
  font-size: 0.85rem;
}

/* Responsive Adjustments */
@media (max-width: 991.98px) {
  .product-title {
    font-size: 1.5rem;
  }
  
  .product-price {
    font-size: 1.4rem;
  }
}

@media (max-width: 767.98px) {
  .product-details, .review-section {
    padding: 20px;
  }
  
  .review-header {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .review-toggle {
    margin-top: 10px;
  }

  .recommendation-section h3 {
    font-size: 1.3rem;
    padding: 10px 15px;
  }
    
  .recommendation-section .recommendation-description {
    font-size: 0.95rem;
    max-width: 95%;
  }
}

@media (max-width: 575.98px) {
  .quantity-control {
    width: 100%;
    max-width: 120px;
  }
}
    </style>
</head>
<body>
    <div class="page-container">

        <?php include '../assets/navbar.php'; ?>

        <div class="container mt-5 content">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['success']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['error']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>


            <div class="row mb-5">
                <!-- Gambar Produk -->
                <div class="col-md-6 mb-4">
                    <div class="product-image-container">
                        <img src="../assets/img/products/<?php echo $product['image']; ?>" class="img-fluid product-image" alt="<?php echo $product['name']; ?>">
                    </div>
                </div>

                <!-- Detail Produk -->
                <div class="col-md-6">
                    <div class="product-details">
                        <h2 class="product-title"><?php echo $product['name']; ?></h2>

                        <div class="d-flex align-items-center mb-2">
                            <span class="product-category"><?php echo $product['category_name']; ?></span>
                        </div>

                        <?php if ($product_total_reviews > 0): ?>
                            <div class="d-flex align-items-center mb-3">
                                <?php echo displayRating(floor($product_avg_rating)); ?>
                                <span class="ms-2"><?php echo $product_avg_rating; ?>/5 (<?php echo $product_total_reviews; ?> ulasan)</span>
                            </div>
                        <?php endif; ?>

                        <h4 class="product-price">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></h4>

                        <div class="d-flex align-items-center mb-3">
                            <span class="badge bg-<?php echo $product['stock'] > 10 ? 'success' : ($product['stock'] > 0 ? 'warning' : 'danger'); ?> p-2">
                                <?php
                                if ($product['stock'] > 10) {
                                    echo 'Stok Tersedia (' . $product['stock'] . ')';
                                } elseif ($product['stock'] > 0) {
                                    echo 'Stok Terbatas (' . $product['stock'] . ')';
                                } else {
                                    echo 'Stok Habis';
                                }
                                ?>
                            </span>
                        </div>

                        <div class="product-description">
                            <?php echo nl2br($product['description']); ?>
                        </div>
                        
                        <form action="keranjang.php" method="POST">
                            <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                            <input type="hidden" name="quantity" value="1">
                            <input type="hidden" name="add_to_cart" value="1">

                            <!-- Tombol Tambah ke Keranjang -->
                            <button type="submit" class="btn btn-keranjang w-100 d-flex align-items-center justify-content-center mb-2">
                                <i class="bi bi-cart-plus me-2"></i> Tambahkan ke Keranjang
                            </button>
                            
                            <a href="javascript:history.back()" class="btn btn-kembali w-100 d-flex align-items-center justify-content-center">
                                <i class="bi bi-arrow-left me-2"></i> Kembali
                            </a>
                        </form>
                    </div>
                </div>
            </div>
        
        <!-- Rekomendasi produk menggunakan CBF -->
        <?php if (!empty($recommended_products)): ?>
        <div class="recommendation-section mt-5">
            <div class="text-center">
                <h3 class="d-inline-block mb-3">Rekomendasi Produk Serupa</h3>
            </div>
            <p class="recommendation-description">
                Produk yang memiliki kesamaan dengan <span class="product-name"><?= htmlspecialchars($product['name']) ?></span>. 
                Kami memilihkan produk-produk ini khusus untuk Anda berdasarkan kategori, jenis kelamin, rentang harga, dan warna yang serupa.
            </p>
            
            <div class="recommendation-carousel-container">
                <div class="owl-carousel recommendation-carousel">
                    <?php foreach($recommended_products as $index => $rec_product): ?>
                    <div class="item">
                        <div class="card product-card h-100">
                            <?php if ($index < 3): ?>
                            <div class="recommendation-badge">Rekomendasi Top</div>
                            <?php endif; ?>
                            <?php if (!empty($rec_product['image'])): ?>
                                <img src="../assets/img/products/<?= htmlspecialchars($rec_product['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($rec_product['name']) ?>">
                            <?php else: ?>
                                <img src="../assets/img/product-placeholder.jpg" class="card-img-top" alt="<?= htmlspecialchars($rec_product['name']) ?>">
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title text-truncate" title="<?= htmlspecialchars($rec_product['name']) ?>"><?= htmlspecialchars($rec_product['name']) ?></h5>
                                <p class="card-text text-primary fw-bold">Rp <?= number_format($rec_product['price'], 0, ',', '.') ?></p>
                                <div class="rating-stars mb-2">
                                    <?php 
                                    $rating = $rec_product['avg_rating'] ?: 0;
                                    for ($i = 1; $i <= 5; $i++): 
                                    ?>
                                        <?php if ($i <= $rating): ?>
                                            <i class="fas fa-star text-warning"></i>
                                        <?php elseif ($i - 0.5 <= $rating): ?>
                                            <i class="fas fa-star-half-alt text-warning"></i>
                                        <?php else: ?>
                                            <i class="far fa-star text-warning"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    <small class="text-muted">(<?= $rec_product['review_count'] ?: 0 ?>)</small>
                                </div>
                                <?php if (isset($rec_product['similarity_score'])): ?>
                                <div class="similarity-score mb-2">
                                    <small class="text-success fw-bold">Kesamaan: <?= round($rec_product['similarity_score'] * 100, 1) ?>%</small>
                                </div>
                                <?php endif; ?>
                                <a href="produk_detail.php?id=<?= $rec_product['product_id'] ?>" class="btn btn-sm btn-outline-primary w-100">Lihat Detail</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Review Produk Section -->
        <div class="review-section mb-5 mt-4">
            <div class="review-header">
                <div class="d-flex align-items-center">
                    <h4 class="review-title">Ulasan Produk <?php if ($total_reviews > 0): ?>(<?php echo $total_reviews; ?>)<?php endif; ?></h4>
                    <?php if ($total_reviews > 0): ?>
                        <div class="ms-3">
                            <?php echo displayRating($avg_rating); ?>
                            <span class="ms-2 fw-bold"><?php echo $avg_rating; ?>/5</span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="review-toggle" id="toggleReviews">
                    <i class="bi bi-chevron-down"></i> Lihat Ulasan
                </div>
            </div>

            <div class="reviews-container" id="reviewsContainer">
                <?php if ($total_reviews > 0): ?>
                    <div class="filter-sort-section mb-3">
                        <select id="sortReviews" class="form-select form-select-sm">
                            <option value="newest" <?php echo ($sort == 'newest') ? 'selected' : ''; ?>>Terbaru dulu</option>
                            <option value="oldest" <?php echo ($sort == 'oldest') ? 'selected' : ''; ?>>Terlama dulu</option>
                            <option value="highest" <?php echo ($sort == 'highest') ? 'selected' : ''; ?>>Rating tertinggi</option>
                            <option value="lowest" <?php echo ($sort == 'lowest') ? 'selected' : ''; ?>>Rating terendah</option>
                        </select>
                    </div>
                    
                    <div class="reviews-list">
                        <?php foreach ($reviews as $rev): ?>
                          <div class="card mb-3 review-card">
                              <div class="card-body">
                                  <div class="d-flex justify-content-between align-items-center mb-2">
                                      <h6 class="mb-0 review-user"><?php echo htmlspecialchars($rev['username']); ?></h6>
                                      <div><?php echo displayRating($rev['rating']); ?></div>
                                  </div>
                                  <p class="mb-2"><?php echo nl2br(htmlspecialchars($rev['review'])); ?></p>
                                  <?php if (!empty($rev['review_photo'])): ?>
                                      <img src="uploads/<?php echo htmlspecialchars($rev['review_photo']); ?>" alt="Foto ulasan" class="review-photo" data-bs-toggle="modal" data-bs-target="#photoModal" data-src="uploads/<?php echo htmlspecialchars($rev['review_photo']); ?>">
                                  <?php endif; ?>
                                  <div class="mt-2">
                                      <small class="text-muted"><?php echo date("d M Y", strtotime($rev['created_at'])); ?></small>
                                  </div>
                              </div>
                          </div>
                      <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-chat-square-text" style="font-size: 3rem; color: #dee2e6;"></i>
                        <p class="mt-3">Belum ada ulasan untuk produk ini. Jadilah yang pertama memberikan ulasan!</p>
                    </div>
                <?php endif; ?>
                
                <div class="d-flex justify-content-center mt-4">
                    <?php if ($loggedIn): ?>
                        <button type="button" class="btn btn-keranjang" data-bs-toggle="modal" data-bs-target="#reviewModal">
                            <i class="bi bi-pencil-square me-2"></i> <?php echo $has_reviewed ? 'Edit Ulasan Anda' : 'Berikan Ulasan Anda'; ?>
                        </button>
                    <?php else: ?>
                        <a href="login.php?redirect=<?= urlencode('produk_detail.php?id=' . $product_id) ?>" class="btn btn-keranjang">
                            <i class="bi bi-person-circle me-2"></i> Masuk untuk Memberikan Ulasan
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Modal untuk melihat foto ulasan lebih besar -->
        <div class="modal fade" id="photoModal" tabindex="-1" aria-labelledby="photoModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="photoModalLabel">Foto Ulasan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <img src="" id="modalImage" class="review-modal-img">
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal untuk menambah/mengedit ulasan -->
        <div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="reviewModalLabel">
                            <?php echo $has_reviewed ? 'Edit Ulasan Anda' : 'Berikan Ulasan Anda'; ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php if ($has_reviewed): ?>
                            <form action="process_review.php" method="POST" enctype="multipart/form-data" id="reviewForm">
                                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                <input type="hidden" name="review_id" value="<?php echo $existing_review['id']; ?>">
                                <input type="hidden" name="action" value="update">

                                <div class="mb-3">
                                    <label for="rating" class="form-label">Rating</label>
                                    <div class="star-rating">
                                        <?php for ($i = 5; $i >= 1; $i--): ?>
                                            <input type="radio" name="rating" id="star<?php echo $i; ?>" value="<?php echo $i; ?>" <?php echo ($existing_review['rating'] == $i) ? 'checked' : ''; ?>>
                                            <label for="star<?php echo $i; ?>"><i class="fas fa-star"></i></label>
                                        <?php endfor; ?>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="review_text" class="form-label">Ulasan Anda</label>
                                    <textarea name="review_text" id="review_text" class="form-control" rows="4" required maxlength="500"><?php echo htmlspecialchars($existing_review['review']); ?></textarea>
                                    <div class="word-counter" id="wordCounter">0/500 karakter</div>
                                </div>

                                <div class="mb-3">
                                    <label for="review_photo" class="form-label">Foto (opsional)</label>
                                    <input type="file" name="review_photo" id="review_photo" class="form-control" accept="image/*">
                                    <?php if (!empty($existing_review['review_photo'])): ?>
                                        <div class="mt-2">
                                            <img src="uploads/<?php echo htmlspecialchars($existing_review['review_photo']); ?>" alt="Foto ulasan" class="preview-image">
                                            <div class="form-check mt-1">
                                                <input class="form-check-input" type="checkbox" name="delete_photo" id="delete_photo">
                                                <label class="form-check-label" for="delete_photo">
                                                    Hapus foto ini
                                                </label>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <div id="imagePreview"></div>
                                </div>
                            </form>
                        <?php else: ?>
                            <form action="process_review.php" method="POST" enctype="multipart/form-data" id="reviewForm">
                                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                <input type="hidden" name="action" value="add">

                                <div class="mb-3">
                                    <label for="rating" class="form-label">Rating</label>
                                    <div class="star-rating">
                                        <?php for ($i = 5; $i >= 1; $i--): ?>
                                            <input type="radio" name="rating" id="star<?php echo $i; ?>" value="<?php echo $i; ?>">
                                            <label for="star<?php echo $i; ?>"><i class="fas fa-star"></i></label>
                                        <?php endfor; ?>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="review_text" class="form-label">Ulasan Anda</label>
                                    <textarea name="review_text" id="review_text" class="form-control" rows="4" required maxlength="500"></textarea>
                                    <div class="word-counter" id="wordCounter">0/500 karakter</div>
                                </div>

                                <div class="mb-3">
                                    <label for="review_photo" class="form-label">Foto (opsional)</label>
                                    <input type="file" name="review_photo" id="review_photo" class="form-control" accept="image/*">
                                    <div id="imagePreview"></div>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" form="reviewForm" class="btn btn-primary">
                            <?php echo $has_reviewed ? 'Perbarui Ulasan' : 'Kirim Ulasan'; ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../assets/footer.php'; ?>

    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Owl Carousel JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
    <!-- Custom JavaScript -->
    <script src="assets/js/product_detail.js"></script>
    
    <script>
    $(document).ready(function() {
        // Inisialisasi Owl Carousel untuk rekomendasi produk
        $('.recommendation-carousel').owlCarousel({
            loop: true,
            margin: 15,
            nav: true,
            dots: true,
            autoplay: true,
            autoplayTimeout: 5000,
            autoplayHoverPause: true,
            navText: ['<i class="fas fa-chevron-left"></i>', '<i class="fas fa-chevron-right"></i>'],
            responsive: {
                0: {
                    items: 1,
                    nav: true
                },
                576: {
                    items: 2,
                    nav: true
                },
                768: {
                    items: 3,
                    nav: true
                },
                992: {
                    items: 4,
                    nav: true
                }
            },
            slideBy: 1,
            smartSpeed: 500,
            responsiveRefreshRate: 100
        });
        
        // Efek hover pada produk card
        $('.product-card').hover(
            function() {
                $(this).css('transform', 'translateY(-5px)');
                $(this).css('box-shadow', '0 10px 20px rgba(0,0,0,0.1)');
            },
            function() {
                $(this).css('transform', 'translateY(0)');
                $(this).css('box-shadow', '0 5px 10px rgba(0,0,0,0.05)');
            }
        );
    });
    
    // Fungsi untuk tombol plus dan minus pada quantity
    function increaseQuantity() {
        var input = document.getElementById('quantity');
        var maxStock = <?= $product['stock'] ?>;
        var value = parseInt(input.value, 10);
        
        if (value < maxStock) {
            input.value = value + 1;
        }
    }
    
    function decreaseQuantity() {
        var input = document.getElementById('quantity');
        var value = parseInt(input.value, 10);
        
        if (value > 1) {
            input.value = value - 1;
        }
    }
    </script>

    <script>
        // Toggle reviews section
    const toggleReviews = document.getElementById('toggleReviews');
    const reviewsContainer = document.getElementById('reviewsContainer');

    if (toggleReviews && reviewsContainer) {
        toggleReviews.addEventListener('click', function() {
            reviewsContainer.classList.toggle('show');
            
            // Change the icon and text based on the current state
            if (reviewsContainer.classList.contains('show')) {
                this.innerHTML = '<i class="bi bi-chevron-up"></i> Sembunyikan Ulasan';
            } else {
                this.innerHTML = '<i class="bi bi-chevron-down"></i> Lihat Ulasan';
            }
        });
    }

    // Tambahkan kode JavaScript berikut setelah kode untuk toggle reviews
    // Ini akan membatasi tampilan awal menjadi 5 review saja
    document.addEventListener('DOMContentLoaded', function() {
        // Kode yang sudah ada...
        
        // Tambahkan ini untuk mengatur jumlah review yang ditampilkan
        const reviewCards = document.querySelectorAll('.review-card');
        const showAllBtn = document.createElement('button');
        let isShowingAll = false;
        
        // Jika ada lebih dari 5 review, sembunyikan yang lainnya
        if (reviewCards.length > 5) {
            // Atur tinggi maksimum pada reviews-list berdasarkan 5 review pertama
            let totalHeight = 0;
            for (let i = 0; i < 5; i++) {
                if (reviewCards[i]) {
                    totalHeight += reviewCards[i].offsetHeight + 16; // 16px untuk margin-bottom
                }
            }
            
            document.querySelector('.reviews-list').style.maxHeight = totalHeight + 'px';
            
            // Tambahkan tombol "Lihat Semua Ulasan"
            showAllBtn.className = 'btn btn-outline-success mt-3 w-100';
            showAllBtn.innerHTML = '<i class="bi bi-list-ul me-2"></i>Lihat Semua Ulasan';
            document.querySelector('.reviews-list').after(showAllBtn);
            
            // Event listener untuk tombol "Lihat Semua Ulasan"
            showAllBtn.addEventListener('click', function() {
                if (!isShowingAll) {
                    // Tampilkan semua review
                    document.querySelector('.reviews-list').style.maxHeight = 'none';
                    this.innerHTML = '<i class="bi bi-chevron-up me-2"></i>Tampilkan Lebih Sedikit';
                    isShowingAll = true;
                } else {
                    // Kembali ke 5 review pertama
                    document.querySelector('.reviews-list').style.maxHeight = totalHeight + 'px';
                    document.querySelector('.reviews-list').scrollTop = 0;
                    this.innerHTML = '<i class="bi bi-list-ul me-2"></i>Lihat Semua Ulasan';
                    isShowingAll = false;
                }
            });
        }
    });

    // Sort reviews functionality
    document.getElementById('sortReviews').addEventListener('change', function() {
        const sortValue = this.value;
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('sort', sortValue);
        window.location.href = currentUrl.toString();
    });

    // Photo modal functionality
    document.querySelectorAll('.review-photo').forEach(function(img) {
        img.addEventListener('click', function() {
            const modalImg = document.getElementById('modalImage');
            modalImg.src = this.dataset.src;
        });
    });

    // Star rating functionality
    document.querySelectorAll('.star-rating input[type="radio"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            const rating = this.value;
            const labels = this.closest('.star-rating').querySelectorAll('label');
            
            labels.forEach(function(label, index) {
                if (index < rating) {
                    label.classList.add('active');
                } else {
                    label.classList.remove('active');
                }
            });
        });
    });

    // Character counter for review text
    const reviewTextarea = document.getElementById('review_text');
    const wordCounter = document.getElementById('wordCounter');
    
    if (reviewTextarea && wordCounter) {
        // Set initial count
        wordCounter.textContent = reviewTextarea.value.length + '/500 karakter';
        
        reviewTextarea.addEventListener('input', function() {
            const currentLength = this.value.length;
            wordCounter.textContent = currentLength + '/500 karakter';
            
            // Change color based on character count
            if (currentLength > 450) {
                wordCounter.style.color = '#dc3545'; // Red
            } else if (currentLength > 400) {
                wordCounter.style.color = '#fd7e14'; // Orange
            } else {
                wordCounter.style.color = '#6c757d'; // Gray
            }
        });
    }

    // Image preview functionality
    const reviewPhotoInput = document.getElementById('review_photo');
    const imagePreview = document.getElementById('imagePreview');
    
    if (reviewPhotoInput && imagePreview) {
        reviewPhotoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.innerHTML = `
                        <div class="mt-2">
                            <img src="${e.target.result}" alt="Preview" class="preview-image">
                            <button type="button" class="btn btn-sm btn-danger mt-1" onclick="removeImagePreview()">
                                <i class="bi bi-trash"></i> Hapus
                            </button>
                        </div>
                    `;
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Function to remove image preview
    function removeImagePreview() {
        document.getElementById('imagePreview').innerHTML = '';
        document.getElementById('review_photo').value = '';
    }

    // Form validation
    document.getElementById('reviewForm').addEventListener('submit', function(e) {
        const rating = document.querySelector('input[name="rating"]:checked');
        const reviewText = document.getElementById('review_text').value.trim();
        
        if (!rating) {
            e.preventDefault();
            alert('Silakan pilih rating terlebih dahulu.');
            return;
        }
        
        if (reviewText.length < 10) {
            e.preventDefault();
            alert('Ulasan harus minimal 10 karakter.');
            return;
        }
        
        if (reviewText.length > 500) {
            e.preventDefault();
            alert('Ulasan tidak boleh lebih dari 500 karakter.');
            return;
        }
    });

    // Smooth scrolling for better UX
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    </script>
</body>
</html>
