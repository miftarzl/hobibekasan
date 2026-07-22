<?php
require 'config/config.php';

echo "=== Checking Table Structures ===\n";

// Check products table structure
echo "📦 Products Table Structure:\n";
$result = $conn->query("DESCRIBE products");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
}

echo "\n🏷️ Categories Table Structure:\n";
$result = $conn->query("DESCRIBE categories");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
}

echo "\n🛒 Transactions Table Structure:\n";
$result = $conn->query("DESCRIBE transactions");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
}

$conn->close();
?>
