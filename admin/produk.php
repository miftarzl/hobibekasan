<?php
session_start();

// Simple session check - redirect jika tidak login sebagai admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../pengguna/login.php");
    exit();
}

// Database connection
require '../config/config.php';

// Menambahkan Produk
if (isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $category_id = $_POST['category_id'];
    $stock = $_POST['stock'];
    $sizes = isset($_POST['sizes']) ? implode(',', $_POST['sizes']) : '';
    $image = $_FILES['image']['name'];
    
    // Upload imageww
    if ($image) {
        $target_dir = "../assets/img/products/";
        $target_file = $target_dir . basename($image);
        move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
    }
    
    // Query dengan prepared statement untuk mencegah SQL injection
    $query = "INSERT INTO products (name, description, price, category_id, stock, image, sizes) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssdiiss", $name, $description, $price, $category_id, $stock, $image, $sizes);
    $stmt->execute();
    $stmt->close();
    
    header("Location: produk.php");
    exit();
}

// Menghapus Produk
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Query dengan prepared statement untuk mencegah SQL injection
    $query = "DELETE FROM products WHERE product_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: produk.php");
    exit();
}

// Tambah Stok Produk
if (isset($_POST['add_stock'])) {
    $product_id = $_POST['product_id'];
    $add_amount = $_POST['add_stock_amount'];
    
    // Validasi input
    if ($add_amount > 0 && is_numeric($add_amount)) {
        // Ambil stok saat ini
        $query = "SELECT stock FROM products WHERE product_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $current_stock = $result->fetch_assoc()['stock'];
        $stmt->close();
        
        // Hitung stok baru
        $new_stock = $current_stock + $add_amount;
        
        // Update stok
        $query = "UPDATE products SET stock = ? WHERE product_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $new_stock, $product_id);
        $stmt->execute();
        $stmt->close();
        
        $_SESSION['success_message'] = "Berhasil menambahkan {$add_amount} stok. Stok saat ini: {$new_stock}";
    } else {
        $_SESSION['error_message'] = "Jumlah stok harus berupa angka positif!";
    }
    
    header("Location: produk.php");
    exit();
}

// Edit Produk (via modal)
if (isset($_POST['edit_product'])) {
    $product_id = $_POST['product_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $category_id = $_POST['category_id'];
    $sizes = isset($_POST['sizes']) ? implode(',', $_POST['sizes']) : '';
    
    // Handle image upload if new image is provided
    if ($_FILES['edit_image']['name']) {
        $image = $_FILES['edit_image']['name'];
        $target_dir = "../assets/img/products/";
        $target_file = $target_dir . basename($image);
        move_uploaded_file($_FILES["edit_image"]["tmp_name"], $target_file);
        
        $query = "UPDATE products SET name=?, description=?, price=?, category_id=?, image=?, sizes=? WHERE product_id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssdissi", $name, $description, $price, $category_id, $image, $sizes, $product_id);
    } else {
        $query = "UPDATE products SET name=?, description=?, price=?, category_id=?, sizes=? WHERE product_id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssdissi", $name, $description, $price, $category_id, $sizes, $product_id);
    }
    
    $stmt->execute();
    $stmt->close();
    
    $_SESSION['success_message'] = "Produk berhasil diperbarui!";
    header("Location: produk.php");
    exit();
}

// Ambil data produk
$products = $conn->query("SELECT * FROM products ORDER BY created_at DESC");

