<?php
ob_start(); // Aktifkan output buffering
session_start(); // Mulai session

// Cek apakah user login atau belum
$isLoggedIn = isset($_SESSION['user']['user_id']);

include '../config/config.php';

$price_slider_max = 1000000;
$capRes = $conn->query("SELECT COALESCE(MAX(price), 1000000) AS c FROM products WHERE stock > 0");
if ($capRes && ($capRow = $capRes->fetch_assoc())) {
    $c = (int) $capRow['c'];
    $price_slider_max = max(1000000, (int) ceil($c / 10000) * 10000);
}

// Default sorting
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$category = isset($_GET['category']) ? intval($_GET['category']) : 0;
$gender = isset($_GET['gender']) ? strtolower(trim($_GET['gender'])) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$min_price = isset($_GET['min_price']) ? intval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? intval($_GET['max_price']) : $price_slider_max;

// Normalisasi input filter
$allowedSort = ['newest', 'oldest', 'price_asc', 'price_desc', 'name_asc', 'name_desc'];
if (!in_array($sort, $allowedSort, true)) {
    $sort = 'newest';
}
if ($min_price < 0) {
    $min_price = 0;
}
if ($min_price > $price_slider_max) {
    $min_price = $price_slider_max;
}
if ($max_price > $price_slider_max) {
    $max_price = $price_slider_max;
}
if ($max_price < $min_price) {
    $max_price = $min_price;
}

// Query dasar untuk produk yang masih bisa dijual
$sql = "SELECT p.*, c.category_name
FROM products p
JOIN categories c ON p.category_id = c.category_id
WHERE 1=1
AND p.stock > 0
AND p.product_id NOT IN (
    SELECT pd.product_id 
    FROM purchase_details pd
    JOIN transactions t ON pd.transaction_id = t.transaction_id
    WHERE t.status IN ('pending', 'paid', 'shipped')
    AND pd.quantity >= p.stock
)";

// Tambahkan filter berdasarkan kategori jika ada
if ($category > 0) {
    $sql .= " AND p.category_id = $category";
}

// Filter jenis kelamin (admin menyimpan "Laki-laki"/"Perempuan"; filter UI memakai pria/wanita)


// Tambahkan filter berdasarkan rentang harga
$sql .= " AND p.price BETWEEN $min_price AND $max_price";

// Tambahkan filter pencarian jika ada
if ($search !== '') {
    $search_esc = $conn->real_escape_string($search);
    $sql .= " AND (p.name LIKE '%$search_esc%' OR p.description LIKE '%$search_esc%')";
}

// Tambahkan filter ukuran jika ada
if (isset($_GET['filter_sizes']) && !empty($_GET['filter_sizes'])) {
    $sizeConditions = [];
    foreach ($_GET['filter_sizes'] as $size) {
        $sizeEsc = $conn->real_escape_string($size);
        $sizeConditions[] = "p.sizes LIKE '%$sizeEsc%'";
    }
    if (!empty($sizeConditions)) {
        $sql .= " AND (" . implode(" OR ", $sizeConditions) . ")";
    }
}

// Tambahkan sorting
switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY p.price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY p.price DESC";
        break;
    case 'name_asc':
        $sql .= " ORDER BY p.name ASC";
        break;
    case 'name_desc':
        $sql .= " ORDER BY p.name DESC";
        break;
    case 'oldest':
        $sql .= " ORDER BY p.created_at ASC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY p.created_at DESC";
        break;
}

$result = $conn->query($sql);
if ($result === false) {
    die('Query produk gagal: ' . htmlspecialchars($conn->error));
}

// Query untuk 3 produk terbaru yang dimasukkan admin
$sqlTerbaru = "SELECT p.*, c.category_name FROM products p 
               JOIN categories c ON p.category_id = c.category_id 
               WHERE p.stock > 0
               AND p.product_id NOT IN (
                   SELECT pd.product_id 
                   FROM purchase_details pd
                   JOIN transactions t ON pd.transaction_id = t.transaction_id
                   WHERE t.status IN ('pending', 'paid', 'shipped', 'completed')
                   AND pd.quantity >= p.stock
               )
               ORDER BY p.created_at DESC
               LIMIT 3";
$resultTerbaru = $conn->query($sqlTerbaru);

// Ambil semua kategori untuk dropdown filter
$categorySql = "SELECT * FROM categories ORDER BY category_name ASC";
$categoryResult = $conn->query($categorySql);

// ===========================
// 2. Query untuk kategori produk
// ===========================
$queryKategori = "SELECT * FROM categories";
$resultKategori = mysqli_query($conn, $queryKategori);

if (!$resultKategori) {
    die("Query kategori gagal: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produk - hobiBekasan</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Owl Carousel CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css">
    <!-- Custom CSS -->
    <style>
/* Global Styles */
body {
    font-family: 'Poppins', sans-serif;
    background-color: #f8f9fa;
    color: #333;
    line-height: 1.6;
}

a {
    text-decoration: none;
    color: inherit;
    transition: all 0.3s ease;
}

/* Store Header Styles */
.store-header {
    padding: 40px 0;
    background-image: linear-gradient(135deg, rgba(97, 178, 255, 0.1) 0%, rgba(30, 127, 214, 0.1) 70%);
    border-radius: 15px;
    margin-bottom: 40px;
}

.store-title {
    font-size: 3.2rem;
    font-weight: 800;
    color: #003366;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 15px;
    position: relative;
    display: inline-block;
}

.store-title::after {
    content: "";
    position: absolute;
    width: 60px;
    height: 4px;
    background: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%);
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    border-radius: 2px;
}

.store-subtitle {
    font-size: 1.25rem;
    color: #5c5c5c;
    max-width: 600px;
    margin: 0 auto;
    font-weight: 300;
}

/* Section Styles */
.section-header {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 30px;
    position: relative;
}

.section-title {
    font-size: 2rem;
    font-weight: 700;
    color: #003366;
    margin-bottom: 15px;
    position: relative;
    text-align: center;
}

.section-decoration {
    width: 80px;
    height: 4px;
    background: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%);
    border-radius: 2px;
}

