<?php
require 'config/config.php';

// Update beberapa produk existing dengan ukuran
$products = [
    1 => '36,37,38,39,40,41,42',
    2 => '38,39,40,41,42,43,44',
    3 => '37,38,39,40,41,42',
    4 => '40,41,42,43,44,45',
    5 => '36,37,38,39,40'
];

foreach ($products as $product_id => $sizes) {
    $sql = "UPDATE products SET sizes = ? WHERE product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $sizes, $product_id);
    $stmt->execute();
    echo "Product $product_id updated with sizes: $sizes<br>";
}

echo "<h3>Sample products with sizes have been added!</h3>";
echo "<p><a href='admin/produk.php'>Go to Admin Products</a> | <a href='pengguna/kategori.php'>Go to User Categories</a></p>";

$conn->close();
?>
