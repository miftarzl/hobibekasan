<?php
session_start();

// Simple session check - redirect jika tidak login sebagai admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../pengguna/login.php");
    exit();
}

// Database connection
require '../config/config.php';

// Get order ID from URL
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$order_id) {
    header("Location: pembelian.php");
    exit();
}

// Handle updating order status
if (isset($_POST['update_status'])) {
    $id = $_POST['order_id'];
    $status = $_POST['status'];
    $notes = $_POST['notes'] ?? '';
    
    // Update order status and notes
    $update_query = "UPDATE orders SET status = ?, notes = ?, updated_at = NOW() WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("ssi", $status, $notes, $id);
    $update_stmt->execute();
    
    // Set success message
    $_SESSION['success_message'] = "Status pesanan berhasil diperbarui!";
    
    header("Location: view_order.php?id=$id");
    exit();
}

// Get order details with user info
$order_query = "SELECT o.*, u.username, u.email, u.address as user_address
               FROM orders o 
               LEFT JOIN users u ON o.user_id = u.id 
               WHERE o.id = ?";
$order_stmt = $conn->prepare($order_query);
$order_stmt->bind_param("i", $order_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result();
$order = $order_result->fetch_assoc();

if (!$order) {
    header("Location: pembelian.php");
    exit();
}

// Get order items with product details
$items_query = "SELECT oi.*, p.name, p.image, p.description
               FROM order_items oi 
               LEFT JOIN products p ON oi.product_id = p.product_id 
               WHERE oi.order_id = ?";
$items_stmt = $conn->prepare($items_query);
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();

// Calculate totals
$total_items = 0;
$total_amount = 0;
$order_items = [];

while ($item = $items_result->fetch_assoc()) {
    $total_items += $item['quantity'];
    $total_amount += $item['subtotal'];
    $order_items[] = $item;
}

// Payment method information
$payment_methods = [
    'transfer_bank' => ['name' => 'Transfer Bank', 'info' => 'Silakan transfer ke rekening kami'],
    'ewallet' => ['name' => 'E-Wallet', 'info' => 'Scan QR code untuk pembayaran'],
    'cod' => ['name' => 'Cash on Delivery', 'info' => 'Pembayaran saat barang diterima'],
    'credit_card' => ['name' => 'Kartu Kredit', 'info' => 'Pembayaran dengan kartu kredit']
];

$payment_method_name = $payment_methods[$order['payment_method']]['name'] ?? $order['payment_method'];
$payment_info = $payment_methods[$order['payment_method']]['info'] ?? '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan - hobiBekasan Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #4f46e5;
            --secondary-color: #7c3aed;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
            --dark-color: #1f2937;
            --light-color: #f8f9fa;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #ffffff;
            min-height: 100vh;
            color: var(--dark-color);
            margin: 0;
            padding: 0;
        }
        
        .main-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar di kiri */
        .sidebar {
            width: 280px;
            height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            transition: all 0.3s ease;
            box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .sidebar-header h3 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .menu-item {
            display: block;
            padding: 15px 20px;
            color: #ffffff;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .menu-item:hover {
            background: #667eea;
            color: #fff;
            border-left-color: #fff;
            padding-left: 25px;
        }
        
        .menu-item.active {
            background: #764ba2;
            color: #fff;
            border-left-color: #fff;
            padding-left: 25px;
        }
        
        .menu-item i {
            width: 20px;
            margin-right: 10px;
            text-align: center;
        }
        
        /* Content area di kanan sidebar */
        .content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
            background: #ffffff;
            min-height: 100vh;
            overflow-y: auto;
        }
        
        .page-container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .page-header {
            background: #ffffff;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 0;
        }
        
        .order-details-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .detail-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }
        
        .detail-card h3 {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: #6b7280;
        }
        
        .detail-value {
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: #fed7aa;
            color: #92400e;
        }
        
        .status-processing {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .items-table {
            background: #ffffff;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            margin-bottom: 2rem;
        }
        
        .items-table h3 {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .item-row {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        
        .item-row:hover {
            background: rgba(79, 70, 229, 0.05);
        }
        
        .item-row:last-child {
            border-bottom: none;
        }
        
        .item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 10px;
            margin-right: 1rem;
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-name {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.25rem;
        }
        
        .item-quantity {
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .item-price {
            font-weight: 600;
            color: var(--success-color);
            margin-right: 1rem;
        }
        
        .item-subtotal {
            font-weight: 700;
            color: var(--primary-color);
            min-width: 120px;
            text-align: right;
        }
        
        .total-section {
            background: #ffffff;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            margin-bottom: 2rem;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        
        .total-row.final {
            font-weight: 700;
            font-size: 1.3rem;
            color: var(--primary-color);
            border-top: 2px solid #e5e7eb;
            padding-top: 1rem;
            margin-top: 1rem;
        }
        
        .status-update-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }
        
        .status-update-card h3 {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .form-select, .form-textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .btn-update {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 12px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-update:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(79, 70, 229, 0.3);
        }
        
        .btn-back {
            background: #6b7280;
            color: white;
            border: none;
            border-radius: 12px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-back:hover {
            background: #4b5563;
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            border: none;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .order-details-container {
                grid-template-columns: 1fr;
            }
            
            .page-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .item-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .item-image {
                margin-right: 0;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Sidebar Admin -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-crown"></i> hobiBekasan Admin</h3>
            </div>
            <div class="sidebar-menu">
                <a href="admin_dashboard.php" class="menu-item">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="produk.php" class="menu-item">
                    <i class="fas fa-box"></i> Produk
                </a>
                <a href="kategori.php" class="menu-item">
                    <i class="fas fa-tags"></i> Kategori
                </a>
                <a href="pembelian.php" class="menu-item">
                    <i class="fas fa-shopping-cart"></i> Pembelian
                </a>
                <a href="pelanggan.php" class="menu-item">
                    <i class="fas fa-users"></i> Pelanggan
                </a>
                <a href="laporan.php" class="menu-item">
                    <i class="fas fa-file-alt"></i> Laporan
                </a>
                <a href="rating.php" class="menu-item">
                    <i class="fas fa-star"></i> Rating
                </a>
                                <a href="../pengguna/logout.php" class="menu-item" style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content">
            <div class="page-container">
                <!-- Page Header -->
                <div class="page-header">
                    <div>
                        <h1 class="page-title">
                            <i class="fas fa-receipt"></i> Detail Pesanan
                        </h1>
                        <p class="text-muted">Order #<?php echo htmlspecialchars($order['order_number']); ?></p>
                    </div>
                    <a href="pembelian.php" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                    </div>
                <?php endif; ?>

                <!-- Order Details Grid -->
                <div class="order-details-container">
                    <!-- Customer Information -->
                    <div class="detail-card">
                        <h3><i class="fas fa-user"></i> Informasi Pelanggan</h3>
                        <div class="detail-row">
                            <span class="detail-label">Nama</span>
                            <span class="detail-value"><?php echo htmlspecialchars($order['username']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Email</span>
                            <span class="detail-value"><?php echo htmlspecialchars($order['email']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Alamat</span>
                            <span class="detail-value"><?php echo htmlspecialchars($order['user_address'] ?? '-'); ?></span>
                        </div>
                    </div>

                    <!-- Order Information -->
                    <div class="detail-card">
                        <h3><i class="fas fa-shopping-cart"></i> Informasi Pesanan</h3>
                        <div class="detail-row">
                            <span class="detail-label">Nomor Order</span>
                            <span class="detail-value">#<?php echo htmlspecialchars($order['order_number']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Tanggal</span>
                            <span class="detail-value"><?php echo date('d M Y, H:i', strtotime($order['created_at'])); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Status</span>
                            <span class="detail-value">
                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                    <?php 
                                    $status_text = [
                                        'pending' => 'Menunggu',
                                        'processing' => 'Diproses',
                                        'completed' => 'Selesai',
                                        'cancelled' => 'Dibatalkan'
                                    ];
                                    echo $status_text[$order['status']] ?? ucfirst($order['status']);
                                    ?>
                                </span>
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Metode Pembayaran</span>
                            <span class="detail-value"><?php echo htmlspecialchars($payment_method_name); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="items-table">
                    <h3><i class="fas fa-shopping-bag"></i> Detail Produk</h3>
                    <?php foreach ($order_items as $item): ?>
                        <div class="item-row">
                            <img src="../assets/img/products/<?php echo htmlspecialchars($item['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                 class="item-image">
                            <div class="item-details">
                                <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="item-quantity">Quantity: <?php echo $item['quantity']; ?></div>
                            </div>
                            <div class="item-price">Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></div>
                            <div class="item-subtotal">Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Shipping Information -->
                <div class="detail-card">
                    <h3><i class="fas fa-truck"></i> Informasi Pengiriman</h3>
                    <div class="detail-row">
                        <span class="detail-label">Alamat Pengiriman</span>
                        <span class="detail-value"><?php echo htmlspecialchars($order['shipping_address']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Biaya Pengiriman</span>
                        <span class="detail-value">Rp <?php echo number_format($order['shipping_cost'], 0, ',', '.'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Biaya Layanan</span>
                        <span class="detail-value">Rp <?php echo number_format($order['service_fee'], 0, ',', '.'); ?></span>
                    </div>
                </div>

                <!-- Total Summary -->
                <div class="total-section">
                    <h3><i class="fas fa-calculator"></i> Ringkasan Pembayaran</h3>
                    <div class="total-row">
                        <span>Subtotal Produk</span>
                        <span>Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></span>
                    </div>
                    <div class="total-row">
                        <span>Biaya Pengiriman</span>
                        <span>Rp <?php echo number_format($order['shipping_cost'], 0, ',', '.'); ?></span>
                    </div>
                    <div class="total-row">
                        <span>Biaya Layanan</span>
                        <span>Rp <?php echo number_format($order['service_fee'], 0, ',', '.'); ?></span>
                    </div>
                    <div class="total-row final">
                        <span>Total Pembayaran</span>
                        <span>Rp <?php echo number_format($order['total_amount'] + $order['shipping_cost'] + $order['service_fee'], 0, ',', '.'); ?></span>
                    </div>
                </div>

                <!-- Status Update -->
                <div class="status-update-card">
                    <h3><i class="fas fa-edit"></i> Update Status Pesanan</h3>
                    <form method="POST">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        
                        <div class="form-group">
                            <label class="form-label">Status Pesanan</label>
                            <select name="status" class="form-select" required>
                                <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Menunggu</option>
                                <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Diproses</option>
                                <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Selesai</option>
                                <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Dibatalkan</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Catatan (Opsional)</label>
                            <textarea name="notes" class="form-textarea" placeholder="Tambahkan catatan untuk update status ini..."><?php echo htmlspecialchars($order['notes'] ?? ''); ?></textarea>
                        </div>
                        
                        <button type="submit" name="update_status" class="btn-update">
                            <i class="fas fa-save"></i> Update Status
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
