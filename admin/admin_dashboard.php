<?php
session_start();

// Simple session check - redirect jika tidak login sebagai admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../pengguna/login.php");
    exit();
}

// Database connection
require '../config/config.php';

// Dashboard data queries
try {
    // Total Produk
    $produk_query = $conn->query("SELECT COUNT(*) AS total FROM products");
    $total_produk = $produk_query ? $produk_query->fetch_assoc()['total'] : 0;

    // Total Kategori
    $kategori_query = $conn->query("SELECT COUNT(*) AS total FROM categories");
    $total_kategori = $kategori_query ? $kategori_query->fetch_assoc()['total'] : 0;

    // Total Pesanan (Orders)
    $pesanan_query = $conn->query("SELECT COUNT(*) AS total FROM orders");
    $total_pesanan = $pesanan_query ? $pesanan_query->fetch_assoc()['total'] : 0;

    // Total User
    $user_query = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'user'");
    $total_user = $user_query ? $user_query->fetch_assoc()['total'] : 0;

    // Total Pendapatan dari Orders
    $pendapatan_query = $conn->query("SELECT SUM(total_amount) AS total FROM orders WHERE status = 'completed'");
    $pendapatan_result = $pendapatan_query ? $pendapatan_query->fetch_assoc() : ['total' => 0];
    $total_pendapatan = $pendapatan_result['total'] ?? 0;

    // Pesanan Pending
    $pending_query = $conn->query("SELECT COUNT(*) AS total FROM orders WHERE status = 'pending'");
    $total_pending = $pending_query ? $pending_query->fetch_assoc()['total'] : 0;

    // Pesanan Processing
    $processing_query = $conn->query("SELECT COUNT(*) AS total FROM orders WHERE status = 'processing'");
    $total_processing = $processing_query ? $processing_query->fetch_assoc()['total'] : 0;

    // Pesanan Completed
    $completed_query = $conn->query("SELECT COUNT(*) AS total FROM orders WHERE status = 'completed'");
    $total_completed = $completed_query ? $completed_query->fetch_assoc()['total'] : 0;

    // Data untuk grafik pesanan (6 bulan terakhir)
    $grafik_query = $conn->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as bulan,
            COUNT(*) as total_pesanan,
            SUM(total_amount) as total_pendapatan
        FROM orders 
        WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY bulan ASC
    ");

    $grafik_data = [];
    $pendapatan_data = [];
    $bulan_labels = [];
    
    if ($grafik_query) {
        while ($row = $grafik_query->fetch_assoc()) {
            $bulan_labels[] = date('M Y', strtotime($row['bulan'] . '-01'));
            $grafik_data[] = $row['total_pesanan'];
            $pendapatan_data[] = $row['total_pendapatan'];
        }
    }

    // Pesanan Terbaru
    $pesanan_terbaru = $conn->query("
        SELECT o.id, o.order_number, o.total_amount, o.created_at, o.status, u.username
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        ORDER BY o.created_at DESC 
        LIMIT 10
    ");

} catch (Exception $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    // Set default values
    $total_produk = 0;
    $total_kategori = 0;
    $total_pesanan = 0;
    $total_user = 0;
    $total_pendapatan = 0;
    $total_pending = 0;
    $total_processing = 0;
    $total_completed = 0;
    $bulan_labels = [];
    $grafik_data = [];
    $pendapatan_data = [];
    $pesanan_terbaru = null;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - hobiBekasan</title>
    
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

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .dashboard-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .dashboard-title {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .dashboard-subtitle {
            color: #6b7280;
            font-size: 1.1rem;
            font-weight: 500;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }

        .stat-card.primary::before { background: linear-gradient(90deg, #3b82f6, #1d4ed8); }
        .stat-card.success::before { background: linear-gradient(90deg, #10b981, #059669); }
        .stat-card.warning::before { background: linear-gradient(90deg, #f59e0b, #d97706); }
        .stat-card.danger::before { background: linear-gradient(90deg, #ef4444, #dc2626); }
        .stat-card.info::before { background: linear-gradient(90deg, #3b82f6, #2563eb); }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: white;
        }

        .stat-card.primary .stat-icon { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
        .stat-card.success .stat-icon { background: linear-gradient(135deg, #10b981, #059669); }
        .stat-card.warning .stat-icon { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .stat-card.danger .stat-icon { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .stat-card.info .stat-icon { background: linear-gradient(135deg, #3b82f6, #2563eb); }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
            line-height: 1;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .chart-container {
            background: #ffffff;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid #e5e7eb;
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .chart-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark-color);
        }

        .chart-period {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .table-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
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

        .modern-table {
            width: 100%;
            border-collapse: collapse;
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

        .amount {
            font-weight: 600;
            color: var(--success-color);
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }
        .stat-card:nth-child(5) { animation-delay: 0.5s; }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            
            .content {
                margin-left: 70px;
                padding: 1rem;
            }
            
            .dashboard-title {
                font-size: 2rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .stat-value {
                font-size: 2rem;
            }
            
            .modern-table {
                font-size: 0.8rem;
            }
            
            .modern-table th,
            .modern-table td {
                padding: 0.75rem 0.5rem;
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
                <a href="admin_dashboard.php" class="menu-item active">
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
            <div class="dashboard-container">
                <!-- Header -->
                <div class="dashboard-header fade-in-up">
                    <h1 class="dashboard-title">
                        <i class="fas fa-chart-line"></i> Dashboard Admin
                    </h1>
                    <p class="dashboard-subtitle">Monitor dan kelola bisnis thrift Anda secara real-time</p>
                </div>

                <!-- Dashboard Cards -->
                <div class="stats-grid">
                    <div class="stat-card primary fade-in-up">
                        <div class="stat-icon">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($total_produk, 0, ',', '.'); ?></div>
                        <div class="stat-label">Total Produk</div>
                    </div>

                    <div class="stat-card success fade-in-up">
                        <div class="stat-icon">
                            <i class="fas fa-tags"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($total_kategori, 0, ',', '.'); ?></div>
                        <div class="stat-label">Total Kategori</div>
                    </div>

                    <div class="stat-card warning fade-in-up">
                        <div class="stat-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($total_pesanan, 0, ',', '.'); ?></div>
                        <div class="stat-label">Total Pesanan</div>
                    </div>

                    <div class="stat-card danger fade-in-up">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($total_user, 0, ',', '.'); ?></div>
                        <div class="stat-label">Total Pelanggan</div>
                    </div>

                    <div class="stat-card info fade-in-up">
                        <div class="stat-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-value">Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></div>
                        <div class="stat-label">Total Pendapatan</div>
                    </div>

                    <div class="stat-card primary fade-in-up">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($total_pending, 0, ',', '.'); ?></div>
                        <div class="stat-label">Pesanan Pending</div>
                    </div>
                </div>

                <!-- Grafik Pesanan -->
                <div class="chart-container fade-in-up">
                    <div class="chart-header">
                        <h2 class="chart-title">
                            <i class="fas fa-chart-bar"></i> Grafik Pesanan
                        </h2>
                        <span class="chart-period">6 Bulan Terakhir</span>
                    </div>
                    <canvas id="transaksiChart" style="max-height: 400px;"></canvas>
                </div>

                <!-- Tabel Transaksi Terbaru -->
                <div class="table-container fade-in-up">
                    <div class="table-header">
                        <h2 class="table-title">
                            <i class="fas fa-receipt"></i> Transaksi Terbaru
                        </h2>
                        <span class="badge-count">10 Transaksi</span>
                    </div>
                    
                    <?php if ($pesanan_terbaru && $pesanan_terbaru->num_rows > 0): ?>
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama</th>
                                    <th>Total</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($transaksi = $pesanan_terbaru->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $transaksi['order_number']; ?></td>
                                        <td><?php echo htmlspecialchars($transaksi['username']); ?></td>
                                        <td class="amount">Rp <?php echo number_format($transaksi['total_amount'], 0, ',', '.'); ?></td>
                                        <td><?php echo date('d M Y, H:i', strtotime($transaksi['created_at'])); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $transaksi['status']; ?>">
                                                <?php 
                                                $status_text = [
                                                    'completed' => 'Selesai',
                                                    'pending' => 'Menunggu',
                                                    'processing' => 'Proses'
                                                ];
                                                echo $status_text[$transaksi['status']] ?? ucfirst($transaksi['status']);
                                                ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>Belum ada transaksi yang tercatat</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Chart Configuration
        const ctx = document.getElementById('transaksiChart').getContext('2d');
        
        const chartData = {
            labels: <?php echo json_encode($bulan_labels); ?>,
            datasets: [
                {
                    label: 'Jumlah Transaksi',
                    data: <?php echo json_encode($grafik_data); ?>,
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    borderColor: 'rgba(79, 70, 229, 1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: 'rgba(79, 70, 229, 1)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8
                },
                {
                    label: 'Total Pendapatan (Rp)',
                    data: <?php echo json_encode($pendapatan_data); ?>,
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderColor: 'rgba(16, 185, 129, 1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: 'rgba(16, 185, 129, 1)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    yAxisID: 'y1'
                }
            ]
        };

        const chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20,
                        font: {
                            family: 'Inter',
                            size: 12,
                            weight: '600'
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    padding: 12,
                    displayColors: true,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                if (context.datasetIndex === 1) {
                                    label += 'Rp ' + new Intl.NumberFormat('id-ID').format(context.parsed.y);
                                } else {
                                    label += new Intl.NumberFormat('id-ID').format(context.parsed.y);
                                }
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            family: 'Inter',
                            size: 11
                        }
                    }
                },
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Jumlah Transaksi',
                        font: {
                            family: 'Inter',
                            size: 12,
                            weight: '600'
                        }
                    },
                    ticks: {
                        font: {
                            family: 'Inter',
                            size: 11
                        }
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Pendapatan (Rp)',
                        font: {
                            family: 'Inter',
                            size: 12,
                            weight: '600'
                        }
                    },
                    ticks: {
                        font: {
                            family: 'Inter',
                            size: 11
                        },
                        callback: function(value) {
                            return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                        }
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        };

        // Create Chart
        new Chart(ctx, {
            type: 'line',
            data: chartData,
            options: chartOptions
        });

        // Sidebar Toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.querySelector('.sidebar');
            const content = document.querySelector('.content');
            
            // Toggle sidebar collapse/expand
            sidebar.addEventListener('click', function(e) {
                if (e.target.closest('.menu-item')) return;
                
                sidebar.classList.toggle('collapsed');
                if (sidebar.classList.contains('collapsed')) {
                    sidebar.style.width = '70px';
                    content.style.marginLeft = '70px';
                } else {
                    sidebar.style.width = '280px';
                    content.style.marginLeft = '280px';
                }
            });
        });
    </script>
</body>
</html>

<?php
// Close database connection
if (isset($conn)) {
    $conn->close();
}
?>
