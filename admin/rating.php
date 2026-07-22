<?php
session_start();

// Simple session check - redirect jika tidak login sebagai admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../pengguna/login.php");
    exit();
}

// Database connection
require '../config/config.php';

// Handle hapus rating
if (isset($_GET['delete'])) {
    $review_id = $_GET['delete'];
    
    // Ambil informasi rating untuk disimpan dalam pesan
    $result = mysqli_query($conn, "SELECT pr.*, p.name as product_name, u.username 
                                   FROM product_reviews pr
                                   JOIN products p ON pr.product_id = p.product_id
                                   JOIN users u ON pr.user_id = u.id 
                                   WHERE pr.id = $review_id");
    $data = mysqli_fetch_assoc($result);
    
    // Hapus rating dari database
    mysqli_query($conn, "DELETE FROM product_reviews WHERE id = $review_id");
    
    $_SESSION['message'] = "Rating untuk produk '{$data['product_name']}' dari pengguna '{$data['username']}' berhasil dihapus!";
    header("Location: rating.php");
    exit();
}

// Pagination settings
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filter settings
$search_filter = isset($_GET['search']) ? trim($_GET['search']) : '';
$rating_filter = isset($_GET['rating']) ? (int)$_GET['rating'] : 0;

// Build query
$where_conditions = ["1=1"];
$params = [];
$types = '';

// Search filter
if (!empty($search_filter)) {
    $where_conditions[] = "(p.name LIKE ? OR u.username LIKE ? OR pr.review LIKE ?)";
    $search_param = "%$search_filter%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

// Rating filter
if ($rating_filter > 0) {
    $where_conditions[] = "pr.rating = ?";
    $params[] = $rating_filter;
    $types .= 'i';
}

// Build WHERE clause
$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Count total ratings
$count_sql = "SELECT COUNT(*) as total FROM product_reviews pr
              JOIN products p ON pr.product_id = p.product_id
              JOIN users u ON pr.user_id = u.id 
              $where_clause";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_ratings = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_ratings / $limit);

// Get ratings with pagination
$sql = "SELECT pr.*, p.name as product_name, p.image as product_image, u.username, u.email
        FROM product_reviews pr
        JOIN products p ON pr.product_id = p.product_id
        JOIN users u ON pr.user_id = u.id 
        $where_clause
        ORDER BY pr.created_at DESC 
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$param_types = $types . 'ii';
$param_values = array_merge($params, [$limit, $offset]);
$stmt->bind_param($param_types, ...$param_values);
$stmt->execute();
$ratings_result = $stmt->get_result();

// Get statistics
$total_reviews_query = $conn->query("SELECT COUNT(*) as total FROM product_reviews");
$total_reviews = $total_reviews_query->fetch_assoc()['total'];

$avg_rating_query = $conn->query("SELECT AVG(rating) as avg FROM product_reviews");
$avg_rating = $avg_rating_query->fetch_assoc()['avg'] ?? 0;

$rating_5_query = $conn->query("SELECT COUNT(*) as total FROM product_reviews WHERE rating = 5");
$rating_5 = $rating_5_query->fetch_assoc()['total'];

$rating_4_query = $conn->query("SELECT COUNT(*) as total FROM product_reviews WHERE rating = 4");
$rating_4 = $rating_4_query->fetch_assoc()['total'];

$rating_3_query = $conn->query("SELECT COUNT(*) as total FROM product_reviews WHERE rating = 3");
$rating_3 = $rating_3_query->fetch_assoc()['total'];

$rating_2_query = $conn->query("SELECT COUNT(*) as total FROM product_reviews WHERE rating = 2");
$rating_2 = $rating_2_query->fetch_assoc()['total'];

$rating_1_query = $conn->query("SELECT COUNT(*) as total FROM product_reviews WHERE rating = 1");
$rating_1 = $rating_1_query->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Rating - hobiBekasan Admin</title>
    
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

        /* Rating Distribution */
        .rating-distribution {
            background: #ffffff;
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid #e5e7eb;
        }

        .rating-bar {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .rating-label {
            width: 60px;
            font-weight: 600;
            color: var(--dark-color);
        }

        .rating-progress {
            flex: 1;
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            margin: 0 1rem;
        }

        .rating-fill {
            height: 100%;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            transition: width 0.3s ease;
        }

        .rating-count {
            width: 50px;
            text-align: right;
            font-weight: 600;
            color: var(--dark-color);
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

        .filter-group input, .filter-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .filter-group input:focus, .filter-group select:focus {
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

        .product-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .product-image {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
            border: 2px solid var(--border-color);
        }

        .product-details {
            flex: 1;
        }

        .product-name {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.25rem;
        }

        .user-name {
            color: #6b7280;
            font-size: 0.85rem;
        }

        .rating-stars {
            color: #f59e0b;
            font-size: 1rem;
        }

        .rating-stars .fas {
            margin-right: 2px;
        }

        .rating-stars .far {
            margin-right: 2px;
            color: #d1d5db;
        }

        .review-text {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: var(--dark-color);
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

        .btn-delete {
            background: #fee2e2;
            color: #dc2626;
        }

        .btn-delete:hover {
            background: #fecaca;
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

        /* Alert */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-success i {
            color: #10b981;
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
                <a href="laporan.php" class="menu-item">
                    <i class="fas fa-file-alt"></i> Laporan
                </a>
                <a href="rating.php" class="menu-item active">
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
                    <h1 class="page-title">Manajemen Rating</h1>
                    <p class="page-subtitle">Kelola rating dan ulasan produk dari pengguna</p>
                </div>

                <!-- Alert Message -->
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($total_reviews); ?></div>
                        <div class="stat-label">Total Rating</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($avg_rating, 1); ?></div>
                        <div class="stat-label">Rating Rata-rata</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon purple">
                            <i class="fas fa-star-half-alt"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($rating_5); ?></div>
                        <div class="stat-label">Rating 5 Bintang</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($total_reviews - $rating_5); ?></div>
                        <div class="stat-label">Rating Lainnya</div>
                    </div>
                </div>

                <!-- Rating Distribution -->
                <div class="rating-distribution">
                    <h3 style="margin-bottom: 1.5rem; color: var(--dark-color); font-weight: 700;">Distribusi Rating</h3>
                    <?php
                    $ratings = [5 => $rating_5, 4 => $rating_4, 3 => $rating_3, 2 => $rating_2, 1 => $rating_1];
                    foreach ($ratings as $stars => $count):
                        $percentage = $total_reviews > 0 ? ($count / $total_reviews) * 100 : 0;
                    ?>
                        <div class="rating-bar">
                            <div class="rating-label"><?php echo $stars; ?> <i class="fas fa-star" style="color: #f59e0b;"></i></div>
                            <div class="rating-progress">
                                <div class="rating-fill" style="width: <?php echo $percentage; ?>%;"></div>
                            </div>
                            <div class="rating-count"><?php echo $count; ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Filter Form -->
                <div class="filter-form">
                    <div class="filter-group">
                        <label for="search">Cari Rating:</label>
                        <input type="text" id="search" name="search" placeholder="Produk, Pengguna, atau Ulasan..." value="<?php echo htmlspecialchars($search_filter); ?>">
                    </div>
                    <div class="filter-group">
                        <label for="rating">Filter Rating:</label>
                        <select id="rating" name="rating">
                            <option value="0">Semua Rating</option>
                            <option value="5" <?php echo $rating_filter == 5 ? 'selected' : ''; ?>>5 Bintang</option>
                            <option value="4" <?php echo $rating_filter == 4 ? 'selected' : ''; ?>>4 Bintang</option>
                            <option value="3" <?php echo $rating_filter == 3 ? 'selected' : ''; ?>>3 Bintang</option>
                            <option value="2" <?php echo $rating_filter == 2 ? 'selected' : ''; ?>>2 Bintang</option>
                            <option value="1" <?php echo $rating_filter == 1 ? 'selected' : ''; ?>>1 Bintang</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-filter" onclick="window.location.href='?search=' + document.getElementById('search').value + '&rating=' + document.getElementById('rating').value">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <?php if (!empty($search_filter) || $rating_filter > 0): ?>
                        <a href="rating.php" class="btn-filter" style="background: #6b7280;">
                            <i class="fas fa-times"></i> Reset
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Ratings Table -->
                <div class="table-container">
                    <?php if ($ratings_result->num_rows > 0): ?>
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Produk & Pengguna</th>
                                    <th>Rating</th>
                                    <th>Ulasan</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($rating = $ratings_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="product-info">
                                                <img src="../assets/img/products/<?php echo $rating['product_image']; ?>" 
                                                     alt="<?php echo htmlspecialchars($rating['product_name']); ?>" 
                                                     class="product-image">
                                                <div class="product-details">
                                                    <div class="product-name"><?php echo htmlspecialchars($rating['product_name']); ?></div>
                                                    <div class="user-name"><?php echo htmlspecialchars($rating['username']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="rating-stars">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="<?php echo $i <= $rating['rating'] ? 'fas' : 'far'; ?> fa-star"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="review-text" title="<?php echo htmlspecialchars($rating['review']); ?>">
                                                <?php echo htmlspecialchars($rating['review']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo date('d M Y, H:i', strtotime($rating['created_at'])); ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="rating.php?delete=<?php echo $rating['id']; ?>" 
                                                   class="btn-action btn-delete" 
                                                   title="Hapus Rating"
                                                   onclick="return confirm('Apakah Anda yakin ingin menghapus rating ini?')">
                                                    <i class="fas fa-trash"></i>
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
                                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search_filter); ?>&rating=<?php echo $rating_filter; ?>" 
                                       class="pagination-link <?php echo $i == $page ? 'active' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-star"></i>
                            <h3>Tidak Ada Rating</h3>
                            <p>Belum ada rating yang sesuai dengan filter yang dipilih.</p>
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
