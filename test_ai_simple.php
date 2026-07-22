<?php
session_start();
require_once 'config/config.php';

echo "<h2>Simple AI Test</h2>";

// Test 1: Check session
echo "<h3>1. Session Check:</h3>";
if (isset($_SESSION['user']) && isset($_SESSION['user']['user_id'])) {
    echo "✅ User logged in: " . $_SESSION['user']['user_id'] . "<br>";
    
    // Test 2: Include helper
    echo "<h3>2. Include Helper:</h3>";
    require_once 'ai/random_integration_helper.php';
    echo "✅ Helper included<br>";
    
    // Test 3: Call function
    echo "<h3>3. Call Function:</h3>";
    $userId = $_SESSION['user']['user_id'];
    echo "Calling getRandomRecommendation($userId)...<br>";
    
    $recommendation = getRandomRecommendation($userId);
    
    echo "<h3>4. Result:</h3>";
    echo "<pre>";
    print_r($recommendation);
    echo "</pre>";
    
    if ($recommendation['success']) {
        echo "<h3>5. Success! Product found:</h3>";
        echo "Product: " . $recommendation['product']['name'] . "<br>";
        echo "Price: Rp " . number_format($recommendation['product']['price'], 0, ',', '.') . "<br>";
        
        // Test 6: Display card
        echo "<h3>6. Card HTML:</h3>";
        $cardHtml = displayRecommendationCard($recommendation);
        echo $cardHtml;
        
    } else {
        echo "<h3>5. Error:</h3>";
        echo "Error: " . ($recommendation['error'] ?? 'Unknown error') . "<br>";
    }
    
} else {
    echo "❌ User not logged in<br>";
    echo "Session data:<br>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
}

// Test 7: Direct API call
echo "<h3>7. Direct API Test:</h3>";
$apiUrl = 'http://localhost:5000/api/random-recommendation/1?limit=1';
echo "Testing: $apiUrl<br>";

$context = stream_context_create([
    'http' => [
        'timeout' => 3,
        'method' => 'GET'
    ]
]);

$response = file_get_contents($apiUrl, false, $context);

if ($response !== false) {
    echo "✅ API Response received<br>";
    $data = json_decode($response, true);
    echo "<pre>";
    print_r($data);
    echo "</pre>";
} else {
    echo "❌ API call failed<br>";
}
?>
