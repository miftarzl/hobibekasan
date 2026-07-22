<?php
session_start();

// Simple session check - redirect jika tidak login sebagai admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../pengguna/login.php");
    exit();
}

// Database connection
require '../config/config.php';

// Get customer ID from URL
$customer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($customer_id === 0) {
    header("Location: pelanggan.php");
    exit();
}

// Get customer details
$customer_query = $conn->prepare("SELECT id, username, email, profile_photo, created_at FROM users WHERE id = ? AND role = 'user'");
$customer_query->bind_param('i', $customer_id);
$customer_query->execute();
$customer = $customer_query->get_result()->fetch_assoc();

if (!$customer) {
    header("Location: pelanggan.php");
    exit();
}

// Get customer statistics
$stats_query = $conn->prepare("
    SELECT 
        COUNT(*) as total_orders,
        SUM(total_amount) as total_spent,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
        COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing_orders,
        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders,
        MAX(created_at) as last_order_date,
        MIN(created_at) as first_order_date
    FROM orders 
    WHERE user_id = ?
");
$stats_query->bind_param('i', $customer_id);
$stats_query->execute();
$stats = $stats_query->get_result()->fetch_assoc();

// Get customer orders with pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$count_orders_query = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE user_id = ?");
$count_orders_query->bind_param('i', $customer_id);
$count_orders_query->execute();
$total_orders = $count_orders_query->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_orders / $limit);