/* Category Carousel Styles */
.category-section {
    margin-bottom: 60px;
    position: relative;
    padding: 0 50px;
}

.category-item {
    padding: 15px;
}

.category-box {
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    transition: all 0.4s ease;
    background: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%);
    height: 100%;
    display: flex;
    flex-direction: column;
}

.category-box:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 25px rgba(30, 127, 214, 0.15);
}

.category-img-container {
    height: 240px;
    overflow: hidden;
    position: relative;
    padding: 10px;
    background: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%);
}

.category-img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    object-position: center;
    transition: transform 0.6s ease;
}

.category-box:hover .category-img {
    transform: scale(1.08);
}

.category-info {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 15px 10px;
    background: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%);
    flex-grow: 1;
    border-top: 1px solid rgba(255, 255, 255, 0.18);
}

.category-name {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: white;
    text-align: center;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
    transition: all 0.3s ease;
}

.category-box:hover .category-name {
    color: #ffdd57;
    transform: scale(1.05);
}

/* Owl Carousel Navigation */
.owl-nav button {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 45px !important;
    height: 45px !important;
    border-radius: 50% !important;
    background: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%) !important;
    color: white !important;
    display: flex !important;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem !important;
    opacity: 0.8;
    transition: all 0.3s ease;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    margin: 0 !important;
}

.owl-nav button:hover {
    opacity: 1;
    box-shadow: 0 5px 15px rgba(30, 127, 214, 0.3);
}

.owl-prev {
    left: -50px;
}

.owl-next {
    right: -50px;
}

.owl-dots {
    margin-top: 20px;
    text-align: center;
}

.owl-dot {
    width: 12px;
    height: 12px;
    margin: 0 6px;
    border-radius: 50%;
    background-color: #d6d6d6 !important;
    transition: all 0.3s ease;
}

.owl-dot.active {
    background: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%) !important;
    transform: scale(1.2);
}

/* Product Card Styles - UPDATED */
.produk-terbaru, .all-products {
    margin-bottom: 60px;
}

.product-card {
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
    transition: all 0.4s ease;
    height: 100%;
    background-color: #f0f2f5; /* Warna abu-abu muda untuk card */
    position: relative;
    border: 1px solid #e6e8eb;
}

.product-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 30px rgba(30, 127, 214, 0.2);
    border-color: #d8e0e9;
}

/* Menyesuaikan ukuran container gambar produk sesuai dengan kategori */
.product-img-container {
    height: 240px;
    overflow: hidden;
    position: relative;
    background-color: #ffffff; /* Background putih untuk area gambar */
    border-bottom: 1px solid #e6e8eb;
}

.product-img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    transition: transform 0.6s ease;
}

.product-details {
    padding: 20px;
    background: linear-gradient(to bottom, #f7f9fc, #eff1f5); /* Gradient lembut untuk details */
}

.product-meta {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}

.product-category {
    color: #1e7fd6;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 600;
    position: relative;
    padding-left: 10px;
}

.product-category::before {
    content: "";
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%);
}

.product-stock {
    font-size: 0.8rem;
    color: #28a745;
    font-weight: 500;
}

