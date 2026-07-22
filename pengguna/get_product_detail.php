<?php
require '../config/config.php';

header('Content-Type: application/json');

if (isset($_POST['product_id'])) {
    $product_id = intval($_POST['product_id']);
    
    // Query untuk mengambil detail produk dengan join ke kategori
    $query = "SELECT p.*, c.category_name 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.category_id 
              WHERE p.product_id = $product_id";
    
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $product = $result->fetch_assoc();
        
        // Format data untuk response
        $response = [
            'success' => true,
            'product' => [
                'product_id' => $product['product_id'],
                'name' => $product['name'],
                'description' => $product['description'] ?? 'Tidak ada deskripsi tersedia.',
                'price' => $product['price'],
                'stock' => $product['stock'],
                'image' => $product['image'],
                'sizes' => $product['sizes'] ?? '',
                'category_name' => $product['category_name'],
                'category_id' => $product['category_id'],
                'created_at' => $product['created_at']
            ]
        ];
    } else {
        $response = [
            'success' => false,
            'message' => 'Produk tidak ditemukan'
        ];
    }
} else {
    $response = [
        'success' => false,
        'message' => 'Product ID tidak valid'
    ];
}

echo json_encode($response);
$conn->close();
?>
