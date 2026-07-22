<?php
require 'config/config.php';

echo "=== Database Debug ===\n";

if ($conn) {
    echo "✅ Database connected successfully\n\n";
    
    // Check tables and counts
    $tables = ['products', 'categories', 'transactions', 'users'];
    
    foreach ($tables as $table) {
        echo "📊 Table: $table\n";
        
        // Check if table exists
        $check = $conn->query("SHOW TABLES LIKE '$table'");
        if ($check && $check->num_rows > 0) {
            echo "  ✅ Table exists\n";
            
            // Count records
            $count = $conn->query("SELECT COUNT(*) as total FROM $table");
            if ($count) {
                $row = $count->fetch_assoc();
                echo "  📈 Records: " . $row['total'] . "\n";
            }
            
            // Show sample data for transactions
            if ($table === 'transactions') {
                $sample = $conn->query("SELECT * FROM $table LIMIT 3");
                if ($sample && $sample->num_rows > 0) {
                    echo "  📋 Sample data:\n";
                    while ($row = $sample->fetch_assoc()) {
                        echo "    ID: " . ($row['transaction_id'] ?? 'N/A') . 
                             ", Customer: " . ($row['customer_name'] ?? 'N/A') . 
                             ", Total: " . ($row['total_price'] ?? 'N/A') . 
                             ", Status: " . ($row['status'] ?? 'N/A') . "\n";
                    }
                }
            }
        } else {
            echo "  ❌ Table does NOT exist\n";
        }
        echo "\n";
    }
    
    // Test specific queries
    echo "=== Testing Dashboard Queries ===\n";
    
    try {
        $produk = $conn->query("SELECT COUNT(*) as total FROM products");
        $total_produk = $produk ? $produk->fetch_assoc()['total'] : 0;
        echo "📦 Total Produk: $total_produk\n";
    } catch (Exception $e) {
        echo "❌ Produk Query Error: " . $e->getMessage() . "\n";
    }
    
    try {
        $kategori = $conn->query("SELECT COUNT(*) as total FROM categories");
        $total_kategori = $kategori ? $kategori->fetch_assoc()['total'] : 0;
        echo "🏷️ Total Kategori: $total_kategori\n";
    } catch (Exception $e) {
        echo "❌ Kategori Query Error: " . $e->getMessage() . "\n";
    }
    
    try {
        $transaksi = $conn->query("SELECT COUNT(*) as total FROM transactions");
        $total_transaksi = $transaksi ? $transaksi->fetch_assoc()['total'] : 0;
        echo "🛒 Total Transaksi: $total_transaksi\n";
    } catch (Exception $e) {
        echo "❌ Transaksi Query Error: " . $e->getMessage() . "\n";
    }
    
    try {
        $user = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
        $total_user = $user ? $user->fetch_assoc()['total'] : 0;
        echo "👥 Total User: $total_user\n";
    } catch (Exception $e) {
        echo "❌ User Query Error: " . $e->getMessage() . "\n";
    }
    
    try {
        $pendapatan = $conn->query("SELECT SUM(total_price) as total FROM transactions WHERE status = 'completed'");
        $result = $pendapatan ? $pendapatan->fetch_assoc() : ['total' => 0];
        $total_pendapatan = $result['total'] ?? 0;
        echo "💰 Total Pendapatan: $total_pendapatan\n";
    } catch (Exception $e) {
        echo "❌ Pendapatan Query Error: " . $e->getMessage() . "\n";
    }
    
} else {
    echo "❌ Database connection failed\n";
}

$conn->close();
?>
