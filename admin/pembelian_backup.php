<?php
session_start();

// Simple session check - redirect jika tidak login sebagai admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../pengguna/login.php");
    exit();
}

// Database connection
require '../config/config.php';

// Handle deleting an order
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // Delete order items first
    $conn->query("DELETE FROM order_items WHERE order_id = $id");
    // Then delete the order
    $conn->query("DELETE FROM orders WHERE id = $id");
    header("Location: pembelian.php");
    exit();
}

// Handle updating order status
if (isset($_POST['update_status'])) {
    $id = $_POST['order_id'];
    $status = $_POST['status'];
    $conn->query("UPDATE orders SET status = '$status' WHERE id = $id");
    header("Location: pembelian.php");
    exit();
}

// Pagination settings
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filter settings
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search_filter = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$where_conditions = [];
$params = [];
$types = '';

// Status filter
if (!empty($status_filter)) {
    $where_conditions[] = "o.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

// Search filter
if (!empty($search_filter)) {
    $where_conditions[] = "(o.order_number LIKE ? OR u.username LIKE ? OR o.shipping_address LIKE ?)";
    $search_param = "%$search_filter%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

// Build WHERE clause
$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Count total records
$count_query = "SELECT COUNT(*) as total FROM orders o LEFT JOIN users u ON o.user_id = u.id $where_clause";
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// Get orders with user info
$orders_query = "SELECT o.*, u.username, u.email, 
                COUNT(oi.id) as item_count,
                SUM(oi.quantity * oi.price) as calculated_total
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id 
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

// Get total records
$count_sql = "SELECT COUNT(DISTINCT t.transaction_id) as total 
              FROM transactions t 
              JOIN users u ON t.user_id = u.id 
              $where_clause";

if (!empty($params)) {
    $stmt = $conn->prepare($count_sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($count_sql);
}

$total_records = $result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// Get transactions
$sql = "SELECT t.*, u.username as user_name, u.email as user_email, u.phone_number, u.address,
               COUNT(pd.product_id) as total_items,
               SUM(pd.quantity * pd.price) as total_amount
        FROM transactions t 
        JOIN users u ON t.user_id = u.id 
        LEFT JOIN purchase_details pd ON t.transaction_id = pd.transaction_id 
        $where_clause
        GROUP BY t.transaction_id 
        ORDER BY t.created_at DESC 
        LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $transactions = $stmt->get_result();
} else {
    $transactions = $conn->query($sql);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pembelian - hobiBekasan</title>
    
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
            --light-color: #f9fafb;
            --border-color: #e5e7eb;
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
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
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: #ffffff;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .menu-item:hover {
            background: #667eea;
            color: #fff;
            border-left-color: #fff;
        }

        .menu-item.active {
            background: #764ba2;
            color: #fff;
            border-left-color: #fff;
        }

        .menu-item i {
            margin-right: 12px;
            width: 20px;
        }

        /* Content Area */
        .content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
            background: #ffffff;
            transition: all 0.3s ease;
        }

        .content-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header {
            background: #ffffff;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            text-align: center;
            border: 1px solid #e5e7eb;
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
            font-weight: 500;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3);
            color: white;
        }

        .purchases-table-container {
            background: #ffffff;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid #e5e7eb;
        }

        .table-header-custom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .table-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark-color);
        }

        .badge-count {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .filter-section {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-input {
            padding: 0.5rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .filter-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .filter-select {
            padding: 0.5rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 0.9rem;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .modern-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .modern-table thead {
            background: linear-gradient(135deg, #f9fafb, #f3f4f6);
        }

        .modern-table th {
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
            vertical-align: middle;
        }

        .modern-table tbody tr {
            transition: all 0.3s ease;
        }

        .modern-table tbody tr:hover {
            background: #f8fafc;
            transform: scale(1.01);
        }

        .status-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-menunggu_konfirmasi {
            background: #e0e7ff;
            color: #3730a3;
        }

        .status-berhasil {
            background: #f0fdf4;
            color: #166534;
        }

        .status-paid {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-shipped {
            background: #d1fae5;
            color: #065f46;
        }

        .status-completed {
            background: #dcfce7;
            color: #166534;
        }

        .status-batal {
            background: #fef2f2;
            color: #ea580c;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .action-buttons-cell {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }

        .btn-action {
            padding: 0.4rem 0.8rem;
            border: none;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-view {
            background: var(--info-color);
            color: white;
        }

        .btn-view:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }

        .btn-edit {
            background: var(--warning-color);
            color: white;
        }

        .btn-edit:hover {
            background: #d97706;
            transform: translateY(-1px);
        }

        .btn-delete {
            background: var(--danger-color);
            color: white;
        }

        .btn-delete:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        .pagination-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 2rem;
            gap: 0.5rem;
        }

        .pagination-link {
            padding: 0.5rem 1rem;
            border: 2px solid var(--border-color);
            background: white;
            color: var(--dark-color);
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .pagination-link:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            transform: translateY(-1px);
        }

        .pagination-link.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 4rem;
            color: #d1d5db;
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            font-size: 1rem;
            margin-bottom: 1.5rem;
        }

        /* Stats Cards */
        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 60px;
            height: 60px;
            background: #f3f4f6;
            border-radius: 50%;
            transform: translate(20px, -20px);
        }

        .stats-value {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            line-height: 1;
        }

        .stats-label {
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            opacity: 0.8;
        }

        .stats-percentage {
            font-size: 0.8rem;
            font-weight: 700;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            background: #e5e7eb;
            display: inline-block;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar-header h3 span,
            .menu-item span {
                display: none;
            }
            
            .content {
                margin-left: 70px;
                padding: 1rem;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .table-header-custom {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .filter-section {
                width: 100%;
            }
            
            .filter-input,
            .filter-select {
                width: 100%;
                margin-bottom: 0.5rem;
            }
            
            .modern-table {
                font-size: 0.8rem;
            }
            
            .modern-table th,
            .modern-table td {
                padding: 0.75rem 0.5rem;
            }
            
            .action-buttons-cell {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Sidebar Admin -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-crown"></i> <span>hobiBekasan Admin</span></h3>
            </div>
            <div class="sidebar-menu">
                <a href="admin_dashboard.php" class="menu-item">
                    <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
                </a>
                <a href="produk.php" class="menu-item">
                    <i class="fas fa-box"></i> <span>Produk</span>
                </a>
                <a href="kategori.php" class="menu-item">
                    <i class="fas fa-tags"></i> <span>Kategori</span>
                </a>
                <a href="pembelian.php" class="menu-item active">
                    <i class="fas fa-shopping-cart"></i> <span>Pembelian</span>
                </a>
                <a href="pelanggan.php" class="menu-item">
                    <i class="fas fa-users"></i> <span>Pelanggan</span>
                </a>
                <a href="laporan.php" class="menu-item">
                    <i class="fas fa-file-alt"></i> <span>Laporan</span>
                </a>
                <a href="rating.php" class="menu-item">
                    <i class="fas fa-star"></i> <span>Rating</span>
                </a>
                                <a href="../pengguna/logout.php" class="menu-item" style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </a>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content">
            <div class="content-container">
                <!-- Header -->
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-shopping-cart"></i> Kelola Pembelian
                    </h1>
                    <p class="page-subtitle">Lihat dan kelola semua transaksi pembelian produk</p>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="admin_dashboard.php" class="btn-primary-custom">
                        <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                    </a>
                </div>

                <!-- Status Statistics -->
                <div class="purchases-table-container mb-4">
                    <div class="table-header-custom">
                        <h2 class="table-title">
                            <i class="fas fa-chart-bar"></i> Statistik Status Transaksi
                        </h2>
                    </div>
                    
                    <?php
                    // Get statistics for all statuses
                    $stats_sql = "SELECT 
                                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
                                    COUNT(CASE WHEN status = 'menunggu_konfirmasi' THEN 1 END) as menunggu_konfirmasi,
                                    COUNT(CASE WHEN status = 'berhasil' THEN 1 END) as berhasil,
                                    COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid,
                                    COUNT(CASE WHEN status = 'shipped' THEN 1 END) as shipped,
                                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                                    COUNT(CASE WHEN status = 'batal' THEN 1 END) as batal,
                                    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled,
                                    COUNT(*) as total
                                  FROM transactions";
                    $stats_result = $conn->query($stats_sql);
                    $stats = $stats_result->fetch_assoc();
                    ?>
                    
                    <div class="row">
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stats-card" style="background: linear-gradient(135deg, #fef3c7, #fde68a); border-left: 4px solid #f59e0b;">
                                <div class="stats-value text-warning"><?php echo $stats['pending']; ?></div>
                                <div class="stats-label">Menunggu Pembayaran</div>
                                <div class="stats-percentage">
                                    <?php echo $stats['total'] > 0 ? round(($stats['pending'] / $stats['total']) * 100, 1) : 0; ?>%
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stats-card" style="background: linear-gradient(135deg, #e0e7ff, #c7d2fe); border-left: 4px solid #6366f1;">
                                <div class="stats-value text-primary"><?php echo $stats['menunggu_konfirmasi']; ?></div>
                                <div class="stats-label">Menunggu Konfirmasi</div>
                                <div class="stats-percentage">
                                    <?php echo $stats['total'] > 0 ? round(($stats['menunggu_konfirmasi'] / $stats['total']) * 100, 1) : 0; ?>%
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stats-card" style="background: linear-gradient(135deg, #f0fdf4, #dcfce7); border-left: 4px solid #22c55e;">
                                <div class="stats-value text-success"><?php echo $stats['berhasil']; ?></div>
                                <div class="stats-label">Berhasil</div>
                                <div class="stats-percentage">
                                    <?php echo $stats['total'] > 0 ? round(($stats['berhasil'] / $stats['total']) * 100, 1) : 0; ?>%
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stats-card" style="background: linear-gradient(135deg, #dbeafe, #bfdbfe); border-left: 4px solid #3b82f6;">
                                <div class="stats-value text-info"><?php echo $stats['paid']; ?></div>
                                <div class="stats-label">Sudah Dibayar</div>
                                <div class="stats-percentage">
                                    <?php echo $stats['total'] > 0 ? round(($stats['paid'] / $stats['total']) * 100, 1) : 0; ?>%
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stats-card" style="background: linear-gradient(135deg, #d1fae5, #a7f3d0); border-left: 4px solid #10b981;">
                                <div class="stats-value" style="color: #065f46;"><?php echo $stats['shipped']; ?></div>
                                <div class="stats-label">Dikirim</div>
                                <div class="stats-percentage">
                                    <?php echo $stats['total'] > 0 ? round(($stats['shipped'] / $stats['total']) * 100, 1) : 0; ?>%
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stats-card" style="background: linear-gradient(135deg, #dcfce7, #bbf7d0); border-left: 4px solid #16a34a;">
                                <div class="stats-value" style="color: #166534;"><?php echo $stats['completed']; ?></div>
                                <div class="stats-label">Selesai</div>
                                <div class="stats-percentage">
                                    <?php echo $stats['total'] > 0 ? round(($stats['completed'] / $stats['total']) * 100, 1) : 0; ?>%
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stats-card" style="background: linear-gradient(135deg, #fef2f2, #fecaca); border-left: 4px solid #ea580c;">
                                <div class="stats-value" style="color: #ea580c;"><?php echo $stats['batal']; ?></div>
                                <div class="stats-label">Batal</div>
                                <div class="stats-percentage">
                                    <?php echo $stats['total'] > 0 ? round(($stats['batal'] / $stats['total']) * 100, 1) : 0; ?>%
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stats-card" style="background: linear-gradient(135deg, #fee2e2, #fca5a5); border-left: 4px solid #ef4444;">
                                <div class="stats-value text-danger"><?php echo $stats['cancelled']; ?></div>
                                <div class="stats-label">Dibatalkan</div>
                                <div class="stats-percentage">
                                    <?php echo $stats['total'] > 0 ? round(($stats['cancelled'] / $stats['total']) * 100, 1) : 0; ?>%
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <div class="stats-card" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); border-left: 4px solid var(--dark-color);">
                                <div class="stats-value text-white"><?php echo $stats['total']; ?></div>
                                <div class="stats-label" style="color: white;">Total Semua Transaksi</div>
                                <div class="stats-percentage" style="color: white;">100%</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Purchases Table -->
                <div class="purchases-table-container">
                    <div class="table-header-custom">
                        <h2 class="table-title">
                            <i class="fas fa-list"></i> Daftar Pembelian
                        </h2>
                        <span class="badge-count">
                            <?php echo $total_records; ?> Transaksi
                        </span>
                    </div>

                    <!-- Filter Section -->
                    <div class="filter-section">
                        <form method="GET" action="pembelian.php" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                            <input type="text" name="search" class="filter-input" placeholder="Cari transaksi..." value="<?php echo htmlspecialchars($search_filter); ?>">
                            <select name="status" class="filter-select">
                                <option value="">Semua Status</option>
                                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Menunggu Pembayaran</option>
                                <option value="menunggu_konfirmasi" <?php echo $status_filter == 'menunggu_konfirmasi' ? 'selected' : ''; ?>>Menunggu Konfirmasi</option>
                                <option value="berhasil" <?php echo $status_filter == 'berhasil' ? 'selected' : ''; ?>>Berhasil</option>
                                <option value="paid" <?php echo $status_filter == 'paid' ? 'selected' : ''; ?>>Sudah Dibayar</option>
                                <option value="shipped" <?php echo $status_filter == 'shipped' ? 'selected' : ''; ?>>Dikirim</option>
                                <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Selesai</option>
                                <option value="batal" <?php echo $status_filter == 'batal' ? 'selected' : ''; ?>>Batal</option>
                                <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Dibatalkan</option>
                            </select>
                            <button type="submit" class="btn-primary-custom">
                                <i class="fas fa-search"></i> Cari
                            </button>
                            <a href="pembelian.php" class="btn-primary-custom">
                                <i class="fas fa-redo"></i> Reset
                            </a>
                        </form>
                    </div>
                    
                    <?php if ($transactions && $transactions->num_rows > 0): ?>
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kode Transaksi</th>
                                    <th>Pelanggan</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = $offset + 1;
                                while ($transaction = $transactions->fetch_assoc()): 
                                ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo htmlspecialchars($transaction['transaction_code'] ?? 'TRX' . str_pad($transaction['transaction_id'], 6, '0', STR_PAD_LEFT)); ?></td>
                                        <td><?php echo htmlspecialchars($transaction['user_name']); ?></td>
                                        <td><?php echo date('d M Y H:i', strtotime($transaction['created_at'])); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $transaction['status']; ?>">
                                                <?php 
                                                $status_text = [
                                                    'pending' => 'Menunggu',
                                                    'menunggu_konfirmasi' => 'Menunggu Konfirmasi',
                                                    'berhasil' => 'Berhasil',
                                                    'paid' => 'Dibayar',
                                                    'shipped' => 'Dikirim',
                                                    'completed' => 'Selesai',
                                                    'batal' => 'Batal',
                                                    'cancelled' => 'Dibatalkan'
                                                ];
                                                echo $status_text[$transaction['status']] ?? ucfirst($transaction['status']);
                                                ?>
                                            </span>
                                        </td>
                                        <td>Rp <?php echo number_format($transaction['total_amount'] ?? $transaction['total_price'], 0, ',', '.'); ?></td>
                                        <td>
                                            <div class="action-buttons-cell">
                                                <!-- Detail Button -->
                                                <button class="btn-action btn-view" data-bs-toggle="modal" data-bs-target="#detailModal<?php echo $transaction['transaction_id']; ?>">
                                                    <i class="fas fa-eye"></i> Detail
                                                </button>
                                                
                                                <!-- Edit Status Button (only for pending/menunggu_konfirmasi) -->
                                                <?php if ($transaction['status'] == 'pending' || $transaction['status'] == 'menunggu_konfirmasi'): ?>
                                                    <button class="btn-action btn-edit" data-bs-toggle="modal" data-bs-target="#statusModal<?php echo $transaction['transaction_id']; ?>">
                                                        <i class="fas fa-edit"></i> Status
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <!-- Delete Button -->
                                                <a href="pembelian.php?delete=<?php echo $transaction['transaction_id']; ?>" class="btn-action btn-delete" onclick="return confirm('Apakah Anda yakin ingin menghapus transaksi ini?')">
                                                    <i class="fas fa-trash"></i> Hapus
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination-container">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search_filter); ?>" class="pagination-link">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                <?php endif; ?>

                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <a href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search_filter); ?>" class="pagination-link <?php echo $i == $page ? 'active' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search_filter); ?>" class="pagination-link">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-shopping-cart"></i>
                            <h3>Belum Ada Data Pembelian</h3>
                            <p>Belum ada transaksi pembelian yang tercatat dalam sistem.</p>
                            <a href="pembelian.php" class="btn-primary-custom">
                                <i class="fas fa-redo"></i> Reset Filter
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Detail Pembelian -->
    <?php 
    // Reset pointer untuk mengambil data detail
    $transactions->data_seek(0);
    while ($transaction = $transactions->fetch_assoc()): 
    ?>
        <!-- Detail Modal -->
        <div class="modal fade" id="detailModal<?php echo $transaction['transaction_id']; ?>" tabindex="-1" aria-labelledby="detailModalLabel<?php echo $transaction['transaction_id']; ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; border-radius: 15px 15px 0 0;">
                        <h5 class="modal-title" id="detailModalLabel<?php echo $transaction['transaction_id']; ?>">
                            <i class="fas fa-shopping-cart"></i> Detail Pembelian
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Informasi Pembeli -->
                        <div class="card mb-3" style="border-left: 4px solid var(--primary-color);">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-user"></i> Informasi Pembeli</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Nama Lengkap:</strong> <?php echo htmlspecialchars($transaction['user_name']); ?></p>
                                        <p><strong>Email:</strong> <?php echo htmlspecialchars($transaction['user_email']); ?></p>
                                        <p><strong>No. Telepon:</strong> <?php echo htmlspecialchars($transaction['phone_number'] ?? 'Tidak tersedia'); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Alamat Lengkap:</strong></p>
                                        <p><?php echo nl2br(htmlspecialchars($transaction['address'] ?? 'Tidak tersedia')); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Informasi Transaksi -->
                        <div class="card mb-3" style="border-left: 4px solid var(--info-color);">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-receipt"></i> Informasi Transaksi</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Kode Transaksi:</strong> <?php echo htmlspecialchars($transaction['transaction_code'] ?? 'TRX' . str_pad($transaction['transaction_id'], 6, '0', STR_PAD_LEFT)); ?></p>
                                        <p><strong>Tanggal Transaksi:</strong> <?php echo date('d M Y H:i:s', strtotime($transaction['created_at'])); ?></p>
                                        <p><strong>Metode Pembayaran:</strong> <?php echo htmlspecialchars($transaction['payment_method'] ?? 'Transfer Bank'); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Status Saat Ini:</strong> 
                                            <span class="status-badge status-<?php echo $transaction['status']; ?>">
                                                <?php 
                                                echo $status_text[$transaction['status']] ?? ucfirst($transaction['status']);
                                                ?>
                                            </span>
                                        </p>
                                        <p><strong>Total Pembayaran:</strong> <span style="color: var(--success-color); font-weight: bold;">Rp <?php echo number_format($transaction['total_amount'] ?? $transaction['total_price'], 0, ',', '.'); ?></span></p>
                                        <p><strong>Jumlah Item:</strong> <?php echo $transaction['total_items'] ?? 0; ?> produk</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Detail Produk yang Dibeli -->
                        <div class="card" style="border-left: 4px solid var(--warning-color);">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-box"></i> Produk yang Dibeli</h6>
                            </div>
                            <div class="card-body">
                                <?php
                                // Get product details for this transaction
                                $detail_query = "SELECT pd.quantity, pd.price, p.name, p.image, p.description
                                               FROM purchase_details pd 
                                               JOIN products p ON pd.product_id = p.product_id 
                                               WHERE pd.transaction_id = ?";
                                $detail_stmt = $conn->prepare($detail_query);
                                $detail_stmt->bind_param('i', $transaction['transaction_id']);
                                $detail_stmt->execute();
                                $detail_result = $detail_stmt->get_result();
                                
                                if ($detail_result->num_rows > 0):
                                ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Nama Produk</th>
                                                    <th>Qty</th>
                                                    <th>Harga Satuan</th>
                                                    <th>Subtotal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $total_detail = 0;
                                                while ($detail = $detail_result->fetch_assoc()): 
                                                    $subtotal = $detail['quantity'] * $detail['price'];
                                                    $total_detail += $subtotal;
                                                ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <?php if (!empty($detail['image'])): ?>
                                                                    <img src="../assets/img/produk/<?php echo $detail['image']; ?>" alt="<?php echo htmlspecialchars($detail['name']); ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 8px; margin-right: 10px;">
                                                                <?php endif; ?>
                                                                <div>
                                                                    <strong><?php echo htmlspecialchars($detail['name']); ?></strong>
                                                                    <?php if (!empty($detail['description'])): ?>
                                                                        <br><small class="text-muted"><?php echo htmlspecialchars(substr($detail['description'], 0, 100)); ?>...</small>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="text-center"><?php echo $detail['quantity']; ?></td>
                                                        <td>Rp <?php echo number_format($detail['price'], 0, ',', '.'); ?></td>
                                                        <td><strong>Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></strong></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                            <tfoot class="table-light">
                                                <tr>
                                                    <th colspan="3" class="text-end">Total:</th>
                                                    <th><strong style="color: var(--success-color);">Rp <?php echo number_format($total_detail, 0, ',', '.'); ?></strong></th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center">Tidak ada detail produk untuk transaksi ini.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Update Status -->
        <div class="modal fade" id="statusModal<?php echo $transaction['transaction_id']; ?>" tabindex="-1" aria-labelledby="statusModalLabel<?php echo $transaction['transaction_id']; ?>" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header" style="background: linear-gradient(135deg, var(--warning-color), #f97316); color: white; border-radius: 15px 15px 0 0;">
                        <h5 class="modal-title" id="statusModalLabel<?php echo $transaction['transaction_id']; ?>">
                            <i class="fas fa-edit"></i> Update Status Transaksi
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="pembelian.php">
                        <input type="hidden" name="transaction_id" value="<?php echo $transaction['transaction_id']; ?>">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="status" class="form-label"><strong>Pilih Status Baru:</strong></label>
                                <select name="status" id="status" class="form-select" required>
                                    <option value="">-- Pilih Status --</option>
                                    <option value="menunggu_konfirmasi" <?php echo $transaction['status'] == 'menunggu_konfirmasi' ? 'selected' : ''; ?>>Menunggu Konfirmasi</option>
                                    <option value="berhasil" <?php echo $transaction['status'] == 'berhasil' ? 'selected' : ''; ?>>Berhasil</option>
                                    <option value="batal" <?php echo $transaction['status'] == 'batal' ? 'selected' : ''; ?>>Batal</option>
                                    <option value="paid" <?php echo $transaction['status'] == 'paid' ? 'selected' : ''; ?>>Sudah Dibayar</option>
                                    <option value="shipped" <?php echo $transaction['status'] == 'shipped' ? 'selected' : ''; ?>>Dikirim</option>
                                    <option value="completed" <?php echo $transaction['status'] == 'completed' ? 'selected' : ''; ?>>Selesai</option>
                                    <option value="cancelled" <?php echo $transaction['status'] == 'cancelled' ? 'selected' : ''; ?>>Dibatalkan</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="keterangan" class="form-label"><strong>Keterangan (Opsional):</strong></label>
                                <textarea name="keterangan" id="keterangan" class="form-control" rows="3" placeholder="Tambahkan keterangan untuk perubahan status..."></textarea>
                            </div>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Panduan Status:</strong><br>
                                • <strong>Menunggu Konfirmasi:</strong> Pembayaran diterima, menunggu verifikasi admin<br>
                                • <strong>Berhasil:</strong> Transaksi berhasil diproses dan diverifikasi<br>
                                • <strong>Batal:</strong> Transaksi dibatalkan (timeout, user request, dll)<br>
                                • <strong>Sudah Dibayar:</strong> Pembayaran terkonfirmasi<br>
                                • <strong>Dikirim:</strong> Barang sedang dalam pengiriman<br>
                                • <strong>Selesai:</strong> Barang sampai dan transaksi selesai
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times"></i> Batal
                            </button>
                            <button type="submit" name="update_status" class="btn btn-warning">
                                <i class="fas fa-save"></i> Update Status
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endwhile; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
