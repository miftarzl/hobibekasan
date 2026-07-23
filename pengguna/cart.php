<?php
session_start();
require_once '../config/config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

// Ambil user ID
$user_id = $_SESSION['user']['user_id'] ?? $_SESSION['user']['id'] ?? 0;

// Inisialisasi cart jika belum ada
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

// Update cart count
$_SESSION['cart_count'] = count($_SESSION['cart']);

// Handle add to cart
if (isset($_POST['add_to_cart']) && isset($_POST['product_id'])) {
    $product_id = $_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    // Cek produk di database
    $query = "SELECT * FROM products WHERE product_id = ? AND stock > 0";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        
        // Cek apakah produk sudah ada di cart
        if (isset($_SESSION['cart'][$product_id])) {
            // Update quantity
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
        } else {
            // Add new product to cart
            $_SESSION['cart'][$product_id] = array(
                'product_id' => $product_id,
                'name' => $product['name'],
                'price' => $product['price'],
                'image' => $product['image'],
                'quantity' => $quantity,
                'stock' => $product['stock']
            );
        }
        
        // Update cart count
        $_SESSION['cart_count'] = count($_SESSION['cart']);
        
        // Set success message
        $_SESSION['success_message'] = "Produk berhasil ditambahkan ke keranjang!";
    } else {
        $_SESSION['error_message'] = "Produk tidak tersedia atau stok habis!";
    }
    
    // Redirect kembali ke halaman sebelumnya
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
}

// Handle remove from cart
if (isset($_GET['remove']) && isset($_GET['product_id'])) {
    $product_id = $_GET['product_id'];
    
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        $_SESSION['cart_count'] = count($_SESSION['cart']);
        $_SESSION['success_message'] = "Produk berhasil dihapus dari keranjang!";
    }
    
    header('Location: cart.php');
    exit();
}

// Handle update quantity
if (isset($_POST['update_cart']) && isset($_POST['quantities'])) {
    $quantities = $_POST['quantities'];
    
    foreach ($quantities as $product_id => $quantity) {
        $quantity = (int)$quantity;
        
        if ($quantity > 0 && isset($_SESSION['cart'][$product_id])) {
            // Cek stok
            $query = "SELECT stock FROM products WHERE product_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            
            if ($quantity <= $product['stock']) {
                $_SESSION['cart'][$product_id]['quantity'] = $quantity;
            } else {
                $_SESSION['error_message'] = "Stok tidak mencukupi untuk produk: " . $_SESSION['cart'][$product_id]['name'];
            }
        }
    }
    
    $_SESSION['cart_count'] = count($_SESSION['cart']);
    $_SESSION['success_message'] = "Keranjang berhasil diperbarui!";
    
    header('Location: cart.php');
    exit();
}

// Calculate total
$total_price = 0;
foreach ($_SESSION['cart'] as $item) {
    $total_price += $item['price'] * $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - hobiBekasan</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .cart-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 30px;
            margin: 30px auto;
            max-width: 1200px;
        }
        
        .cart-header {
            background: linear-gradient(135deg, #003366 0%, #005b99 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .cart-item {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .cart-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .product-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 10px;
            border: 2px solid #007bff;
        }
        
        .quantity-input {
            width: 70px;
            text-align: center;
            border: 2px solid #007bff;
            border-radius: 8px;
            padding: 5px;
        }
        
        .price-display {
            font-size: 1.2rem;
            font-weight: bold;
            color: #007bff;
        }
        
        .total-section {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-top: 30px;
        }
        
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .empty-cart i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #dee2e6;
        }
        
        .btn-checkout {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .btn-checkout:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }
        
        .btn-remove {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            color: white;
            transition: all 0.3s ease;
        }
        
        .btn-remove:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }
        
        .alert-custom {
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include '../assets/navbar.php'; ?>

    <div class="container">
        <div class="cart-container animate__animated animate__fadeIn">
            <div class="cart-header">
                <h2><i class="fas fa-shopping-cart me-2"></i>Keranjang Belanja</h2>
                <p class="mb-0">Kelola produk pilihan Anda sebelum checkout</p>
            </div>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-custom animate__animated animate__fadeInDown">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-custom animate__animated animate__fadeInDown">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($_SESSION['cart'])): ?>
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>Keranjang Belanja Kosong</h3>
                    <p>Anda belum menambahkan produk ke keranjang</p>
                    <a href="index.php" class="btn btn-primary btn-lg mt-3">
                        <i class="fas fa-shopping-bag me-2"></i>Lanjut Belanja
                    </a>
                </div>
            <?php else: ?>
                <form method="POST" action="cart.php">
                    <div class="row">
                        <div class="col-lg-8">
                            <?php foreach ($_SESSION['cart'] as $product_id => $item): ?>
                                <div class="cart-item animate__animated animate__fadeInUp">
                                    <div class="row align-items-center">
                                        <div class="col-md-2">
                                            <img src="../assets/img/products/<?php echo $item['image']; ?>" 
                                                 class="product-image" alt="<?php echo $item['name']; ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <h5><?php echo $item['name']; ?></h5>
                                            <p class="text-muted mb-0">Stok tersedia: <?php echo $item['stock']; ?></p>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="input-group">
                                                <input type="number" 
                                                       name="quantities[<?php echo $product_id; ?>]" 
                                                       class="quantity-input" 
                                                       value="<?php echo $item['quantity']; ?>" 
                                                       min="1" 
                                                       max="<?php echo $item['stock']; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="price-display">
                                                Rp <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?>
                                            </div>
                                        </div>
                                        <div class="col-md-2 text-end">
                                            <a href="cart.php?remove=1&product_id=<?php echo $product_id; ?>" 
                                               class="btn btn-remove btn-sm"
                                               onclick="return confirm('Apakah Anda yakin ingin menghapus produk ini?')">
                                                <i class="fas fa-trash"></i> Hapus
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="col-lg-4">
                            <div class="total-section">
                                <h4 class="mb-3">Ringkasan Belanja</h4>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal (<?php echo count($_SESSION['cart']); ?> produk):</span>
                                    <span>Rp <?php echo number_format($total_price, 0, ',', '.'); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span>Ongkos Kirim:</span>
                                    <span>Gratis</span>
                                </div>
                                <hr class="my-3">
                                <div class="d-flex justify-content-between mb-4">
                                    <h5>Total:</h5>
                                    <h5>Rp <?php echo number_format($total_price, 0, ',', '.'); ?></h5>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" name="update_cart" class="btn btn-outline-light">
                                        <i class="fas fa-sync-alt me-2"></i>Update Keranjang
                                    </button>
                                    <a href="checkout.php" class="btn btn-checkout">
                                        <i class="fas fa-credit-card me-2"></i>Checkout
                                    </a>
                                    <a href="index.php" class="btn btn-outline-light">
                                        <i class="fas fa-arrow-left me-2"></i>Lanjut Belanja
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../assets/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-update quantity when changed
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('change', function() {
                const form = document.querySelector('form');
                if (form) {
                    // Create hidden input to trigger update
                    const updateInput = document.createElement('input');
                    updateInput.type = 'hidden';
                    updateInput.name = 'update_cart';
                    updateInput.value = '1';
                    form.appendChild(updateInput);
                    form.submit();
                }
            });
        });
        
        // Smooth scroll animations
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.animate__animated');
            elements.forEach((el, index) => {
                setTimeout(() => {
                    el.style.opacity = '1';
                }, index * 100);
            });
        });
    </script>
</body>
</html>
