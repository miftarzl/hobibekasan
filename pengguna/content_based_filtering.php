<?php
/**
 * Content-Based Filtering dengan Sistem Bobot
 * Menggunakan bobot berdasarkan jenis kelamin, harga, warna, dan kategori
 */

/**
 * Konfigurasi bobot untuk setiap atribut
 */
const WEIGHT_CONFIG = [
    'warna' => 0.565,           // Warna memiliki bobot tertinggi
    'harga' => 0.262,           // Disusul oleh harga
    'category_id' => 0.118,     // Kategori
];

/**
 * Mendapatkan rekomendasi produk berdasarkan sistem bobot
 * 
 * @param mysqli $conn Koneksi database
 * @param int $product_id ID produk yang sedang dilihat
 * @param int $limit Jumlah rekomendasi yang ingin ditampilkan
 * @return array Array produk yang direkomendasikan dengan skor
 */
function getWeightedProductRecommendations($conn, $product_id, $limit = 4) {
    // Ambil produk yang sedang dilihat
    $current_product = getProductById($conn, $product_id);
    
    if (!$current_product) {
        return [];
    }
    
    // Ambil semua produk lain yang masih ada stoknya
    $sql = "SELECT p.*, c.category_name,
            (SELECT ROUND(AVG(rating), 1) FROM product_reviews WHERE product_id = p.product_id) as avg_rating,
            (SELECT COUNT(*) FROM product_reviews WHERE product_id = p.product_id) as review_count
            FROM products p
            JOIN categories c ON p.category_id = c.category_id
            WHERE p.product_id != ? 
            AND p.stock > 0
            ORDER BY p.product_id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $all_products = $result->fetch_all(MYSQLI_ASSOC);
    
    // Hitung skor untuk setiap produk
    $scored_products = [];
    
    foreach ($all_products as $product) {
        $score = calculateSimilarityScore($current_product, $product);
        
        if ($score > 0) { // Hanya ambil produk yang memiliki kesamaan
            $product['similarity_score'] = $score;
            $scored_products[] = $product;
        }
    }
    
    // Urutkan berdasarkan skor tertinggi
    usort($scored_products, function($a, $b) {
        if ($a['similarity_score'] == $b['similarity_score']) {
            // Jika skor sama, prioritaskan yang lebih baru
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        }
        return $b['similarity_score'] <=> $a['similarity_score'];
    });
    
    // Ambil sesuai limit
    return array_slice($scored_products, 0, $limit);
}

/**
 * Menghitung skor kesamaan antara dua produk berdasarkan bobot
 * 
 * @param array $product1 Produk referensi
 * @param array $product2 Produk yang dibandingkan
 * @return float Skor kesamaan (0-1)
 */
function calculateSimilarityScore($product1, $product2) {
    $total_score = 0;

    $category_score = calculateCategorySimilarity($product1, $product2);
    $total_score += $category_score * WEIGHT_CONFIG['category_id'];

    $price_score = calculatePriceSimilarity($product1, $product2);
    $total_score += $price_score * WEIGHT_CONFIG['harga'];

    $color_score = calculateColorSimilarity($product1, $product2);
    $total_score += $color_score * WEIGHT_CONFIG['warna'];

    return round($total_score, 3);
}

/**
 * Menghitung kesamaan jenis kelamin
 * 
 * @param array $product1
 * @param array $product2
 * @return float Skor 0 atau 1
 */

/**
 * Menghitung kesamaan kategori
 * 
 * @param array $product1
 * @param array $product2
 * @return float Skor 0 atau 1
 */
function calculateCategorySimilarity($product1, $product2) {
    return ($product1['category_id'] === $product2['category_id']) ? 1.0 : 0.0;
}

/**
 * Menghitung kesamaan harga berdasarkan rentang
 * 
 * @param array $product1
 * @param array $product2
 * @return float Skor 0-1
 */
function calculatePriceSimilarity($product1, $product2) {
    $price1 = (float)$product1['price'];
    $price2 = (float)$product2['price'];
    
    // Hitung selisih persentase harga
    $max_price = max($price1, $price2);
    $min_price = min($price1, $price2);
    
    if ($max_price == 0) return 0;
    
    $price_diff_percentage = ($max_price - $min_price) / $max_price;
    
    // Konversi ke skor kesamaan (semakin kecil selisih, semakin tinggi skor)
    // Jika selisih <= 10%, skor = 1.0
    // Jika selisih >= 50%, skor = 0.0
    if ($price_diff_percentage <= 0.1) {
        return 1.0;
    } elseif ($price_diff_percentage >= 0.5) {
        return 0.0;
    } else {
        // Linear interpolation between 10% and 50%
        return 1.0 - (($price_diff_percentage - 0.1) / 0.4);
    }
}

/**
 * Menghitung kesamaan warna
 * 
 * @param array $product1
 * @param array $product2
 * @return float Skor 0 atau 1
 */
function calculateColorSimilarity($product1, $product2) {
    // Validasi array key untuk warna
    $color1 = isset($product1['warna']) ? strtolower(trim($product1['warna'])) : '';
    $color2 = isset($product2['warna']) ? strtolower(trim($product2['warna'])) : '';
    
    return ($color1 === $color2) ? 1.0 : 0.0;
}

/**
 * Mengambil detail produk berdasarkan ID
 * 
 * @param mysqli $conn Koneksi database
 * @param int $product_id ID produk
 * @return array|bool Detail produk atau false jika tidak ditemukan
 */
function getProductById($conn, $product_id) {
    $sql = "SELECT p.*, c.category_name 
            FROM products p
            JOIN categories c ON p.category_id = c.category_id
            WHERE p.product_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return false;
}

