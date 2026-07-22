<?php
session_start();
require_once 'config/config.php';

echo "<h2>Test Image Path AI Recommendation</h2>";

// Test 1: Check session
echo "<h3>1. Session Check:</h3>";
if (isset($_SESSION['user']) && isset($_SESSION['user']['user_id'])) {
    echo "✅ User logged in: " . $_SESSION['user']['user_id'] . " (" . $_SESSION['user']['username'] . ")<br>";
    
    // Test 2: Get recommendation
    require_once 'ai/random_integration_helper.php';
    $userId = $_SESSION['user']['user_id'];
    $recommendation = getRandomRecommendation($userId);
    
    if ($recommendation['success']) {
        $product = $recommendation['product'];
        
        echo "<h3>2. Product Data:</h3>";
        echo "Product ID: " . $product['product_id'] . "<br>";
        echo "Product Name: " . $product['name'] . "<br>";
        echo "Image File: " . ($product['image'] ?? 'NULL') . "<br>";
        
        // Test 3: Check all possible image paths
        echo "<h3>3. Image Path Tests:</h3>";
        
        $imagePaths = [
            '../assets/img/products/' . ($product['image'] ?? 'placeholder.jpg'),
            '/hobibekasan/assets/img/products/' . ($product['image'] ?? 'placeholder.jpg'),
            'assets/img/products/' . ($product['image'] ?? 'placeholder.jpg'),
            './assets/img/products/' . ($product['image'] ?? 'placeholder.jpg'),
            'img/products/' . ($product['image'] ?? 'placeholder.jpg')
        ];
        
        foreach ($imagePaths as $i => $path) {
            echo "Path $i: $path - ";
            if (file_exists($path)) {
                echo "✅ EXISTS<br>";
            } else {
                echo "❌ NOT FOUND<br>";
            }
        }
        
        // Test 4: List all files in products folder
        echo "<h3>4. Files in assets/img/products/:</h3>";
        $files = scandir('../assets/img/products');
        if ($files) {
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    echo "- $file<br>";
                }
            }
        }
        
        // Test 5: Test current directory
        echo "<h3>5. Current Directory:</h3>";
        echo "Current dir: " . getcwd() . "<br>";
        echo "Script location: " . __DIR__ . "<br>";
        
    } else {
        echo "❌ User not logged in<br>";
    }
?>
