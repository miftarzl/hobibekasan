<?php
// Database connection
require 'config/config.php';

echo "<h2>Creating Orders Tables for hobiBekasan</h2>";

// Create orders table
$orders_sql = "CREATE TABLE IF NOT EXISTS `orders` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `order_number` varchar(50) NOT NULL,
    `user_id` int(11) NOT NULL,
    `total_amount` decimal(10,2) NOT NULL,
    `shipping_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
    `service_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
    `payment_method` varchar(50) NOT NULL,
    `shipping_address` text NOT NULL,
    `notes` text DEFAULT NULL,
    `status` enum('pending','processing','completed','cancelled') NOT NULL DEFAULT 'pending',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `order_number` (`order_number`),
    KEY `user_id` (`user_id`),
    KEY `status` (`status`),
    KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// Create order_items table
$order_items_sql = "CREATE TABLE IF NOT EXISTS `order_items` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `order_id` int(11) NOT NULL,
    `product_id` int(11) NOT NULL,
    `quantity` int(11) NOT NULL,
    `price` decimal(10,2) NOT NULL,
    `subtotal` decimal(10,2) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `order_id` (`order_id`),
    KEY `product_id` (`product_id`),
    CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// Create tables
try {
    // Create orders table
    if ($conn->query($orders_sql)) {
        echo "<p style='color: green;'>SUCCESS: Orders table created successfully</p>";
    } else {
        echo "<p style='color: orange;'>WARNING: Orders table may already exist or error occurred</p>";
        echo "<p style='color: blue;'>Info: " . $conn->error . "</p>";
    }
    
    // Create order_items table
    if ($conn->query($order_items_sql)) {
        echo "<p style='color: green;'>SUCCESS: Order_items table created successfully</p>";
    } else {
        echo "<p style='color: orange;'>WARNING: Order_items table may already exist or error occurred</p>";
        echo "<p style='color: blue;'>Info: " . $conn->error . "</p>";
    }
    
    // Verify tables exist
    echo "<h3>Verifying Tables:</h3>";
    
    $tables = ['orders', 'order_items'];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            echo "<p style='color: green;'>Table '$table' exists</p>";
            
            // Show table structure
            echo "<h4>Structure of '$table':</h4>";
            echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
            $structure = $conn->query("DESCRIBE $table");
            while ($row = $structure->fetch_assoc()) {
                echo sprintf("%-20s %-20s %-10s %-10s\n", 
                    $row['Field'], 
                    $row['Type'], 
                    $row['Null'], 
                    $row['Key']
                );
            }
            echo "</pre>";
        } else {
            echo "<p style='color: red;'>Table '$table' does not exist</p>";
        }
    }
    
    // Check if there are any existing orders
    $orders_count = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
    echo "<h3>Current Data:</h3>";
    echo "<p>Total orders in database: $orders_count</p>";
    
    if ($orders_count > 0) {
        $sample_order = $conn->query("SELECT * FROM orders LIMIT 1")->fetch_assoc();
        echo "<h4>Sample Order Data:</h4>";
        echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
        foreach ($sample_order as $key => $value) {
            echo "$key: $value\n";
        }
        echo "</pre>";
    }
    
    echo "<h3>Setup Complete!</h3>";
    echo "<p style='color: blue;'>You can now:</p>";
    echo "<ul>";
    echo "<li>Use the checkout system - orders will be saved to these tables</li>";
    echo "<li>View orders in admin dashboard</li>";
    echo "<li>Manage orders in admin pembelian page</li>";
    echo "</ul>";
    echo "<p><a href='admin/admin_dashboard.php' style='background: #4f46e5; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Admin Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>ERROR: " . $e->getMessage() . "</p>";
}

$conn->close();
?>
