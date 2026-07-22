<?php
session_start();
require_once 'config/config.php';

echo "<h2>DIRECT IMAGE FIX</h2>";

// Cek user login
if (isset($_SESSION['user']) && isset($_SESSION['user']['user_id'])) {
    $userId = $_SESSION['user']['user_id'];
    echo "<p><strong>User ID:</strong> $userId</p>";
    
    // Ambil produk random langsung dari database
    $query = "SELECT p.*, c.name as category_name 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.category_id 
              WHERE p.stock > 0 
              ORDER BY RAND() 
              LIMIT 1";
    
    $result = mysqli_query($conn, $query);
    
    if ($result && $row = mysqli_fetch_assoc($result)) {
        echo "<h3>Direct Database Product:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><td><strong>Product ID</strong></td><td>" . $row['product_id'] . "</td></tr>";
        echo "<tr><td><strong>Name</strong></td><td>" . htmlspecialchars($row['name']) . "</td></tr>";
        echo "<tr><td><strong>Image File</strong></td><td>" . ($row['image'] ?? 'NULL') . "</td></tr>";
        echo "<tr><td><strong>Price</strong></td><td>" . $row['price'] . "</td></tr>";
        echo "<tr><td><strong>Stock</strong></td><td>" . $row['stock'] . "</td></tr>";
        echo "</table>";
        
        // Gunakan gambar yang pasti ada
        $availableImages = ['foto1.jpg', 'foto2.jpg', 'foto3.jpg', 'logo.jpg'];
        $randomImage = $availableImages[array_rand($availableImages)];
        
        echo "<h3>Testing Available Images:</h3>";
        foreach ($availableImages as $img) {
            echo "<p><strong>$img:</strong> ";
            if (file_exists("assets/img/$img")) {
                echo "✅ EXISTS<br>";
                echo "<img src='assets/img/$img' style='max-width: 100px; border: 1px solid #ccc; margin: 5px;' alt='Test'>";
            } else {
                echo "❌ NOT FOUND";
            }
            echo "</p>";
        }
        
        // Tampilkan dengan gambar yang pasti ada
        echo "<h3>Fixed Recommendation Card:</h3>";
        ?>
        <div style="border: 2px solid #007bff; border-radius: 15px; padding: 20px; max-width: 400px; background: white; box-shadow: 0 8px 25px rgba(0,123,255,0.2);">
            <div style="text-align: center; margin-bottom: 15px;">
                <img src="assets/img/<?php echo $randomImage; ?>" 
                     style="max-width: 200px; max-height: 200px; border: 2px solid #007bff; border-radius: 8px;" 
                     alt="<?php echo htmlspecialchars($row['name']); ?>">
            </div>
            <h4 style="color: #007bff; margin-bottom: 10px;"><?php echo htmlspecialchars($row['name']); ?></h4>
            <p style="color: #28a745; font-size: 1.2rem; font-weight: bold; margin-bottom: 10px;">Rp <?php echo number_format($row['price'], 0, ',', '.'); ?></p>
            <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                <span style="background: #17a2b8; color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.8rem;">
                    <i class="fas fa-tag"></i> <?php echo htmlspecialchars($row['category_name'] ?? 'Unknown'); ?>
                </span>
                <span style="background: #28a745; color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.8rem;">
                    <i class="fas fa-cube"></i> Stok: <?php echo $row['stock']; ?>
                </span>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="produk_detail.php?id=<?php echo $row['product_id']; ?>" 
                   style="background: #007bff; color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none; flex: 1; text-align: center;">
                    <i class="fas fa-eye"></i> Lihat Detail
                </a>
                <button style="background: transparent; color: #007bff; border: 2px solid #007bff; padding: 6px 14px; border-radius: 8px; flex: 1;">
                    <i class="fas fa-shopping-cart"></i> + Keranjang
                </button>
            </div>
        </div>
        
        <?php
        echo "<h3>Manual Fix Instructions:</h3>";
        echo "<p>Copy this code to ai/random_integration_helper.php line 109:</p>";
        echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>";
        echo htmlspecialchars('src="assets/img/' . $randomImage . '"');
        echo "</pre>";
        echo "<p>Replace the existing src attribute with this working path!</p>";
        
    } else {
        echo "<p style='color: red;'>No products found in database</p>";
    }
    
} else {
    echo "<p style='color: red;'>User not logged in</p>";
}
?>
