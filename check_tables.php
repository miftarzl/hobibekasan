<?php
// Check database tables for checkout and struk pembelian
require 'config/config.php';

echo "=== DATABASE TABLES CHECK ===\n\n";

// Check required tables
$tables = ['users', 'products', 'cart', 'orders', 'order_items'];

foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "✅ Table '$table' exists\n";
        
        // Show table structure
        $structure = $conn->query("DESCRIBE $table");
        while ($row = $structure->fetch_assoc()) {
            echo "   - {$row['Field']} ({$row['Type']})\n";
        }
        echo "\n";
    } else {
        echo "❌ Table '$table' does not exist\n\n";
    }
}

// Check current data
echo "=== CURRENT DATA ===\n";

// Check if there are any orders
$orders_result = $conn->query("SELECT COUNT(*) as count FROM orders");
$orders_count = $orders_result->fetch_assoc()['count'];
echo "📊 Total orders: $orders_count\n";

// Check if there are any cart items
$cart_result = $conn->query("SELECT COUNT(*) as count FROM cart");
$cart_count = $cart_result->fetch_assoc()['count'];
echo "🛒 Total cart items: $cart_count\n";

// Check if there are any products
$products_result = $conn->query("SELECT COUNT(*) as count FROM products");
$products_count = $products_result->fetch_assoc()['count'];
echo "📦 Total products: $products_count\n";

// Check if there are any users
$users_result = $conn->query("SELECT COUNT(*) as count FROM users");
$users_count = $users_result->fetch_assoc()['count'];
echo "👥 Total users: $users_count\n\n";

// Show sample order if exists
if ($orders_count > 0) {
    echo "=== SAMPLE ORDER DATA ===\n";
    $sample_order = $conn->query("SELECT * FROM orders LIMIT 1")->fetch_assoc();
    if ($sample_order) {
        foreach ($sample_order as $key => $value) {
            echo "   $key: $value\n";
        }
    }
}

$conn->close();
?>
