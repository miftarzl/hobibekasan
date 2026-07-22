<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Debug: Log session
error_log("Session data in struk: " . print_r($_SESSION, true));

// Cek apakah user login atau belum
if (!isset($_SESSION['user']['user_id'])) {
    error_log("User not logged in, redirecting to login.php");
    header("Location: login.php");
    exit();
}

// Debug: Log user ID
error_log("User ID in struk: " . $_SESSION['user']['user_id']);

// Database connection
include '../config/config.php';

// Debug: Log database connection
if ($conn) {
    error_log("Database connection successful in struk");
} else {
    error_log("Database connection failed in struk");
}

// Get order ID from URL
$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : null;

// If no order_id provided, get the most recent order
if (!$order_id) {
    $recent_order_query = "SELECT id FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
    $recent_stmt = $conn->prepare($recent_order_query);
    $recent_stmt->bind_param("i", $_SESSION['user']['user_id']);
    $recent_stmt->execute();
    $recent_result = $recent_stmt->get_result();
    $recent_order = $recent_result->fetch_assoc();
    
    if ($recent_order) {
        $order_id = $recent_order['id'];
    } else {
        $_SESSION['error_message'] = "Belum ada pesanan. Silakan lakukan checkout terlebih dahulu.";
        header("Location: kategori.php");
        exit();
    }
}

// Get order details
$order_query = "SELECT o.*, u.username, u.email, u.address as user_address 
               FROM orders o 
               JOIN users u ON o.user_id = u.id 
               WHERE o.id = ? AND o.user_id = ?";
$order_stmt = $conn->prepare($order_query);
$order_stmt->bind_param("ii", $order_id, $_SESSION['user']['user_id']);
$order_stmt->execute();
$order_result = $order_stmt->get_result();
$order = $order_result->fetch_assoc();

if (!$order) {
    $_SESSION['error_message'] = "Order tidak ditemukan.";
    header("Location: riwayat_pesanan.php");
    exit();
}

// Get order items
$items_query = "SELECT oi.*, p.name, p.image 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.product_id 
                WHERE oi.order_id = ?";
$items_stmt = $conn->prepare($items_query);
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
$order_items = [];

$total_items = 0;
while ($item = $items_result->fetch_assoc()) {
    $order_items[] = $item;
    $total_items += $item['quantity'];
}

