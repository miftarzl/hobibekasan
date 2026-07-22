<?php
session_start(); // Mulai session

    // Cek apakah user login atau belum
    if (!isset($_SESSION['user']['user_id'])) {
        header("Location: login.php");
        exit();
    }

    // Database connection
    include '../config/config.php';
    include '../config/env.php';

    // Handle AJAX request untuk update quantity
    if (isset($_POST['action']) && $_POST['action'] === 'update_quantity') {
        $cart_id = $_POST['cart_id'];
        $quantity_change = $_POST['quantity_change']; // -1 untuk decrease, +1 untuk increase

        // Cek apakah cart item milik user ini
        $check_query = "SELECT * FROM cart WHERE cart_id = ? AND user_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("ii", $cart_id, $_SESSION['user']['user_id']);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $cart_item = $check_result->fetch_assoc();

        if ($cart_item) {
            $new_quantity = $cart_item['quantity'] + $quantity_change;

            if ($new_quantity <= 0) {
                // Hapus item jika quantity 0
                $delete_query = "DELETE FROM cart WHERE cart_id = ?";
                $delete_stmt = $conn->prepare($delete_query);
                $delete_stmt->bind_param("i", $cart_id);
                $delete_stmt->execute();
                echo json_encode(['success' => true, 'action' => 'deleted']);
            } else {
                // Update quantity
                $update_query = "UPDATE cart SET quantity = ? WHERE cart_id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("ii", $new_quantity, $cart_id);
                $update_stmt->execute();
                echo json_encode(['success' => true, 'action' => 'updated', 'new_quantity' => $new_quantity]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Item tidak ditemukan']);
        }
        exit();
    }

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
        
        <!-- Midtrans Snap.js -->
        <script type="text/javascript"
          src="https://app.sandbox.midtrans.com/snap/snap.js"
          data-client-key="<?php echo EnvLoader::get('MIDTRANS_CLIENT_KEY', ''); ?>"></script>
        
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
            
            .btn-quantity-minus {
                position: absolute;
                top: -8px;
                right: -8px;
                width: 32px;
                height: 32px;
                border-radius: 50%;
                background: linear-gradient(135deg, #ff4444, #cc0000);
                color: white;
                border: 2px solid white;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 2px 8px rgba(255, 68, 68, 0.4);
                transition: all 0.3s ease;
                z-index: 10;
            }
            
            .btn-quantity-minus:hover {
                transform: scale(1.1);
                box-shadow: 0 4px 12px rgba(255, 68, 68, 0.6);
            }
            
            .btn-quantity-minus:active {
                transform: scale(0.95);
            }
            
            .btn-quantity-minus i {
                font-size: 12px;
                font-weight: bold;
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
            
            .payment-methods {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 1rem;
            }

            .payment-method {
                background: white;
                border: 2px solid #e9ecef;
                border-radius: 12px;
                padding: 1.5rem;
                cursor: pointer;
                transition: all 0.3s ease;
                text-align: center;
            }

            .payment-method:hover {
                border-color: #003366;
                transform: translateY(-2px);
                box-shadow: 0 4px 15px rgba(0, 51, 102, 0.1);
            }

            .payment-method.selected {
                border-color: #003366;
                background: rgba(0, 51, 102, 0.1);
            }

            .payment-icon {
                width: 50px;
                height: 50px;
                margin-bottom: 1rem;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 2rem;
            }

            .payment-icon.bank {
                color: #dc2626;
            }

            .payment-icon.e-wallet {
                color: #0066cc;
            }

            .payment-icon.qris {
                color: #00a651;
            }

            .payment-icon.credit-card {
                color: #6b7280;
            }

            .payment-icon.virtual-account {
                color: #8b5cf6;
            }

            .payment-icon.cash {
                color: #10b981;
            }

            .payment-name {
                font-weight: 600;
                color: #333;
                margin-bottom: 0.5rem;
            }

            .payment-desc {
                font-size: 0.85rem;
                color: #6b7280;
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
            
            /* Custom Popup Modal */
            .custom-popup-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.7);
                display: none;
                justify-content: center;
                align-items: center;
                z-index: 9999;
                animation: fadeIn 0.3s ease;
            }
            
            .custom-popup-overlay.show {
                display: flex;
            }
            
            @keyframes fadeIn {
                from {
                    opacity: 0;
                }
                to {
                    opacity: 1;
                }
            }
            
            .custom-popup {
                background: white;
                border-radius: 20px;
                padding: 2rem;
                max-width: 450px;
                width: 90%;
                text-align: center;
                animation: slideUp 0.4s ease;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            }
            
            @keyframes slideUp {
                from {
                    transform: translateY(50px);
                    opacity: 0;
                }
                to {
                    transform: translateY(0);
                    opacity: 1;
                }
            }
            
            .popup-icon {
                width: 80px;
                height: 80px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 1.5rem;
                font-size: 2.5rem;
            }
            
            .popup-icon.success {
                background: linear-gradient(135deg, #10b981, #059669);
                color: white;
                animation: pulse 2s infinite;
            }
            
            .popup-icon.error {
                background: linear-gradient(135deg, #ef4444, #dc2626);
                color: white;
            }
            
            @keyframes pulse {
                0%, 100% {
                    transform: scale(1);
                }
                50% {
                    transform: scale(1.05);
                }
            }
            
            .popup-title {
                font-size: 1.5rem;
                font-weight: 700;
                margin-bottom: 0.75rem;
                color: #1f2937;
            }
            
            .popup-title.success {
                color: #059669;
            }
            
            .popup-title.error {
                color: #dc2626;
            }
            
            .popup-message {
                font-size: 1rem;
                color: #6b7280;
                margin-bottom: 1.5rem;
                line-height: 1.6;
            }
            
            .popup-order-info {
                background: #f8f9fa;
                border-radius: 12px;
                padding: 1rem;
                margin-bottom: 1.5rem;
                border: 1px solid #e5e7eb;
            }
            
            .popup-order-info-item {
                display: flex;
                justify-content: space-between;
                padding: 0.5rem 0;
                border-bottom: 1px solid #e5e7eb;
            }
            
            .popup-order-info-item:last-child {
                border-bottom: none;
            }
            
            .popup-order-label {
                font-weight: 600;
                color: #6b7280;
            }
            
            .popup-order-value {
                font-weight: 700;
                color: #1f2937;
            }
            
            .popup-buttons {
                display: flex;
                gap: 1rem;
                justify-content: center;
            }
            
            .popup-btn {
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
            
            .popup-btn-primary {
                background: linear-gradient(135deg, #4f46e5, #7c3aed);
                color: white;
                border: none;
            }
            
            .popup-btn-primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(79, 70, 229, 0.4);
            }
            
            .popup-btn-secondary {
                background: #f8f9fa;
                color: #6b7280;
                border: 2px solid #e5e7eb;
            }
            
            .popup-btn-secondary:hover {
                background: #e5e7eb;
                color: #1f2937;
            }
            
            @media (max-width: 992px) {
                .checkout-grid {
                    grid-template-columns: 1fr;
                    gap: 20px;
                }
            }

            @media (max-width: 768px) {
                .main-container {
                    padding: 15px;
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

                .payment-method {
                    padding: 15px;
                }

                .payment-method-icon {
                    font-size: 1.5rem;
                }

                .payment-method-name {
                    font-size: 0.9rem;
                }

                .payment-method-description {
                    font-size: 0.8rem;
                }
            }

            @media (max-width: 576px) {
                .main-container {
                    padding: 10px;
                }

                .checkout-header {
                    padding: 1rem;
                }

                .checkout-title {
                    font-size: 1.3rem;
                }

                .checkout-content {
                    padding: 0.8rem;
                }

                .checkout-item {
                    padding: 12px;
                }

                .item-image {
                    width: 50px;
                    height: 50px;
                }

                .item-details {
                    font-size: 0.85rem;
                }

                .item-name {
                    font-size: 0.9rem;
                }

                .item-price {
                    font-size: 1rem;
                }

                .payment-method {
                    padding: 12px;
                }

                .payment-method-icon {
                    font-size: 1.3rem;
                }

                .payment-method-name {
                    font-size: 0.85rem;
                }

                .payment-method-description {
                    font-size: 0.75rem;
                }

                .btn-checkout, .btn-back {
                    padding: 10px 15px;
                    font-size: 0.9rem;
                }

                .form-control, .form-select {
                    font-size: 0.9rem;
                    padding: 0.5rem;
                }

                .form-label {
                    font-size: 0.85rem;
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
                                    <div style="position: relative;">
                                        <img src="../assets/img/products/<?php echo htmlspecialchars($item['image']); ?>"
                                            alt="<?php echo htmlspecialchars($item['name']); ?>"
                                            class="item-image">
                                    </div>
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
                        
                        <!-- Form Checkout -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="fas fa-truck"></i>
                                Informasi Pengiriman
                            </h3>
                            <form id="checkoutForm">
                                <div class="form-group">
                                    <label class="form-label">Alamat Pengiriman</label>
                                    <textarea class="form-control" name="shipping_address" rows="3" required><?php echo htmlspecialchars($user_data['address'] ?? ''); ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Catatan (Opsional)</label>
                                    <textarea class="form-control" name="notes" rows="2" placeholder="Tambahkan catatan untuk pesanan Anda..."></textarea>
                                </div>
                               <div class="form-group">
    <label class="form-label">Metode Pembayaran</label>

    <div class="payment-methods">

        <!-- Midtrans (All Payment Methods) -->
        <div class="payment-method selected" onclick="selectPayment('midtrans', event)">
            <div class="payment-icon credit-card">
                <i class="fas fa-credit-card"></i>
            </div>
            <div class="payment-name">Midtrans</div>
            <div class="payment-desc">Kartu Kredit, E-Wallet, QRIS, Bank Transfer</div>
        </div>

        <!-- COD -->
        <div class="payment-method" onclick="selectPayment('cod', event)">
            <div class="payment-icon cash">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="payment-name">COD</div>
            <div class="payment-desc">Bayar di Tempat</div>
        </div>

    </div>

    <input type="hidden" 
           name="payment_method" 
           id="payment_method" 
           value="midtrans" 
           required>
</div>

<!-- PAYMENT DETAILS -->
<div id="paymentDetails" class="payment-info-card">
    <h5 class="payment-title">
        <i class="fas fa-circle-info"></i>
        Informasi Pembayaran
    </h5>

    <div id="paymentDetailsContent"></div>
</div>

<!-- Action Buttons -->
    <button type="submit" class="btn-checkout" id="checkoutBtn">
        <i class="fas fa-receipt"></i>
        Proses Pembayaran
    </button>
</div>
</form>
</div>
<?php endif; ?>

<!-- Custom Payment Popup -->
<div id="paymentPopup" class="custom-popup-overlay">
    <div class="custom-popup">
        <div class="popup-icon" id="popupIcon">
            <i class="fas fa-check"></i>
        </div>
        <h2 class="popup-title" id="popupTitle">Pembayaran Berhasil</h2>
        <p class="popup-message" id="popupMessage">Pembayaran Anda telah berhasil diproses. Pesanan Anda sedang dipersiapkan.</p>
        
        <div class="popup-order-info" id="popupOrderInfo">
            <div class="popup-order-info-item">
                <span class="popup-order-label">Nomor Order:</span>
                <span class="popup-order-value" id="popupOrderNumber">ORD-123456</span>
            </div>
            <div class="popup-order-info-item">
                <span class="popup-order-label">Total Pembayaran:</span>
                <span class="popup-order-value" id="popupAmount">Rp 150.000</span>
            </div>
        </div>
        
        <div class="popup-buttons">
            <a href="riwayat_pesanan.php" class="popup-btn popup-btn-primary">
                <i class="fas fa-receipt"></i> Lihat Pesanan
            </a>
            <a href="kategori.php" class="popup-btn popup-btn-secondary">
                <i class="fas fa-shopping-bag"></i> Belanja Lagi
            </a>
        </div>
    </div>
</div>

<?php include '../assets/footer.php'; ?>
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

            fetch('checkout.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.action === 'deleted') {
                    // Hapus elemen item dari DOM
                    const checkoutItem = document.querySelector(`button[data-cart-id="${cartId}"]`).closest('.checkout-item');
                    checkoutItem.remove();
                    updateTotals();
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

        fetch('checkout.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.action === 'updated') {
                // Update quantity display
                const checkoutItem = document.querySelector(`button[data-cart-id="${cartId}"]`).closest('.checkout-item');
                const quantitySpan = checkoutItem.querySelector('.item-quantity');
                const newQuantity = data.new_quantity;
                quantitySpan.textContent = 'Qty: ' + newQuantity;
                
                // Update onclick attribute dengan quantity baru
                const minusBtn = checkoutItem.querySelector('.btn-quantity-minus');
                minusBtn.setAttribute('onclick', `decreaseQuantity(${cartId}, ${newQuantity})`);
                
                // Update subtotal
                const priceText = checkoutItem.querySelector('.item-price').textContent.replace(/[^0-9]/g, '');
                const price = parseInt(priceText);
                const subtotalSpan = checkoutItem.querySelector('.item-subtotal');
                subtotalSpan.textContent = 'Subtotal: Rp ' + (price * newQuantity).toLocaleString('id-ID');
                
                updateTotals();
            } else {
                alert('Gagal mengurangi quantity: ' + data.message);
            }
        })
        .catch(error => {
            alert('Terjadi kesalahan: ' + error);
        });
    }
}

// Function untuk update total harga
function updateTotals() {
    let totalPrice = 0;
    let totalItems = 0;
    
    document.querySelectorAll('.checkout-item').forEach(item => {
        const quantityText = item.querySelector('.item-quantity').textContent.replace('Qty: ', '');
        const priceText = item.querySelector('.item-price').textContent.replace(/[^0-9]/g, '');
        const quantity = parseInt(quantityText);
        const price = parseInt(priceText);
        
        totalPrice += (price * quantity);
        totalItems += quantity;
    });
    
    // Update summary values jika ada
    const summaryValues = document.querySelectorAll('.summary-value');
    if (summaryValues.length > 0) {
        summaryValues[0].textContent = 'Rp ' + totalPrice.toLocaleString('id-ID');
    }
}

document.addEventListener('DOMContentLoaded', function() {

    // =========================
    // PAYMENT DATA
    // =========================
    const paymentData = {

        // Midtrans
        midtrans: {
            name: 'Midtrans Payment Gateway',
            description: 'Kartu Kredit, E-Wallet (GoPay, OVO, DANA, ShopeePay), QRIS, Bank Transfer (BCA, Mandiri, BNI, BRI)',
            type: 'midtrans'
        },

        // COD
        cod: {
            name: 'Bayar di Tempat',
            number: 'Bayar saat barang diterima',
            holder: 'hobiBekasin',
            type: 'cod'
        }
    };

    // =========================
    // SELECT PAYMENT
    // =========================
    window.selectPayment = function(method, event) {

    document.querySelectorAll('.payment-method').forEach(function(el) {
        el.classList.remove('selected');
    });

    event.currentTarget.classList.add('selected');

    document.getElementById('payment_method').value = method;

    showPaymentDetails(paymentData[method]);
}

    // =========================
    // SHOW PAYMENT DETAILS
    // =========================
    function showPaymentDetails(payment) {
 const paymentDetails = document.getElementById('paymentDetails');
    const paymentDetailsContent = document.getElementById('paymentDetailsContent');
    let html = '';

    if (payment.type === 'midtrans') {
        html = `
            <div class="payment-method-item">
                <span>Payment Gateway</span>
                <span>${payment.name}</span>
            </div>

            <div class="payment-method-item">
                <span>Metode Tersedia</span>
                <span>${payment.description}</span>
            </div>
        `;
    } else if (payment.type === 'cod') {
        html = `
            <div class="payment-method-item">
                <span class="payment-method-name">Metode:</span>
                <span class="payment-method-name">${payment.name}</span>
            </div>

            <div class="payment-method-item">
                <span class="payment-method-name">Instruksi:</span>
                <span class="payment-method-name">${payment.number}</span>
            </div>
        `;
    }

          html += `
        <div class="payment-instructions">
            <strong>Petunjuk Pembayaran:</strong><br>
            1. Lakukan pembayaran sesuai informasi di atas<br>
            2. Simpan bukti pembayaran<br>
            3. Pesanan akan diproses setelah pembayaran dikonfirmasi
        </div>
        <div style="margin-top: 15px;">
            <a href="keranjang.php" class="btn-back" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; text-decoration: none;">
                <i class="fas fa-arrow-left"></i> Kembali ke Keranjang
            </a>
        </div>
    `;


        paymentDetailsContent.innerHTML = html;
        paymentDetails.style.display = 'block';
    }

    // =========================
    // FORM VALIDATION & SUBMISSION
    // =========================
    const checkoutForm = document.getElementById('checkoutForm');

    if (checkoutForm) {

        checkoutForm.addEventListener('submit', function(e) {
            e.preventDefault();

           const shippingAddress =
            checkoutForm.querySelector('textarea[name="shipping_address"]').value.trim();

           const paymentMethodSelect =
            document.getElementById('payment_method').value;

             if (!shippingAddress) {
            alert('Silakan isi alamat pengiriman');
            return false;
        }

             if (!paymentMethodSelect) {
            alert('Pilih metode pembayaran');
            return false;
        }

            const submitBtn = document.getElementById('checkoutBtn');

            submitBtn.innerHTML =
                '<i class="fas fa-spinner fa-spin"></i> Memproses...';

            submitBtn.disabled = true;

            // Prepare payment data
            const paymentData = {
                shipping_address: shippingAddress,
                payment_method: paymentMethodSelect,
                notes: checkoutForm.querySelector('textarea[name="notes"]').value
            };

            // Call payment processing API
            fetch('../api/payment_process.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(paymentData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Check if payment method is Midtrans (ewallet, virtual_account, card_payment, qris)
                    const midtransMethods = ['ewallet', 'virtual_account', 'card_payment', 'qris', 'midtrans'];
                    if (midtransMethods.includes(paymentMethodSelect)) {
                        // Open Midtrans Snap popup
                        window.snap.pay(data.snap_token, {
                            onSuccess: function(result) {
                                showPaymentPopup('success', data.order_number, data.amount);
                            },
                            onPending: function(result) {
                                showPaymentPopup('pending', data.order_number, data.amount);
                            },
                            onError: function(result) {
                                showPaymentPopup('error', data.order_number, data.amount);
                                submitBtn.innerHTML = '<i class="fas fa-receipt"></i> Proses Pembayaran';
                                submitBtn.disabled = false;
                            }
                        });
                    } else {
                        // For COD or other methods
                        showPaymentPopup('success', data.order_number, data.amount);
                    }
                } else {
                    showPaymentPopup('error', null, null, data.message);
                    submitBtn.innerHTML = '<i class="fas fa-receipt"></i> Proses Pembayaran';
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                alert('Terjadi kesalahan: ' + error);
                submitBtn.innerHTML = '<i class="fas fa-receipt"></i> Proses Pembayaran';
                submitBtn.disabled = false;
            });
        });
    }

    showPaymentDetails(paymentData.midtrans);
    
    // =========================
    // CUSTOM POPUP FUNCTIONS
    // =========================
    function showPaymentPopup(type, orderNumber, amount, customMessage = null) {
        const popup = document.getElementById('paymentPopup');
        const popupIcon = document.getElementById('popupIcon');
        const popupTitle = document.getElementById('popupTitle');
        const popupMessage = document.getElementById('popupMessage');
        const popupOrderInfo = document.getElementById('popupOrderInfo');
        const popupOrderNumber = document.getElementById('popupOrderNumber');
        const popupAmount = document.getElementById('popupAmount');
        
        // Reset classes
        popupIcon.className = 'popup-icon';
        popupTitle.className = 'popup-title';
        
        if (type === 'success') {
            popupIcon.classList.add('success');
            popupIcon.innerHTML = '<i class="fas fa-check"></i>';
            popupTitle.classList.add('success');
            popupTitle.textContent = 'Pembayaran Berhasil';
            popupMessage.textContent = 'Pembayaran Anda telah berhasil diproses. Pesanan Anda sedang dipersiapkan.';
            
            // Show order info
            popupOrderInfo.style.display = 'block';
            popupOrderNumber.textContent = orderNumber || 'N/A';
            popupAmount.textContent = amount ? 'Rp ' + parseInt(amount).toLocaleString('id-ID') : 'N/A';
            
        } else if (type === 'pending') {
            popupIcon.classList.add('success');
            popupIcon.innerHTML = '<i class="fas fa-clock"></i>';
            popupTitle.classList.add('success');
            popupTitle.textContent = 'Pembayaran Pending';
            popupMessage.textContent = 'Pembayaran Anda sedang diproses. Silakan selesaikan pembayaran sesuai metode yang dipilih.';
            
            // Show order info
            popupOrderInfo.style.display = 'block';
            popupOrderNumber.textContent = orderNumber || 'N/A';
            popupAmount.textContent = amount ? 'Rp ' + parseInt(amount).toLocaleString('id-ID') : 'N/A';
            
        } else if (type === 'error') {
            popupIcon.classList.add('error');
            popupIcon.innerHTML = '<i class="fas fa-times"></i>';
            popupTitle.classList.add('error');
            popupTitle.textContent = 'Pembayaran Gagal';
            popupMessage.textContent = customMessage || 'Pembayaran gagal. Silakan coba lagi atau pilih metode pembayaran lain.';
            
            // Hide order info for errors
            popupOrderInfo.style.display = 'none';
        }
        
        // Show popup
        popup.classList.add('show');
        
        // Close popup when clicking outside
        popup.addEventListener('click', function(e) {
            if (e.target === popup) {
                popup.classList.remove('show');
            }
        });
    }
    
    // Close popup function
    function closePaymentPopup() {
        const popup = document.getElementById('paymentPopup');
        popup.classList.remove('show');
    }
});
</script>
    </body>
    </html>