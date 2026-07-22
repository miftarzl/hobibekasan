<?php
session_start();

// Simple session check - redirect jika tidak login sebagai admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../pengguna/login.php");
    exit();
}

// Database connection
require '../config/config.php';

// Handle adding a category
if (isset($_POST['add_category'])) {
    $category_name = htmlspecialchars($_POST['category_name']);
    $category_photo = isset($_FILES['category_photo']) ? $_FILES['category_photo']['name'] : '';
    
    // Upload category photo
    if ($category_photo) {
        $target_dir = "../assets/img/category/";
        if (!is_dir($target_dir)) {
            @mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . basename($category_photo);
        @move_uploaded_file($_FILES["category_photo"]["tmp_name"], $target_file);
    }
    
    // Insert dengan semua field yang diperlukan
    $query = "INSERT INTO categories (name, category_name, category_photo) VALUES ('$category_name', '$category_name', '$category_photo')";
    $conn->query($query);
    
    header("Location: kategori.php");
    exit();
}

// Handle deleting a category
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM categories WHERE category_id = $id");
    header("Location: kategori.php");
    exit();
}

// Handle editing a category
if (isset($_POST['update_category'])) {
    $id = $_POST['category_id'];
    $category_name = htmlspecialchars($_POST['category_name']);
    $category_photo = isset($_FILES['category_photo']) ? $_FILES['category_photo']['name'] : '';
    
    // Upload new category photo if provided
    if ($category_photo) {
        $target_dir = "../assets/img/category/";
        if (!is_dir($target_dir)) {
            @mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . basename($category_photo);
        @move_uploaded_file($_FILES["category_photo"]["tmp_name"], $target_file);
        
        $query = "UPDATE categories SET name = '$category_name', category_name = '$category_name', category_photo = '$category_photo' WHERE category_id = $id";
    } else {
        $query = "UPDATE categories SET name = '$category_name', category_name = '$category_name' WHERE category_id = $id";
    }
    
    $conn->query($query);
    
    header("Location: kategori.php");
    exit();
}

// Get category data for editing
$edit_category = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $result = $conn->query("SELECT * FROM categories WHERE category_id = $id");
    $edit_category = $result->fetch_assoc();
}

// Get all categories with filtering and sorting
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'category_name';
$order = isset($_GET['order']) ? $_GET['order'] : 'ASC';

// Build query
$query = "SELECT * FROM categories WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND category_name LIKE ?";
    $params[] = "%$search%";
}

// Validasi sort column untuk mencegah SQL injection
$allowed_sorts = ['category_id', 'category_name', 'created_at'];
if (!in_array($sort, $allowed_sorts)) {
    $sort = 'category_name';
}

// Validasi order
$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

$query .= " ORDER BY $sort $order";

// Execute query
if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $categories = $stmt->get_result();
} else {
    $categories = $conn->query($query);
}

