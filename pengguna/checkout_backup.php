<?php
session_start(); // Mulai session

// Cek apakah user login atau belum
if (!isset($_SESSION['user']['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
include '../config/config.php';

// Function untuk menghitung biaya pengiriman
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
    
    // Calculate additional cost based on distance
    $additional_cost = 0;
    if ($distance > $shipping_zones[$zone]['max_distance']) {
        $extra_distance = $distance - $shipping_zones[$zone]['max_distance'];
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

// Ambil user ID
$user_id = $_SESSION['user']['user_id'];

// Ambil data keranjang
$cart_query = "SELECT c.*, p.name, p.price, p.image, p.stock, p.description
               FROM cart c
               JOIN products p ON c.product_id = p.product_id
               WHERE c.user_id = ?
               ORDER BY c.created_at DESC";
$cart_stmt = $conn->prepare($cart_query);
$cart_stmt->bind_param("i", $user_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();
$cart_items = [];

$total_items = 0;
$total_price = 0;

while ($row = $cart_result->fetch_assoc()) {
    $cart_items[] = $row;
    $total_items += $row['quantity'];
    $total_price += ($row['price'] * $row['quantity']);
}

// Ambil data user
$user_query = "SELECT id, username, email, address FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();

// Calculate shipping cost based on distance
$shipping_cost = calculateShippingCost($user_data);

// Calculate total payment
$service_fee = 5000;
$total_payment = $total_price + $shipping_cost['total_cost'] + $service_fee;

// Update cart count in session
$_SESSION['cart_count'] = $total_items;

// AI Assistant Configuration
$ai_assistant_enabled = !empty($gemini_key_manual) || !empty($openai_key_manual);
$ai_chat_endpoint = defined('AI_CHAT_ENDPOINT') ? AI_CHAT_ENDPOINT : '../assets/ai_chat.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - hobiBekasan</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome 6 -->
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
        
        .checkout-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.15);
            overflow: hidden;
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .checkout-header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
        }
        
        .checkout-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }
        
        .checkout-title {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }
        
        .checkout-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        .checkout-content {
            padding: 2rem;
        }
        
        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .checkout-summary {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            padding: 1.5rem;
            border: 1px solid #dee2e6;
        }
        
        .checkout-form {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            border: 1px solid #dee2e6;
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
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 0.25rem rgba(79, 70, 229, 0.25);
            outline: none;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .summary-item:last-child {
            border-bottom: none;
            padding-top: 1rem;
            margin-top: 0.5rem;
            border-top: 2px solid #4f46e5;
        }
        
        .summary-label {
            font-weight: 600;
            color: #6c757d;
        }
        
        .summary-value {
            font-weight: 700;
            color: #333;
        }
        
        .summary-total {
            font-size: 1.3rem;
            color: #4f46e5;
        }
        
        .checkout-items {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            border: 1px solid #dee2e6;
            margin-bottom: 2rem;
        }
        
        .checkout-item {
            display: flex;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #f1f3f4;
            gap: 1rem;
        }
        
        .checkout-item:last-child {
            border-bottom: none;
        }
        
        .item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-name {
            font-weight: 700;
            font-size: 1.1rem;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .item-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 0.5rem;
        }
        
        .item-quantity {
            background: #f8f9fa;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 600;
            color: #6c757d;
        }
        
        .item-price {
            background: #28a745;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .item-subtotal {
            font-weight: 700;
            color: #4f46e5;
            font-size: 1.1rem;
        }
        
        .btn-checkout {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 15px 30px;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
        }
        
        .btn-checkout:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(79, 70, 229, 0.4);
        }
        
        .btn-checkout:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .btn-back {
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 12px;
            padding: 15px 30px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-back:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .payment-info-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 1rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .payment-title {
            color: #495057;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .payment-details-content {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            border: 1px solid #dee2e6;
        }
        
        .payment-method-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f1f3f4;
        }
        
        .payment-method-item:last-child {
            border-bottom: none;
        }
        
        .payment-method-name {
            font-weight: 600;
            color: #333;
        }
        
        .payment-method-number {
            font-family: 'Courier New', monospace;
            font-weight: 700;
            color: #007bff;
            background: #e7f3ff;
            padding: 4px 8px;
            border-radius: 4px;
        }
        
        .payment-method-qr {
            width: 150px;
            height: 150px;
            border: 2px dashed #007bff;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            margin: 0.5rem 0;
        }
        
        .payment-instructions {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            font-size: 0.9rem;
            color: #856404;
        }
        
        @media (max-width: 992px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .main-container {
                padding: 10px;
            }
            
            .checkout-header {
                padding: 1.5rem;
            }
            
            .checkout-title {
                font-size: 1.5rem;
            }
            
            .checkout-content {
                padding: 1rem;
            }
            
            .checkout-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .item-image {
                width: 60px;
                height: 60px;
            }
            
            .btn-checkout, .btn-back {
                width: 100%;
                justify-content: center;
            }
        }
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .checkout-header {
            background: linear-gradient(135deg, #003366 0%, #005b99 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .checkout-header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .checkout-body {
            padding: 40px;
        }
        
        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #003366;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkout-summary {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid #e9ecef;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: white;
            border-radius: 12px;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .summary-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .summary-label {
            font-weight: 600;
            font-size: 1rem;
            color: #495057;
        }
        
        .summary-value {
            font-weight: 700;
            font-size: 1.1rem;
            color: #003366;
        }
        
        .summary-item.total {
            background: linear-gradient(135deg, #003366 0%, #005b99 100%);
            color: white;
            border: none;
        }
        
        .summary-item.total .summary-label,
        .summary-item.total .summary-value {
            color: white;
            font-size: 1.2rem;
        }
        
        .checkout-items {
            margin-bottom: 30px;
        }
        
        .checkout-item {
            display: flex;
            align-items: center;
            padding: 20px;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            margin-bottom: 15px;
            background: white;
            transition: all 0.3s ease;
        }
        
        .checkout-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 10px;
            margin-right: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .item-details {
            flex: 1;
        }
        
        .payment-info-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 1rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .payment-title {
            color: #495057;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .payment-details-content {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            border: 1px solid #dee2e6;
        }
        
        .payment-method-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f1f3f4;
        }
        
        .payment-method-item:last-child {
            border-bottom: none;
        }
        
        .payment-method-name {
            font-weight: 600;
            color: #333;
        }
        
        .payment-method-number {
            font-family: 'Courier New', monospace;
            font-weight: 700;
            color: #007bff;
            background: #e7f3ff;
            padding: 4px 8px;
            border-radius: 4px;
        }
        
        .payment-method-qr {
            width: 150px;
            height: 150px;
            border: 2px dashed #007bff;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            margin: 0.5rem 0;
        }
        
        .payment-instructions {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            font-size: 0.9rem;
            color: #856404;
        }
        
        .item-name {
            font-weight: 700;
            font-size: 1.1rem;
            color: #333;
            margin-bottom: 5px;
        }
        
        .item-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 10px;
        }
        
        .item-quantity {
            background: #f8f9fa;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 600;
            color: #6c757d;
        }
        
        .item-price {
            background: #28a745;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .item-subtotal {
            font-weight: 700;
            font-size: 1.1rem;
            color: #003366;
        }
        
        .form-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid #e9ecef;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 10px;
            display: block;
        }
        
        .form-control {
            width: 100%;
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #003366;
            box-shadow: 0 0 0 0.2rem rgba(0, 51, 102, 0.25);
            outline: none;
        }
        
        .btn-checkout {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 15px 40px;
            font-weight: 700;
            font-size: 1.1rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 5px 20px rgba(40, 167, 69, 0.3);
            width: 100%;
            justify-content: center;
        }
        
        .btn-checkout:hover {
            background: linear-gradient(135deg, #20c997, #28a745);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
        }
        
        .btn-checkout:active {
            transform: translateY(0);
        }
        
        .btn-back {
            background: linear-gradient(135deg, #61b2ff, #1e7fd6);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px 30px;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 5px 20px rgba(97, 178, 255, 0.3);
        }
        
        .btn-back:hover {
            background: linear-gradient(135deg, #1e7fd6, #61b2ff);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(97, 178, 255, 0.4);
        }
        
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .empty-cart-icon {
            font-size: 4rem;
            color: #6c757d;
            margin-bottom: 20px;
        }
        
        .empty-cart-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #495057;
            margin-bottom: 10px;
        }
        
        .empty-cart-text {
            color: #6c757d;
            font-size: 1.1rem;
            margin-bottom: 30px;
        }
        
        @media (max-width: 768px) {
            .main-container {
                padding: 15px 10px 60px;
            }
            
            .checkout-body {
                padding: 20px;
            }
            
            .checkout-item {
                flex-direction: column;
                text-align: center;
            }
            
            .item-image {
                margin-right: 0;
                margin-bottom: 15px;
            }
            
            .item-meta {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Include navbar yang sama -->
    <?php include '../assets/navbar.php'; ?>
    
    <div class="main-container">
        <?php if (empty($cart_items)): ?>
            <div class="checkout-container">
                <div class="empty-cart">
                    <div class="empty-cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h2 class="empty-cart-title">Keranjang Belanja Kosong</h2>
                    <p class="empty-cart-text">
                        Keranjang belanja Anda kosong. Silakan tambahkan produk terlebih dahulu.
                    </p>
                    <a href="kategori.php" class="btn-back">
                        <i class="fas fa-arrow-left"></i>
                        Kembali ke Kategori
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="checkout-container">
                <div class="checkout-header">
                    <h1>
                        <i class="fas fa-credit-card"></i>
                        Checkout
                    </h1>
                </div>
                
                <div class="checkout-body">
                    <!-- Ringkasan Checkout -->
                    <div class="checkout-summary">
                        <h3 class="section-title">
                            <i class="fas fa-receipt"></i>
                            Ringkasan Checkout
                        </h3>
                        <div class="summary-grid">
                            <div class="summary-item">
                                <span class="summary-label">Total Produk:</span>
                                <span class="summary-value"><?php echo $total_items; ?> Item</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Subtotal Produk:</span>
                                <span class="summary-value">Rp <?php echo number_format($total_price, 0, ',', '.'); ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Biaya Dasar Pengiriman:</span>
                                <span class="summary-value">Rp <?php echo number_format($shipping_cost['base_cost'], 0, ',', '.'); ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Total Biaya Pengiriman:</span>
                                <span class="summary-value">Rp <?php echo number_format($shipping_cost['total_cost'], 0, ',', '.'); ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Biaya Layanan:</span>
                                <span class="summary-value">Rp 5.000</span>
                            </div>
                            <div class="summary-item total">
                                <span class="summary-label">Total Pembayaran:</span>
                                <span class="summary-value">Rp <?php echo number_format($total_payment, 0, ',', '.'); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Detail Produk yang Dibeli -->
                    <div class="checkout-items">
                        <h3 class="section-title">
                            <i class="fas fa-shopping-bag"></i>
                            Detail Produk yang Dibeli
                        </h3>
                        <?php foreach ($cart_items as $item): ?>
                            <div class="checkout-item">
                                <img src="../assets/img/produk/<?php echo htmlspecialchars($item['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                     class="item-image">
                                <div class="item-details">
                                    <h4 class="item-name"><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <div class="item-meta">
                                        <span class="item-quantity">Qty: <?php echo $item['quantity']; ?></span>
                                        <span class="item-price">Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></span>
                                    </div>
                                    <div class="item-subtotal">
                                        Subtotal: Rp <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Include footer yang sama -->
    <?php include '../assets/footer.php'; ?>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Payment method details
    const paymentMethod = document.getElementById('paymentMethod');
    const paymentDetails = document.getElementById('paymentDetails');
    const paymentDetailsContent = document.getElementById('paymentDetailsContent');
    
    const paymentData = {
        transfer_bca: {
            name: 'Bank BCA',
            number: '1234567890',
            holder: 'hobiBekasin',
            type: 'bank'
        },
        transfer_mandiri: {
            name: 'Bank Mandiri',
            number: '0987654321',
            holder: 'hobiBekasin',
            type: 'bank'
        },
        transfer_bni: {
            name: 'Bank BNI',
            number: '1122334455',
            holder: 'hobiBekasin',
            type: 'bank'
        },
        transfer_bri: {
            name: 'Bank BRI',
            number: '5544332211',
            holder: 'hobiBekasin',
            type: 'bank'
        },
        gopay: {
            name: 'GoPay',
            number: '081234567890',
            holder: 'hobiBekasin',
            type: 'ewallet'
        },
        ovo: {
            name: 'OVO',
            number: '089876543210',
            holder: 'hobiBekasin',
            type: 'ewallet'
        },
        dana: {
            name: 'DANA',
            number: '085678901234',
            holder: 'hobiBekasin',
            type: 'ewallet'
        },
        shopeepay: {
            name: 'ShopeePay',
            number: '08123456789',
            holder: 'hobiBekasin',
            type: 'ewallet'
        },
        qris: {
            name: 'QRIS',
            number: 'QRIS Payment',
            holder: 'hobiBekasin',
            type: 'qris'
        }
    };
    
    paymentMethod.addEventListener('change', function() {
        const selectedMethod = this.value;
        
        if (selectedMethod === 'cod' || selectedMethod === '') {
            paymentDetails.style.display = 'none';
            return;
        }
        
        const payment = paymentData[selectedMethod];
        if (payment) {
            let html = '';
            
            if (payment.type === 'bank') {
                html = `
                    <div class="payment-method-item">
                        <span class="payment-method-name">Bank:</span>
                        <span class="payment-method-name">${payment.name}</span>
                    </div>
                    <div class="payment-method-item">
                        <span class="payment-method-name">No. Rekening:</span>
                        <span class="payment-method-number">${payment.number}</span>
                    </div>
                    <div class="payment-method-item">
                        <span class="payment-method-name">Atas Nama:</span>
                        <span class="payment-method-name">${payment.holder}</span>
                    </div>
                `;
            } else if (payment.type === 'ewallet') {
                html = `
                    <div class="payment-method-item">
                        <span class="payment-method-name">E-Wallet:</span>
                        <span class="payment-method-name">${payment.name}</span>
                    </div>
                    <div class="payment-method-item">
                        <span class="payment-method-name">No. HP:</span>
                        <span class="payment-method-number">${payment.number}</span>
                    </div>
                    <div class="payment-method-item">
                        <span class="payment-method-name">Atas Nama:</span>
                        <span class="payment-method-name">${payment.holder}</span>
                    </div>
                `;
            } else if (payment.type === 'qris') {
                html = `
                    <div class="payment-method-item">
                        <span class="payment-method-name">Metode:</span>
                        <span class="payment-method-name">${payment.name}</span>
                    </div>
                    <div class="payment-method-qr">
                        <i class="fas fa-qrcode fa-3x text-muted"></i>
                    </div>
                    <div class="payment-method-item">
                        <span class="payment-method-name">Atas Nama:</span>
                        <span class="payment-method-name">${payment.holder}</span>
                    </div>
                `;
            }
            
            html += `
                <div class="payment-instructions">
                    <strong>Petunjuk Pembayaran:</strong><br>
                    1. Lakukan pembayaran sesuai dengan informasi di atas<br>
                    2. Simpan bukti pembayaran<br>
                    3. Pesanan akan diproses setelah pembayaran dikonfirmasi
                </div>
            `;
            
            paymentDetailsContent.innerHTML = html;
            paymentDetails.style.display = 'block';
        }
    });
    
    // Form submission handler
    const checkoutForm = document.querySelector('form[action="pembayaran.php"]');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            console.log('Form submitted');
            
            // Basic validation
            const shippingAddress = checkoutForm.querySelector('textarea[name="shipping_address"]').value.trim();
            const paymentMethodSelect = checkoutForm.querySelector('select[name="payment_method"]').value;
            
            if (!shippingAddress) {
                e.preventDefault();
                alert('Silakan isi alamat pengiriman terlebih dahulu.');
                return false;
            }
            
            if (!paymentMethodSelect) {
                e.preventDefault();
                alert('Silakan pilih metode pembayaran.');
                return false;
            }
            
            // Show loading
            const submitBtn = checkoutForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
            submitBtn.disabled = true;
            
            console.log('Form validation passed, submitting...');
            
            // Allow form to submit normally
            return true;
        });
    }
});
</script>
</body>
</html>