.product-title {
    font-weight: 600;
    font-size: 1.1rem;
    margin-bottom: 10px;
    color: #333;
    line-height: 1.4;
    height: 3rem;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.product-price {
    font-size: 1.2rem;
    font-weight: 700;
    color: #003366;
    margin-bottom: 15px;
}

.product-actions {
    display: flex;
    justify-content: space-between;
    background-color: linear-gradient(to bottom, #f7f9fc, #eff1f5); /* Gradient lembut untuk details */
    margin: 0 -20px -20px -20px;
    padding: 15px 20px;
    border-top: 1px solid #e0e4e9;
    gap: 15px; /* Menambahkan gap antar tombol */
}

/* Perbaikan untuk tombol login yang mepet */
.btn-detail, .btn-cart, .btn-view-cart, .btn-login, .btn-disabled {
    padding: 10px 16px; 
    border-radius: 30px;
    font-size: 0.95rem; 
    font-weight: 600; 
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    min-width: 120px; /* Diperbesar dari 120px menjadi lebih fleksibel */
    text-align: center;
    white-space: nowrap; /* Mencegah teks membungkus ke baris baru */
    overflow: hidden; /* Menyembunyikan overflow jika ada */
    text-overflow: ellipsis; /* Menambahkan titik-titik jika teks terpotong */
}

.btn-detail {
    background-color: #ffffff;
    color: #003366;
    border: 2px solid #003366; /* Menambahkan border yang lebih tebal */
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
}

.btn-detail:hover {
    background-color: #f0f5ff;
    color: #1e7fd6;
    box-shadow: 0 5px 10px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
}

.btn-cart {
    background: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%);
    color: white;
    box-shadow: 0 3px 8px rgba(30, 127, 214, 0.3);
    min-width: 140px; /* Tombol keranjang sedikit lebih lebar */
}

.btn-cart:hover {
    box-shadow: 0 6px 15px rgba(30, 127, 214, 0.4);
    transform: translateY(-3px);
    background: linear-gradient(135deg, #4da3ff 30%, #0c6eca 70%);
}

/* Penyesuaian khusus untuk tombol login */
.btn-login {
    background-color: #6c757d;
    color: white;
    box-shadow: 0 3px 8px rgba(108, 117, 125, 0.3);
    min-width: 140px; /* Diperbesar khusus untuk tombol login */
    padding: 10px 12px; /* Padding yang lebih kompak */
    font-size: 0.85rem; /* Font sedikit lebih kecil untuk muat */
}

.btn-login:hover {
    background-color: #5a6268;
    box-shadow: 0 6px 15px rgba(108, 117, 125, 0.4);
    transform: translateY(-3px);
}

.btn-disabled {
    background-color: #e9ecef;
    color: #adb5bd;
    cursor: not-allowed;
    box-shadow: none;
    min-width: 140px;
}

.btn-detail i, .btn-cart i, .btn-view-cart i, .btn-login i, .btn-disabled i {
    margin-right: 8px; /* Memperbesar jarak antara ikon dan teks */
    font-size: 1rem; /* Memperbesar ukuran ikon */
}
/* Gender Badge */
.gender-badge {
    position: absolute;
    top: 15px;
    left: 15px;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    z-index: 2;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.gender-pria {
    background-color: rgba(30, 127, 214, 0.9);
    color: white;
}

.gender-wanita {
    background-color: rgba(255, 105, 180, 0.9);
    color: white;
}

/* Sold Out Badge */
.sold-out-badge {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: rgba(0, 0, 0, 0.6);
    color: white;
    font-size: 1.2rem;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    z-index: 1;
}

/* Filter Styles */
.filter-card {
    background-color: white;
    border-radius: 15px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    margin-bottom: 20px;
}

.filter-header {
    background: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%);
    color: white;
    padding: 15px 20px;
    font-size: 1.1rem;
    font-weight: 600;
}

.filter-body {
    padding: 20px;
}

.filter-group {
    margin-bottom: 20px;
}

.filter-label {
    display: block;
    font-size: 0.95rem;
    font-weight: 500;
    margin-bottom: 8px;
    color: #495057;
}

.filter-select, .filter-input {
    width: 100%;
    padding: 10px 15px;
    border: 1px solid #ced4da;
    border-radius: 8px;
    font-size: 0.9rem;
    background-color: #f8f9fa;
    transition: all 0.3s ease;
}

.filter-select:focus, .quantity-input:focus {
    border-color: #61b2ff;
    outline: none;
}

.product-detail-sizes {
    margin-bottom: 15px;
}

.size-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 8px;
}

.size-badge {
    background: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(97, 178, 255, 0.3);
}

.product-sizes {
    margin-bottom: 12px;
}

.sizes-label {
    font-size: 0.8rem;
    font-size: 0.7rem;
    grid-template-columns: repeat(5, 1fr);
    gap: 8px;
    margin-top: 8px;
}

.size-filter-item {
    text-align: center;
}

.size-filter-item .form-check {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 8px 4px;
    border-radius: 8px;
    transition: all 0.3s ease;
    cursor: pointer;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
}

.size-filter-item .form-check:hover {
    background: #e3f2fd;
    border-color: #90caf9;
}

.size-filter-item .form-check-input {
    margin-right: 4px;
    width: 16px;
    height: 16px;
    cursor: pointer;
}

.size-filter-item .form-check-input:checked {
    background-color: #1e7fd6;
    border-color: #1e7fd6;
}

.size-filter-item .form-check-label {
    font-size: 0.85rem;
    font-weight: 500;
    color: #495057;
    cursor: pointer;
    margin: 0;
}

.size-filter-item .form-check-input:checked ~ .form-check-label {
    color: #1e7fd6;
    font-weight: 600;
}

/* Price Range Slider - Perbaikan */
.price-inputs {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
}

.price-input {
    display: flex;
    flex-direction: column;
    font-size: 0.85rem;
}

.price-input span:first-child {
    color: #6c757d;
    margin-bottom: 5px;
}

.price-input span:last-child {
    font-weight: 600;
    color: #003366;
}

/* Price Range Slider - Komponen Utama */
.range-slider {
    position: relative;
    width: 100%;
    height: 6px;
    margin: 30px 0;
}

.slider-track {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 6px;
    background: #e9ecef;
    border-radius: 3px;
}

.price-progress {
    position: absolute;
    height: 6px;
    background: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%);
    border-radius: 3px;
    z-index: 1;
}

/* Range Input Styling - Perbaikan */
.filter-range {
    position: absolute;
    width: 100%;
    height: 6px;
    background: transparent;
    -webkit-appearance: none;
    margin: 0;
    z-index: 2;
    pointer-events: none;
}

/* Range Thumb Styling */
.filter-range::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%);
    cursor: pointer;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    pointer-events: auto;
    margin-top: -7px;
    position: relative;
    z-index: 3;
}

.filter-range::-moz-range-thumb {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%);
    cursor: pointer;
    border: none;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    position: relative;
    z-index: 3;
}

