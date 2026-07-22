<?php
session_start();

// Cek apakah user login atau belum
if (!isset($_SESSION['user']['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
include '../config/config.php';

// Ambil user ID
$user_id = $_SESSION['user']['user_id'];

// Pagination settings
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filter settings
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search_filter = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$where_conditions = ["o.user_id = ?"];
$params = [$user_id];
$types = 'i';

// Status filter
if (!empty($status_filter)) {
    $where_conditions[] = "o.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

// Search filter
if (!empty($search_filter)) {
    $where_conditions[] = "(o.order_number LIKE ? OR o.shipping_address LIKE ?)";
    $search_param = "%$search_filter%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

// Build WHERE clause
$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Count total records
$count_query = "SELECT COUNT(*) as total FROM orders o $where_clause";
$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// Get orders with items
$orders_query = "SELECT o.*, 
                COUNT(oi.id) as item_count,
                SUM(oi.quantity * oi.price) as calculated_total
                FROM orders o 
                LEFT JOIN order_items oi ON o.id = oi.order_id 
                $where_clause
                GROUP BY o.id 
                ORDER BY o.created_at DESC 
                LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$orders_stmt = $conn->prepare($orders_query);
$orders_stmt->bind_param($types, ...$params);
$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pesanan - hobiBekasan</title>
    
    <!-- Bootstrap 5 CSS -->
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
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }
        
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 40px 20px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 20px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }
        
        .page-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: white;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        
        .page-subtitle {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.9);
            position: relative;
            z-index: 1;
        }
        
        .filter-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 50px;
            box-shadow: 0px 15px 35px rgba(0, 0, 0, 0.08);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            border: none;
        }

        .filter-section:before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 20px 0 0 20px;
        }

        .filter-section:hover {
            transform: translateY(-10px);
            box-shadow: 0px 25px 50px rgba(79, 70, 229, 0.15);
        }

        .filter-form {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            align-items: end;
        }

        .filter-group {
            flex: 0 0 auto;
            min-width: 200px;
            max-width: 300px;
        }
        
        .filter-label {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 700;
            color: var(--dark-color);
            font-size: 0.95rem;
        }

        .filter-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .filter-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .btn-filter {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 12px;
            padding: 0.75rem 2rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0px 8px 20px rgba(79, 70, 229, 0.35);
        }

        .btn-filter:hover {
            transform: translateY(-4px);
            box-shadow: 0px 12px 25px rgba(79, 70, 229, 0.45);
        }
        
        .orders-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0px 15px 35px rgba(0, 0, 0, 0.08);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            border: none;
        }

        .orders-container:before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 20px 0 0 20px;
        }

        .orders-container:hover {
            transform: translateY(-10px);
            box-shadow: 0px 25px 50px rgba(79, 70, 229, 0.15);
        }

        .orders-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
        }

        .orders-title {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .orders-info {
            color: #6b7280;
            font-size: 0.95rem;
            font-weight: 500;
        }

        .order-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0px 8px 25px rgba(0, 0, 0, 0.08);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .order-card:before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 20px 0 0 20px;
        }

        .order-card:hover {
            transform: translateY(-8px);
            box-shadow: 0px 15px 40px rgba(79, 70, 229, 0.15);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid #e5e7eb;
        }

        .order-number {
            font-size: 1.4rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .order-date {
            color: #6b7280;
            font-size: 0.95rem;
            margin-top: 0.5rem;
        }

        .order-status {
            padding: 0.4rem 1rem;
            border-radius: 25px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
        
        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .order-detail-item {
            display: flex;
            flex-direction: column;
        }

        .order-detail-label {
            font-size: 0.85rem;
            color: #6b7280;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .order-detail-value {
            font-weight: 700;
            color: var(--dark-color);
            font-size: 1rem;
        }
        
        .order-items {
            margin-bottom: 1rem;
        }
        
        .order-items-title {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-item-name {
            flex: 1;
            font-size: 0.9rem;
        }
        
        .order-item-quantity {
            color: #6b7280;
            font-size: 0.8rem;
            margin: 0 1rem;
        }
        
        .order-item-price {
            font-weight: 600;
            color: var(--success-color);
        }
        
        .order-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 0;
            border-top: 2px solid #e5e7eb;
            font-weight: 800;
            font-size: 1.3rem;
            color: var(--dark-color);
        }

        .order-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .btn-action {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-view {
            background: var(--info-color);
            color: white;
        }
        
        .btn-view:hover {
            background: #2563eb;
        }
        
        .btn-download {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0px 8px 20px rgba(16, 185, 129, 0.3);
        }

        .btn-download:hover {
            transform: translateY(-4px);
            box-shadow: 0px 12px 25px rgba(16, 185, 129, 0.4);
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.75rem;
            margin-top: 3rem;
        }

        .pagination-link {
            padding: 0.75rem 1.25rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            text-decoration: none;
            color: var(--dark-color);
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .pagination-link:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-2px);
        }

        .pagination-link.active {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-color: var(--primary-color);
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 5rem;
            margin-bottom: 25px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .empty-state h3 {
            font-size: 1.8rem;
            margin-bottom: 15px;
            font-weight: 700;
            color: var(--dark-color);
        }
        
        @media (max-width: 768px) {
            .main-container {
                padding: 15px;
            }

            .page-header {
                padding: 25px 20px;
            }

            .page-title {
                font-size: 1.8rem;
            }

            .filter-section {
                padding: 25px;
                margin-bottom: 30px;
            }

            .filter-form {
                flex-direction: column;
                gap: 1rem;
            }

            .filter-group {
                max-width: 100%;
                min-width: auto;
            }

            .orders-container {
                padding: 25px;
            }

            .orders-header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }

            .orders-title {
                font-size: 1.5rem;
            }

            .order-card {
                padding: 1.5rem;
            }

            .order-header {
                flex-direction: column;
                gap: 0.5rem;
                text-align: center;
            }

            .order-number {
                font-size: 1.2rem;
            }

            .order-details {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .order-actions {
                flex-direction: column;
                gap: 0.5rem;
            }

            .btn-action {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 576px) {
            .main-container {
                padding: 10px;
            }

            .page-header {
                padding: 20px 15px;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .filter-section {
                padding: 20px;
            }

            .orders-container {
                padding: 20px;
            }

            .orders-title {
                font-size: 1.3rem;
            }

            .order-card {
                padding: 1rem;
                margin-bottom: 1rem;
            }

            .order-number {
                font-size: 1.1rem;
            }

            .order-date {
                font-size: 0.85rem;
            }

            .order-status {
                font-size: 0.7rem;
                padding: 0.3rem 0.8rem;
            }

            .order-detail-label {
                font-size: 0.8rem;
            }

            .order-detail-value {
                font-size: 0.9rem;
            }

            .order-total {
                font-size: 1.1rem;
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }

            .btn-action {
                font-size: 0.85rem;
                padding: 0.6rem 1rem;
            }

            .pagination-link {
                padding: 0.5rem 1rem;
                font-size: 0.85rem;
            }

            .empty-state {
                padding: 40px 15px;
            }

            .empty-state i {
                font-size: 3rem;
            }

            .empty-state h3 {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>

<!-- Sertakan navbar -->
<?php include "../assets/navbar.php"; ?>

<!-- Main Content -->
<div class="main-container">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-history me-3"></i>Riwayat Pesanan
        </h1>
        <p class="page-subtitle">Lihat semua pesanan yang pernah Anda buat</p>
    </div>
    
    <!-- Filter Section -->
    <div class="filter-section">
        <form method="GET" class="filter-form">
            <div class="filter-group">
                <label class="filter-label">Cari Pesanan</label>
                <input type="text" name="search" class="filter-input" placeholder="Nomor order atau alamat..." value="<?php echo htmlspecialchars($search_filter); ?>">
            </div>
            <div class="filter-group">
                <label class="filter-label">Status</label>
                <select name="status" class="filter-input">
                    <option value="">Semua Status</option>
                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="processing" <?php echo $status_filter == 'processing' ? 'selected' : ''; ?>>Processing</option>
                    <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Complated</option>
                    <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <button type="submit" class="btn-filter">
                <i class="fas fa-search"></i> Cari
            </button>
            <a href="lacak_pesanan.php" class="btn-filter" style="background: var(--warning-color);">
                <i class="fas fa-map-marker-alt"></i> Lacak Pesanan
            </a>
            <a href="riwayat_pesanan.php" class="btn-filter" style="background: #6b7280;">
                <i class="fas fa-redo"></i> Reset
            </a>
        </form>
    </div>
    
    <!-- Orders Container -->
    <div class="orders-container">
        <div class="orders-header">
            <h2 class="orders-title">
                <i class="fas fa-list"></i> Daftar Pesanan
            </h2>
            <span class="orders-info">
                <?php echo $total_records; ?> pesanan ditemukan
            </span>
        </div>
        
        <?php if ($orders_result && $orders_result->num_rows > 0): ?>
            <?php while ($order = $orders_result->fetch_assoc()): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <div class="order-number">#<?php echo htmlspecialchars($order['order_number']); ?></div>
                            <div class="order-date"><?php echo date('d M Y, H:i', strtotime($order['created_at'])); ?></div>
                        </div>
                        <span class="order-status status-<?php echo $order['status']; ?>">
                            <?php 
                            $status_text = [
                                'pending' => 'Pending',
                                'processing' => 'Processing',
                                'completed' => 'Complated',
                                'cancelled' => 'Cancelled'
                            ];
                            echo $status_text[$order['status']] ?? ucfirst($order['status']);
                            ?>
                        </span>
                    </div>
                    
                    <div class="order-details">
                        <div class="order-detail-item">
                            <span class="order-detail-label">Jumlah Item</span>
                            <span class="order-detail-value"><?php echo $order['item_count']; ?> item</span>
                        </div>
                        <div class="order-detail-item">
                            <span class="order-detail-label">Total Harga</span>
                            <span class="order-detail-value">Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></span>
                        </div>
                        <div class="order-detail-item">
                            <span class="order-detail-label">Biaya Pengiriman</span>
                            <span class="order-detail-value">Rp <?php echo number_format($order['shipping_cost'], 0, ',', '.'); ?></span>
                        </div>
                        <div class="order-detail-item">
                            <span class="order-detail-label">Total Pembayaran</span>
                            <span class="order-detail-value">Rp <?php echo number_format($order['total_amount'] + $order['shipping_cost'] + $order['service_fee'], 0, ',', '.'); ?></span>
                        </div>
                    </div>
                    
                    <div class="order-details">
                        <div class="order-detail-item">
                            <span class="order-detail-label">Metode Pembayaran</span>
                            <span class="order-detail-value"><?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></span>
                        </div>
                        <div class="order-detail-item" style="grid-column: span 2;">
                            <span class="order-detail-label">Alamat Pengiriman</span>
                            <span class="order-detail-value"><?php echo htmlspecialchars(substr($order['shipping_address'], 0, 100)) . (strlen($order['shipping_address']) > 100 ? '...' : ''); ?></span>
                        </div>
                    </div>
                    
                    <div class="order-total">
                        <span>Total Pembayaran:</span>
                        <span>Rp <?php echo number_format($order['total_amount'] + $order['shipping_cost'] + $order['service_fee'], 0, ',', '.'); ?></span>
                    </div>
                    
                        <a href="struk_pembelian.php?order_id=<?php echo $order['id']; ?>&print=1" class="btn-action btn-download" target="_blank">
                            <i class="fas fa-download"></i> Download Struk
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search_filter); ?>" 
                           class="pagination-link <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>Belum Ada Pesanan</h3>
                <p>Anda belum memiliki riwayat pesanan. Mulai belanja sekarang!</p>
                <a href="kategori.php" class="btn-filter">
                    <i class="fas fa-shopping-bag"></i> Mulai Belanja
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
