<?php
echo "<h2>🔍 AI API Status Check</h2>";

// Test koneksi ke API
$apiUrl = 'http://localhost:5000/api/health';
$context = stream_context_create([
    'http' => [
        'timeout' => 2,
        'method' => 'GET',
        'ignore_errors' => true
    ]
]);

echo "<h3>Testing API Connection...</h3>";
$response = @file_get_contents($apiUrl, false, $context);

if ($response === false) {
    echo "<p style='color: red; font-size: 18px;'>❌ AI API Server is OFFLINE</p>";
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h4>🔧 How to Start AI Server:</h4>";
    echo "<ol>";
    echo "<li><strong>Open Command Prompt/Terminal</strong></li>";
    echo "<li><strong>Navigate to project folder:</strong> cd c:\\xampp\\htdocs\\hobibekasan</li>";
    echo "<li><strong>Start AI Server:</strong> python ai\\random_recommendation_api.py</li>";
    echo "<li><strong>Or use our helper:</strong> python start_ai_server.py</li>";
    echo "</ol>";
    echo "<p style='color: #28a745;'><strong>✅ Alternative:</strong> The system will automatically use fallback recommendation if API is offline</p>";
    echo "</div>";
} else {
    echo "<p style='color: green; font-size: 18px;'>✅ AI API Server is ONLINE</p>";
    
    $data = json_decode($response, true);
    if ($data) {
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
        echo "<h4>📡 API Response:</h4>";
        echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
        echo "</div>";
    }
}

echo "<h3>🔄 Testing Recommendation Endpoint...</h3>";
$testUrl = 'http://localhost:5000/api/random-recommendation/9?limit=1';
$testResponse = @file_get_contents($testUrl, false, $context);

if ($testResponse === false) {
    echo "<p style='color: red;'>❌ Recommendation endpoint failed</p>";
} else {
    echo "<p style='color: green;'>✅ Recommendation endpoint working</p>";
    $testData = json_decode($testResponse, true);
    if ($testData && isset($testData['success'])) {
        echo "<p style='color: blue;'>📦 Sample recommendation: " . ($testData['recommendations'][0]['name'] ?? 'N/A') . "</p>";
    }
}

echo "<div style='margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px;'>";
echo "<h4>🎯 Next Steps:</h4>";
echo "<ol>";
echo "<li><strong>If API is offline:</strong> Start the AI server using the commands above</li>";
echo "<li><strong>If API is online:</strong> Refresh the main page to see recommendations</li>";
echo "<li><strong>Check logs:</strong> Look at XAMPP Apache and MySQL logs for errors</li>";
echo "</ol>";
echo "</div>";
?>