/* Range Track Styling */
.filter-range::-webkit-slider-runnable-track {
    width: 100%;
    height: 6px;
    background: transparent;
    border: none;
}

.filter-range::-moz-range-track {
    width: 100%;
    height: 6px;
    background: transparent;
    border: none;
}

/* Posisi input range */
#min_price {
    bottom: 0;
}

#max_price {
    bottom: 0;
}

.filter-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.btn-filter, .btn-reset {
    padding: 10px 15px;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 500;
    text-align: center;
    transition: all 0.3s ease;
    cursor: pointer;
    border: none;
}

.btn-filter {
    background: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%);
    color: white;
}

.btn-filter:hover {
    box-shadow: 0 5px 15px rgba(30, 127, 214, 0.3);
    transform: translateY(-2px);
}

.btn-reset {
    background-color: #f8f9fa;
    color: #495057;
    border: 1px solid #dee2e6;
}

.btn-reset:hover {
    background-color: #e9ecef;
}

/* No Products Found */
.no-products {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 40px;
    background-color: white;
    border-radius: 15px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
}

.no-products i {
    color: #adb5bd;
    margin-bottom: 15px;
}

.no-products h4 {
    color: #495057;
    margin-bottom: 10px;
    font-weight: 600;
}

.no-products p {
    color: #6c757d;
    margin-bottom: 20px;
    max-width: 500px;
}

.btn-reset-search {
    padding: 10px 20px;
    background: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%);
    color: white;
    border-radius: 30px;
    font-weight: 500;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
}

.btn-reset-search:hover {
    box-shadow: 0 5px 15px rgba(30, 127, 214, 0.3);
    transform: translateY(-2px);
}

.btn-reset-search i {
    color: white;
    margin-right: 5px;
    margin-bottom: 0;
}

/* Produk Terbaru Carousel */
.produk-terbaru {
    position: relative;
    padding: 0 50px;
}

/* Menyesuaikan item produk terbaru agar konsisten dengan kategori */
.produk-terbaru .item {
    padding: 15px;
}

.produk-terbaru .owl-nav button.owl-prev {
    position: absolute;
    left: -60px;
    top: 50%;
    transform: translateY(-50%);
}

.produk-terbaru .owl-nav button.owl-next {
    position: absolute;
    right: -60px;
    top: 50%;
    transform: translateY(-50%);
}

/* Responsive Styles */
@media (max-width: 1199.98px) {
    .store-title {
        font-size: 2.8rem;
    }
    
    .store-subtitle {
        font-size: 1.2rem;
    }
    
    .owl-prev {
        left: -45px;
    }
    
    .owl-next {
        right: -45px;
    }
    
    .produk-terbaru .owl-nav button.owl-prev {
        left: -45px;
    }
    
    .produk-terbaru .owl-nav button.owl-next {
        right: -45px;
    }
}

@media (max-width: 991.98px) {
    .category-section, .produk-terbaru {
        padding: 0 40px;
    }

    .store-title {
        font-size: 2.5rem;
    }
    
    .store-subtitle {
        font-size: 1.1rem;
    }
    
    .section-title {
        font-size: 1.8rem;
    }
    
    .owl-prev, .produk-terbaru .owl-nav button.owl-prev {
        left: -30px;
    }
    
    .owl-next, .produk-terbaru .owl-nav button.owl-next {
        right: -30px;
    }
    
    .owl-nav button {
        width: 35px !important;
        height: 35px !important;
    }
    
    .product-img-container, .category-img-container {
        height: 220px;
    }
}

@media (max-width: 767.98px) {
    .store-header {
        padding: 25px 0;
    }

    .category-section, .produk-terbaru {
        padding: 0 20px;
    }

    .store-title {
        font-size: 1.8rem;
    }

    .store-subtitle {
        font-size: 0.95rem;
    }

    .section-title {
        font-size: 1.4rem;
    }

    .category-item {
        padding: 8px;
    }

    /* Menyesuaikan padding produk terbaru dengan kategori */
    .produk-terbaru .item {
        padding: 8px;
    }

    .category-img-container, .product-img-container {
        height: 180px;
    }

    .owl-nav button {
        width: 28px !important;
        height: 28px !important;
    }

    .owl-prev, .produk-terbaru .owl-nav button.owl-prev {
        left: -15px;
    }

    .owl-next, .produk-terbaru .owl-nav button.owl-next {
        right: -15px;
    }

    .product-card {
        margin-bottom: 15px;
    }

    .btn-cart {
        font-size: 0.85rem;
        padding: 8px 12px;
    }
}

@media (max-width: 575.98px) {
    .store-header {
        padding: 20px 0;
    }

    .store-title {
        font-size: 1.5rem;
    }

    .store-subtitle {
        font-size: 0.85rem;
    }

    .section-title {
        font-size: 1.2rem;
    }

    .category-section, .produk-terbaru {
        padding: 0 15px;
    }

    .category-img-container, .product-img-container {
        height: 150px;
    }

    .product-title {
        font-size: 0.9rem;
    }

    .product-price {
        font-size: 1rem;
    }

    .btn-detail, .btn-cart, .btn-view-cart, .btn-login, .btn-disabled {
        padding: 6px 10px;
        font-size: 0.75rem;
    }

    .owl-nav button {
        width: 25px !important;
        height: 25px !important;
    }

    .owl-prev, .produk-terbaru .owl-nav button.owl-prev {
        left: -10px;
    }

    .owl-next, .produk-terbaru .owl-nav button.owl-next {
        right: -10px;
    }
}

