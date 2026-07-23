<?php
/**
 * Random Recommendation Integration Helper
 * Menghubungkan PHP dengan Flask API untuk AI recommendation
 */

// Fungsi untuk mendapatkan rekomendasi random
function getRandomRecommendation($userId) {
    // Cek apakah user sudah memiliki rekomendasi di session
    if (isset($_SESSION['user_recommendation_' . $userId])) {
        return $_SESSION['user_recommendation_' . $userId];
    }
    
    $apiUrl = 'http://localhost:5000/api/random-recommendation/' . $userId;
    $params = ['limit' => 1];
    
    $queryString = http_build_query($params);
    $fullUrl = $apiUrl . '?' . $queryString;
    
    try {
        $context = stream_context_create([
            'http' => [
                'timeout' => 1,
                'method' => 'GET',
                'ignore_errors' => true
            ]
        ]);
        
        // Coba koneksi ke API dengan error handling
        $response = @file_get_contents($fullUrl, false, $context);
        
        if ($response === false) {
            // Log error untuk debugging
            error_log("AI API Connection Failed: " . $fullUrl);
            $recommendation = getFallbackRecommendation($userId);
        } else {
            $data = json_decode($response, true);
            
            if ($data && isset($data['success']) && $data['success']) {
                $recommendation = [
                    'success' => true,
                    'product' => $data['recommendations'][0] ?? null
                ];
            } else {
                $recommendation = getFallbackRecommendation($userId);
            }
        }
        
        // Simpan rekomendasi ke session
        $_SESSION['user_recommendation_' . $userId] = $recommendation;
        
        return $recommendation;
    } catch (Exception $e) {
        $recommendation = getFallbackRecommendation($userId);
        $_SESSION['user_recommendation_' . $userId] = $recommendation;
        return $recommendation;
    }
}

// Fallback jika API offline
function getFallbackRecommendation($userId) {
    global $conn;

    if (!isset($conn) || $conn->connect_error) {
        require_once dirname(__DIR__) . '/config/config.php';
    }

    if (!isset($conn) || $conn->connect_error) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Weighted random selection
    $query = "
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id 
        WHERE p.stock > 0 
        ORDER BY RAND() * p.stock DESC 
        LIMIT 1
    ";
    
    $result = $conn->query($query);
    
    if ($result && $row = $result->fetch_assoc()) {
        return [
            'success' => true,
            'product' => $row,
            'fallback' => true
        ];
    }
    
    return ['success' => false, 'error' => 'No products available'];
}

// Fungsi untuk menampilkan product card HTML
function displayRecommendationCard($recommendation) {
    if (!$recommendation['success'] || !$recommendation['product']) {
        return '';
    }
    
    $product = $recommendation['product'];
    $badge = isset($recommendation['fallback']) ? 
        '<span class="badge bg-secondary position-absolute top-0 start-0 m-2">Random Pick</span>' : 
        '<span class="badge bg-success position-absolute top-0 start-0 m-2">AI Recommended</span>';
    
    // Stock status
    $stockStatus = '';
    $stockBadge = '';
    if ($product['stock'] > 10) {
        $stockStatus = 'Tersedia';
        $stockBadge = 'bg-success';
    } elseif ($product['stock'] > 0) {
        $stockStatus = 'Terbatas';
        $stockBadge = 'bg-warning';
    } else {
        $stockStatus = 'Habis';
        $stockBadge = 'bg-danger';
    }
    
    $image = htmlspecialchars($product['image'] ?? 'foto1.jpg');
    $titleEsc = addslashes(htmlspecialchars($product['name'] ?? ''));
    $priceFmt = number_format($product['price'], 0, ',', '.');
    $categoryEsc = htmlspecialchars($product['category_name'] ?? 'Unknown');
    $prodId = $product['product_id'];
    $stock = $product['stock'];

    return <<<HTML
    <div class="card recommendation-card shadow-sm animate__animated animate__fadeInUp">
        <div class="position-relative overflow-hidden">
            {$badge}
            <div class="product-image-container" style="background: #f8f9fa; min-height: 150px; display: flex; align-items: center; justify-content: center; cursor: pointer;" onclick="openImageModal('/assets/img/products/{$image}', '{$titleEsc}')">
                <img src="/assets/img/products/{$image}" class="card-img-top product-image" alt="{$titleEsc}" style="max-width: 100%; max-height: 150px; object-fit: contain; border: 2px solid #007bff; border-radius: 8px; transition: transform 0.3s ease;" onerror="this.src='/assets/img/logo.jpg'; console.log('Image failed to load, using placeholder');">
            </div>
        </div>
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <h5 class="card-title text-truncate">{$titleEsc}</h5>
                <span class="badge {$stockBadge} text-white">{$stockStatus}</span>
            </div>
            <p class="card-text text-primary fw-bold mb-2">Rp {$priceFmt}</p>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <small class="text-muted">
                    <i class="fas fa-tag me-1"></i>
                    {$categoryEsc}
                </small>
                <small class="text-muted">
                    <i class="fas fa-cube me-1"></i>
                    Stok: {$stock}
                </small>
            </div>
            <div class="d-grid gap-2">
                <a href="produk_detail.php?id={$prodId}" class="btn btn-primary btn-sm">
                    <i class="fas fa-eye me-1"></i>
                    Lihat Detail
                </a>
                <button class="btn btn-outline-primary btn-sm add-to-cart-btn" data-product-id="{$prodId}">
                    <i class="fas fa-shopping-cart me-1"></i>
                    + Keranjang
                </button>
            </div>
        </div>
    </div>

    <!-- Modal untuk popup gambar -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">Detail Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" alt="" style="max-width: 100%; max-height: 180px; object-fit: contain; border-radius: 8px;">
                    <h6 id="modalTitle" class="mt-3"></h6>
                </div>
            </div>
        </div>
    </div>

    <script>
    function openImageModal(imageSrc, title) {
        document.getElementById('modalImage').src = imageSrc;
        document.getElementById('modalTitle').textContent = title;
        new bootstrap.Modal(document.getElementById('imageModal')).show();
    }
    </script>
HTML;
}

// Log AI analytics
function logAIAnalytics($userId, $productId, $eventType, $eventData = []) {
    global $conn;

    if (!isset($conn) || $conn->connect_error) {
        require_once dirname(__DIR__) . '/config/config.php';
    }

    if (!isset($conn) || $conn->connect_error) {
        return false;
    }
    
    $eventDataJson = json_encode(array_merge([
        'user_id' => $userId,
        'product_id' => $productId,
        'timestamp' => date('Y-m-d H:i:s')
    ], $eventData));
    
    $query = "
        INSERT INTO ai_analytics (event_type, user_id, product_id, event_data, success) 
        VALUES (?, ?, ?, ?, 1)
    ";
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param('siisi', $eventType, $userId, $productId, $eventDataJson, 1);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    return false;
}
?>
