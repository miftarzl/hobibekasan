<?php
/**
 * Payment Notification Handler
 * Handles payment notifications from Midtrans
 */

require_once '../config/config.php';
require_once '../config/env.php';
require_once '../includes/MidtransPayment.php';

// Get notification data from Midtrans
$notification = json_decode(file_get_contents('php://input'), true);

if (!$notification) {
    http_response_code(400);
    echo 'Invalid notification data';
    exit();
}

try {
    // Initialize Midtrans payment
    $midtrans = new MidtransPayment();
    
    // Verify notification signature
    $midtrans->verifyNotification($notification);
    
    // Get order details from notification
    $order_number = $notification['order_id'];
    $transaction_status = $notification['transaction_status'];
    $fraud_status = $notification['fraud_status'] ?? null;
    $payment_type = $notification['payment_type'] ?? '';
    
    // Find order in database
    $order_query = "SELECT * FROM orders WHERE order_number = ?";
    $order_stmt = $conn->prepare($order_query);
    $order_stmt->bind_param("s", $order_number);
    $order_stmt->execute();
    $order_result = $order_stmt->get_result();
    
    if ($order_result->num_rows === 0) {
        http_response_code(404);
        echo 'Order not found';
        exit();
    }
    
    $order = $order_result->fetch_assoc();
    $current_status = $order['status'];
    
    // Handle different transaction statuses
    $new_status = $current_status;
    
    if ($transaction_status == 'capture') {
        if ($fraud_status == 'challenge') {
            $new_status = 'pending';
        } else if ($fraud_status == 'accept') {
            $new_status = 'processing';
        }
    } else if ($transaction_status == 'settlement') {
        $new_status = 'processing';
    } else if ($transaction_status == 'pending') {
        $new_status = 'pending';
    } else if ($transaction_status == 'deny') {
        $new_status = 'cancelled';
    } else if ($transaction_status == 'expire') {
        $new_status = 'cancelled';
    } else if ($transaction_status == 'cancel') {
        $new_status = 'cancelled';
    }
    
    // Update order status if changed
    if ($new_status !== $current_status) {
        $update_query = "UPDATE orders SET status = ?, updated_at = NOW() WHERE order_number = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ss", $new_status, $order_number);
        $update_stmt->execute();
        
        // If payment is successful, update product stock
        if ($new_status == 'processing') {
            // Get order items
            $items_query = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
            $items_stmt = $conn->prepare($items_query);
            $items_stmt->bind_param("i", $order['id']);
            $items_stmt->execute();
            $items_result = $items_stmt->get_result();
            
            while ($item = $items_result->fetch_assoc()) {
                // Update product stock
                $stock_query = "UPDATE products SET stock = stock - ? WHERE product_id = ?";
                $stock_stmt = $conn->prepare($stock_query);
                $stock_stmt->bind_param("ii", $item['quantity'], $item['product_id']);
                $stock_stmt->execute();
            }
        }
        
        // Log status change
        $log_query = "INSERT INTO order_status_log (order_id, old_status, new_status, payment_type, created_at) 
                      VALUES (?, ?, ?, ?, NOW())";
        $log_stmt = $conn->prepare($log_query);
        $log_stmt->bind_param("isss", $order['id'], $current_status, $new_status, $payment_type);
        $log_stmt->execute();
    }
    
    // Send response to Midtrans
    http_response_code(200);
    echo 'OK';
    
} catch (Exception $e) {
    http_response_code(500);
    echo 'Error: ' . $e->getMessage();
}
