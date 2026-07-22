<?php
require 'config/config.php';

echo "=== Menambahkan Kategori Sample ===\n";

// Kategori sample dengan nama file yang realistis
$categories = [
    ['Sepatu Pria', 'shoes-men.jpg'],
    ['Sepatu Wanita', 'shoes-women.jpg'],
    ['Sneakers', 'sneakers.jpg'],
    ['Boots', 'boots.jpg'],
    ['Sandal', 'sandals.jpg'],
    ['Flat Shoes', 'flat-shoes.jpg'],
    ['Heels', 'heels.jpg'],
    ['Sport Shoes', 'sport-shoes.jpg']
];

foreach ($categories as $category) {
    $category_name = $category[0];
    $category_photo = $category[1];
    
    // Cek apakah kategori sudah ada
    $check = $conn->query("SELECT category_id FROM categories WHERE category_name = '$category_name'");
    
    if ($check && $check->num_rows == 0) {
        $query = "INSERT INTO categories (category_name, category_photo) VALUES ('$category_name', '$category_photo')";
        if ($conn->query($query)) {
            echo "✅ Kategori '$category_name' berhasil ditambahkan\n";
        } else {
            echo "❌ Gagal menambahkan kategori '$category_name': " . $conn->error . "\n";
        }
    } else {
        echo "⚠️ Kategori '$category_name' sudah ada\n";
    }
}

echo "\n=== Data Kategori Terbaru ===\n";
$result = $conn->query("SELECT * FROM categories ORDER BY category_id");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['category_id'] . 
             " | Name: " . $row['category_name'] . 
             " | Photo: " . ($row['category_photo'] ?? 'NULL') . "\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

$conn->close();
?>
