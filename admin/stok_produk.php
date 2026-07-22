<?php
session_start();

// Simple session check - redirect jika tidak login sebagai admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../pengguna/login.php");
    exit();
}

// Database connection
require '../config/config.php';

// Update stok produk
if (isset($_POST['update_stock'])) {
    $product_id = $_POST['product_id'];
    $new_stock = $_POST['new_stock'];
    
    // Validasi input
    if ($new_stock >= 0 && is_numeric($new_stock)) {
        $query = "UPDATE products SET stock = ? WHERE product_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $new_stock, $product_id);
        $stmt->execute();
        $stmt->close();
        
        // Set success message
        $_SESSION['success_message'] = "Stok produk berhasil diperbarui!";
    } else {
        $_SESSION['error_message'] = "Stok harus berupa angka positif!";
    }
    
    header("Location: stok_produk.php");
    exit();
}

// Get produk dengan stok habis
$query_out_of_stock = "SELECT p.*, c.category_name 
                       FROM products p 
                       JOIN categories c ON p.category_id = c.category_id 
                       WHERE p.stock <= 0 
                       ORDER BY p.created_at DESC";
$result_out_of_stock = $conn->query($query_out_of_stock);

// Get produk dengan stok rendah (kurang dari 5)
$query_low_stock = "SELECT p.*, c.category_name 
                   FROM products p 
                   JOIN categories c ON p.category_id = c.category_id 
                   WHERE p.stock > 0 AND p.stock <= 5 
                   ORDER BY p.stock ASC";
$result_low_stock = $conn->query($query_low_stock);

// Get semua produk untuk update stok
$query_all_products = "SELECT p.*, c.category_name 
                       FROM products p 
                       JOIN categories c ON p.category_id = c.category_id 
                       ORDER BY p.stock ASC";
