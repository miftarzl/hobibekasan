<?php
session_start();

// Simple session check - redirect jika tidak login sebagai admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../pengguna/login.php");
    exit();
}

// Database connection
require '../config/config.php';

// Handle deleting a customer
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // Check if customer has orders
    $check_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE user_id = $id");
    $has_orders = $check_orders->fetch_assoc()['count'] > 0;
    
    if (!$has_orders) {
        // Delete customer profile photo if exists
        $user_query = $conn->query("SELECT profile_photo FROM users WHERE id = $id");
        if ($user = $user_query->fetch_assoc()) {
            if ($user['profile_photo'] && $user['profile_photo'] != 'default.png') {
                $photo_path = "../assets/img/profiles/" . $user['profile_photo'];
                if (file_exists($photo_path)) {
                    unlink($photo_path);
                }
            }
        }
        // Delete customer
        $conn->query("DELETE FROM users WHERE id = $id");
    }
    header("Location: pelanggan.php");
    exit();
}

// Pagination settings
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filter settings
$search_filter = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$where_conditions = ["u.role = 'user'"];
$params = [];
$types = '';

// Search filter
if (!empty($search_filter)) {
    $where_conditions[] = "(u.username LIKE ? OR u.email LIKE ?)";
    $search_param = "%$search_filter%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

// Build WHERE clause
$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Count total customers
$count_sql = "SELECT COUNT(*) as total FROM users u $where_clause";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_customers = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_customers / $limit);