/**
 * Fungsi untuk menampilkan rekomendasi dengan detail skor
 * 
 * @param mysqli $conn Koneksi database
 * @param int $product_id ID produk yang sedang dilihat
 * @param int $limit Jumlah rekomendasi
 * @return array Array dengan detail skor
 */
function getDetailedRecommendations($conn, $product_id, $limit = 4) {
    $current_product = getProductById($conn, $product_id);
    
    if (!$current_product) {
        return [];
    }
    
    // Ambil semua produk lain
    $sql = "SELECT p.*, c.category_name,
            (SELECT ROUND(AVG(rating), 1) FROM product_reviews WHERE product_id = p.product_id) as avg_rating,
            (SELECT COUNT(*) FROM product_reviews WHERE product_id = p.product_id) as review_count
            FROM products p
            JOIN categories c ON p.category_id = c.category_id
            WHERE p.product_id != ? 
            AND p.stock > 0";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $all_products = $result->fetch_all(MYSQLI_ASSOC);
    
    $detailed_recommendations = [];
    
    foreach ($all_products as $product) {
        // Hitung skor detail untuk setiap atribut
        $gender_score = calculateGenderSimilarity($current_product, $product);
        $category_score = calculateCategorySimilarity($current_product, $product);
        $price_score = calculatePriceSimilarity($current_product, $product);
        $color_score = calculateColorSimilarity($current_product, $product);
        
        $total_score = (
            $category_score * WEIGHT_CONFIG['category_id'] +
            $price_score * WEIGHT_CONFIG['harga'] +
            $color_score * WEIGHT_CONFIG['warna']
        );
        
        if ($total_score > 0) {
            $product['similarity_details'] = [
                'total_score' => round($total_score, 3),
                'gender_score' => $gender_score,
                'category_score' => $category_score,
                'price_score' => round($price_score, 3),
                'color_score' => $color_score,
                'weighted_scores' => [
                    'category' => round($category_score * WEIGHT_CONFIG['category_id'], 3),
                    'price' => round($price_score * WEIGHT_CONFIG['harga'], 3),
                    'color' => round($color_score * WEIGHT_CONFIG['warna'], 3)
                ]
            ];
            
            $detailed_recommendations[] = $product;
        }
    }
    
    // Urutkan berdasarkan skor total
    usort($detailed_recommendations, function($a, $b) {
        return $b['similarity_details']['total_score'] <=> $a['similarity_details']['total_score'];
    });
    
    return array_slice($detailed_recommendations, 0, $limit);
}

/**
 * Fungsi untuk mengupdate konfigurasi bobot
 * 
 * @param array $new_weights Array bobot baru
 * @return bool True jika berhasil
 */
function updateWeightConfig($new_weights) {
    // Validasi bahwa total bobot = 1.0
    $total_weight = array_sum($new_weights);
    
    if (abs($total_weight - 1.0) > 0.001) {
        throw new Exception("Total bobot harus sama dengan 1.0, saat ini: " . $total_weight);
    }
    
    // Update konstanta (dalam implementasi nyata, simpan di database atau file config)
    // Untuk demo, kita return true
    return true;
}

/**
 * Contoh penggunaan sistem rekomendasi
 */
function demonstrateRecommendationSystem($conn, $product_id) {
    echo "<h2>Sistem Rekomendasi Produk dengan Bobot</h2>";
    
    // Ambil produk yang sedang dilihat
    $current_product = getProductById($conn, $product_id);
    
    if (!$current_product) {
        echo "Produk tidak ditemukan!";
        return;
    }
    
    echo "<h3>Produk yang sedang dilihat:</h3>";
    echo "<p><strong>{$current_product['name']}</strong> - Rp " . number_format($current_product['price']) . "</p>";
    echo "<p>Kategori: {$current_product['category_name']}, Warna: {$current_product['warna']}</p>";
    
    // Ambil rekomendasi detail
    $recommendations = getDetailedRecommendations($conn, $product_id, 5);
    
    echo "<h3>Rekomendasi Produk:</h3>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr>
            <th>Produk</th>
            <th>Harga</th>
            <th>Kategori</th>
            <th>Warna</th>
            <th>Skor Total</th>
            <th>Detail Skor</th>
          </tr>";
    
    foreach ($recommendations as $product) {
        $details = $product['similarity_details'];
        echo "<tr>";
        echo "<td>{$product['name']}</td>";
        echo "<td>Rp " . number_format($product['price']) . "</td>";
        echo "<td>{$product['category_name']}</td>";
        echo "<td>{$product['warna']}</td>";
        echo "<td><strong>{$details['total_score']}</strong></td>";
        echo "<td>
                G: {$details['weighted_scores']['gender']}<br>
                C: {$details['weighted_scores']['category']}<br>
                P: {$details['weighted_scores']['price']}<br>
                W: {$details['weighted_scores']['color']}
              </td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<h4>Keterangan Bobot:</h4>";
    echo "<ul>";
    echo "<li>Ukuran (G): " . (WEIGHT_CONFIG['Ukuran'] * 100) . "%</li>";
    echo "<li>Warna (C): " . (WEIGHT_CONFIG['warna'] * 100) . "%</li>";
    echo "<li>kategori (P): " . (WEIGHT_CONFIG['kategori'] * 100) . "%</li>";
    echo "<li>harga (W): " . (WEIGHT_CONFIG['harga'] * 100) . "%</li>";
    echo "</ul>";
}

// Contoh penggunaan untuk API atau AJAX
function getRecommendationsJSON($conn, $product_id, $limit = 4) {
    $recommendations = getWeightedProductRecommendations($conn, $product_id, $limit);
    
    return json_encode([
        'status' => 'success',
        'data' => $recommendations,
        'total' => count($recommendations)
    ]);
}

?>