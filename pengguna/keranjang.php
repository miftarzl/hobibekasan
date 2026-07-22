<?php
session_start();

// Cek apakah user login atau belum
if (!isset($_SESSION['user']['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
include '../config/config.php';

// Handle AJAX request untuk update quantity
if (isset($_POST['action']) && $_POST['action'] === 'update_quantity') {
    $cart_id = $_POST['cart_id'];
    $quantity_change = $_POST['quantity_change']; // -1 untuk decrease, +1 untuk increase

    // Cek apakah cart item milik user ini
    $check_query = "SELECT * FROM cart WHERE id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $cart_id, $_SESSION['user']['user_id']);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $cart_item = $check_result->fetch_assoc();

    if ($cart_item) {
        $new_quantity = $cart_item['quantity'] + $quantity_change;

        if ($new_quantity <= 0) {
            // Hapus item jika quantity 0
            $delete_query = "DELETE FROM cart WHERE id = ?";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bind_param("i", $cart_id);
            $delete_stmt->execute();
            echo json_encode(['success' => true, 'action' => 'deleted']);
        } else {
            // Check stock availability
            $stock_query = "SELECT stock FROM products WHERE product_id = ?";
            $stock_stmt = $conn->prepare($stock_query);
            $stock_stmt->bind_param("i", $cart_item['product_id']);
            $stock_stmt->execute();
            $stock_result = $stock_stmt->get_result();
            $product_stock = $stock_result->fetch_assoc()['stock'];

            if ($new_quantity > $product_stock) {
                echo json_encode(['success' => false, 'message' => 'Stok tidak mencukupi. Maksimal: ' . $product_stock]);
            } else {
                // Update quantity
                $update_query = "UPDATE cart SET quantity = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("ii", $new_quantity, $cart_id);
                $update_stmt->execute();
                echo json_encode(['success' => true, 'action' => 'updated', 'new_quantity' => $new_quantity]);
            }
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Item tidak ditemukan']);
    }
    exit();
}

// Ambil user ID
$user_id = $_SESSION['user']['user_id'];

// Handle add to cart from kategori page
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    
    // Check if product exists and has stock
    $product_check = $conn->prepare("SELECT stock FROM products WHERE product_id = ?");
    $product_check->bind_param("i", $product_id);
    $product_check->execute();
    $product_result = $product_check->get_result();
    
    if ($product_result->num_rows > 0) {
        $product = $product_result->fetch_assoc();
        
        if ($product['stock'] >= $quantity) {
            // Check if product already in cart
            $cart_check = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $cart_check->bind_param("ii", $user_id, $product_id);
            $cart_check->execute();
            $cart_result = $cart_check->get_result();
            
            if ($cart_result->num_rows > 0) {
                // Update existing cart item
                $existing_cart = $cart_result->fetch_assoc();
                $new_quantity = $existing_cart['quantity'] + $quantity;
                
                if ($new_quantity <= $product['stock']) {
                    $update_cart = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                    $update_cart->bind_param("ii", $new_quantity, $existing_cart['id']);
                    $update_cart->execute();
                }
            } else {
                // Add new cart item
                $insert_cart = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                $insert_cart->bind_param("iii", $user_id, $product_id, $quantity);
                $insert_cart->execute();
            }
        }
    }
    
    header("Location: keranjang.php");
    exit();
}

// Handle update quantity
if (isset($_POST['update_quantity'])) {
    $cart_id = $_POST['cart_id'];
    $new_quantity = $_POST['quantity'];
    
    if ($new_quantity > 0) {
        // Check stock
        $stock_check = $conn->prepare("SELECT p.stock FROM cart c JOIN products p ON c.product_id = p.product_id WHERE c.id = ? AND c.user_id = ?");
        $stock_check->bind_param("ii", $cart_id, $user_id);
        $stock_check->execute();
        $stock_result = $stock_check->get_result();
        
        if ($stock_result->num_rows > 0) {
            $stock_data = $stock_result->fetch_assoc();
            
            if ($new_quantity <= $stock_data['stock']) {
                $update_cart = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
                $update_cart->bind_param("iii", $new_quantity, $cart_id, $user_id);
                $update_cart->execute();
            }
        }
    }
    
    header("Location: keranjang.php");
    exit();
}

// Handle remove from cart
if (isset($_GET['remove'])) {
    $cart_id = $_GET['remove'];
    
    $delete_cart = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $delete_cart->bind_param("ii", $cart_id, $user_id);
    $delete_cart->execute();
    
    header("Location: keranjang.php");
    exit();
}

// Get cart items
$cart_query = "SELECT c.*, p.name, p.price, p.image, p.stock, p.description
               FROM cart c
               JOIN products p ON c.product_id = p.product_id
               WHERE c.user_id = ?
               ORDER BY c.created_at DESC";
$cart_stmt = $conn->prepare($cart_query);
$cart_stmt->bind_param("i", $user_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();

// Calculate totals
$total_items = 0;
$total_price = 0;
$cart_items = [];

// Get user address for shipping calculation
$user_query = "SELECT id, username, email, address FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();

// Calculate shipping cost based on distance
$shipping_cost = calculateShippingCost($user_data);

while ($item = $cart_result->fetch_assoc()) {
    // Check if product is still available
    if ($item['stock'] > 0) {
        $subtotal = $item['quantity'] * $item['price'];
        $item['subtotal'] = $subtotal;
        $total_items += $item['quantity'];
        $total_price += $subtotal;
        $cart_items[] = $item;
    }
}

// Update session cart count
$_SESSION['cart_count'] = $total_items;

/**
 * Calculate shipping cost based on user location
 * @param array $user_data User address data
 * @return array Shipping cost details
 */
function calculateShippingCost($user_data) {
    // Store location (alamat toko) - Bekasi
    $store_location = [
        'address' => 'JL. Bintara Jaya Gang Masjid sebrang komplek Puri Idaman',
        'district' => 'Kec. Bintara Jaya',
        'city' => 'Kota Bekasi',
        'province' => 'Jawa Barat',
        'coordinates' => ['lat' => -6.2382, 'lng' => 106.9756]
    ];
    
    // Default shipping zones and costs based on distance from Bekasi store
    $shipping_zones = [
        'bekasi' => ['base_cost' => 8000, 'max_distance' => 15],
        'jabodetabek' => ['base_cost' => 12000, 'max_distance' => 40],
        'jakarta' => ['base_cost' => 15000, 'max_distance' => 60],
        'jabar' => ['base_cost' => 18000, 'max_distance' => 100],
        'banten' => ['base_cost' => 20000, 'max_distance' => 120],
        'bali' => ['base_cost' => 35000, 'max_distance' => 200],
        'sumatera' => ['base_cost' => 40000, 'max_distance' => 300],
        'kalimantan' => ['base_cost' => 45000, 'max_distance' => 400],
        'sulawesi' => ['base_cost' => 40000, 'max_distance' => 300],
        'papua' => ['base_cost' => 50000, 'max_distance' => 600],
        'ntt' => ['base_cost' => 45000, 'max_distance' => 400],
        'default' => ['base_cost' => 25000, 'max_distance' => 150]
    ];
    
    // Get user address
    $user_address = strtolower($user_data['address'] ?? '');
    
    // Check for city/province indicators in address
    if (strpos($user_address, 'jakarta') !== false || strpos($user_address, 'dki jakarta') !== false) {
        $zone = 'jakarta';
        $distance = 35; // Distance from Bekasi to Jakarta (realistic)
    } elseif (strpos($user_address, 'bekasi') !== false) {
        $zone = 'bekasi';
        $distance = 8; // Average distance within Bekasi city
    } elseif (strpos($user_address, 'tangerang') !== false || strpos($user_address, 'banten') !== false) {
        $zone = 'banten';
        $distance = 45; // Distance from Bekasi to Tangerang/Banten
    } elseif (strpos($user_address, 'bandung') !== false || strpos($user_address, 'jabar') !== false) {
        $zone = 'jabar';
        $distance = 120; // Distance from Bekasi to Bandung
    } elseif (strpos($user_address, 'bogor') !== false || strpos($user_address, 'depok') !== false || strpos($user_address, 'cibinong') !== false) {
        $zone = 'jabodetabek';
        $distance = 20; // Distance from Bekasi to Bogor/Depok area
    } elseif (strpos($user_address, 'surabaya') !== false || strpos($user_address, 'malang') !== false) {
        $zone = 'jabar';
        $distance = 700; // Distance from Bekasi to Surabaya/Malang (East Java)
    } elseif (strpos($user_address, 'medan') !== false || strpos($user_address, 'sumatera') !== false || strpos($user_address, 'palembang') !== false) {
        $zone = 'sumatera';
        $distance = 1000; // Distance from Bekasi to Sumatera (via ferry/flight)
    } elseif (strpos($user_address, 'bali') !== false || strpos($user_address, 'denpasar') !== false) {
        $zone = 'bali';
        $distance = 600; // Distance from Bekasi to Bali (via ferry/flight)
    } elseif (strpos($user_address, 'makassar') !== false || strpos($user_address, 'sulawesi') !== false) {
        $zone = 'sulawesi';
        $distance = 1500; // Distance from Bekasi to Sulawesi (via flight)
    } elseif (strpos($user_address, 'kalimantan') !== false || strpos($user_address, 'borneo') !== false) {
        $zone = 'kalimantan';
        $distance = 1200; // Distance from Bekasi to Kalimantan
    } elseif (strpos($user_address, 'jayapura') !== false || strpos($user_address, 'papua') !== false) {
        $zone = 'papua';
        $distance = 2800; // Distance from Bekasi to Papua (via flight)
    } elseif (strpos($user_address, 'kupang') !== false || strpos($user_address, 'ntt') !== false) {
        $zone = 'ntt';
        $distance = 2000; // Distance from Bekasi to NTT (via flight)
    } else {
        $zone = 'default';
        $distance = 100; // Default distance for unknown locations
    }
    
    // Get base cost for the zone
    $base_cost = $shipping_zones[$zone]['base_cost'];
    $max_distance = $shipping_zones[$zone]['max_distance'];
    
    // Calculate additional cost for extra distance
    $additional_cost = 0;
    if ($distance > $max_distance) {
        $extra_distance = $distance - $max_distance;
        $additional_cost = ceil($extra_distance / 10) * 2000; // Rp 2,000 per 10km extra
    }
    
    // Calculate total shipping cost
    $total_cost = $base_cost + $additional_cost;
    
    // Calculate estimated delivery days
    $estimated_days = '1-2 hari';
    if ($distance > 100) {
        $estimated_days = '3-5 hari';
    }
    if ($distance > 500) {
        $estimated_days = '5-7 hari';
    }
    if ($distance > 1000) {
        $estimated_days = '7-10 hari';
    }
    
    return [
        'zone' => $zone,
        'distance' => $distance,
        'base_cost' => $base_cost,
        'additional_cost' => $additional_cost,
        'total_cost' => $total_cost,
        'estimated_days' => $estimated_days,
        'store_location' => $store_location
    ];
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
    
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }
        
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            background: transparent;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 40px 20px;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            border-radius: 20px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }
        
        .page-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: white;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        
        .page-subtitle {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.9);
            position: relative;
            z-index: 1;
        }
        
        .cart-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.15);
            padding: 2rem;
            margin-bottom: 30px;
            border: 1px solid rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            border: 1px solid #dee2e6;
        }
        
        .empty-cart-icon {
            font-size: 4rem;
            color: #6c757d;
            margin-bottom: 20px;
        }
        
        .empty-cart-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #495057;
            margin-bottom: 10px;
        }
        
        .empty-cart-text {
            color: #6c757d;
            font-size: 1.1rem;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .btn-shopping {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 15px 30px;
            font-weight: 600;
            font-size: 1.1rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
        }
        
        .btn-shopping:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(79, 70, 229, 0.4);
            color: white;
        }
        
        .purchase-details {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 30px;
            border: 1px solid rgba(0,0,0,0.05);
            box-shadow: 0 15px 50px rgba(0,0,0,0.15);
        }
        
        .purchase-details-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .purchase-details-title i {
            color: #4f46e5;
        }
        
        .purchase-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .purchase-info-item {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            padding: 1.5rem;
            border: 1px solid #dee2e6;
            transition: all 0.3s ease;
        }
        
        .purchase-info-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .purchase-info-label {
            font-weight: 600;
            color: #6c757d;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }
        
        .purchase-info-label i {
            color: #4f46e5;
        }
        
        .purchase-info-value {
            font-weight: 700;
            color: #333;
            font-size: 1.1rem;
        }
        
        .purchase-info-value.highlight {
            color: #4f46e5;
            font-size: 1.3rem;
        }
        
        .purchased-products-section {
            margin-top: 2rem;
        }
        
        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
            color: #4f46e5;
        }
        
        .purchased-products-grid {
            display: grid;
            gap: 1.5rem;
        }
        
        .purchased-product-item {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            position: relative;
        }
        
        .purchased-product-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        
        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .product-image-container {
            position: relative;
            flex-shrink: 0;
        }
        
        .purchased-product-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .quantity-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #4f46e5;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 700;
            border: 2px solid white;
        }
        
        .btn-delete-product {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #dc3545, #bd2130);
            color: white;
            border: 2px solid white;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
            z-index: 10;
        }
        
        .btn-delete-product:hover {
            transform: scale(1.1);
            background: linear-gradient(135deg, #c82333, #a71e2a);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
            color: white;
        }
        
        .product-details-content {
            flex: 1;
        }
        
        .product-name-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .product-description {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            line-height: 1.5;
        }
        
        .product-meta {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        
        .price-tag {
            background: #28a745;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .stock-info {
            background: #f8f9fa;
            color: #6c757d;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .subtotal-info {
            font-size: 1.1rem;
            font-weight: 700;
            color: #4f46e5;
        }
        
        .purchase-actions {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid #e9ecef;
        }
        
        .btn-purchase {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 15px 30px;
            font-weight: 700;
            font-size: 1.1rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        
        .btn-purchase:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
            color: white;
        }
        
        .btn-continue-shopping {
            background: linear-gradient(135deg, #61b2ff, #1e7fd6);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 15px 30px;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(97, 178, 255, 0.3);
        }
        
        .btn-continue-shopping:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(97, 178, 255, 0.4);
            color: white;
        }
        
        @media (max-width: 768px) {
            .main-container {
                padding: 15px;
            }

            .page-header {
                padding: 25px 20px;
            }

            .page-title {
                font-size: 1.8rem;
            }

            .purchase-info-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .product-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .purchased-product-image {
                width: 80px;
                height: 80px;
            }

            .cart-item {
                padding: 15px;
            }

            .quantity-controls {
                flex-direction: row;
                gap: 5px;
            }

            .quantity-btn {
                width: 35px;
                height: 35px;
                font-size: 1rem;
            }

            .quantity-display {
                font-size: 1rem;
                min-width: 40px;
            }

            .btn-checkout {
                padding: 12px 20px;
                font-size: 1rem;
            }
        }

        @media (max-width: 576px) {
            .main-container {
                padding: 10px;
            }

            .page-header {
                padding: 20px 15px;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .cart-item {
                padding: 12px;
                margin-bottom: 15px;
            }

            .purchased-product-image {
                width: 60px;
                height: 60px;
            }

            .product-info {
                font-size: 0.9rem;
            }

            .product-name {
                font-size: 1rem;
            }

            .product-price {
                font-size: 1.1rem;
            }

            .quantity-controls {
                gap: 3px;
            }

            .quantity-btn {
                width: 30px;
                height: 30px;
                font-size: 0.9rem;
            }

            .quantity-display {
                font-size: 0.9rem;
                min-width: 35px;
            }

            .btn-checkout {
                padding: 10px 15px;
                font-size: 0.9rem;
            }

            .total-section {
                padding: 15px;
            }

            .total-amount {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>

<!-- Sertakan navbar -->
<?php include "../assets/navbar.php"; ?>

<!-- Main Content -->
<div class="main-container">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-shopping-cart me-3"></i>Keranjang Belanja
        </h1>
        <p class="page-subtitle">Kelola produk yang ingin Anda beli</p>
    </div>
    
    <?php if (empty($cart_items)): ?>
        <!-- Empty Cart -->
        <div class="cart-container">
            <div class="empty-cart">
                <div class="empty-cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h2 class="empty-cart-title">Keranjang Belanja Kosong</h2>
                <p class="empty-cart-text">
                    Belum ada produk yang ditambahkan ke keranjang Anda.<br>
                    Mulai berbelanja sekarang!
                </p>
                <a href="kategori.php" class="btn-shopping">
                    <i class="fas fa-shopping-bag me-2"></i>Mulai Belanja
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Purchase Details Section -->
        <div class="purchase-details">
            <h3 class="purchase-details-title">
                <i class="fas fa-receipt"></i>
                Detail Pembelian
            </h3>
            
            <div class="purchase-info-grid">
                <div class="purchase-info-item">
                    <div class="purchase-info-label">
                        <i class="fas fa-box"></i>
                        Total Produk
                    </div>
                    <div class="purchase-info-value"><?php echo $total_items; ?> Item</div>
                </div>
                
                <div class="purchase-info-item">
                    <div class="purchase-info-label">
                        <i class="fas fa-tag"></i>
                        Total Harga
                    </div>
                    <div class="purchase-info-value">Rp <?php echo number_format($total_price, 0, ',', '.'); ?></div>
                </div>
                
                <div class="purchase-info-item">
                    <div class="purchase-info-label">
                        <i class="fas fa-home"></i>
                        Alamat Pengiriman
                    </div>
                    <div class="purchase-info-value"><?php echo htmlspecialchars($user_data['address'] ?? 'Alamat belum diatur'); ?></div>
                </div>
                
                <div class="purchase-info-item">
                    <div class="purchase-info-label">
                        <i class="fas fa-store"></i>
                        Lokasi Toko
                    </div>
                    <div class="purchase-info-value"><?php echo $shipping_cost['store_location']['city']; ?>, <?php echo $shipping_cost['store_location']['province']; ?></div>
                </div>
                
                <div class="purchase-info-item">
                    <div class="purchase-info-label">
                        <i class="fas fa-map-marker-alt"></i>
                        Alamat Lengkap
                    </div>
                    <div class="purchase-info-value"><?php echo $shipping_cost['store_location']['address']; ?>, <?php echo $shipping_cost['store_location']['district']; ?></div>
                </div>
                
                <div class="purchase-info-item">
                    <div class="purchase-info-label">
                        <i class="fas fa-truck"></i>
                        Estimasi Pengiriman
                    </div>
                    <div class="purchase-info-value"><?php echo $shipping_cost['estimated_days']; ?></div>
                </div>
                
                <div class="purchase-info-item">
                    <div class="purchase-info-label">
                        <i class="fas fa-credit-card"></i>
                        Total Pembayaran
                    </div>
                    <div class="purchase-info-value highlight">Rp <?php echo number_format($total_price + $shipping_cost['total_cost'], 0, ',', '.'); ?></div>
                </div>
            </div>
            
            <!-- Detail Produk yang Dibeli -->
            <div class="purchased-products-section">
                <h4 class="section-title">
                    <i class="fas fa-shopping-bag"></i>
                    Detail Produk yang Dibeli
                </h4>
                <div class="purchased-products-grid">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="purchased-product-item">
                            <div class="product-header">
                                <div class="product-image-container">
                                    <img src="../assets/img/products/<?php echo htmlspecialchars($item['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                         class="purchased-product-image">
                                    <div class="quantity-badge"><?php echo $item['quantity']; ?></div>
                                </div>
                                <button type="button"
                                   class="btn-delete-product"
                                   onclick="decreaseQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity']; ?>)"
                                   title="Kurangi jumlah">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                            <div class="product-details-content">
                                <h5 class="product-name-title"><?php echo htmlspecialchars($item['name']); ?></h5>
                                <p class="product-description"><?php echo htmlspecialchars(substr($item['description'], 0, 100)) . '...'; ?></p>
                                <div class="product-meta">
                                    <span class="price-tag">Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></span>
                                    <span class="stock-info">Stok: <?php echo $item['stock']; ?></span>
                                </div>
                                <div class="subtotal-info">
                                    <strong>Subtotal: Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></strong>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Purchase Actions -->
            <div class="purchase-actions">
                <a href="kategori.php" class="btn-continue-shopping">
                    <i class="fas fa-arrow-left me-2"></i>
                    Lanjut Belanja
                </a>
                <a href="checkout.php" class="btn-purchase">
                    <i class="fas fa-shopping-cart me-2"></i>
                    Proses Checkout
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Function untuk mengurangi quantity
function decreaseQuantity(cartId, currentQuantity) {
    if (currentQuantity <= 1) {
        // Jika quantity 1, tampilkan konfirmasi untuk menghapus item
        if (confirm('Apakah Anda ingin menghapus item ini dari keranjang?')) {
            // Hapus item menggunakan AJAX
            const formData = new FormData();
            formData.append('action', 'update_quantity');
            formData.append('cart_id', cartId);
            formData.append('quantity_change', -1);

            fetch('keranjang.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Gagal menghapus item: ' + data.message);
                }
            })
            .catch(error => {
                alert('Terjadi kesalahan: ' + error);
            });
        }
    } else {
        // Kurangi quantity menggunakan AJAX
        const formData = new FormData();
        formData.append('action', 'update_quantity');
        formData.append('cart_id', cartId);
        formData.append('quantity_change', -1);

        fetch('keranjang.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Gagal mengurangi quantity: ' + data.message);
            }
        })
        .catch(error => {
            alert('Terjadi kesalahan: ' + error);
        });
    }
}
</script>

</body>
</html>