// Get customers with pagination
$sql = "SELECT u.id, u.username, u.email, u.profile_photo, u.created_at,
        (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as total_orders,
        (SELECT SUM(total_amount) FROM orders WHERE user_id = u.id AND status = 'completed') as total_spent,
        (SELECT MAX(created_at) FROM orders WHERE user_id = u.id) as last_order_date
        FROM users u 
        $where_clause 
        ORDER BY u.created_at DESC 
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$param_types = $types . 'ii';
$param_values = array_merge($params, [$limit, $offset]);
$stmt->bind_param($param_types, ...$param_values);
$stmt->execute();
$customers_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pelanggan - hobiBekasan Admin</title>
    
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
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.2);
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

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid #e5e7eb;
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
            margin-bottom: 1rem;
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

        /* Filter Form */
        .filter-form {
            background: #ffffff;
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid #e5e7eb;
            display: flex;
            gap: 1rem;
            align-items: end;
            flex-wrap: wrap;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark-color);
        }

        .filter-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .filter-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .btn-filter {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            height: fit-content;
        }

        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
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

        .customer-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .customer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--border-color);
        }

        .customer-details {
            flex: 1;
        }

        .customer-name {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.25rem;
        }

        .customer-email {
            color: #6b7280;
            font-size: 0.85rem;
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
        }

        .btn-view {
            background: #dbeafe;
            color: #1e40af;
        }

        .btn-view:hover {
            background: #bfdbfe;
            transform: translateY(-2px);
        }

        .btn-delete {
            background: #fee2e2;
            color: #dc2626;
        }

        .btn-delete:hover {
            background: #fecaca;
            transform: translateY(-2px);
        }

        .btn-delete:disabled {
            background: #f3f4f6;
            color: #9ca3af;
            cursor: not-allowed;
            transform: none;
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
            
            .filter-form {
                flex-direction: column;
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
                    <h1 class="page-title">Manajemen Pelanggan</h1>
                    <p class="page-subtitle">Kelola data pelanggan dan pantau aktivitas mereka</p>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($total_customers); ?></div>
                        <div class="stat-label">Total Pelanggan</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <div class="stat-value">
                            <?php 
                            $total_orders_query = $conn->query("SELECT COUNT(*) as total FROM orders");
                            echo number_format($total_orders_query->fetch_assoc()['total']);
                            ?>
                        </div>
                        <div class="stat-label">Total Pesanan</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon purple">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-value">
                            <?php 
                            $avg_orders_query = $conn->query("SELECT AVG(order_count) as avg FROM (SELECT COUNT(*) as order_count FROM orders GROUP BY user_id) as counts");
                            $avg_orders = $avg_orders_query->fetch_assoc()['avg'];
                            echo $avg_orders ? number_format($avg_orders, 1) : '0';
                            ?>
                        </div>
                        <div class="stat-label">Rata-rata Pesanan/Pelanggan</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-value">
                            <?php 
                            $total_revenue_query = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE status = 'completed'");
                            echo 'Rp ' . number_format($total_revenue_query->fetch_assoc()['total'] ?? 0, 0, ',', '.');
                            ?>
                        </div>
                        <div class="stat-label">Total Pendapatan</div>
                    </div>
                </div>

                <!-- Filter Form -->
                <div class="filter-form">
                    <div class="filter-group">
                        <label for="search">Cari Pelanggan:</label>
                        <input type="text" id="search" name="search" placeholder="Username, Email, atau Nama..." value="<?php echo htmlspecialchars($search_filter); ?>">
                    </div>
                    <button type="submit" class="btn-filter" onclick="window.location.href='?search=' + document.getElementById('search').value">
                        <i class="fas fa-search"></i> Cari
                    </button>
                    <?php if (!empty($search_filter)): ?>
                        <a href="pelanggan.php" class="btn-filter" style="background: #6b7280;">
                            <i class="fas fa-times"></i> Reset
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Customers Table -->
                <div class="table-container">
                    <?php if ($customers_result->num_rows > 0): ?>
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Pelanggan</th>
                                    <th>Email Pengguna</th>
                                    <th>Total Pesanan</th>
                                    <th>Total Belanja</th>
                                    <th>Pesanan Terakhir</th>
                                    <th>Bergabung</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($customer = $customers_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="customer-info">
                                                <img src="../assets/img/profiles/<?php echo $customer['profile_photo'] ?: 'default.png'; ?>" 
                                                     alt="<?php echo htmlspecialchars($customer['username']); ?>" 
                                                     class="customer-avatar">
                                                <div class="customer-details">
                                                    <div class="customer-name"><?php echo htmlspecialchars($customer['username']); ?></div>
                                                    <div class="customer-email"><?php echo htmlspecialchars($customer['email']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                        <td>
                                            <span style="font-weight: 600; color: var(--primary-color);">
                                                <?php echo number_format($customer['total_orders']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span style="font-weight: 600; color: var(--success-color);">
                                                <?php echo 'Rp ' . number_format($customer['total_spent'] ?: 0, 0, ',', '.'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            if ($customer['last_order_date']) {
                                                echo date('d M Y, H:i', strtotime($customer['last_order_date']));
                                            } else {
                                                echo '<span style="color: #9ca3af;">Belum ada pesanan</span>';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo date('d M Y', strtotime($customer['created_at'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="pelanggan_detail.php?id=<?php echo $customer['id']; ?>" class="btn-action btn-view" title="Lihat Detail">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button 
                                                    class="btn-action btn-delete" 
                                                    title="Hapus Pelanggan"
                                                    onclick="confirmDelete(<?php echo $customer['id']; ?>, '<?php echo htmlspecialchars($customer['username']); ?>', <?php echo $customer['total_orders']; ?>)"
                                                    <?php echo $customer['total_orders'] > 0 ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-trash"></i>
                                                </button>
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
                                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search_filter); ?>" 
                                       class="pagination-link <?php echo $i == $page ? 'active' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <h3>Tidak ada pelanggan</h3>
                            <p>Belum ada pelanggan yang sesuai dengan filter yang dipilih.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function confirmDelete(customerId, customerName, totalOrders) {
            if (totalOrders > 0) {
                alert('Tidak dapat menghapus pelanggan yang memiliki riwayat pesanan.');
                return;
            }
            
            if (confirm(`Apakah Anda yakin ingin menghapus pelanggan "${customerName}"?`)) {
                window.location.href = `pelanggan.php?delete=${customerId}`;
            }
        }

        // Search on Enter key
        document.getElementById('search').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                window.location.href = '?search=' + this.value;
            }
        });
    </script>
</body>
</html>
