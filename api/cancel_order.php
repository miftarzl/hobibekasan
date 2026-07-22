<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user']['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../config/config.php';

$input = json_decode(file_get_contents('php://input'), true);
$order_number = $input['order_number'] ?? '';
$status = $input['status'] ?? 'cancelled';

if (!$order_number) {
    echo json_encode(['success' => false, 'message' => 'Missing order number']);
    exit();
}

$conn->begin_transaction();
try {
    // Check if order exists and belongs to current user
    $query = "SELECT order_id, status FROM orders WHERE order_number = ? AND user_id = ? AND status = 'pending'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $order_number, $_SESSION['user']['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();

    if ($order) {
        $order_id = $order['order_id'];

        // Restore stock
        $items_query = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
        $items_stmt = $conn->prepare($items_query);
        $items_stmt->bind_param("i", $order_id);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();

        while ($item = $items_result->fetch_assoc()) {
            $restore_stmt = $conn->prepare("UPDATE products SET stock = stock + ? WHERE product_id = ?");
            $restore_stmt->bind_param("ii", $item['quantity'], $item['product_id']);
            $restore_stmt->execute();
        }

        // Update order status to cancelled
        $update_stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        $update_stmt->bind_param("si", $status, $order_id);
        $update_stmt->execute();
    }

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