// Ambil data kategori untuk dropdown
$categories = $conn->query("SELECT * FROM categories ORDER BY name");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Produk - hobiBekasan</title>
    
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

        .content-container {
            max-width: 1400px;
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

        .btn-stock-custom {
            background: linear-gradient(135deg, #10b981 30%, #059669 70%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.2);
        }

        .btn-stock-custom:hover {
            background: linear-gradient(135deg, #059669 30%, #047857 70%);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
            color: white;
        }

        .products-table-container {
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

        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
        }

        .btn-action {
            padding: 6px 12px;
            border-radius: 8px;
            border: none;
            font-size: 0.8rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            margin: 0 2px;
        }

        .btn-edit {
            background: var(--warning-color);
            color: white;
        }

        .btn-edit:hover {
            background: #d97706;
            color: white;
        }

        .btn-delete {
            background: var(--danger-color);
            color: white;
        }

        .btn-delete:hover {
            background: #dc2626;
            color: white;
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

        /* Modal Styles */
        .modal-content {
            border-radius: 20px;
            border: none;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 20px 20px 0 0;
            border: none;
        }

        .modal-title {
            font-weight: 700;
        }

        .form-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }

        .form-control {
            border-radius: 12px;
            border: 1px solid var(--border-color);
            padding: 12px 16px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        /* Stock Badge Styles */
        .stock-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .stock-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .stock-badge.out-of-stock {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .stock-badge.low-stock {
            background: rgba(245, 158, 11, 0.1);
            color: #d97706;
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .stock-badge.in-stock {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .stock-number {
            font-weight: 600;
            color: var(--dark-color);
            background: var(--light-color);
            padding: 4px 8px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        /* Stock Button Style */
        .btn-stock {
            background: linear-gradient(135deg, #10b981 30%, #059669 70%);
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            margin: 0 2px;
            cursor: pointer;
            pointer-events: auto;
            position: relative;
            z-index: 10;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }

        .btn-stock:hover {
            background: linear-gradient(135deg, #059669 30%, #047857 70%);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(16, 185, 129, 0.3);
            color: white;
        }

        .btn-stock:active {
            transform: translateY(0);
        }

        .btn-stock:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.3);
        }

        /* Action Buttons Container */
        .action-buttons {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
            justify-content: center;
            position: relative;
            z-index: 5;
        }

        /* Ensure table cells don't block clicks */
        .modern-table td {
            position: relative;
            z-index: 1;
        }

        .modern-table td:hover {
            z-index: 2;
        }

        /* Product Info in Modal */
        .product-info {
            background: var(--light-color);
            padding: 12px;
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
        }

        .product-info h6 {
            margin: 0 0 8px 0;
            color: var(--dark-color);
            font-weight: 600;
        }

        .product-info p {
            margin: 0;
            font-size: 0.9rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
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
                <a href="admin_dashboard.php" class="menu-item">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="produk.php" class="menu-item active">
                    <i class="fas fa-box"></i> Produk
                </a>
                <a href="kategori.php" class="menu-item">
                    <i class="fas fa-tags"></i> Kategori
                </a>
                <a href="pembelian.php" class="menu-item">
                    <i class="fas fa-shopping-cart"></i> Pembelian
                </a>
                <a href="pengguna.php" class="menu-item">
                    <i class="fas fa-users"></i> Pengguna
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
            <div class="content-container">
                <!-- Header -->
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-box"></i> Kelola Produk
                    </h1>
                    <p class="page-subtitle">Tambah, edit, dan hapus produk thrift Anda</p>
                </div>

                <!-- Alert Messages -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $_SESSION['success_message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $_SESSION['error_message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addProductModal">
                        <i class="fas fa-plus"></i> Tambah Produk
                    </button>
                    <button class="btn-stock-custom" data-bs-toggle="modal" data-bs-target="#addStockModal">
                        <i class="fas fa-plus-circle"></i> Tambah Stok
                    </button>
                    <a href="admin_dashboard.php" class="btn-primary-custom">
                        <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                    </a>
                </div>

                <!-- Products Table -->
                <div class="products-table-container">
                    <div class="table-header-custom">
                        <h2 class="table-title">
                            <i class="fas fa-list"></i> Daftar Produk
                        </h2>
                        <span class="badge-count">
                            <?php echo $products ? $products->num_rows : 0; ?> Produk
                        </span>
                    </div>
                    
                    <?php if ($products && $products->num_rows > 0): ?>
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Gambar</th>
                                    <th>Nama Produk</th>
                                    <th>Kategori</th>
                                    <th>Harga</th>
                                    <th>Stok</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($product = $products->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <?php if ($product['image']): ?>
                                                <img src="../assets/img/products/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="product-image">
                                            <?php else: ?>
                                                <img src="../assets/img/no-image.jpg" alt="No Image" class="product-image">
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td><?php echo htmlspecialchars($product['category_id']); ?></td>
                                        <td>Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></td>
                                        <td>
                                            <div class="stock-info">
                                                <?php if ($product['stock'] <= 0): ?>
                                                    <span class="stock-badge out-of-stock">Habis</span>
                                                <?php elseif ($product['stock'] <= 5): ?>
                                                    <span class="stock-badge low-stock">Rendah</span>
                                                <?php else: ?>
                                                    <span class="stock-badge in-stock">Tersedia</span>
                                                <?php endif; ?>
                                                <span class="stock-number"><?php echo $product['stock']; ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn-action btn-edit" data-bs-toggle="modal" data-bs-target="#editProductModal<?php echo $product['product_id']; ?>">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <a href="produk.php?delete=<?php echo $product['product_id']; ?>" class="btn-action btn-delete" onclick="return confirm('Apakah Anda yakin ingin menghapus produk ini?')">
                                                    <i class="fas fa-trash"></i> Hapus
                                                </a>
                                            </div>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-box-open"></i>
                            <p>Belum ada produk yang ditambahkan</p>
                            <p>Silakan tambah produk pertama Anda</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addProductModalLabel">
                        <i class="fas fa-plus"></i> Tambah Produk Baru
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Nama Produk</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="category_id" class="form-label">Kategori</label>
                                <select class="form-control" id="category_id" name="category_id" required>
                                    <option value="">Pilih Kategori</option>
                                    <?php while ($category = $categories->fetch_assoc()): ?>
                                        <option value="<?php echo $category['category_id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="price" class="form-label">Harga (Rp)</label>
                                <input type="number" class="form-control" id="price" name="price" min="0" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="stock" class="form-label">Stok</label>
                                <input type="number" class="form-control" id="stock" name="stock" min="0" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="image" class="form-label">Gambar Produk</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ukuran Sepatu (Pilih yang tersedia)</label>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="sizes[]" value="36" id="size36">
                                        <label class="form-check-label" for="size36">36</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="sizes[]" value="37" id="size37">
                                        <label class="form-check-label" for="size37">37</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="sizes[]" value="38" id="size38">
                                        <label class="form-check-label" for="size38">38</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="sizes[]" value="39" id="size39">
                                        <label class="form-check-label" for="size39">39</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="sizes[]" value="40" id="size40">
                                        <label class="form-check-label" for="size40">40</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="sizes[]" value="41" id="size41">
                                        <label class="form-check-label" for="size41">41</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="sizes[]" value="42" id="size42">
                                        <label class="form-check-label" for="size42">42</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="sizes[]" value="43" id="size43">
                                        <label class="form-check-label" for="size43">43</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="sizes[]" value="44" id="size44">
                                        <label class="form-check-label" for="size44">44</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="sizes[]" value="45" id="size45">
                                        <label class="form-check-label" for="size45">45</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ukuran Jaket (Pilih yang tersedia)</label>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="sizes[]" value="S" id="sizeS">
                                        <label class="form-check-label" for="sizeS">S</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="sizes[]" value="M" id="sizeM">
                                        <label class="form-check-label" for="sizeM">M</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="sizes[]" value="L" id="sizeL">
                                        <label class="form-check-label" for="sizeL">L</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="sizes[]" value="XL" id="sizeXL">
                                        <label class="form-check-label" for="sizeXL">XL</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="sizes[]" value="XXL" id="sizeXXL">
                                        <label class="form-check-label" for="sizeXXL">XXL</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" name="add_product" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan Produk
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Stock Modal -->
    <div class="modal fade" id="addStockModal" tabindex="-1" aria-labelledby="addStockModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addStockModalLabel">
                        <i class="fas fa-plus-circle"></i> Tambah Stok Produk
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="product_select" class="form-label">Pilih Produk</label>
                            <select class="form-control" id="product_select" name="product_id" required>
                                <option value="">-- Pilih Produk --</option>
                                <?php 
                                // Reset pointer untuk products
                                $products->data_seek(0);
                                while ($product = $products->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $product['product_id']; ?>">
                                        <?php echo htmlspecialchars($product['name']); ?> - Stok: <?php echo $product['stock']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="add_stock_amount" class="form-label">Jumlah Stok yang Ditambahkan</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="add_stock_amount" name="add_stock_amount" 
                                       min="1" value="1" required>
                                <span class="input-group-text">pcs</span>
                            </div>
                            <div class="form-text">Masukkan jumlah stok yang ingin ditambahkan (minimal 1)</div>
                        </div>
                        <div class="mb-3">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Info:</strong> Stok akan ditambahkan ke stok yang ada saat ini.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" name="add_stock" class="btn btn-success">
                                <i class="fas fa-plus"></i> Tambah Stok
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Product Modals -->
    <?php 
    // Reset pointer untuk products
    $products->data_seek(0);
    while ($product = $products->fetch_assoc()): 
        // Get selected sizes
        $selected_sizes = [];
        if ($product['sizes']) {
            $selected_sizes = explode(',', $product['sizes']);
        }
    ?>
        <!-- Edit Product Modal -->
        <div class="modal fade" id="editProductModal<?php echo $product['product_id']; ?>" tabindex="-1" aria-labelledby="editProductModalLabel<?php echo $product['product_id']; ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editProductModalLabel<?php echo $product['product_id']; ?>">
                            <i class="fas fa-edit"></i> Edit Produk
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="edit_name" class="form-label">Nama Produk</label>
                                    <input type="text" class="form-control" id="edit_name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="edit_category_id" class="form-label">Kategori</label>
                                    <select class="form-control" id="edit_category_id" name="category_id" required>
                                        <option value="">Pilih Kategori</option>
                                        <?php 
                                        // Reset pointer untuk categories
                                        $categories->data_seek(0);
                                        while ($category = $categories->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo $category['category_id']; ?>" <?php echo $product['category_id'] == $category['category_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_description" class="form-label">Deskripsi</label>
                                <textarea class="form-control" id="edit_description" name="description" rows="4" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="edit_price" class="form-label">Harga (Rp)</label>
                                    <input type="number" class="form-control" id="edit_price" name="price" min="0" value="<?php echo $product['price']; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="edit_image" class="form-label">Gambar Produk</label>
                                    <input type="file" class="form-control" id="edit_image" name="edit_image" accept="image/*">
                                    <small class="text-muted">Kosongkan jika tidak ingin mengubah gambar</small>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Ukuran Sepatu (Pilih yang tersedia)</label>
                                <div class="row">
                                    <?php 
                                    $sizes = [36, 37, 38, 39, 40, 41, 42, 43, 44, 45];
                                    foreach ($sizes as $size): 
                                    ?>
                                        <div class="col-md-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="sizes[]" value="<?php echo $size; ?>" 
                                                       id="edit_size<?php echo $product['product_id'] . '_' . $size; ?>"
                                                       <?php echo in_array($size, $selected_sizes) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="edit_size<?php echo $product['product_id'] . '_' . $size; ?>"><?php echo $size; ?></label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Ukuran Jaket (Pilih yang tersedia)</label>
                                <div class="row">
                                    <?php 
                                    $jacket_sizes = ['S', 'M', 'L', 'XL', 'XXL'];
                                    foreach ($jacket_sizes as $jsize): 
                                    ?>
                                        <div class="col-md-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="sizes[]" value="<?php echo $jsize; ?>" 
                                                       id="edit_jsize<?php echo $product['product_id'] . '_' . $jsize; ?>"
                                                       <?php echo in_array($jsize, $selected_sizes) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="edit_jsize<?php echo $product['product_id'] . '_' . $jsize; ?>"><?php echo $jsize; ?></label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" name="edit_product" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Produk
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endwhile; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
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

            // Initialize Bootstrap modals
            var modals = document.querySelectorAll('.modal');
            modals.forEach(function(modal) {
                new bootstrap.Modal(modal);
            });

            // Fix stock button clicks
            var stockButtons = document.querySelectorAll('.btn-stock');
            stockButtons.forEach(function(button) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    var targetModal = document.querySelector(button.getAttribute('data-bs-target'));
                    if (targetModal) {
                        var modal = new bootstrap.Modal(targetModal);
                        modal.show();
                    }
                });
            });

            // Debug: Log when stock buttons are clicked
            stockButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    console.log('Stock button clicked:', button.getAttribute('data-bs-target'));
                });
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
