<?php
require 'config/config.php';

echo "=== Adding Sample Data ===\n";

try {
    // Sample Categories (hanya jika belum ada)
    $categories = [
        ['Baju', 'Pakaian bekas berkualitas'],
        ['Celana', 'Celana bekas kondisi baik'],
        ['Sepatu', 'Sepatu bekas original'],
        ['Aksesoris', 'Aksesoris fashion bekas']
    ];
    
    foreach ($categories as $cat) {
        $check = $conn->query("SELECT COUNT(*) as count FROM categories WHERE category_name = '" . $cat[0] . "'");
        if ($check->fetch_assoc()['count'] == 0) {
            $conn->query("INSERT INTO categories (category_name, name, category_photo) VALUES ('" . $cat[0] . "', '" . $cat[1] . "', 'category.jpg')");
            echo "✅ Added category: " . $cat[0] . "\n";
        }
    }
    
    // Sample Products
    $products = [
        ['Kemeja Flanel', 150000, 1, 'Kemeja flanel bekas impor', 'flanel.jpg', 15],
        ['Jeans Levis', 200000, 2, 'Jeans original bekas', 'jeans.jpg', 10],
        ['Sepatu Nike', 300000, 3, 'Sepatu Nike bekas 90%', 'nike.jpg', 5],
        ['Tas Vintage', 100000, 4, 'Tas kulit vintage', 'tas.jpg', 8],
        ['Jaket Kulit', 250000, 1, 'Jaket kulit asli', 'jaket.jpg', 6],
        ['Kaos Polo', 80000, 1, 'Kaos polo original', 'polo.jpg', 20],
        ['Sweater', 120000, 1, 'Sweater wool import', 'sweater.jpg', 12],
        ['Topi', 50000, 4, 'Topi baseball vintage', 'topi.jpg', 25]
    ];
    
    foreach ($products as $product) {
        $check = $conn->query("SELECT COUNT(*) as count FROM products WHERE name = '" . $product[0] . "'");
        if ($check->fetch_assoc()['count'] == 0) {
            $conn->query("INSERT INTO products (name, price, category_id, description, image, stock, created_at) VALUES ('" . $product[0] . "', " . $product[1] . ", " . $product[2] . ", '" . $product[3] . "', '" . $product[4] . "', " . $product[5] . ", NOW())");
            echo "✅ Added product: " . $product[0] . " (Rp " . number_format($product[1]) . ")\n";
        }
    }
    
    // Sample Transactions
    $transactions = [
        ['Budi Santoso', 450000, 'completed', 'transfer'],
        ['Siti Nurhaliza', 150000, 'completed', 'cash'],
        ['Ahmad Fadli', 300000, 'pending', 'ewallet'],
        ['Dewi Lestari', 200000, 'completed', 'transfer'],
        ['Rizki Ahmad', 100000, 'processing', 'cash'],
        ['Maya Putri', 350000, 'completed', 'ewallet'],
        ['Doni Prasetyo', 180000, 'pending', 'transfer'],
        ['Lina Marlina', 220000, 'completed', 'cash']
    ];
    
    foreach ($transactions as $trans) {
        $user_id = rand(1, 2); // Random user ID
        $conn->query("INSERT INTO transactions (user_id, total_price, status, payment_method, created_at) VALUES ($user_id, " . $trans[1] . ", '" . $trans[2] . "', '" . $trans[3] . "', NOW())");
        echo "✅ Added transaction: " . $trans[0] . " - Rp " . number_format($trans[1]) . " (" . $trans[2] . ")\n";
    }
    
    echo "\n🎉 Sample data added successfully!\n";
    echo "📊 Summary:\n";
    
    // Count final data
    $count_produk = $conn->query("SELECT COUNT(*) as total FROM products")->fetch_assoc()['total'];
    $count_kategori = $conn->query("SELECT COUNT(*) as total FROM categories")->fetch_assoc()['total'];
    $count_transaksi = $conn->query("SELECT COUNT(*) as total FROM transactions")->fetch_assoc()['total'];
    $count_user = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'")->fetch_assoc()['total'];
    
    echo "  📦 Products: $count_produk\n";
    echo "  🏷️ Categories: $count_kategori\n";
    echo "  🛒 Transactions: $count_transaksi\n";
    echo "  👥 Users: $count_user\n";
    
    echo "\n🌐 Silakan refresh dashboard untuk melihat data!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

$conn->close();
?>
