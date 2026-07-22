<?php
// Database connection
require 'config/config.php';

echo "<h2>Creating Orders Tables</h2>";

// Read SQL file
$sql_file = 'create_orders_tables.sql';
if (file_exists($sql_file)) {
    $sql = file_get_contents($sql_file);
    
    // Split SQL statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $result = $conn->query($statement);
                if ($result) {
                    echo "<p style='color: green;'>SUCCESS: " . substr($statement, 0, 100) . "...</p>";
                } else {
                    echo "<p style='color: orange;'>WARNING: " . $conn->error . "</p>";
                }
            } catch (Exception $e) {
                echo "<p style='color: red;'>ERROR: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    echo "<h3>Tables Created Successfully!</h3>";
    
    // Verify tables exist
    echo "<h3>Verifying Tables:</h3>";
    
    $tables = ['orders', 'order_items'];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            echo "<p style='color: green;'>Table '$table' exists</p>";
            
            // Show table structure
            echo "<pre>";
            $structure = $conn->query("DESCRIBE $table");
            while ($row = $structure->fetch_assoc()) {
                echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']}\n";
            }
            echo "</pre>";
        } else {
            echo "<p style='color: red;'>Table '$table' does not exist</p>";
        }
    }
    
} else {
    echo "<p style='color: red;'>SQL file not found: $sql_file</p>";
}

$conn->close();
?>
