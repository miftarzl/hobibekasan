<?php
session_start();
require_once 'config/config.php';

echo "<h2>Debug Image AI Recommendation</h2>";

// Check if user is logged in
echo "<h3>Session Status:</h3>";
if (isset($_SESSION['user']) && isset($_SESSION['user']['user_id'])) {
    echo "✅ User logged in: " . $_SESSION['user']['user_id'] . " (" . $_SESSION['user']['username'] . ")<br>";
    
    // Get recommendation
    require_once 'ai/random_integration_helper.php';
    $userId = $_SESSION['user']['user_id'];
    $recommendation = getRandomRecommendation($userId);
    
    echo "<h3>Recommendation Result:</h3>";
    echo "<pre>";
    print_r($recommendation);
    echo "</pre>";
    
    if ($recommendation['success']) {
        $product = $recommendation['product'];
        
        echo "<h3>Product Data:</h3>";
        echo "Product ID: " . $product['product_id'] . "<br>";
        echo "Product Name: " . $product['name'] . "<br>";
        echo "Image: " . ($product['image'] ?? 'NULL') . "<br>";
        echo "Price: " . $product['price'] . "<br>";
        echo "Stock: " . $product['stock'] . "<br>";
        
        // Check if image file exists
        $imagePath = '../assets/img/products/' . ($product['image'] ?? 'placeholder.jpg');
        echo "<h3>Image Path Check:</h3>";
        echo "Expected path: $imagePath<br>";
        
        if (file_exists($imagePath)) {
            echo "✅ File exists: $imagePath<br>";
            echo "File size: " . filesize($imagePath) . " bytes<br>";
        } else {
            echo "❌ File NOT found: $imagePath<br>";
            
            // List available images
            echo "<h4>Available Images:</h4>";
            $images = glob('../assets/img/products/*.{jpg,jpeg,png,gif}');
            foreach ($images as $img) {
                echo "- " . basename($img) . "<br>";
            }
        }
        
        // Test displayRecommendationCard function
        echo "<h3>Testing displayRecommendationCard:</h3>";
        $cardHtml = displayRecommendationCard($recommendation);
        
        // Check if image is in the HTML
        if (strpos($cardHtml, $product['image'] ?? '') !== false) {
            echo "✅ Image found in card HTML<br>";
        } else {
            echo "❌ Image NOT found in card HTML<br>";
        }
        
        echo "<h4>Generated Card HTML:</h4>";
        echo "<div style='border: 1px solid #ccc; padding: 10px; max-height: 200px; overflow-y: auto;'>";
        echo $cardHtml;
        echo "</div>";
        
    } else {
        echo "❌ User not logged in<br>";
    }
?>
