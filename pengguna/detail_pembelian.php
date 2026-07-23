<?php
// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect jika user belum login
if (!isset($_SESSION['user'])) {
    $_SESSION['error_message'] = "Anda harus login terlebih dahulu untuk melihat detail pembelian.";
    header("Location: login.php");
    exit();
}

// Database connection
require_once '../config/config.php';

// Ambil user_id dari session
$user_id = $_SESSION['user']['user_id'];

// Periksa apakah parameter ID ada
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "ID transaksi tidak valid.";
    header("Location: riwayat_pembelian.php");
    exit();
}

$transaction_id = $_GET['id'];

// Query untuk mengambil detail transaksi
$query = "SELECT t.*, o.city_name, o.shipping_cost
          FROM transactions t
          LEFT JOIN ongkir o ON t.ongkir_id = o.ongkir_id
          WHERE t.transaction_id = ? AND t.user_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $transaction_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Jika transaksi tidak ditemukan
if ($result->num_rows == 0) {
    $_SESSION['error_message'] = "Transaksi tidak ditemukan atau Anda tidak memiliki akses.";
    header("Location: riwayat_pembelian.php");
    exit();
}

$transaction = $result->fetch_assoc();

// Query untuk mengambil detail produk yang dibeli
$query_products = "SELECT pd.*, p.name as product_name, p.image as product_image
                  FROM purchase_details pd
                  JOIN products p ON pd.product_id = p.product_id
                  WHERE pd.transaction_id = ?";

$stmt_products = $conn->prepare($query_products);
$stmt_products->bind_param("i", $transaction_id);
$stmt_products->execute();
$products_result = $stmt_products->get_result();

// Proses upload bukti pembayaran jika ada
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["payment_proof"]) && $transaction['status'] == 'pending') {
    $target_dir = "../uploads/payments/";
    
    // Buat direktori jika belum ada
    if (!is_dir($target_dir)) {
        @mkdir($target_dir, 0777, true);
    }
    @chmod($target_dir, 0777);
    
    // Dapatkan ekstensi file
    $imageFileType = strtolower(pathinfo($_FILES["payment_proof"]["name"], PATHINFO_EXTENSION));
    
    // Generate nama file unik
    $unique_filename = "payment_" . $transaction['transaction_unique_id'] . "_" . time() . "." . $imageFileType;
    $target_file = $target_dir . $unique_filename;
    
    // Cek apakah file adalah gambar
    $check = getimagesize($_FILES["payment_proof"]["tmp_name"]);
    if ($check === false) {
        $_SESSION['error_message'] = "File yang diunggah bukan gambar.";
    } else {
        // Batasi ukuran file (2MB)
        if ($_FILES["payment_proof"]["size"] > 2000000) {
            $_SESSION['error_message'] = "Ukuran file terlalu besar. Maksimal 2MB.";
        } 
        // Hanya izinkan format JPG, JPEG, PNG & GIF
        else if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
            $_SESSION['error_message'] = "Hanya file JPG, JPEG, PNG & GIF yang diizinkan.";
        } 
        // Upload file
        else if (@move_uploaded_file($_FILES["payment_proof"]["tmp_name"], $target_file)) {
            // Update database dengan nama file
            $update_query = "UPDATE transactions SET payment_proof = ? WHERE transaction_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("si", $unique_filename, $transaction_id);
            
            if ($update_stmt->execute()) {
                $_SESSION['success_message'] = "Bukti pembayaran berhasil diunggah.";
                // Redirect untuk refresh halaman
                header("Location: detail_pembelian.php?id=" . $transaction_id);
                exit();
            } else {
                $_SESSION['error_message'] = "Terjadi kesalahan saat menyimpan data bukti pembayaran.";
            }
        } else {
            $_SESSION['error_message'] = "Terjadi kesalahan saat mengunggah file.";
        }
    }
}

