<?php
session_start();

// Simple session check - redirect jika tidak login sebagai admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../pengguna/login.php");
    exit();
}

// Database connection
require '../config/config.php';

// Handle update produk
if (isset($_POST['update_product'])) {
    $product_id = $_POST['product_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $category_id = $_POST['category_id'];
    $sizes = isset($_POST['sizes']) ? implode(',', $_POST['sizes']) : '';
    
    // Handle image upload if new image is provided
    if (isset($_FILES['image']) && !empty($_FILES['image']['name'])) {
        $image = $_FILES['image']['name'];
        $target_dir = "../assets/img/products/";
        if (!is_dir($target_dir)) {
            @mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . basename($image);
        @move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
        
        $query = "UPDATE products SET name='$name', description='$description', price='$price', 
                  category_id='$category_id', image='$image', sizes='$sizes' 
                  WHERE product_id=$product_id";
    } else {
        $query = "UPDATE products SET name='$name', description='$description', price='$price', 
                  category_id='$category_id', sizes='$sizes' 
                  WHERE product_id=$product_id";
    }
    
    $conn->query($query);
    header("Location: produk.php");
    exit();
}

// Get product data
if (isset($_GET['id'])) {
    $product_id = $_GET['id'];
    $result = $conn->query("SELECT * FROM products WHERE product_id = $product_id");
    $product = $result->fetch_assoc();
    
    // Get selected sizes
    $selected_sizes = [];
    if ($product['sizes']) {
        $selected_sizes = explode(',', $product['sizes']);
    }
} else {
    header("Location: produk.php");
    exit();
}

// Get categories
$categories = $conn->query("SELECT * FROM categories ORDER BY name");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Produk - hobiBekasan</title>
    
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

        .sidebar {
            width: 250px;
            background: #ffffff;
            border-right: 1px solid #e5e7eb;
            padding: 2rem;
        }

        .content {
            flex: 1;
            padding: 2rem;
        }

        .form-container {
            background: #ffffff;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid #e5e7eb;
            max-width: 800px;
            margin: 0 auto;
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
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3);
            color: white;
        }

        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .size-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
            gap: 10px;
        }

        .size-item {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <h4 class="mb-4">Admin Panel</h4>
            <nav class="nav flex-column">
                <a class="nav-link" href="admin_dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                <a class="nav-link" href="kategori.php">
                    <i class="fas fa-tags me-2"></i> Kategori
                </a>
                <a class="nav-link active" href="produk.php">
                    <i class="fas fa-box me-2"></i> Produk
                </a>
                <a class="nav-link" href="pesanan.php">
                    <i class="fas fa-shopping-bag me-2"></i> Pesanan
                </a>
                <a class="nav-link" href="users.php">
                    <i class="fas fa-users me-2"></i> Users
                </a>
                <a class="nav-link text-danger" href="../logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </nav>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="form-container">
                <h2 class="mb-4">Edit Produk</h2>
                
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Nama Produk</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="category_id" class="form-label">Kategori</label>
                            <select class="form-control" id="category_id" name="category_id" required>
                                <option value="">Pilih Kategori</option>
                                <?php while ($category = $categories->fetch_assoc()): ?>
                                    <option value="<?php echo $category['category_id']; ?>" <?php echo $product['category_id'] == $category['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['category_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="description" name="description" rows="4" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="price" class="form-label">Harga (Rp)</label>
                            <input type="number" class="form-control" id="price" name="price" min="0" value="<?php echo $product['price']; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="image" class="form-label">Gambar Produk</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            <small class="text-muted">Kosongkan jika tidak ingin mengubah gambar</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Ukuran Sepatu (Pilih yang tersedia)</label>
                        <div class="size-grid">
                            <?php
                            $sizes = [36, 37, 38, 39, 40, 41, 42, 43, 44, 45];
                            foreach ($sizes as $size):
                            ?>
                            <div class="size-item">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="sizes[]" value="<?php echo $size; ?>" id="size<?php echo $size; ?>" <?php echo in_array($size, $selected_sizes) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="size<?php echo $size; ?>"><?php echo $size; ?></label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" name="update_product" class="btn btn-primary-custom">
                            <i class="fas fa-save"></i> Update Produk
                        </button>
                        <a href="produk.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
