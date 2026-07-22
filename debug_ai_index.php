<?php
session_start();
require_once 'config/config.php';

echo "<h2>AI Recommendation Debug</h2>";

// Check session
echo "<h3>Session Check:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if (isset($_SESSION['user']['user_id'])) {
    echo "<h3>User ID: " . $_SESSION['user']['user_id'] . "</h3>";
    
    // Test helper function
    require_once 'ai/random_integration_helper.php';
    
    echo "<h3>Testing getRandomRecommendation function:</h3>";
    
    $userId = $_SESSION['user']['user_id'];
    echo "User ID: $userId<br>";
    
    // Test API call
    $recommendation = getRandomRecommendation($userId);
    
    echo "<h3>Recommendation Result:</h3>";
    echo "<pre>";
    print_r($recommendation);
    echo "</pre>";
    
    if ($recommendation['success']) {
        echo "<h3>Product Card HTML:</h3>";
        echo displayRecommendationCard($recommendation);
    } else {
        echo "<h3>Error:</h3>";
        echo $recommendation['error'] ?? 'Unknown error';
    }
    
} else {
    echo "<h3>Not logged in!</h3>";
}

// Test API directly
echo "<h3>Direct API Test:</h3>";
$apiUrl = 'http://localhost:5000/api/random-recommendation/1?limit=1';
echo "Testing URL: $apiUrl<br>";

$context = stream_context_create([
    'http' => [
        'timeout' => 3,
        'method' => 'GET'
    ]
]);

$response = file_get_contents($apiUrl, false, $context);

if ($response !== false) {
    echo "API Response: " . htmlspecialchars($response) . "<br>";
    
    $data = json_decode($response, true);
    echo "<pre>";
    print_r($data);
    echo "</pre>";
} else {
    echo "API call failed!";
}
?>
