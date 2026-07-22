<?php
require 'config/config.php';

echo "=== Menambahkan Produk Sample ===\n";

// Copy gambar yang ada ke folder products jika belum ada
$source_images = [
    'assets/img/foto1.jpg',
    'assets/img/foto2.jpg', 
    'assets/img/foto3.jpg',
    'assets/img/foto4.jpg',
    'assets/img/foto5.jpg',
    'assets/img/foto6.jpg'
];

$target_dir = 'assets/img/products/';
foreach ($source_images as $source) {
    $filename = basename($source);
    $target = $target_dir . $filename;
    
    if (!file_exists($target) && file_exists($source)) {
        copy($source, $target);
        echo "✅ Copy $filename ke folder products\n";
    }
}

// Produk sample
$products = [
    [
        'name' => 'Nike Air Max 90',
        'category_id' => 17, // Sneakers
        'description' => 'Sepatu sneakers classic dengan desain timeless',
        'price' => 850000,
        'stock' => 5,
        'image' => 'foto1.jpg'
    ],
    [
        'name' => 'Adidas Ultraboost 22',
        'category_id' => 17, // Sneakers  
        'description' => 'Sepatu running dengan teknologi boost terbaru',
        'price' => 1200000,
        'stock' => 3,
        'image' => 'foto2.jpg'
    ],
    [
        'name' => 'Converse Chuck 70',
        'category_id' => 18, // Jaket
        'description' => 'Jaket casual yang nyaman dan stylish',
        'price' => 650000,
        'stock' => 8,
        'image' => 'foto3.jpg'
    ],
    [
        'name' => 'Dr. Martens 1460',
        'category_id' => 19, // Boots
        'description' => 'Boots kulit yang tahan lama',
        'price' => 1500000,
        'stock' => 2,
        'image' => 'foto4.jpg'
    ],
    [
        'name' => 'Vans Old Skool',
        'category_id' => 17, // Sneakers
        'description' => 'Skate shoes classic dengan desain iconic',
        'price' => 750000,
        'stock' => 6,
        'image' => 'foto5.jpg'
    ],
    [
        'name' => 'New Balance 550',
        'category_id' => 20, // KidsBoots
        'description' => 'Boots anak dengan desain lucu dan nyaman',
        'price' => 450000,
        'stock' => 4,
        'image' => 'foto6.jpg'
    ]
];

foreach ($products as $product) {
    // Cek apakah produk sudah ada
    $check = $conn->query("SELECT product_id FROM products WHERE name = '" . $product['name'] . "'");
    
    if ($check && $check->num_rows == 0) {
        $query = "INSERT INTO products (name, category_id, description, price, stock, image, created_at) 
                  VALUES ('" . $product['name'] . "', " . $product['category_id'] . ", '" . $product['description'] . "', 
                  " . $product['price'] . ", " . $product['stock'] . ", '" . $product['image'] . "', NOW())";
        
        if ($conn->query($query)) {
            echo "✅ Produk '" . $product['name'] . "' berhasil ditambahkan\n";
        } else {
            echo "❌ Gagal menambahkan produk '" . $product['name'] . "': " . $conn->error . "\n";
        }
    } else {
        echo "⚠️ Produk '" . $product['name'] . "' sudah ada\n";
    }
}

echo "\n=== Data Produk Terbaru ===\n";
$result = $conn->query("SELECT p.*, c.category_name FROM products p 
                        JOIN categories c ON p.category_id = c.category_id 
                        ORDER BY p.product_id DESC LIMIT 10");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['product_id'] . 
             " | Name: " . $row['name'] . 
             " | Category: " . $row['category_name'] . 
             " | Image: " . $row['image'] . 
             " | Price: " . number_format($row['price'], 0, ',', '.') . 
             " | Stock: " . $row['stock'] . "\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

$conn->close();
?>
