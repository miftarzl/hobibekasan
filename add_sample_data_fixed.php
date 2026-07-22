<?php
require 'config/config.php';

echo "=== Adding Sample Data ===\n";

try {
    // Sample Categories (hanya jika belum ada)
    $categories = [
        ['Baju', 'Pakaian bekas berkualitas', 'jaket.png'],
        ['Celana', 'Celana bekas kondisi baik', 'boots.png'],
        ['Sepatu', 'Sepatu bekas original', 'sneakers_new.png'],
        ['Aksesoris', 'Aksesoris fashion bekas', 'kidsboots.png']
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
        ['Nike Air Force 1', 150000, 3, 'Sepatu Nike Air Force 1 preloved', 'Nike Air Force 1.jpg', 15],
        ['Nike Air Jordan 1 High OG', 200000, 3, 'Sepatu Nike Air Jordan 1 High OG preloved', 'Nike Air Jordan 1 High OG.jpeg', 10],
        ['Nike Air Jordan 1 Low OG', 300000, 3, 'Sepatu Nike Air Jordan 1 Low OG preloved', 'Nike Air Jordan 1 Low OG.jpg', 5],
        ['Vans Old Skool', 100000, 4, 'Sepatu Vans Old Skool preloved', 'Vans Old Skool.jpeg', 8],
        ['Hoodie NBA', 250000, 1, 'Hoodie NBA vintage', 'Hoodie NBA.jpeg', 6],
        ['Hoodie Supreme', 80000, 1, 'Hoodie Supreme preloved', 'Hoodie Supreme.jpeg', 20],
        ['Hoodie Cosmic', 120000, 1, 'Hoodie Cosmic limited edition', 'Hoodie Cosmic.jpeg', 12],
        ['Nike Air Jordan 1 Mid', 50000, 3, 'Sepatu Nike Air Jordan 1 Mid preloved', 'Nike Air Jordan 1 Mid.jpg', 25]
    ];
    
    foreach ($products as $product) {
        $check = $conn->query("SELECT COUNT(*) as count FROM products WHERE name = '" . $product[0] . "'");
        if ($check->fetch_assoc()['count'] == 0) {
            $conn->query("INSERT INTO products (name, price, category_id, description, image, stock, created_at) VALUES ('" . $product[0] . "', " . $product[1] . ", " . $product[2] . ", '" . $product[3] . "', '" . $product[4] . "', " . $product[5] . ", NOW())");
            echo "✅ Added product: " . $product[0] . " (Rp " . number_format($product[1]) . ")\n";
        }
    }

    // Sample Users (needed for foreign key on transactions)
    $users = [
        ['user1', 'user1@example.com', 'password123'],
        ['user2', 'user2@example.com', 'password123']
    ];

    foreach ($users as $user) {
        $check = $conn->query("SELECT COUNT(*) as count FROM users WHERE username = '" . $user[0] . "'");
        if ($check->fetch_assoc()['count'] == 0) {
            $hashed = password_hash($user[2], PASSWORD_BCRYPT);
            $conn->query("INSERT INTO users (username, email, password, verification_token, is_verified, role) VALUES ('" . $user[0] . "', '" . $user[1] . "', '" . $hashed . "', '', 1, 'user')");
            echo "✅ Added user: " . $user[0] . "\n";
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