// Handle AJAX requests
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    ob_clean();
    header('Content-Type: text/html');
    
    if ($categories->num_rows > 0) {
        while ($category = $categories->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . $category['category_id'] . '</td>';
            echo '<td>' . htmlspecialchars($category['category_name']) . '</td>';
            echo '<td>';
            if ($category['category_photo']) {
                echo '<img src="../assets/img/category/' . $category['category_photo'] . '" alt="' . $category['category_name'] . '" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">';
            } else {
                echo '<span style="color: #999;">No Photo</span>';
            }
            echo '</td>';
            echo '<td>';
            echo '<a href="kategori.php?edit=' . $category['category_id'] . '" class="btn-action btn-edit">';
            echo '<i class="fas fa-edit"></i> Edit';
            echo '</a>';
            echo '<a href="kategori.php?delete=' . $category['category_id'] . '" class="btn-action btn-delete" onclick="return confirm(\'Apakah Anda yakin ingin menghapus kategori ini?\')">';
            echo '<i class="fas fa-trash"></i> Hapus';
            echo '</a>';
            echo '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="4" style="text-align: center; padding: 2rem;">';
        echo '<div class="empty-state">';
        echo '<i class="fas fa-tags"></i>';
        echo '<p>Tidak ada kategori yang ditemukan</p>';
        echo '</div>';
        echo '</td></tr>';
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori - hobiBekasan</title>
    
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

        .categories-table-container {
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
                <a href="produk.php" class="menu-item">
                    <i class="fas fa-box"></i> Produk
                </a>
                <a href="kategori.php" class="menu-item active">
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
            <div class="content-container">
                <!-- Header -->
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-tags"></i> Kelola Kategori
                    </h1>
                    <p class="page-subtitle">Tambah, edit, dan hapus kategori produk</p>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="fas fa-plus"></i> Tambah Kategori
                    </button>
                    <a href="admin_dashboard.php" class="btn-primary-custom">
                        <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                    </a>
                </div>

                <!-- Categories Table -->
                <div class="categories-table-container">
                    <div class="table-header-custom">
                        <h2 class="table-title">
                            <i class="fas fa-list"></i> Daftar Kategori
                        </h2>
                        <span class="badge-count">
                            <?php echo $categories ? $categories->num_rows : 0; ?> Kategori
                        </span>
                    </div>
                    
                    <!-- Filter and Sort Controls -->
                    <div class="filter-sort-container" style="margin-bottom: 1.5rem;">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-search"></i>
                                    </span>
                                    <input type="text" id="searchInput" class="form-control" placeholder="Cari kategori..." value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <select id="sortSelect" class="form-select">
                                    <option value="category_name" <?php echo $sort == 'category_name' ? 'selected' : ''; ?>>Urutkan berdasarkan Nama</option>
                                    <option value="category_id" <?php echo $sort == 'category_id' ? 'selected' : ''; ?>>Urutkan berdasarkan ID</option>
                                    <option value="created_at" <?php echo $sort == 'created_at' ? 'selected' : ''; ?>>Urutkan berdasarkan Tanggal</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select id="orderSelect" class="form-select">
                                    <option value="ASC" <?php echo $order == 'ASC' ? 'selected' : ''; ?>>A-Z (Naik)</option>
                                    <option value="DESC" <?php echo $order == 'DESC' ? 'selected' : ''; ?>>Z-A (Turun)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($categories && $categories->num_rows > 0): ?>
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama Kategori</th>
                                    <th>Photo</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="categoriesTableBody">
                                <?php while ($category = $categories->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $category['category_id']; ?></td>
                                        <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                                        <td>
                                            <?php if ($category['category_photo']): ?>
                                                <img src="../assets/img/category/<?php echo $category['category_photo']; ?>" alt="<?php echo $category['category_name']; ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                                            <?php else: ?>
                                                <span style="color: #999;">No Photo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="kategori.php?edit=<?php echo $category['category_id']; ?>" class="btn-action btn-edit">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="kategori.php?delete=<?php echo $category['category_id']; ?>" class="btn-action btn-delete" onclick="return confirm('Apakah Anda yakin ingin menghapus kategori ini?')">
                                                <i class="fas fa-trash"></i> Hapus
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-tags"></i>
                            <p>Belum ada kategori yang ditambahkan</p>
                            <p>Silakan tambah kategori pertama Anda</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCategoryModalLabel">
                        <i class="fas fa-plus"></i> Tambah Kategori Baru
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="category_name" class="form-label">Nama Kategori</label>
                            <input type="text" class="form-control" id="category_name" name="category_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="category_photo" class="form-label">Photo Kategori</label>
                            <input type="file" class="form-control" id="category_photo" name="category_photo" accept="image/*">
                            <small class="text-muted">Format: JPG, PNG, GIF. Maksimal 2MB</small>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" name="add_category" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan Kategori
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <?php if ($edit_category): ?>
    <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCategoryModalLabel">
                        <i class="fas fa-edit"></i> Edit Kategori
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="category_id" value="<?php echo $edit_category['category_id']; ?>">
                        <div class="mb-3">
                            <label for="edit_category_name" class="form-label">Nama Kategori</label>
                            <input type="text" class="form-control" id="edit_category_name" name="category_name" value="<?php echo htmlspecialchars($edit_category['category_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_category_photo" class="form-label">Photo Kategori</label>
                            <input type="file" class="form-control" id="edit_category_photo" name="category_photo" accept="image/*">
                            <small class="text-muted">Kosongkan jika tidak ingin mengubah photo</small>
                            <?php if ($edit_category['category_photo']): ?>
                                <div class="mt-2">
                                    <img src="../assets/img/category/<?php echo $edit_category['category_photo']; ?>" alt="Current Photo" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px;">
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" name="update_category" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Kategori
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

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
        });

        // Auto show edit modal if edit parameter exists
        <?php if ($edit_category): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const editModal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
            editModal.show();
        });
        <?php endif; ?>

        // AJAX Filter and Sort Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const sortSelect = document.getElementById('sortSelect');
            const orderSelect = document.getElementById('orderSelect');
            const tableBody = document.getElementById('categoriesTableBody');
            const badgeCount = document.querySelector('.badge-count');
            
            let searchTimeout;
            
            function loadCategories() {
                const search = searchInput.value.trim();
                const sort = sortSelect.value;
                const order = orderSelect.value;
                
                // Show loading
                tableBody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 2rem;"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>';
                
                // Build URL
                const params = new URLSearchParams({
                    ajax: '1',
                    search: search,
                    sort: sort,
                    order: order
                });
                
                // Make AJAX request
                fetch(`kategori.php?${params.toString()}`)
                    .then(response => response.text())
                    .then(html => {
                        tableBody.innerHTML = html;
                        
                        // Update count
                        const rows = tableBody.querySelectorAll('tr');
                        const count = rows.length > 0 && !rows[0].querySelector('.empty-state') ? rows.length : 0;
                        badgeCount.textContent = `${count} Kategori`;
                    })
                    .catch(error => {
                        console.error('Error loading categories:', error);
                        tableBody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 2rem;"><div class="alert alert-danger">Error loading categories. Please try again.</div></td></tr>';
                    });
            }
            
            // Search with debounce
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(loadCategories, 500);
            });
            
            // Sort and order change
            sortSelect.addEventListener('change', loadCategories);
            orderSelect.addEventListener('change', loadCategories);
            
            // Handle edit and delete links to maintain filter state
            tableBody.addEventListener('click', function(e) {
                const editLink = e.target.closest('.btn-edit');
                const deleteLink = e.target.closest('.btn-delete');
                
                if (editLink) {
                    // For edit, we need to preserve filter state
                    const href = editLink.getAttribute('href');
                    const search = searchInput.value.trim();
                    const sort = sortSelect.value;
                    const order = orderSelect.value;
                    
                    // Add filter parameters to edit link
                    if (search || sort !== 'category_name' || order !== 'ASC') {
                        const separator = href.includes('?') ? '&' : '?';
                        const newParams = [];
                        if (search) newParams.push(`search=${encodeURIComponent(search)}`);
                        if (sort !== 'category_name') newParams.push(`sort=${sort}`);
                        if (order !== 'ASC') newParams.push(`order=${order}`);
                        
                        editLink.setAttribute('href', href + separator + newParams.join('&'));
                    }
                } else if (deleteLink) {
                    // For delete, preserve filter state after deletion
                    const href = deleteLink.getAttribute('href');
                    const search = searchInput.value.trim();
                    const sort = sortSelect.value;
                    const order = orderSelect.value;
                    
                    if (confirm('Apakah Anda yakin ingin menghapus kategori ini?')) {
                        // Perform deletion via AJAX
                        const categoryId = href.match(/delete=(\d+)/)[1];
                        
                        fetch(`kategori.php?delete=${categoryId}`, {
                            method: 'GET'
                        })
                        .then(() => {
                            // Reload categories after deletion
                            loadCategories();
                        })
                        .catch(error => {
                            console.error('Error deleting category:', error);
                            alert('Error deleting category. Please try again.');
                        });
                        
                        e.preventDefault();
                    }
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
