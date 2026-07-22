<?php
session_start();
require_once 'config/config.php';

echo "<h2>Final Fix Image Issue</h2>";

// Force refresh session to get new recommendation
if (isset($_SESSION['user']['user_id'])) {
    $userId = $_SESSION['user']['user_id'];
    
    echo "<h3>Current User ID: $userId</h3>";
    
    // Clear any cache
    unset($_SESSION['ai_recommendation_cache']);
    
    // Get fresh recommendation
    require_once 'ai/random_integration_helper.php';
    $recommendation = getRandomRecommendation($userId);
    
    echo "<h3>Debug Info:</h3>";
    echo "<pre>";
    print_r($recommendation);
    echo "</pre>";
    
    if ($recommendation['success']) {
        $product = $recommendation['product'];
        
        echo "<h3>Product Image Debug:</h3>";
        echo "Image file: " . ($product['image'] ?? 'NULL') . "<br>";
        
        // Test all possible paths
        $paths = [
            '../assets/img/products/' . ($product['image'] ?? 'placeholder.jpg'),
            '/hobibekasan/assets/img/products/' . ($product['image'] ?? 'placeholder.jpg'),
            'assets/img/products/' . ($product['image'] ?? 'placeholder.jpg'),
            './assets/img/products/' . ($product['image'] ?? 'placeholder.jpg')
        ];
        
        foreach ($paths as $i => $path) {
            echo "Path $i: $path - ";
            if (file_exists($path)) {
                echo "✅ EXISTS<br>";
            } else {
                echo "❌ NOT FOUND<br>";
            }
        }
        
        // Force display with absolute path
        echo "<h3>Forced Display:</h3>";
        $absolutePath = $_SERVER['DOCUMENT_ROOT'] . '/hobibekasan/assets/img/products/' . ($product['image'] ?? 'placeholder.jpg');
        echo "Absolute path: $absolutePath<br>";
        echo "URL path: /hobibekasan/assets/img/products/" . ($product['image'] ?? 'placeholder.jpg') . "<br>";
        
        // Create simple HTML display
        echo "<h3>Simple Card Display:</h3>";
        ?>
        <div style="border: 2px solid #ccc; padding: 20px; margin: 20px 0; max-width: 400px;">
            <div style="text-align: center; margin-bottom: 15px;">
                <img src="/hobibekasan/assets/img/products/<?php echo $product['image'] ?? 'placeholder.jpg'; ?>" 
                     style="max-width: 200px; max-height: 200px; border: 1px solid #ddd;" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>">
            </div>
            <h4><?php echo htmlspecialchars($product['name']); ?></h4>
            <p><strong>Price:</strong> Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></p>
            <p><strong>Stock:</strong> <?php echo $product['stock']; ?></p>
            <p><strong>Category:</strong> <?php echo htmlspecialchars($product['category_name'] ?? 'Unknown'); ?></p>
        </div>
        
        <?php
    } else {
        echo "<h3>Not logged in</h3>";
    }
?>
