<?php
require 'config/config.php';

echo "=== Struktur Tabel Products ===\n";
$result = $conn->query("DESCRIBE products");
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

echo "\n=== Data Produk Saat Ini ===\n";
$result = $conn->query("SELECT product_id, name, image, stock FROM products ORDER BY product_id LIMIT 10");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['product_id'] . 
             " | Name: " . $row['name'] . 
             " | Image: " . $row['image'] . 
             " | Stock: " . $row['stock'] . "\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

echo "\n=== Cek File Gambar ===\n";
$products_dir = 'assets/img/products/';
if (is_dir($products_dir)) {
    $files = scandir($products_dir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "File: $file\n";
        }
    }
} else {
    echo "Directory tidak ditemukan: $products_dir\n";
}

$conn->close();
?>
