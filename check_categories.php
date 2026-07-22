<?php
require 'config/config.php';

echo "=== Struktur Tabel Categories ===\n";
$result = $conn->query("DESCRIBE categories");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "Field: " . $row['Field'] . 
             " | Type: " . $row['Type'] . 
             " | Null: " . $row['Null'] . 
             " | Key: " . $row['Key'] . "\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

echo "\n=== Data Kategori Saat Ini ===\n";
$result = $conn->query("SELECT * FROM categories ORDER BY category_id");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['category_id'] . 
             " | Name: " . $row['name'] . 
             " | Description: " . ($row['description'] ?? 'NULL') . "\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

$conn->close();
?>