// Get payment method details
$payment_method_name = '';
$payment_info = '';
switch ($order['payment_method']) {
    case 'transfer_bca':
        $payment_method_name = 'Transfer Bank BCA';
        $payment_info = 'BCA: 1234567890 a.n hobiBekasin';
        break;
    case 'transfer_mandiri':
        $payment_method_name = 'Transfer Bank Mandiri';
        $payment_info = 'Mandiri: 0987654321 a.n hobiBekasin';
        break;
    case 'transfer_bni':
        $payment_method_name = 'Transfer Bank BNI';
        $payment_info = 'BNI: 1122334455 a.n hobiBekasin';
        break;
    case 'transfer_bri':
        $payment_method_name = 'Transfer Bank BRI';
        $payment_info = 'BRI: 5544332211 a.n hobiBekasin';
        break;
    case 'gopay':
        $payment_method_name = 'GoPay';
        $payment_info = 'GoPay: 081234567890 a.n hobiBekasin';
        break;
    case 'ovo':
        $payment_method_name = 'OVO';
        $payment_info = 'OVO: 089876543210 a.n hobiBekasin';
        break;
    case 'dana':
        $payment_method_name = 'DANA';
        $payment_info = 'DANA: 085678901234 a.n hobiBekasin';
        break;
    case 'shopeepay':
        $payment_method_name = 'ShopeePay';
        $payment_info = 'ShopeePay: 08123456789 a.n hobiBekasin';
        break;
    case 'qris':
        $payment_method_name = 'QRIS';
        $payment_info = 'QRIS Payment a.n hobiBekasin';
        break;
    case 'cod':
        $payment_method_name = 'Cash on Delivery (COD)';
        $payment_info = 'Pembayaran tunai saat pengiriman';
        break;
    default:
        $payment_method_name = $order['payment_method'];
        $payment_info = 'Metode pembayaran lainnya';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pembelian - hobiBekasan</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
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
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        
        .struk-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .struk-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
        }
        
        .struk-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }
        
        .struk-title {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }
        
        .struk-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        .struk-body {
            padding: 2rem;
        }
        
        .info-section {
            background: var(--light-color);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-left: 4px solid var(--primary-color);
        }
        
        .info-title {
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-weight: 600;
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 4px;
        }
        
        .info-value {
            color: var(--dark-color);
            font-weight: 500;
        }
        
        .items-section {
            margin-bottom: 2rem;
        }
        
        .items-title {
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .item-row {
            display: flex;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #e5e7eb;
            gap: 1rem;
        }
        
        .item-row:last-child {
            border-bottom: none;
        }
        
        .item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-name {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 4px;
        }
        
        .item-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.9rem;
            color: #6b7280;
        }
        
        .item-quantity {
            background: var(--light-color);
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 600;
        }
        
        .item-price {
            color: var(--success-color);
            font-weight: 600;
        }
        
        .item-subtotal {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 1.1rem;
        }
        
        .summary-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            padding: 1.5rem;
            border: 2px solid var(--primary-color);
        }
        
        .summary-title {
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 1rem;
            text-align: center;
            font-size: 1.2rem;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .summary-row:last-child {
            border-bottom: none;
            padding-top: 1rem;
            margin-top: 0.5rem;
            border-top: 2px solid var(--primary-color);
        }
        
        .summary-label {
            font-weight: 600;
            color: #6b7280;
        }
        
        .summary-value {
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .summary-total {
            font-weight: 800;
            color: var(--primary-color);
            font-size: 1.3rem;
        }
        
        .actions-section {
            padding: 2rem;
            text-align: center;
            background: var(--light-color);
        }
        
        .btn-print {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            margin: 0 0.5rem;
        }
        
        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3);
        }
        
        .btn-home {
            background: var(--success-color);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            margin: 0 0.5rem;
        }
        
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
        }
        
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 4rem;
            color: rgba(79, 70, 229, 0.1);
            font-weight: 800;
            pointer-events: none;
            z-index: 0;
        }
        
        @media print {
            body {
                padding: 0;
                background: white;
            }
            
            .struk-container {
                box-shadow: none;
                border: 1px solid #dee2e6;
            }
            
            .actions-section {
                display: none;
            }
            
            .watermark {
                display: block;
            }
        }
        
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .item-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .item-image {
                width: 50px;
                height: 50px;
            }
        }
    </style>