$result_all_products = $conn->query($query_all_products);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Stok - Admin hobiBekasan</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --dark-color: #1f2937;
            --light-color: #f9fafb;
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

        /* Sidebar */
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
        }

        .menu-item.active {
            background: #764ba2;
            color: #fff;
            border-left-color: #fff;
        }

        .menu-item i {
            width: 20px;
            margin-right: 10px;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 20px;
            background: #ffffff;
        }

        .content-header {
            background: #ffffff;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .content-header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .content-header p {
            margin: 10px 0 0 0;
            color: #6b7280;
            font-size: 1rem;
        }

        /* Cards */
        .stats-card {
            background: #ffffff;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border-left: 4px solid var(--primary-color);
        }

        .stats-card.danger {
            border-left-color: var(--danger-color);
        }

        .stats-card.warning {
            border-left-color: var(--warning-color);
        }

        .stats-card h3 {
            margin: 0 0 15px 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stats-card .number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
        }

        .stats-card.danger .number {
            color: var(--danger-color);
        }

        .stats-card.warning .number {
            color: var(--warning-color);
        }

        /* Tables */
        .table-container {
            background: #ffffff;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .table-container h3 {
            margin: 0 0 20px 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }

        .table {
            margin: 0;
        }

        .table thead th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            font-weight: 600;
            border: none;
            padding: 15px;
        }

        .table tbody tr {
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background: rgba(102, 126, 234, 0.05);
            transform: translateY(-1px);
        }

        .table tbody td {
            padding: 15px;
            vertical-align: middle;
            border-color: #e5e7eb;
        }

        .stock-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .stock-badge.out-of-stock {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        .stock-badge.low-stock {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }

        .stock-badge.in-stock {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .product-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e5e7eb;
        }

        /* Forms */
        .stock-form {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stock-input {
            width: 100px;
            padding: 8px 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .stock-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-stock {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-stock-update {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .btn-stock-update:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        /* Alerts */
        .alert {
            border: none;
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            .content-header h1 {
                font-size: 1.5rem;
            }

            .stats-card .number {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-store me-2"></i>Admin Panel</h3>
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
                <a href="pengguna.php" class="menu-item">
                    <i class="fas fa-users"></i> Pengguna
                </a>
                <a href="pembelian.php" class="menu-item">
                    <i class="fas fa-shopping-cart"></i> Pembelian
                </a>
                <a href="laporan.php" class="menu-item">
                    <i class="fas fa-chart-bar"></i> Laporan
                </a>
                <a href="stok_produk.php" class="menu-item active">
                    <i class="fas fa-warehouse"></i> Stok Produk
                </a>
                <a href="rating.php" class="menu-item">
                    <i class="fas fa-star"></i> Rating
                </a>
                                <a href="../pengguna/logout.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i> Keluar
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="content-header">
                <h1><i class="fas fa-warehouse"></i> Manajemen Stok Produk</h1>
                <p>Kelola stok produk, pantau produk yang habis, dan tambahkan stok dengan mudah.</p>
            </div>

            <!-- Alerts -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="row">
                <div class="col-md-4">
                    <div class="stats-card danger">
                        <h3><i class="fas fa-exclamation-triangle"></i> Stok Habis</h3>
                        <p class="number"><?php echo $result_out_of_stock->num_rows; ?></p>
                        <p class="text-muted mb-0">Produk yang perlu segera diisi ulang</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card warning">
                        <h3><i class="fas fa-exclamation-circle"></i> Stok Rendah</h3>
                        <p class="number"><?php echo $result_low_stock->num_rows; ?></p>
                        <p class="text-muted mb-0">Produk dengan stok kurang dari 5</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card">
                        <h3><i class="fas fa-box"></i> Total Produk</h3>
                        <p class="number"><?php echo $result_all_products->num_rows; ?></p>
                        <p class="text-muted mb-0">Semua produk dalam database</p>
                    </div>
                </div>
            </div>

            <!-- Out of Stock Products -->
            <?php if ($result_out_of_stock->num_rows > 0): ?>
            <div class="table-container">
                <h3><i class="fas fa-exclamation-triangle text-danger"></i> Produk Stok Habis</h3>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Gambar</th>
                                <th>Nama Produk</th>
                                <th>Kategori</th>
                                <th>Harga</th>
                                <th>Stok</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result_out_of_stock->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <img src="../assets/img/products/<?php echo $row['image']; ?>" 
                                         alt="<?php echo $row['name']; ?>" 
                                         class="product-img"
                                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjYwIiBoZWlnaHQ9IjYwIiBmaWxsPSIjRjVGNUY1Ii8+CjxwYXRoIGQ9Ik0yMCAyMEg0MFY0MEgyMFYyMFoiIGZpbGw9IiNEMUQ1REUiLz4KPGNpcmNsZSBjeD0iMzAiIGN5PSIzMCIgcj0iOCIgZmlsbD0iIzk3NEEzNCIvPgo8L3N2Zz4K';">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($row['category_name']); ?></span>
                                </td>
                                <td>Rp <?php echo number_format($row['price'], 0, ',', '.'); ?></td>
                                <td>
                                    <span class="stock-badge out-of-stock">0</span>
                                </td>
                                <td>
                                    <span class="badge bg-danger">Habis</span>
                                </td>
                                <td>
                                    <form method="POST" class="stock-form">
                                        <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
                                        <input type="number" name="new_stock" class="stock-input" min="0" placeholder="0" required>
                                        <button type="submit" name="update_stock" class="btn-stock btn-stock-update">
                                            <i class="fas fa-plus"></i> Tambah
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Low Stock Products -->
            <?php if ($result_low_stock->num_rows > 0): ?>
            <div class="table-container">
                <h3><i class="fas fa-exclamation-circle text-warning"></i> Produk Stok Rendah</h3>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Gambar</th>
                                <th>Nama Produk</th>
                                <th>Kategori</th>
                                <th>Harga</th>
                                <th>Stok</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result_low_stock->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <img src="../assets/img/products/<?php echo $row['image']; ?>" 
                                         alt="<?php echo $row['name']; ?>" 
                                         class="product-img"
                                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjYwIiBoZWlnaHQ9IjYwIiBmaWxsPSIjRjVGNUY1Ii8+CjxwYXRoIGQ9Ik0yMCAyMEg0MFY0MEgyMFYyMFoiIGZpbGw9IiNEMUQ1REUiLz4KPGNpcmNsZSBjeD0iMzAiIGN5PSIzMCIgcj0iOCIgZmlsbD0iIzk3NEEzNCIvPgo8L3N2Zz4K';">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($row['category_name']); ?></span>
                                </td>
                                <td>Rp <?php echo number_format($row['price'], 0, ',', '.'); ?></td>
                                <td>
                                    <span class="stock-badge low-stock"><?php echo $row['stock']; ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-warning">Rendah</span>
                                </td>
                                <td>
                                    <form method="POST" class="stock-form">
                                        <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
                                        <input type="number" name="new_stock" class="stock-input" min="0" value="<?php echo $row['stock']; ?>" required>
                                        <button type="submit" name="update_stock" class="btn-stock btn-stock-update">
                                            <i class="fas fa-sync"></i> Update
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- All Products -->
            <div class="table-container">
                <h3><i class="fas fa-box"></i> Semua Produk</h3>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Gambar</th>
                                <th>Nama Produk</th>
                                <th>Kategori</th>
                                <th>Harga</th>
                                <th>Stok</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Reset pointer untuk all products
                            $result_all_products->data_seek(0);
                            while ($row = $result_all_products->fetch_assoc()): 
                            ?>
                            <tr>
                                <td>
                                    <img src="../assets/img/products/<?php echo $row['image']; ?>" 
                                         alt="<?php echo $row['name']; ?>" 
                                         class="product-img"
                                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjYwIiBoZWlnaHQ9IjYwIiBmaWxsPSIjRjVGNUY1Ii8+CjxwYXRoIGQ9Ik0yMCAyMEg0MFY0MEgyMFYyMFoiIGZpbGw9IiNEMUQ1REUiLz4KPGNpcmNsZSBjeD0iMzAiIGN5PSIzMCIgcj0iOCIgZmlsbD0iIzk3NEEzNCIvPgo8L3N2Zz4K';">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($row['category_name']); ?></span>
                                </td>
                                <td>Rp <?php echo number_format($row['price'], 0, ',', '.'); ?></td>
                                <td>
                                    <?php if ($row['stock'] <= 0): ?>
                                        <span class="stock-badge out-of-stock"><?php echo $row['stock']; ?></span>
                                    <?php elseif ($row['stock'] <= 5): ?>
                                        <span class="stock-badge low-stock"><?php echo $row['stock']; ?></span>
                                    <?php else: ?>
                                        <span class="stock-badge in-stock"><?php echo $row['stock']; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['stock'] <= 0): ?>
                                        <span class="badge bg-danger">Habis</span>
                                    <?php elseif ($row['stock'] <= 5): ?>
                                        <span class="badge bg-warning">Rendah</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Tersedia</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" class="stock-form">
                                        <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
                                        <input type="number" name="new_stock" class="stock-input" min="0" value="<?php echo $row['stock']; ?>" required>
                                        <button type="submit" name="update_stock" class="btn-stock btn-stock-update">
                                            <i class="fas fa-sync"></i> Update
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
