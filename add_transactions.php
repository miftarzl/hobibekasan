<?php
require 'config/config.php';

echo "=== Adding Sample Transactions (Fixed) ===\n";

try {
    // Sample Transactions (hanya jika belum ada data)
    $transactions = [
        [1, 450000, 'completed', 'transfer'],
        [2, 150000, 'completed', 'cash'],
        [1, 300000, 'pending', 'ewallet'],
        [2, 200000, 'completed', 'transfer'],
        [1, 100000, 'processing', 'cash'],
        [2, 350000, 'completed', 'ewallet'],
        [1, 180000, 'pending', 'transfer'],
        [2, 220000, 'completed', 'cash']
    ];
    
    foreach ($transactions as $trans) {
        // Cek apakah sudah ada transaksi
        $check = $conn->query("SELECT COUNT(*) as count FROM transactions");
        if ($check->fetch_assoc()['count'] < 8) {
            $conn->query("INSERT INTO transactions (user_id, total_price, status, payment_method, created_at) VALUES (" . $trans[0] . ", " . $trans[1] . ", '" . $trans[2] . "', '" . $trans[3] . "', NOW())");
            echo "✅ Added transaction - Rp " . number_format($trans[1]) . " (" . $trans[2] . ")\n";
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
    
    echo "\n🌐 Dashboard sekarang siap digunakan!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

$conn->close();
?>