@media (max-width: 375.98px) {
    .store-title {
        font-size: 1.6rem;
    }
    
    .store-subtitle {
        font-size: 0.85rem;
    }
    
    .section-title {
        font-size: 1.3rem;
    }
    
    .section-decoration {
        width: 60px;
    }
    
    .product-img-container, .category-img-container {
        height: 160px;
    }
    
    .product-details {
        padding: 15px;
    }
    
    .owl-nav {
        display: none;
    }
}

/* Back to Top Button */
.back-to-top {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%);
    color: white;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    text-decoration: none;
    box-shadow: 0px 5px 15px rgba(97, 178, 255, 0.5);
    opacity: 0;
    visibility: hidden;
    transition: all 0.4s ease;
    z-index: 1000;
}

.back-to-top.active {
    opacity: 1;
    visibility: visible;
}

.back-to-top:hover {
    transform: translateY(-5px);
    box-shadow: 0px 8px 20px rgba(97, 178, 255, 0.7);
    color: white;
}

.back-to-top i {
    font-size: 20px;
}
.category-img{
width:100%;
height:260px;
object-fit:cover;
display:block;
margin:auto;
filter: drop-shadow(0px 5px 10px rgba(0,0,0,0.2));
}
.category-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 18px;
    min-height: 0;
    justify-content: flex-start;
    width: 100%;
    padding: 16px 16px 22px;
    border-radius: 15px 15px 0 0;
}

.category-card-title {
    margin: 0;
    text-align: center;
    font-size: 2rem;
    font-weight: 700;
    line-height: 1.1;
    color: #0b63d1;
    letter-spacing: 0.3px;
}

/* Responsive adjustments */
@media (max-width: 767.98px) {
    .back-to-top {
        width: 45px;
        height: 45px;
        font-size: 16px;
        bottom: 20px;
        right: 20px;
    }

    .category-img {
        height: 220px;
    }
}

@media (max-width: 575.98px) {
    .category-img {
        height: 190px;
    }
}
    </style>
</head>
<body>

<!-- Sertakan navbar -->
<?php include("../assets/navbar.php"); ?>

<div class="container my-5">
    <!-- Header Nama Toko -->
    <div class="store-header text-center mb-5">
        <h1 class="store-title">hobiBekasan</h1>
        <p class="store-subtitle">Temukan sepatu branded preloved berkualitas dengan harga terjangkau</p>
    </div>
    
    <!-- Kategori Carousel -->
    <div class="category-section mb-5">
        <div class="section-header">
            <h3 class="section-title">Kategori</h3>
            <div class="section-decoration"></div>
        </div>
        <div class="owl-carousel category-carousel">
            <?php
            // Reset pointer kategoris
            $categoryResult->data_seek(0);
            while($cat = $categoryResult->fetch_assoc()): 
            ?>
            <div class="category-item">
                <a href="kategori.php?category=<?php echo $cat['category_id']; ?>" class="text-decoration-none">
                    <div class="category-box">
                    <div class="category-card text-center">
                    <img src="../assets/img/category/<?php echo $cat['category_photo']; ?>" 
                    alt="<?php echo $cat['category_name']; ?>" 
                    class="category-img">

<h5 class="category-card-title"><?php echo $cat['category_name']; ?></h5>

