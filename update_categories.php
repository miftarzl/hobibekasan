<?php
require 'config/config.php';

echo "=== Update Field Name untuk Kategori yang Ada ===\n";

// Update semua kategori yang ada untuk mengisi field name
$result = $conn->query("SELECT * FROM categories WHERE name IS NULL OR name = ''");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $category_id = $row['category_id'];
        $category_name = $row['category_name'];
        
        $update_query = "UPDATE categories SET name = '$category_name' WHERE category_id = $category_id";
        
        if ($conn->query($update_query)) {
            echo "✅ Kategori ID $category_id ('$category_name') berhasil diupdate\n";
        } else {
            echo "❌ Gagal update kategori ID $category_id: " . $conn->error . "\n";
        }
    }
}

echo "\n=== Data Kategori Setelah Update ===\n";
$result = $conn->query("SELECT * FROM categories ORDER BY category_id");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['category_id'] . 
             " | Name: " . $row['name'] . 
             " | Category Name: " . $row['category_name'] . 
             " | Photo: " . ($row['category_photo'] ?? 'NULL') . "\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

$conn->close();
?>
