<?php
session_start();
echo "<h2>Debug AI Recommendations</h2>";

// 1. Cek Login Status
echo "<h3>1. Login Status:</h3>";
$isLoggedIn = isset($_SESSION['user']['user_id']);
echo "Is Logged In: " . ($isLoggedIn ? "YES" : "NO") . "<br>";
if ($isLoggedIn) {
    echo "User ID: " . $_SESSION['user']['user_id'] . "<br>";
    echo "Username: " . ($_SESSION['user']['username'] ?? 'N/A') . "<br>";
}

// 2. Cek AI Server
echo "<h3>2. AI Server Test:</h3>";
$apiUrl = 'http://localhost:5000/api/health';
try {
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'method' => 'GET'
        ]
    ]);
    
    $response = file_get_contents($apiUrl, false, $context);
    if ($response !== false) {
        echo "AI Server: <span style='color: green;'>ONLINE</span><br>";
        echo "Response: " . htmlspecialchars($response) . "<br>";
    } else {
        echo "AI Server: <span style='color: red;'>OFFLINE</span><br>";
    }
} catch (Exception $e) {
    echo "AI Server Error: " . htmlspecialchars($e->getMessage()) . "<br>";
}

// 3. Test AI Recommendations
echo "<h3>3. AI Recommendations Test:</h3>";
if ($isLoggedIn) {
    include 'ai/ai_integration_example.php';
    $userId = $_SESSION['user']['user_id'];
    $recommendations = getAIRecommendations($userId, null, 6);
    
    echo "Recommendations Count: " . count($recommendations) . "<br>";
    
    if (!empty($recommendations)) {
        echo "<h4>Sample Products:</h4>";
        foreach ($recommendations as $i => $product) {
            if ($i >= 3) break;
            echo "- " . htmlspecialchars($product['name'] ?? 'Unknown') . " (Rp " . number_format($product['price'] ?? 0, 0, ',', '.') . ")<br>";
        }
    } else {
        echo "<span style='color: orange;'>No recommendations returned</span><br>";
    }
} else {
    echo "<span style='color: orange;'>User not logged in - cannot test recommendations</span><br>";
}

// 4. Cek Database Connection
echo "<h3>4. Database Connection:</h3>";
try {
    include '../config/config.php';
    $conn = get_db_connection();
    if ($conn) {
        echo "Database: <span style='color: green;'>CONNECTED</span><br>";
        
        // Cek tabel user_interactions
        $result = mysqli_query($conn, "SHOW TABLES LIKE 'user_interactions'");
        echo "user_interactions table: " . (mysqli_num_rows($result) > 0 ? "<span style='color: green;'>EXISTS</span>" : "<span style='color: red;'>MISSING</span>") . "<br>";
        
        mysqli_close($conn);
    } else {
        echo "Database: <span style='color: red;'>FAILED</span><br>";
    }
} catch (Exception $e) {
    echo "Database Error: " . htmlspecialchars($e->getMessage()) . "<br>";
}

echo "<br><a href='pengguna/index.php'>Back to Index</a>";
?>