</div>
                    </div>
                </a>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    
    <!-- PRODUK TERBARU -->
    <section class="produk-terbaru mb-5">
        <div class="section-header">
            <h3 class="section-title">Produk Terbaru</h3>
            <div class="section-decoration"></div>
        </div>

        <?php if ($resultTerbaru && $resultTerbaru->num_rows > 0): ?>
            <div class="owl-carousel produk-terbaru-carousel">
                <?php while ($row = $resultTerbaru->fetch_assoc()): ?>
                    <div class="item">
                        <div class="product-card">
                            <div class="product-img-container">
                                <img src="../assets/img/products/<?php echo $row['image']; ?>" alt="<?php echo $row['name']; ?>" class="product-img">
                                <?php if($row['stock'] <= 0): ?>
                                <div class="sold-out-badge">Stok Habis</div>
                                <?php endif; ?>
                            </div>
                            <div class="product-details">
                                <div class="product-meta">
                                    <span class="product-category"><?php echo $row['category_name']; ?></span>
                                    <div class="stock-info">
                                        <?php if($row['stock'] <= 0): ?>
                                            <span class="stock-badge out-of-stock">Stok Habis</span>
                                        <?php elseif($row['stock'] <= 5): ?>
                                            <span class="stock-badge low-stock">Stok: <?php echo $row['stock']; ?></span>
                                        <?php else: ?>
                                            <span class="stock-badge in-stock">Stok: <?php echo $row['stock']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <h5 class="product-title"><?php echo $row['name']; ?></h5>
                                <p class="product-price">Rp <?php echo number_format($row['price'], 0, ',', '.'); ?></p>
                                <?php if (!empty($row['sizes'])): ?>
                                <div class="product-sizes">
                                    <span class="sizes-label">Ukuran:</span>
                                    <div class="sizes-list">
                                        <?php 
                                        $sizes = explode(',', $row['sizes']);
                                        $display_sizes = array_slice($sizes, 0, 3); // Tampilkan max 3 ukuran
                                        foreach ($display_sizes as $size): 
                                        ?>
                                            <span class="size-badge-small"><?php echo $size; ?></span>
                                        <?php endforeach; ?>
                                        <?php if (count($sizes) > 3): ?>
                                            <span class="size-more">+<?php echo count($sizes) - 3; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="product-actions">
                                    <?php if($row['stock'] > 0 && isset($_SESSION['user'])): ?>
                                    <form action="keranjang.php" method="POST" class="m-0 p-0">
                                        <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <input type="hidden" name="add_to_cart" value="1">
                                        <button type="submit" class="btn-cart">
                                            <i class="fas fa-shopping-cart"></i> Keranjang
                                        </button>
                                    </form>
                                    <?php elseif(!isset($_SESSION['user'])): ?>
                                    <a href="login.php" class="btn-login">
                                        <i class="fas fa-sign-in-alt"></i> Login untuk Beli
                                    </a>
                                    <?php elseif($row['stock'] <= 0): ?>
                                    <button class="btn-disabled" disabled>
                                        <i class="fas fa-times-circle"></i> Stok Habis
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i> Belum ada produk yang tersedia.
            </div>
        <?php endif; ?>
    </section>
    
    <!-- Produk Kami dengan Filter di sebelah kiri -->
    <section class="all-products">
        <div class="section-header">
            <h3 class="section-title">Produk Kami</h3>
            <div class="section-decoration"></div>
        </div>
        
        <div class="row">
            <!-- Filter dan Sorting Column -->
            <div class="col-lg-3 mb-4">
                <div class="filter-card">
                    <div class="filter-header">
                        <i class="fas fa-filter me-2"></i> Filter & Sort
                    </div>
                    <div class="filter-body">
                        <form id="filterProdukForm" action="kategori.php" method="GET">
                            <!-- Sorting -->
                            <div class="filter-group">
                                <label for="sort" class="filter-label">Urutkan berdasarkan:</label>
                                <select name="sort" id="sort" class="filter-select" onchange="this.form.submit()">
                                    <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Terbaru</option>
                                    <option value="oldest" <?php echo $sort == 'oldest' ? 'selected' : ''; ?>>Terlama</option>
                                    <option value="price_asc" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>Harga: Rendah ke Tinggi</option>
                                    <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>Harga: Tinggi ke Rendah</option>
                                    <option value="name_asc" <?php echo $sort == 'name_asc' ? 'selected' : ''; ?>>Nama: A-Z</option>
                                    <option value="name_desc" <?php echo $sort == 'name_desc' ? 'selected' : ''; ?>>Nama: Z-A</option>
                                </select>
                            </div>
                            
                            <!-- Kategori Filter -->
                            <div class="filter-group">
                                <label for="category" class="filter-label">Kategori:</label>
                                <select name="category" id="category" class="filter-select" onchange="this.form.submit()">
                                    <option value="0">Semua Kategori</option>
                                    <?php 
                                    $categoryResult->data_seek(0);
                                    while($cat = $categoryResult->fetch_assoc()): 
                                    ?>
                                    <option value="<?php echo $cat['category_id']; ?>" <?php echo $category == $cat['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo $cat['category_name']; ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                                    </div>
                            <!-- Search -->
                            <div class="filter-group">
                                <label for="search" class="filter-label">Pencarian:</label>
                                <input type="text" name="search" id="search" class="filter-input" placeholder="Cari produk..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            
                            <!-- Ukuran Filter -->
                            <div class="filter-group">
                                <label class="filter-label">Ukuran Sepatu:</label>
                                <div class="size-filter-grid">
                                    <?php
                                    $sizes = [36, 37, 38, 39, 40, 41, 42, 43, 44, 45];
                                    foreach ($sizes as $size):
                                    ?>
                                    <div class="size-filter-item">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="filter_sizes[]" value="<?php echo $size; ?>" id="filter_size_<?php echo $size; ?>" <?php echo isset($_GET['filter_sizes']) && in_array($size, $_GET['filter_sizes']) ? 'checked' : ''; ?> onchange="this.form.submit()">
                                            <label class="form-check-label" for="filter_size_<?php echo $size; ?>"><?php echo $size; ?></label>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Price Range -->
                            <!-- Ganti bagian Price Range di form filter -->
                            <div class="filter-group">
                                <label class="filter-label">Rentang Harga:</label>
                                <div class="price-slider">
                                    <div class="price-inputs">
                                        <div class="price-input min">
                                            <span>Min:</span>
                                            <span id="min_price_display">Rp <?php echo number_format($min_price, 0, ',', '.'); ?></span>
                                        </div>
                                        <div class="price-input max">
                                            <span>Max:</span>
                                            <span id="max_price_display">Rp <?php echo number_format($max_price, 0, ',', '.'); ?></span>
                                        </div>
                                    </div>
                                    <div class="range-slider">
                                        <div class="slider-track"></div>
                                        <input type="range" class="filter-range min-range" id="min_price" name="min_price" min="0" max="<?php echo (int) $price_slider_max; ?>" step="10000" value="<?php echo (int) $min_price; ?>">
                                        <input type="range" class="filter-range max-range" id="max_price" name="max_price" min="0" max="<?php echo (int) $price_slider_max; ?>" step="10000" value="<?php echo (int) $max_price; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="filter-actions">
                                <button type="submit" class="btn-filter">
                                    <i class="fas fa-filter me-1"></i> Terapkan Filter
                                </button>
                                <a href="kategori.php" class="btn-reset">
                                    <i class="fas fa-redo me-1"></i> Reset Filter
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Products Column -->
            <div class="col-lg-9">
                <?php if($result->num_rows > 0): ?>
                    <div class="row g-4">
                        <?php while($row = $result->fetch_assoc()): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="product-card">
                                <div class="product-img-container">
                                    <img src="../assets/img/products/<?php echo $row['image']; ?>" alt="<?php echo $row['name']; ?>" class="product-img" onclick="openCategoryProductModal('../assets/img/products/<?php echo $row['image']; ?>', '<?php echo htmlspecialchars($row['name']); ?>')" style="cursor: pointer;">
                                    </span>
                                    <?php if($row['stock'] <= 0): ?>
                                    <div class="sold-out-badge">Stok Habis</div>
                                    <?php endif; ?>
                                </div>
                                <div class="product-details">
                                    <div class="product-meta">
                                        <span class="product-category"><?php echo $row['category_name']; ?></span>
                                        <div class="stock-info">
                                            <?php if($row['stock'] <= 0): ?>
                                                <span class="stock-badge out-of-stock">Stok Habis</span>
                                            <?php elseif($row['stock'] <= 5): ?>
                                                <span class="stock-badge low-stock">Stok: <?php echo $row['stock']; ?></span>
                                            <?php else: ?>
                                                <span class="stock-badge in-stock">Stok: <?php echo $row['stock']; ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <h5 class="product-title"><?php echo $row['name']; ?></h5>
                                    <p class="product-price">Rp <?php echo number_format($row['price'], 0, ',', '.'); ?></p>
                                    
                                    <!-- Ukuran Sepatu -->
                                    <?php if (!empty($row['sizes'])): ?>
                                    <div class="product-sizes">
                                        <span class="sizes-label">Ukuran:</span>
                                        <div class="sizes-list">
                                            <?php 
                                            $sizes = explode(',', $row['sizes']);
                                            $display_sizes = array_slice($sizes, 0, 3); // Tampilkan max 3 ukuran
                                            foreach ($display_sizes as $size): 
                                            ?>
                                                <span class="size-badge-small"><?php echo $size; ?></span>
                                            <?php endforeach; ?>
                                            <?php if (count($sizes) > 3): ?>
                                                <span class="size-more">+<?php echo count($sizes) - 3; ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="product-actions">
                                        <a href="produk_detail.php?id=<?php echo $row['product_id']; ?>" class="btn-detail">
                                            <i class="fas fa-eye"></i> Detail
                                        </a>
                                        <?php if($row['stock'] > 0 && isset($_SESSION['user'])): ?>
                                        <div class="d-flex gap-2">
                                            <form action="keranjang.php" method="POST" class="m-0 p-0">
                                                <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
                                                <input type="hidden" name="quantity" value="1">
                                                <input type="hidden" name="add_to_cart" value="1">
                                                <button type="submit" class="btn-cart">
                                                    <i class="fas fa-shopping-cart"></i> Keranjang
                                                </button>
                                            </form>
                                            <a href="keranjang.php" class="btn-view-cart">
                                                <i class="fas fa-eye"></i> Lihat Keranjang
                                            </a>
                                        </div>
                                        <?php elseif(!isset($_SESSION['user'])): ?>
                                        <a href="login.php" class="btn-login">
                                            <i class="fas fa-sign-in-alt"></i> Login untuk Beli
                                        </a>
                                        <?php elseif($row['stock'] <= 0): ?>
                                        <button class="btn-disabled" disabled>
                                            <i class="fas fa-times-circle"></i> Stok Habis
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="no-products">
                        <i class="fas fa-search fa-3x mb-3"></i>
                        <h4>Produk Tidak Ditemukan</h4>
                        <p>Maaf, tidak ada produk yang sesuai dengan filter yang Anda pilih. Coba ubah filter atau cari produk lainnya.</p>
                        <a href="kategori.php" class="btn-reset-search">
                            <i class="fas fa-redo me-1"></i> Reset Pencarian
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<!-- Modal Detail Produk -->
<div class="modal fade" id="productDetailModal" tabindex="-1" aria-labelledby="productDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productDetailModalLabel">
                    <i class="fas fa-box"></i> Detail Produk
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="productDetailContent">
                <!-- Konten detail produk akan dimuat di sini -->
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" id="modalAddToCart">
                    <i class="fas fa-shopping-cart"></i> Tambah ke Keranjang
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Tombol Back to Top -->
<a href="#" class="back-to-top" id="backToTop">
        <i class="fa-solid fa-arrow-up"></i>
    </a>

<?php include '../assets/footer.php'; ?>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<!-- Owl Carousel JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
<!-- Custom JS -->
<script>
    $(document).ready(function(){
        // Inisialisasi kategori carousel
        $('.category-carousel').owlCarousel({
            loop: true,
            margin: 30,
            nav: true,
            dots: true,
            autoplay: true,
            autoplayTimeout: 5000,
            autoplayHoverPause: true,
            navText: ['<i class="fas fa-chevron-left"></i>', '<i class="fas fa-chevron-right"></i>'],
            responsive:{
                0: {
                    items: 1,
                    margin: 15
                },
                576: {
                    items: 2,
                    margin: 20
                },
                768: {
                    items: 3,
                    margin: 20
                },
                992: {
                    items: 3,
                    margin: 25
                },
                1200: {
                    items: 3
                }
            }
        });
        
        // Inisialisasi produk terbaru carousel
        $('.produk-terbaru-carousel').owlCarousel({
            loop: true,
            margin: 30,
            nav: true,
            dots: true,
            autoplay: true,
            autoplayTimeout: 6000,
            autoplayHoverPause: true,
            navText: ['<i class="fas fa-chevron-left"></i>', '<i class="fas fa-chevron-right"></i>'],
            responsive:{
                0: {
                    items: 1
                },
                576: {
                    items: 2,
                    margin: 20
                },
                992: {
                    items: 3,
                    margin: 25
                },
                1200: {
                    items: 3
                }
            }
        });
        
        // Efek hover pada produk card
        $('.product-card').hover(
            function() {
                $(this).find('.product-img').css('transform', 'scale(1.05)');
            },
            function() {
                $(this).find('.product-img').css('transform', 'scale(1)');
            }
        );
    });
    
    function updateMinPrice(val) {
        document.getElementById('min_price_display').textContent = 'Rp ' + formatNumber(val);
    }
    
    function updateMaxPrice(val) {
        document.getElementById('max_price_display').textContent = 'Rp ' + formatNumber(val);
    }
    
    function formatNumber(num) {
        return new Intl.NumberFormat('id-ID').format(num);
    }

    // Tambahkan script JavaScript berikut di dalam tag <script> di bagian bawah file
// Fungsi untuk memformat angka dalam format mata uang Indonesia
function formatNumber(num) {
    return new Intl.NumberFormat('id-ID').format(num);
}

// Script untuk price range slider yang sinkron
document.addEventListener('DOMContentLoaded', function() {
    const minRange = document.getElementById('min_price');
    const maxRange = document.getElementById('max_price');
    const minDisplay = document.getElementById('min_price_display');
    const maxDisplay = document.getElementById('max_price_display');
    const rangeSlider = document.querySelector('.range-slider');
    
    // Tambahkan elemen progress untuk track aktif
    const progress = document.createElement('div');
    progress.className = 'price-progress';
    rangeSlider.appendChild(progress);
    
    // Fungsi untuk memperbarui progress
    function updateProgress() {
        const minVal = parseInt(minRange.value);
        const maxVal = parseInt(maxRange.value);
        const minPercent = (minVal / parseInt(minRange.max)) * 100;
        const maxPercent = (maxVal / parseInt(maxRange.max)) * 100;
        
        progress.style.left = minPercent + '%';
        progress.style.width = (maxPercent - minPercent) + '%';
        
        minDisplay.textContent = 'Rp ' + formatNumber(minVal);
        maxDisplay.textContent = 'Rp ' + formatNumber(maxVal);
    }
    
    // Inisialisasi progress
    updateProgress();
    
    const filterProdukForm = document.getElementById('filterProdukForm');
    if (filterProdukForm) {
        minRange.addEventListener('change', function () {
            filterProdukForm.submit();
        });
        maxRange.addEventListener('change', function () {
            filterProdukForm.submit();
        });
    }
    
    // Event listener untuk slider min
    minRange.addEventListener('input', function() {
        const minVal = parseInt(minRange.value, 10);
        const maxVal = parseInt(maxRange.value, 10);
        const gap = Math.min(50000, Math.max(10000, maxVal));
        
        if (minVal > maxVal - gap) {
            minRange.value = Math.max(0, maxVal - gap);
        }
        
        updateProgress();
    });
    
    // Event listener untuk slider max
    maxRange.addEventListener('input', function() {
        const minVal = parseInt(minRange.value, 10);
        const maxVal = parseInt(maxRange.value, 10);
        const maxCap = parseInt(maxRange.max, 10) || 1000000;
        const gap = Math.min(50000, Math.max(10000, maxCap - minVal));
        
        if (maxVal < minVal + gap) {
            maxRange.value = Math.min(maxCap, minVal + gap);
        }
        
        updateProgress();
    });
});
</script>

<!-- Modal untuk popup gambar produk kategori -->
<div class="modal fade" id="categoryProductModal" tabindex="-1" aria-labelledby="categoryProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="categoryProductModalLabel">Detail Produk</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body text-center">
                <img id="categoryProductModalImage" src="" alt="" style="max-width: 100%; max-height: 180px; object-fit: contain; border-radius: 8px;">
                <h6 id="categoryProductModalTitle" class="mt-3"></h6>
            </div>
        </div>
    </div>
</div>

<script>
function openCategoryProductModal(imageSrc, title) {
    document.getElementById('categoryProductModalImage').src = imageSrc;
    document.getElementById('categoryProductModalTitle').textContent = title;
    new bootstrap.Modal(document.getElementById('categoryProductModal')).show();
}
</script>

    <!-- AI Chatbot Widget -->
    <?php
    if ($_SERVER['HTTP_HOST'] == 'localhost') {
        echo '<script src="http://localhost:3001/widget.js"></script>';
    }
    ?>

    <script>
        // Script untuk tombol Back to Top
        const backToTopButton = document.getElementById('backToTop');
        
        // Fungsi untuk menampilkan atau menyembunyikan tombol berdasarkan posisi scroll
        window.onscroll = function() {
            if (document.body.scrollTop > 300 || document.documentElement.scrollTop > 300) {
                backToTopButton.classList.add('active');
            } else {
                backToTopButton.classList.remove('active');
            }
        };
        
        // Event listener untuk tombol scroll ke atas
        backToTopButton.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    </script>
</body>
</html>