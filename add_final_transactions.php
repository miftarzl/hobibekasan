<?php
require 'config/config.php';

echo "=== Adding Sample Transactions (Final Fix) ===\n";

try {
    // Sample Transactions dengan user_id yang benar
    $transactions = [
        [9, 450000, 'completed', 'transfer'], // user simon15
        [9, 150000, 'completed', 'cash'],     // user simon15
        [9, 300000, 'pending', 'ewallet'],    // user simon15
        [9, 200000, 'completed', 'transfer'], // user simon15
        [9, 100000, 'processing', 'cash'],    // user simon15
        [9, 350000, 'completed', 'ewallet'],   // user simon15
        [9, 180000, 'pending', 'transfer'],   // user simon15
        [9, 220000, 'completed', 'cash']      // user simon15
    ];
    
    $current_count = $conn->query("SELECT COUNT(*) as total FROM transactions")->fetch_assoc()['total'];
    
    foreach ($transactions as $trans) {
        if ($current_count < 8) {
            $conn->query("INSERT INTO transactions (user_id, total_price, status, payment_method, created_at) VALUES (" . $trans[0] . ", " . $trans[1] . ", '" . $trans[2] . "', '" . $trans[3] . "', NOW())");
            echo "✅ Added transaction - User ID: " . $trans[0] . ", Rp " . number_format($trans[1]) . " (" . $trans[2] . ")\n";
            $current_count++;
        }
    }
    
    echo "\n🎉 Sample transactions added successfully!\n";
    
    // Count final data
    $count_produk = $conn->query("SELECT COUNT(*) as total FROM products")->fetch_assoc()['total'];
    $count_kategori = $conn->query("SELECT COUNT(*) as total FROM categories")->fetch_assoc()['total'];
    $count_transaksi = $conn->query("SELECT COUNT(*) as total FROM transactions")->fetch_assoc()['total'];
    $count_user = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'")->fetch_assoc()['total'];
    $pendapatan = $conn->query("SELECT SUM(total_price) as total FROM transactions WHERE status = 'completed'")->fetch_assoc()['total'];
    
    echo "\n📊 Final Data Summary:\n";
    echo "  📦 Products: $count_produk\n";
    echo "  🏷️ Categories: $count_kategori\n";
    echo "  🛒 Transactions: $count_transaksi\n";
    echo "  👥 Users: $count_user\n";
    echo "  💰 Total Revenue: Rp " . number_format($pendapatan) . "\n";
    
    echo "\n🌐 Dashboard sekarang memiliki data!\n";
    echo "📱 Silakan refresh halaman admin_dashboard.php\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

$conn->close();
?>