// Include navbar
include '../assets/navbar.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pembelian - itsyourthriftt.id</title>
    <!-- Bootstrap CSS & Font Awesome sudah termasuk di navbar.php -->
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Arial', sans-serif;
        }
        
        .section-header {
            color: #003366;
            position: relative;
            padding-bottom: 10px;
            margin-bottom: 30px;
            font-weight: 700;
        }
        
        .section-header:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 60px;
            height: 3px;
            background: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%);
        }
        
        .page-content {
            min-height: 80vh;
            padding: 40px 0;
        }
        
        .card {
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
            border: none;
        }
        
        .card-header {
            background: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%);
            color: white;
            border: none;
            padding: 15px 20px;
            font-weight: 600;
        }
        
        .animated-gradient {
            background: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%);
            background-size: 200% 200%;
            animation: gradient-shift 5s ease infinite;
        }
        
        @keyframes gradient-shift {
            0% {background-position: 0% 50%}
            50% {background-position: 100% 50%}
            100% {background-position: 0% 50%}
        }
        
        .info-section {
            padding: 20px;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
        }
        
        .info-label {
            font-weight: 600;
            color: #495057;
            min-width: 140px;
        }
        
        .info-value {
            color: #212529;
        }
        
        .info-item {
            margin-bottom: 15px;
            display: flex;
            flex-wrap: wrap;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-pending {
            background-color: #ffeeba;
            color: #856404;
        }
        
        .status-paid {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-shipped {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .status-completed {
            background-color: #c3e6cb;
            color: #155724;
        }
        
        .status-canceled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .product-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .product-item:last-child {
            border-bottom: none;
        }
        
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 15px;
        }
        
        .product-details {
            flex-grow: 1;
        }
        
        .product-name {
            font-weight: 600;
            color: #212529;
            margin-bottom: 5px;
        }
        
        .product-price {
            color: #6c757d;
            font-size: 14px;
        }
        
        .product-quantity {
            background-color: #e9ecef;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 14px;
            margin-left: 8px;
        }
        
        .product-total {
            font-weight: 600;
            color: #003366;
        }
        
        .summary-section {
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 12px;
            margin-top: 20px;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .summary-total {
            font-weight: 700;
            color: #003366;
            border-top: 2px solid #dee2e6;
            padding-top: 10px;
            margin-top: 10px;
        }
        
        .btn-action {
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
            margin-top: 15px;
        }
        
        .btn-action:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.15);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%);
            border: none;
            color: white;
        }
        
        .btn-back {
            background-color: #6c757d;
            color: white;
            border: none;
        }
        
        .upload-section {
            padding: 20px;
            background-color: #f0f8ff;
            border-radius: 12px;
            border: 2px dashed #61b2ff;
            margin-top: 25px;
            text-align: center;
        }
        
        .upload-icon {
            font-size: 40px;
            color: #1e7fd6;
            margin-bottom: 15px;
        }
        
        .upload-title {
            color: #003366;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .form-control-file {
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ced4da;
            width: 100%;
            margin-bottom: 15px;
        }
        
        .preview-container {
            width: 100%;
            max-width: 300px;
            margin: 15px auto;
            display: none;
        }
        
        #imagePreview {
            width: 100%;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .payment-proof-container {
            text-align: center;
            margin-top: 20px;
            padding: 15px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .payment-proof-image {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin-top: 10px;
        }
        
        .tracking-number {
            padding: 5px 10px;
            background-color: #e9ecef;
            border-radius: 4px;
            color: #495057;
            font-family: monospace;
            font-size: 14px;
            letter-spacing: 1px;
        }
        
        .address-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
        }
        
        @media (max-width: 768px) {
            .info-item {
                flex-direction: column;
            }
            
            .info-label {
                margin-bottom: 5px;
            }
            
            .product-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .product-image {
                margin-right: 0;
                margin-bottom: 15px;
                width: 100%;
                height: auto;
            }
            
            .product-details {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="page-content">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="section-header mb-0">Detail Pembelian</h2>
            <a href="riwayat_pembelian.php" class="btn btn-action btn-back">
                <i class="fas fa-arrow-left me-2"></i> Kembali
            </a>
        </div>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_SESSION['success_message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['error_message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-8">
                <!-- Informasi Transaksi -->
                <div class="card">
                    <div class="card-header animated-gradient d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-shopping-bag me-2"></i>
                            No. Transaksi: <?= $transaction['transaction_unique_id'] ?>
                        </div>
                        <div>
                            <?php 
                            $statusClass = "status-" . strtolower($transaction['status']);
                            $statusText = "";
                            
                            switch($transaction['status']) {
                                case 'pending':
                                    $statusText = "Menunggu Pembayaran";
                                    break;
                                case 'paid':
                                    $statusText = "Dibayar";
                                    break;
                                case 'shipped':
                                    $statusText = "Dikirim";
                                    break;
                                case 'completed':
                                    $statusText = "Selesai";
                                    break;
                                case 'canceled':
                                    $statusText = "Dibatalkan";
                                    break;
                                default:
                                    $statusText = ucfirst($transaction['status']);
                            }
                            ?>
                            <span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="info-item">
                            <span class="info-label">
                                <i class="far fa-calendar-alt me-2"></i>Tanggal Pembelian:
                            </span>
                            <span class="info-value">
                                <?= date('d F Y, H:i', strtotime($transaction['created_at'])) ?> WIB
                            </span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">
                                <i class="fas fa-user me-2"></i>Nama Penerima:
                            </span>
                            <span class="info-value">
                                <?= htmlspecialchars($transaction['customer_name']) ?>
                            </span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">
                                <i class="fas fa-credit-card me-2"></i>Metode Pembayaran:
                            </span>
                            <span class="info-value">
                                <?= ucfirst($transaction['payment_method']) ?>
                            </span>
                        </div>
                        
                        <?php if (!empty($transaction['tracking_number']) && ($transaction['status'] == 'shipped' || $transaction['status'] == 'completed')): ?>
                        <div class="info-item">
                            <span class="info-label">
                                <i class="fas fa-barcode me-2"></i>No. Resi:
                            </span>
                            <span class="info-value">
                                <span class="tracking-number"><?= $transaction['tracking_number'] ?></span>
                            </span>
                        </div>
                        <?php elseif ($transaction['status'] != 'canceled' && $transaction['status'] != 'pending'): ?>
                        <div class="info-item">
                            <span class="info-label">
                                <i class="fas fa-barcode me-2"></i>No. Resi:
                            </span>
                            <span class="info-value">
                                <span class="badge bg-secondary">Belum tersedia</span>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="info-item">
                            <span class="info-label">
                                <i class="fas fa-map-marker-alt me-2"></i>Kota Pengiriman:
                            </span>
                            <span class="info-value">
                                <?= htmlspecialchars($transaction['city_name'] ?? 'Tidak tersedia') ?>
                            </span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">
                                <i class="fas fa-home me-2"></i>Alamat Pengiriman:
                            </span>
                            <span class="info-value">
                                <?php
                                // Ambil alamat dari tabel users berdasarkan user_id
                                $query_address = "SELECT address FROM users WHERE user_id = ?";
                                $stmt_address = $conn->prepare($query_address);
                                $stmt_address->bind_param("i", $user_id);
                                $stmt_address->execute();
                                $address_result = $stmt_address->get_result();
                                $address = $address_result->fetch_assoc();
                                ?>
                                <div class="address-section">
                                    <?= nl2br(htmlspecialchars($address['address'])) ?>
                                </div>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Daftar Produk -->
                <div class="card mt-4">
                    <div class="card-header animated-gradient">
                        <i class="fas fa-box me-2"></i>Daftar Produk
                    </div>
                    <div class="card-body p-0">
                        <?php if ($products_result->num_rows > 0): ?>
                            <?php while ($product = $products_result->fetch_assoc()): ?>
                                <div class="product-item">
                                    <img src="../assets/img/<?= $product['product_image'] ?>" alt="<?= $product['product_name'] ?>" class="product-image">
                                    <div class="product-details">
                                        <div class="product-name">
                                            <?= htmlspecialchars($product['product_name']) ?>
                                            <span class="product-quantity">x<?= $product['quantity'] ?></span>
                                        </div>
                                        <div class="product-price">
                                            Rp <?= number_format($product['price'], 0, ',', '.') ?> / item
                                        </div>
                                        <div class="product-total mt-2">
                                            Total: Rp <?= number_format($product['total_price'], 0, ',', '.') ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="p-4 text-center text-muted">
                                <i class="fas fa-box-open fa-3x mb-3"></i>
                                <p>Tidak ada produk ditemukan untuk transaksi ini.</p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="summary-section">
                            <div class="summary-item">
                                <span>Subtotal Produk</span>
                                <span>Rp <?= number_format($transaction['total_price'] - $transaction['shipping_cost'], 0, ',', '.') ?></span>
                            </div>
                            <div class="summary-item">
                                <span>Biaya Pengiriman</span>
                                <span>Rp <?= number_format($transaction['shipping_cost'], 0, ',', '.') ?></span>
                            </div>
                            <div class="summary-item summary-total">
                                <span>Total Pembayaran</span>
                                <span>Rp <?= number_format($transaction['total_price'], 0, ',', '.') ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Status Pembayaran -->
                <div class="info-section">
                    <h5 class="mb-3">Status Pembayaran</h5>
                    <?php if ($transaction['status'] == 'pending'): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Menunggu Pembayaran
                        </div>
                        <p class="text-muted small">
                            Silahkan lakukan pembayaran sesuai dengan total yang tertera.
                        </p>
                    <?php elseif ($transaction['status'] == 'paid'): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-check-circle me-2"></i>
                            Pembayaran Terverifikasi
                        </div>
                        <p class="text-muted small">
                            Pembayaran telah dikonfirmasi. Pesanan Anda sedang diproses.
                        </p>
                    <?php elseif ($transaction['status'] == 'shipped'): ?>
                        <div class="alert alert-primary">
                            <i class="fas fa-shipping-fast me-2"></i>
                            Pesanan Dikirim
                        </div>
                        <p class="text-muted small">
                            Pesanan Anda dalam perjalanan. Silahkan cek nomor resi untuk informasi pengiriman.
                        </p>
                    <?php elseif ($transaction['status'] == 'completed'): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            Pesanan Selesai
                        </div>
                        <p class="text-muted small">
                            Pesanan Anda telah selesai. Terima kasih atas pembelian Anda!
                        </p>
                    <?php elseif ($transaction['status'] == 'canceled'): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-times-circle me-2"></i>
                            Pesanan Dibatalkan
                        </div>
                        <p class="text-muted small">
                            Pesanan ini telah dibatalkan.
                        </p>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>
    </div>
</div>

<?php include '../assets/footer.php'; ?>

<!-- Script untuk menampilkan alert hanya dalam beberapa detik -->
<script>
    // Auto hide alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
        // Image preview when uploading
        const paymentProofInput = document.getElementById('payment_proof');
        const imagePreview = document.getElementById('imagePreview');
        const previewContainer = document.getElementById('previewContainer');
        
        if (paymentProofInput) {
            paymentProofInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imagePreview.src = e.target.result;
                        previewContainer.style.display = 'block';
                    }
                    reader.readAsDataURL(file);
                } else {
                    previewContainer.style.display = 'none';
                }
            });
        }
    });
</script>

</body>
</html>