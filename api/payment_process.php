<?php
/**
 * Payment Processing Endpoint
 * Handles payment requests and creates Midtrans Snap token
 */

session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user']['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit();
}

require_once '../config/config.php';
require_once '../config/env.php';
require_once '../includes/MidtransPayment.php';

try {
    // Start DB transaction
    $conn->begin_transaction();

    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid request data');
    }
    
    $user_id = $_SESSION['user']['user_id'];
    $shipping_address = $input['shipping_address'] ?? '';
    $payment_method = $input['payment_method'] ?? 'midtrans';
    
    // Get cart items
    $cart_query = "SELECT c.*, p.name, p.price 
                  FROM cart c 
                  JOIN products p ON c.product_id = p.product_id 
                  WHERE c.user_id = ?";
    $cart_stmt = $conn->prepare($cart_query);
    $cart_stmt->bind_param("i", $user_id);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();
    
    if ($cart_result->num_rows === 0) {
        throw new Exception('Cart is empty');
    }
    
    $cart_items = [];
    $total_amount = 0;
    
    while ($row = $cart_result->fetch_assoc()) {
        $cart_items[] = $row;
        $total_amount += ($row['price'] * $row['quantity']);
    }
    
    // Calculate shipping cost (reuse function from checkout)
    function calculateShippingCost($address) {
        $address_lower = strtolower($address);
        
        if (strpos($address_lower, 'bekasi') !== false) {
            return 8000;
        } elseif (strpos($address_lower, 'jakarta') !== false) {
            return 15000;
        } elseif (strpos($address_lower, 'tangerang') !== false || strpos($address_lower, 'banten') !== false) {
            return 20000;
        } elseif (strpos($address_lower, 'bandung') !== false) {
            return 18000;
        } else {
            return 25000;
        }
    }
    
    $shipping_cost = calculateShippingCost($shipping_address);
    $service_fee = 5000;
    $final_amount = $total_amount + $shipping_cost + $service_fee;
    
    // Generate order number
    $order_number = 'ORD-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    
    // Insert order into database
    $order_query = "INSERT INTO orders (user_id, order_number, total_amount, shipping_address, shipping_cost, service_fee, status, payment_method, created_at) 
                   VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, NOW())";
    $order_stmt = $conn->prepare($order_query);
    $order_stmt->bind_param("isdisis", $user_id, $order_number, $final_amount, $shipping_address, $shipping_cost, $service_fee, $payment_method);
    $order_stmt->execute();
    $order_id = $conn->insert_id;
    
    // Insert order items and reduce stock
    foreach ($cart_items as $item) {
        $order_item_query = "INSERT INTO order_items (order_id, product_id, quantity, price, subtotal)
                           VALUES (?, ?, ?, ?, ?)";
        $order_item_stmt = $conn->prepare($order_item_query);
        $subtotal = $item['price'] * $item['quantity'];
        $order_item_stmt->bind_param("iiidd", $order_id, $item['product_id'], $item['quantity'], $item['price'], $subtotal);
        $order_item_stmt->execute();

        // Reduce product stock
        $stock_update_query = "UPDATE products SET stock = stock - ? WHERE product_id = ? AND stock >= ?";
        $stock_update_stmt = $conn->prepare($stock_update_query);
        $stock_update_stmt->bind_param("iii", $item['quantity'], $item['product_id'], $item['quantity']);
        $stock_update_stmt->execute();

        // Check if stock was actually reduced (stock was sufficient)
        if ($stock_update_stmt->affected_rows === 0) {
            throw new Exception("Insufficient stock for product: " . $item['name']);
        }
    }

    // Clear cart
    $clear_cart_query = "DELETE FROM cart WHERE user_id = ?";
    $clear_cart_stmt = $conn->prepare($clear_cart_query);
    $clear_cart_stmt->bind_param("i", $user_id);
    $clear_cart_stmt->execute();
    
    // Get user details for Midtrans
    $user_query = "SELECT username, email FROM users WHERE id = ?";
    $user_stmt = $conn->prepare($user_query);
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user_data = $user_result->fetch_assoc();

    // Validate and format email
    $email = filter_var($user_data['email'], FILTER_VALIDATE_EMAIL) ? $user_data['email'] : 'customer@example.com';

    // Prepare Midtrans payment data
    $midtransMethods = ['ewallet', 'virtual_account', 'card_payment', 'qris', 'midtrans'];
    if (in_array($payment_method, $midtransMethods)) {
        $midtrans = new MidtransPayment();

        // Prepare item details for Midtrans
        $item_details = [];
        foreach ($cart_items as $item) {
            $item_details[] = [
                'id' => $item['product_id'],
                'price' => $item['price'],
                'quantity' => $item['quantity'],
                'name' => $item['name']
            ];
        }

        // Add shipping and service fee as items
        $item_details[] = [
            'id' => 'SHIPPING',
            'price' => $shipping_cost,
            'quantity' => 1,
            'name' => 'Biaya Pengiriman'
        ];

        $item_details[] = [
            'id' => 'SERVICE',
            'price' => $service_fee,
            'quantity' => 1,
            'name' => 'Biaya Layanan'
        ];

        // Customer details
        $customer_details = [
            'first_name' => $user_data['username'] ?: 'Customer',
            'email' => $email,
            'shipping_address' => [
                'address' => $shipping_address,
                'country' => 'Indonesia'
            ]
        ];
        
        // Create Snap token (if fails, throws exception and triggers rollback below)
        $orderData = [
            'order_id' => $order_number,
            'amount' => $final_amount,
            'customer_details' => $customer_details,
            'items' => $item_details
        ];
        
        $snapToken = $midtrans->createSnapToken($orderData);
        
        // Commit transaction if Snap token generated successfully
        $conn->commit();

        echo json_encode([
            'success' => true,
            'order_id' => $order_id,
            'order_number' => $order_number,
            'snap_token' => $snapToken,
            'client_key' => $midtrans->getClientKey(),
            'is_production' => $midtrans->isProduction(),
            'amount' => $final_amount
        ]);
    } else {
        // For other payment methods (COD, etc.)
        $conn->commit();

        echo json_encode([
            'success' => true,
            'order_id' => $order_id,
            'order_number' => $order_number,
            'amount' => $final_amount,
            'redirect_url' => 'riwayat_pesanan.php?order=' . $order_number
        ]);
    }
    
} catch (Exception $e) {
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
