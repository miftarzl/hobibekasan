<?php
session_start();
require_once 'config/config.php';

echo "<h2>DEBUG GAMBAR AI RECOMMENDATION</h2>";

if (isset($_SESSION['user']) && isset($_SESSION['user']['user_id'])) {
    $userId = $_SESSION['user']['user_id'];
    echo "<p><strong>User ID:</strong> $userId</p>";
    
    require_once 'ai/random_integration_helper.php';
    $recommendation = getRandomRecommendation($userId);
    
    echo "<h3>Recommendation Data:</h3>";
    echo "<pre>";
    print_r($recommendation);
    echo "</pre>";
    
    if ($recommendation['success'] && isset($recommendation['product'])) {
        $product = $recommendation['product'];
        
        echo "<h3>Product Details:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><td><strong>Product ID</strong></td><td>" . $product['product_id'] . "</td></tr>";
        echo "<tr><td><strong>Name</strong></td><td>" . htmlspecialchars($product['name']) . "</td></tr>";
        echo "<tr><td><strong>Image File</strong></td><td>" . ($product['image'] ?? 'NULL') . "</td></tr>";
        echo "<tr><td><strong>Price</strong></td><td>" . $product['price'] . "</td></tr>";
        echo "<tr><td><strong>Stock</strong></td><td>" . $product['stock'] . "</td></tr>";
        echo "</table>";
        
        // Test semua kemungkinan path
        $imageFile = $product['image'] ?? 'placeholder.jpg';
        echo "<h3>Image Path Testing:</h3>";
        
        $paths = [
            'Full URL' => "http://localhost/assets/img/products/$imageFile",
            'Relative' => "../assets/img/products/$imageFile",
            'Absolute' => "/assets/img/products/$imageFile",
            'Document Root' => $_SERVER['DOCUMENT_ROOT'] . "/assets/img/products/$imageFile"
        ];
        
        foreach ($paths as $type => $path) {
            echo "<p><strong>$type:</strong> $path<br>";
            if (file_exists($path)) {
                echo "<span style='color: green;'>✅ FILE EXISTS</span><br>";
                echo "<img src='$path' style='max-width: 100px; border: 1px solid #ccc; margin: 5px;' alt='Test'>";
            } else {
                echo "<span style='color: red;'>❌ FILE NOT FOUND</span></p>";
            }
        }
        
        // Test file di folder
        echo "<h3>Files in Products Folder:</h3>";
        $productsFolder = 'assets/img/products/';
        if (is_dir($productsFolder)) {
            $files = scandir($productsFolder);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    echo "<p>" . htmlspecialchars($file) . "</p>";
                }
            }
        } else {
            echo "<p style='color: red;'>Folder not found!</p>";
        }
        
    } else {
        echo "<p style='color: red;'>No recommendation data available</p>";
    }
} else {
    echo "<p style='color: red;'>User not logged in</p>";
}
?>
