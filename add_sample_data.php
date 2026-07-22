<?php
require 'config/config.php';

echo "=== Adding Sample Data ===\n";

try {
    // Sample Categories
    $categories = [
        ['Baju', 'Pakaian bekas berkualitas'],
        ['Celana', 'Celana bekas kondisi baik'],
        ['Sepatu', 'Sepatu bekas original'],
        ['Aksesoris', 'Aksesoris fashion bekas']
    ];
    
    foreach ($categories as $cat) {
        $check = $conn->query("SELECT COUNT(*) as count FROM categories WHERE category_name = '" . $cat[0] . "'");
        if ($check->fetch_assoc()['count'] == 0) {
            $conn->query("INSERT INTO categories (category_name, description) VALUES ('" . $cat[0] . "', '" . $cat[1] . "')");
            echo "✅ Added category: " . $cat[0] . "\n";
        }
    }
    
    // Sample Products
    $products = [
        ['Kemeja Flanel', 150000, 1, 'Kemeja flanel bekas impor', 'flanel.jpg'],
        ['Jeans Levis', 200000, 2, 'Jeans original bekas', 'jeans.jpg'],
        ['Sepatu Nike', 300000, 3, 'Sepatu Nike bekas 90%', 'nike.jpg'],
        ['Tas Vintage', 100000, 4, 'Tas kulit vintage', 'tas.jpg'],
        ['Jaket Kulit', 250000, 1, 'Jaket kulit asli', 'jaket.jpg']
    ];
    
    foreach ($products as $product) {
        $check = $conn->query("SELECT COUNT(*) as count FROM products WHERE name = '" . $product[0] . "'");
        if ($check->fetch_assoc()['count'] == 0) {
            $conn->query("INSERT INTO products (name, price, category_id, description, image, stock, created_at) VALUES ('" . $product[0] . "', " . $product[1] . ", " . $product[2] . ", '" . $product[3] . "', '" . $product[4] . "', 10, NOW())");
            echo "✅ Added product: " . $product[0] . "\n";
        }
    }
    
    // Sample Transactions
    $transactions = [
        ['Budi Santoso', 450000, 'completed'],
        ['Siti Nurhaliza', 150000, 'completed'],
        ['Ahmad Fadli', 300000, 'pending'],
        ['Dewi Lestari', 200000, 'completed'],
        ['Rizki Ahmad', 100000, 'processing']
    ];
    
    foreach ($transactions as $trans) {
        $user_id = rand(1, 2); // Random user ID
        $conn->query("INSERT INTO transactions (user_id, customer_name, total_price, status, created_at) VALUES ($user_id, '" . $trans[0] . "', " . $trans[1] . ", '" . $trans[2] . "', NOW())");
        echo "✅ Added transaction: " . $trans[0] . " - Rp " . number_format($trans[1]) . "\n";
    }
    
    echo "\n🎉 Sample data added successfully!\n";
    echo "Silakan refresh dashboard untuk melihat data.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

$conn->close();
?>