</head>
<body>
    <div class="struk-container">
        <div class="watermark">HOBI BEKASAN</div>
        
        <!-- Header -->
        <div class="struk-header">
            <h1 class="struk-title">
                <i class="fas fa-receipt"></i>
                Struk Pembelian
            </h1>
            <p class="struk-subtitle">hobiBekasan - Toko Online Terpercaya</p>
        </div>
        
        <!-- Body -->
        <div class="struk-body">
            <!-- Order Information -->
            <div class="info-section">
                <h3 class="info-title">
                    <i class="fas fa-info-circle"></i>
                    Informasi Order
                </h3>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">No. Order:</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['order_number']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Tanggal:</span>
                        <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Status:</span>
                        <span class="info-value">
                            <span class="badge bg-warning text-dark">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Total Item:</span>
                        <span class="info-value"><?php echo $total_items; ?> item(s)</span>
                    </div>
                </div>
            </div>
            
            <!-- Customer Information -->
            <div class="info-section">
                <h3 class="info-title">
                    <i class="fas fa-user"></i>
                    Informasi Pembeli
                </h3>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Nama:</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['username']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['email']); ?></span>
                    </div>
                    <div class="info-item" style="grid-column: span 2;">
                        <span class="info-label">Alamat Pengiriman:</span>
                        <span class="info-value"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Payment Information -->
            <div class="info-section">
                <h3 class="info-title">
                    <i class="fas fa-credit-card"></i>
                    Informasi Pembayaran
                </h3>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Metode:</span>
                        <span class="info-value"><?php echo $payment_method_name; ?></span>
                    </div>
                    <div class="info-item" style="grid-column: span 2;">
                        <span class="info-label">Detail:</span>
                        <span class="info-value"><?php echo $payment_info; ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Order Items -->
            <div class="items-section">
                <h3 class="items-title">
                    <i class="fas fa-shopping-bag"></i>
                    Detail Pesanan
                </h3>
                <?php foreach ($order_items as $item): ?>
                    <div class="item-row">
                        <img src="../assets/img/products/<?php echo htmlspecialchars($item['image']); ?>" 
                             alt="<?php echo htmlspecialchars($item['name']); ?>" 
                             class="item-image">
                        <div class="item-details">
                            <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div class="item-meta">
                                <span class="item-quantity">Qty: <?php echo $item['quantity']; ?></span>
                                <span class="item-price">Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></span>
                            </div>
                        </div>
                        <div class="item-subtotal">
                            Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Summary -->
            <div class="summary-section">
                <h3 class="summary-title">
                    <i class="fas fa-calculator"></i>
                    Ringkasan Pembayaran
                </h3>
                <div class="summary-row">
                    <span class="summary-label">Subtotal Produk:</span>
                    <span class="summary-value">Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Biaya Pengiriman:</span>
                    <span class="summary-value">Rp <?php echo number_format($order['shipping_cost'], 0, ',', '.'); ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Biaya Layanan:</span>
                    <span class="summary-value">Rp <?php echo number_format($order['service_fee'], 0, ',', '.'); ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label summary-total">Total Pembayaran:</span>
                    <span class="summary-value summary-total">Rp <?php echo number_format($order['total_amount'] + $order['shipping_cost'] + $order['service_fee'], 0, ',', '.'); ?></span>
                </div>
            </div>
            
            <?php if ($order['notes']): ?>
                <div class="info-section">
                    <h3 class="info-title">
                        <i class="fas fa-sticky-note"></i>
                        Catatan Pesanan
                    </h3>
                    <div class="info-value">
                        <?php echo nl2br(htmlspecialchars($order['notes'])); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Actions -->
        <div class="actions-section">
            <button onclick="window.print()" class="btn-print">
                <i class="fas fa-print"></i>
                Cetak Struk
            </button>
            <button onclick="window.location.reload()" class="btn-home">
                <i class="fas fa-sync"></i>
                Refresh
            </button>
            <a href="index.php" class="btn-home">
                <i class="fas fa-home"></i>
                Kembali ke Beranda
            </a>
            <a href="riwayat_pesanan.php" class="btn-home">
                <i class="fas fa-history"></i>
                Riwayat Pesanan
            </a>
        </div>
    </div>
    
    <!-- Success/Error Messages -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Check for success message
        if (window.sessionStorage.getItem('order_success')) {
            const successDiv = document.createElement('div');
            successDiv.className = 'alert alert-success alert-dismissible fade show position-fixed';
            successDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            successDiv.innerHTML = `
                <i class="fas fa-check-circle"></i>
                <strong>Success!</strong> Pesanan berhasil dibuat.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(successDiv);
            
            // Remove after 5 seconds
            setTimeout(() => {
                successDiv.remove();
                sessionStorage.removeItem('order_success');
            }, 5000);
        }
        
        // Check for error message
        if (window.sessionStorage.getItem('order_error')) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'alert alert-danger alert-dismissible fade show position-fixed';
            errorDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            errorDiv.innerHTML = `
                <i class="fas fa-exclamation-circle"></i>
                <strong>Error!</strong> ${sessionStorage.getItem('order_error')}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(errorDiv);
            
            // Remove after 5 seconds
            setTimeout(() => {
                errorDiv.remove();
                sessionStorage.removeItem('order_error');
            }, 5000);
        }
        
        // Set success message if redirected from payment
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('success') === '1') {
            sessionStorage.setItem('order_success', 'true');
        }
    });
    </script>
</body>
</html>