$orders_query = $conn->prepare("
    SELECT o.*
    FROM orders o
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
    LIMIT ? OFFSET ?
");
$orders_query->bind_param('iii', $customer_id, $limit, $offset);
$orders_query->execute();
$orders_result = $orders_query->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pelanggan - hobiBekasan Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
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
            --border-color: #e5e7eb;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #ffffff;
            min-height: 100vh;
            color: var(--dark-color);
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
            font-weight: 800;
            font-size: 1.5rem;
            margin: 0;
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
            box-shadow: var(--shadow-lg);
            border: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: #6b7280;
            font-size: 1.1rem;
            margin-bottom: 0;
        }

        .btn-back {
            background: #ffffff;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-back:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        /* Customer Profile Card */
        .customer-profile {
            background: #ffffff;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid #e5e7eb;
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .customer-avatar-large {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary-color);
            box-shadow: var(--shadow-lg);
        }

        .customer-info {
            flex: 1;
        }

        .customer-name {
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }

        .customer-email {
            font-size: 1.1rem;
            color: #6b7280;
            margin-bottom: 1rem;
        }

        .customer-meta {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            flex-direction: column;
        }

        .meta-label {
            font-size: 0.85rem;
            color: #6b7280;
            margin-bottom: 0.25rem;
        }

        .meta-value {
            font-size: 1rem;
            font-weight: 600;
            color: var(--dark-color);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid #e5e7eb;
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin: 0 auto 1rem;
        }

        .stat-icon.blue {
            background: linear-gradient(135deg, #3b82f6, #1e40af);
            color: white;
        }

        .stat-icon.green {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .stat-icon.purple {
            background: linear-gradient(135deg, #8b5cf6, #6d28d9);
            color: white;
        }

        .stat-icon.orange {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Table Styles */
        .table-container {
            background: #ffffff;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid #e5e7eb;
            overflow-x: auto;
        }

        .modern-table {
            width: 100%;
            border-collapse: collapse;
            background: transparent;
        }

        .modern-table th {
            background: rgba(79, 70, 229, 0.1);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--dark-color);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--border-color);
        }

        .modern-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.9rem;
        }

        .modern-table tbody tr {
            transition: all 0.2s ease;
        }

        .modern-table tbody tr:hover {
            background: rgba(79, 70, 229, 0.05);
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }

        .status-pending {
            background: #fed7aa;
            color: #92400e;
        }

        .status-processing {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #dc2626;
        }

        .amount {
            font-weight: 600;
            color: var(--success-color);
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-action {
            width: 35px;
            height: 35px;
            border: none;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            text-decoration: none;
        }

        .btn-view {
            background: #dbeafe;
            color: #1e40af;
        }

        .btn-view:hover {
            background: #bfdbfe;
            transform: translateY(-2px);
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .pagination-link {
            padding: 0.5rem 1rem;
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            text-decoration: none;
            color: var(--dark-color);
            transition: all 0.3s ease;
        }

        .pagination-link:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .pagination-link.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #d1d5db;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .customer-profile {
                flex-direction: column;
                text-align: center;
            }
            
            .customer-meta {
                justify-content: center;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .modern-table {
                font-size: 0.8rem;
            }
            
            .modern-table th,
            .modern-table td {
                padding: 0.5rem;
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
                <a href="pelanggan.php" class="menu-item active">
                    <i class="fas fa-users"></i> Pelanggan
                </a>
                <a href="laporan.php" class="menu-item">
                    <i class="fas fa-file-alt"></i> Laporan
                </a>
                <a href="rating.php" class="menu-item">
                    <i class="fas fa-star"></i> Rating
                </a>
                                <a href="../pengguna/logout.php" class="menu-item">
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
                        <h1 class="page-title">Detail Pelanggan</h1>
                        <p class="page-subtitle">Informasi lengkap dan riwayat pesanan pelanggan</p>
                    </div>
                    <a href="pelanggan.php" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <!-- Customer Profile Card -->
                <div class="customer-profile">
                    <img src="../assets/img/profiles/<?php echo $customer['profile_photo'] ?: 'default.png'; ?>" 
                         alt="<?php echo htmlspecialchars($customer['username']); ?>" 
                         class="customer-avatar-large">
                    <div class="customer-info">
                        <h2 class="customer-name"><?php echo htmlspecialchars($customer['username']); ?></h2>
                        <p class="customer-email"><?php echo htmlspecialchars($customer['email']); ?></p>
                        <div class="customer-meta">
                            <div class="meta-item">
                                <span class="meta-label">ID Pelanggan</span>
                                <span class="meta-value">#<?php echo str_pad($customer['id'], 6, '0', STR_PAD_LEFT); ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Bergabung</span>
                                <span class="meta-value"><?php echo date('d M Y', strtotime($customer['created_at'])); ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Total Pesanan</span>
                                <span class="meta-value"><?php echo number_format($stats['total_orders']); ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Total Belanja</span>
                                <span class="meta-value">Rp <?php echo number_format($stats['total_spent'] ?: 0, 0, ',', '.'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['total_orders']); ?></div>
                        <div class="stat-label">Total Pesanan</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['completed_orders']); ?></div>
                        <div class="stat-label">Pesanan Selesai</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['pending_orders']); ?></div>
                        <div class="stat-label">Menunggu</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon purple">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-value">Rp <?php echo number_format($stats['total_spent'] ?: 0, 0, ',', '.'); ?></div>
                        <div class="stat-label">Total Belanja</div>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="table-container">
                    <h3 style="margin-bottom: 1.5rem; color: var(--dark-color); font-weight: 700;">Riwayat Pesanan</h3>
                    <?php if ($orders_result->num_rows > 0): ?>
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>ID Pesanan</th>
                                    <th>Produk</th>
                                    <th>Tanggal</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = $orders_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></strong>
                                        </td>
                                        <td>
                                            <span style="color: #6b7280;">Lihat detail pesanan</span>
                                        </td>
                                        <td><?php echo date('d M Y, H:i', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <span class="amount">Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                                <?php 
                                                $status_text = [
                                                    'pending' => 'Menunggu',
                                                    'processing' => 'Proses',
                                                    'completed' => 'Selesai',
                                                    'cancelled' => 'Batal'
                                                ];
                                                echo $status_text[$order['status']] ?? ucfirst($order['status']);
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="view_order.php?id=<?php echo $order['id']; ?>" class="btn-action btn-view" title="Lihat Detail">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?id=<?php echo $customer_id; ?>&page=<?php echo $i; ?>" 
                                       class="pagination-link <?php echo $i == $page ? 'active' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-shopping-bag"></i>
                            <h3>Belum Ada Pesanan</h3>
                            <p>Pelanggan ini belum melakukan pesanan apa pun.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
