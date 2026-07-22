<?php
session_start();

// Simple session check - redirect jika tidak login sebagai admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../pengguna/login.php");
    exit();
}

// Database connection
require '../config/config.php';

// Initialize variables
$semuadata = [];
$tgl_mulai = "";
$tgl_selesai = "";
$status = "";
$total_semua = 0;
$chart_data = [];

// Handle filter form submission
if (isset($_POST["filter"])) {
    $tgl_mulai = $_POST["start_date"];
    $tgl_selesai = $_POST["end_date"];
    $status = isset($_POST["status"]) ? $_POST["status"] : "";

    if (!empty($tgl_mulai) && !empty($tgl_selesai)) {
        if (strtotime($tgl_mulai) > strtotime($tgl_selesai)) {
            $error_message = "Tanggal awal tidak boleh melebihi tanggal akhir.";
        } else {
            // Query untuk mendapatkan data orders (bukan transactions)
            if ($status === "all") {
                $stmt = $conn->prepare("SELECT o.*, u.username, u.email 
                                      FROM orders o 
                                      LEFT JOIN users u ON o.user_id = u.id 
                                      WHERE DATE(o.created_at) BETWEEN ? AND ? 
                                      ORDER BY o.created_at DESC");
                $stmt->bind_param("ss", $tgl_mulai, $tgl_selesai);
            } else {
                $stmt = $conn->prepare("SELECT o.*, u.username, u.email 
                                      FROM orders o 
                                      LEFT JOIN users u ON o.user_id = u.id 
                                      WHERE o.status = ? AND DATE(o.created_at) BETWEEN ? AND ? 
                                      ORDER BY o.created_at DESC");
                $stmt->bind_param("sss", $status, $tgl_mulai, $tgl_selesai);
            }
            $stmt->execute();
            $result = $stmt->get_result();
    
            while ($row = $result->fetch_assoc()) {
                $semuadata[] = $row;
                $total_semua += $row['total_amount'];
    
                $tanggal = date("d-m-Y", strtotime($row['created_at']));
                if (!isset($chart_data[$tanggal])) {
                    $chart_data[$tanggal] = 0;
                }
                $chart_data[$tanggal] += $row['total_amount'];
            }
        }
    }    
}

// Get overall statistics
$total_orders_query = $conn->query("SELECT COUNT(*) as total FROM orders");
$total_orders = $total_orders_query->fetch_assoc()['total'];

$completed_orders_query = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 'completed'");
$completed_orders = $completed_orders_query->fetch_assoc()['total'];

$total_revenue_query = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE status = 'completed'");
$total_revenue = $total_revenue_query->fetch_assoc()['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pembelian - hobiBekasan Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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
            text-align: center;
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
        }

        .form-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }

        .form-control {
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 0.75rem;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
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
        }

        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-export {
            background: linear-gradient(135deg, var(--success-color), #059669);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            color: white;
        }

        /* Table Styles */
        .table-container {
            background: #ffffff;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid #e5e7eb;
            overflow-x: auto;
            margin-bottom: 2rem;
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

        /* Chart Container */
        .chart-container {
            background: #ffffff;
            border-radius: 20px;
            padding: 1rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid #e5e7eb;
            margin-bottom: 2rem;
            max-height: 300px;
        }

        .chart-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 1rem;
            text-align: center;
        }

        #salesChart {
            max-height: 200px !important;
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
            
            .filter-form .row {
                flex-direction: column;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
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
                <a href="pelanggan.php" class="menu-item">
                    <i class="fas fa-users"></i> Pelanggan
                </a>
                <a href="laporan.php" class="menu-item active">
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
                    <h1 class="page-title">Laporan Pembelian</h1>
                    <p class="page-subtitle">Analisis dan laporan penjualan produk</p>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($total_orders); ?></div>
                        <div class="stat-label">Total Pesanan</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($completed_orders); ?></div>
                        <div class="stat-label">Pesanan Selesai</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon purple">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-value">Rp <?php echo number_format($total_revenue, 0, ',', '.'); ?></div>
                        <div class="stat-label">Total Pendapatan</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-value">Rp <?php echo number_format($total_semua, 0, ',', '.'); ?></div>
                        <div class="stat-label">Total Filter</div>
                    </div>
                </div>

                <!-- Filter Form -->
                <div class="filter-form">
                    <form method="POST" class="row g-3">
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">Dari Tanggal:</label>
                            <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($tgl_mulai); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">Sampai Tanggal:</label>
                            <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($tgl_selesai); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status:</label>
                            <select name="status" class="form-control">
                                <option value="all" <?php echo $status === "all" ? "selected" : ""; ?>>Semua Status</option>
                                <option value="pending" <?php echo $status === "pending" ? "selected" : ""; ?>>Pending</option>
                                <option value="processing" <?php echo $status === "processing" ? "selected" : ""; ?>>Processing</option>
                                <option value="completed" <?php echo $status === "completed" ? "selected" : ""; ?>>Completed</option>
                                <option value="cancelled" <?php echo $status === "cancelled" ? "selected" : ""; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="submit" name="filter" class="btn-filter">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                            <?php if (!empty($semuadata)): ?>
                                <a href="laporan_export.php?start_date=<?php echo urlencode($tgl_mulai); ?>&end_date=<?php echo urlencode($tgl_selesai); ?>&status=<?php echo urlencode($status); ?>" class="btn-export">
                                    <i class="fas fa-download"></i> Export
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Chart -->
                <?php if (!empty($chart_data)): ?>
                    <div class="chart-container">
                        <h3 class="chart-title">Grafik Penjualan</h3>
                        <canvas id="salesChart" width="300" height="80"></canvas>
                    </div>
                <?php endif; ?>

                <!-- Table -->
                <div class="table-container">
                    <?php if (!empty($semuadata)): ?>
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>ID Pesanan</th>
                                    <th>Pelanggan</th>
                                    <th>Tanggal</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($semuadata as $data): ?>
                                    <tr>
                                        <td>
                                            <strong>#<?php echo str_pad($data['id'], 6, '0', STR_PAD_LEFT); ?></strong>
                                        </td>
                                        <td>
                                            <div>
                                                <div style="font-weight: 600;"><?php echo htmlspecialchars($data['username'] ?: 'Guest'); ?></div>
                                                <div style="font-size: 0.85rem; color: #6b7280;"><?php echo htmlspecialchars($data['email'] ?? '-'); ?></div>
                                            </div>
                                        </td>
                                        <td><?php echo date('d M Y, H:i', strtotime($data['created_at'])); ?></td>
                                        <td>
                                            <span class="amount">Rp <?php echo number_format($data['total_amount'], 0, ',', '.'); ?></span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $data['status']; ?>">
                                                <?php 
                                                $status_text = [
                                                    'pending' => 'Menunggu',
                                                    'processing' => 'Proses',
                                                    'completed' => 'Selesai',
                                                    'cancelled' => 'Batal'
                                                ];
                                                echo $status_text[$data['status']] ?? ucfirst($data['status']);
                                                ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-file-alt"></i>
                            <h3>Tidak Ada Data</h3>
                            <p>Pilih tanggal filter untuk melihat laporan pembelian.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Chart Script -->
    <?php if (!empty($chart_data)): ?>
        <script>
            const ctx = document.getElementById('salesChart').getContext('2d');
            const salesChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_keys($chart_data)); ?>,
                    datasets: [{
                        label: 'Total Penjualan',
                        data: <?php echo json_encode(array_values($chart_data)); ?>,
                        borderColor: '#4f46e5',
                        backgroundColor: 'rgba(79, 70, 229, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + value.toLocaleString('id-ID');
                                }
                            }
                        }
                    }
                }
            });
        </script>
    <?php endif; ?>
</body>
</html>
