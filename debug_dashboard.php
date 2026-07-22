<?php
require '../config/config.php';

echo "=== Dashboard Debug Test ===\n";

try {
    // Test semua query dashboard
    echo "1. Total Produk:\n";
    $produk_query = $conn->query("SELECT COUNT(*) AS total FROM products");
    if ($produk_query) {
        $result = $produk_query->fetch_assoc();
        echo "   Query berhasil: " . $result['total'] . " produk\n";
    } else {
        echo "   Query GAGAL!\n";
    }
    
    echo "\n2. Total Kategori:\n";
    $kategori_query = $conn->query("SELECT COUNT(*) AS total FROM categories");
    if ($kategori_query) {
        $result = $kategori_query->fetch_assoc();
        echo "   Query berhasil: " . $result['total'] . " kategori\n";
    } else {
        echo "   Query GAGAL!\n";
    }
    
    echo "\n3. Total Transaksi:\n";
    $transaksi_query = $conn->query("SELECT COUNT(*) AS total FROM transactions");
    if ($transaksi_query) {
        $result = $transaksi_query->fetch_assoc();
        echo "   Query berhasil: " . $result['total'] . " transaksi\n";
    } else {
        echo "   Query GAGAL!\n";
    }
    
    echo "\n4. Total User:\n";
    $user_query = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'user'");
    if ($user_query) {
        $result = $user_query->fetch_assoc();
        echo "   Query berhasil: " . $result['total'] . " user\n";
    } else {
        echo "   Query GAGAL!\n";
    }
    
    echo "\n5. Total Pendapatan:\n";
    $pendapatan_query = $conn->query("SELECT SUM(total_price) AS total FROM transactions WHERE status = 'completed'");
    if ($pendapatan_query) {
        $result = $pendapatan_query->fetch_assoc();
        echo "   Query berhasil: " . $result['total'] . " pendapatan\n";
    } else {
        echo "   Query GAGAL!\n";
    }
    
    echo "\n6. Grafik Data:\n";
    $grafik_query = $conn->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as bulan,
            COUNT(*) as total_transaksi,
            SUM(total_price) as total_pendapatan
        FROM transactions 
        WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY bulan ASC
    ");
    if ($grafik_query) {
        echo "   Query grafik berhasil\n";
        $count = $grafik_query->num_rows;
        echo "   Jumlah data: $count bulan\n";
    } else {
        echo "   Query grafik GAGAL!\n";
    }
    
    echo "\n7. Transaksi Terbaru:\n";
    $transaksi_terbaru = $conn->query("
        SELECT t.transaction_id, t.total_price, t.created_at, t.status, u.username
        FROM transactions t
        LEFT JOIN users u ON t.user_id = u.id
        ORDER BY t.created_at DESC 
        LIMIT 10
    ");
    if ($transaksi_terbaru) {
        echo "   Query transaksi terbaru berhasil\n";
        $count = $transaksi_terbaru->num_rows;
        echo "   Jumlah data: $count transaksi\n";
        if ($count > 0) {
            $row = $transaksi_terbaru->fetch_assoc();
            echo "   Sample: ID " . $row['transaction_id'] . ", User: " . $row['username'] . ", Total: " . $row['total_price'] . "\n";
        }
    } else {
        echo "   Query transaksi terbaru GAGAL!\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

$conn->close();
?>
