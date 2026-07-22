<?php
session_start();
require_once 'config/koneksi.php';
require_once 'utils/content_based_filtering.php';

$pageTitle = "Rekomendasi Untuk Anda";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navbar -->
    <?php include("../assets/navbar.php"); ?>

    <div class="container my-5">
        <h1 class="mb-4">Rekomendasi Untuk Anda</h1>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <!-- Rekomendasi berdasarkan riwayat pembelian pengguna -->
            <div class="mb-5">
                <h2 class="h3 mb-4">Berdasarkan Pembelian Anda</h2>
                
                <div class="row">
                    <?php
                    $recommendations = getUserPersonalizedRecommendations($conn, $_SESSION['user_id'], 4);
                    
                    if (empty($recommendations)):
                    ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                Belum ada rekomendasi berdasarkan pembelian. Silakan lakukan pembelian untuk mendapatkan rekomendasi yang lebih personal.
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recommendations as $product): ?>
                            <div class="col-md-3 mb-4">
                                <div class="card h-100">
                                    <a href="produk_detail.php?id=<?= $product['product_id'] ?>">
                                        <img src="uploads/products/<?= $product['image'] ?>" class="card-img-top" alt="<?= $product['name'] ?>">
                                    </a>
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <a href="produk_detail.php?id=<?= $product['product_id'] ?>" class="text-decoration-none text-dark">
                                                <?= $product['name'] ?>
                                            </a>
                                        </h5>
                                        <p class="card-text mb-1">
                                            <span class="badge bg-secondary"><?= $product['category_name'] ?></span>
                                            <span class="badge bg-info"><?= $product['jenis_kelamin'] ?></span>
                                        </p>
                                        <p class="card-text text-danger fw-bold">Rp <?= number_format($product['price'], 0, ',', '.') ?></p>
                                        
                                        <?php if (isset($product['avg_rating'])): ?>
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="ratings me-2">
                                                    <?php
                                                    $rating = $product['avg_rating'] ?? 0;
                                                    for ($i = 1; $i <= 5; $i++) {
                                                        if ($i <= $rating) {
                                                            echo '<i class="fa fa-star text-warning"></i>';
                                                        } elseif ($i - 0.5 <= $rating) {
                                                            echo '<i class="fa fa-star-half-alt text-warning"></i>';
                                                        } else {
                                                            echo '<i class="far fa-star text-warning"></i>';
                                                        }
                                                    }
                                                    ?>
                                                </div>
                                                <span class="small">(<?= $product['review_count'] ?? 0 ?>)</span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="d-grid gap-2">
                                            <a href="produk_detail.php?id=<?= $product['product_id'] ?>" class="btn btn-outline-primary">Lihat Detail</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Produk Terbaru -->
        <div class="mb-5">
            <h2 class="h3 mb-4">Produk Populer</h2>
            
            <div class="row">
                <?php
                // Query untuk mendapatkan produk dengan rating tertinggi
                $sql = "SELECT p.*, c.category_name, 
                               (SELECT ROUND(AVG(rating), 1) FROM product_reviews WHERE product_id = p.product_id) as avg_rating,
                               (SELECT COUNT(*) FROM product_reviews WHERE product_id = p.product_id) as review_count
                        FROM products p
                        JOIN categories c ON p.category_id = c.category_id
                        WHERE (SELECT COUNT(*) FROM product_reviews WHERE product_id = p.product_id) > 0
                        AND p.stock > 0
                        ORDER BY avg_rating DESC, review_count DESC
                        LIMIT 4";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                $popularProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($popularProducts)):
                ?>
                    <div class="col-12">
                        <div class="alert alert-info">
                            Belum ada produk dengan ulasan. Jadilah yang pertama memberikan ulasan!
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($popularProducts as $product): ?>
                        <div class="col-md-3 mb-4">
                            <div class="card h-100">
                                <a href="produk_detail.php?id=<?= $product['product_id'] ?>">
                                    <img src="uploads/products/<?= $product['image'] ?>" class="card-img-top" alt="<?= $product['name'] ?>">
                                </a>
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <a href="produk_detail.php?id=<?= $product['product_id'] ?>" class="text-decoration-none text-dark">
                                            <?= $product['name'] ?>
                                        </a>
                                    </h5>
                                    <p class="card-text mb-1">
                                        <span class="badge bg-secondary"><?= $product['category_name'] ?></span>
                                        <span class="badge bg-info"><?= $product['jenis_kelamin'] ?></span>
                                    </p>
                                    <p class="card-text text-danger fw-bold">Rp <?= number_format($product['price'], 0, ',', '.') ?></p>
                                    
                                    <?php if (isset($product['avg_rating'])): ?>
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="ratings me-2">
                                                <?php
                                                $rating = $product['avg_rating'] ?? 0;
                                                for ($i = 1; $i <= 5; $i++) {
                                                    if ($i <= $rating) {
                                                        echo '<i class="fa fa-star text-warning"></i>';
                                                    } elseif ($i - 0.5 <= $rating) {
                                                        echo '<i class="fa fa-star-half-alt text-warning"></i>';
                                                    } else {
                                                        echo '<i class="far fa-star text-warning"></i>';
                                                    }
                                                }
                                                ?>
                                            </div>
                                            <span class="small">(<?= $product['review_count'] ?? 0 ?>)</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="d-grid gap-2">
                                        <a href="produk_detail.php?id=<?= $product['product_id'] ?>" class="btn btn-outline-primary">Lihat Detail</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Produk Terbaru -->
        <div>
            <h2 class="h3 mb-4">Produk Terbaru</h2>
            
            <div class="row">
                <?php
                $latestProducts = getLatestProducts($conn, 4);
                
                if (empty($latestProducts)):
                ?>
                    <div class="col-12">
                        <div class="alert alert-info">
                            Belum ada produk yang tersedia.
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($latestProducts as $product): ?>
                        <div class="col-md-3 mb-4">
                            <div class="card h-100">
                                <a href="produk_detail.php?id=<?= $product['product_id'] ?>">
                                    <img src="uploads/products/<?= $product['image'] ?>" class="card-img-top" alt="<?= $product['name'] ?>">
                                </a>
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <a href="produk_detail.php?id=<?= $product['product_id'] ?>" class="text-decoration-none text-dark">
                                            <?= $product['name'] ?>
                                        </a>
                                    </h5>
                                    <p class="card-text mb-1">
                                        <span class="badge bg-secondary"><?= $product['category_name'] ?></span>
                                        <span class="badge bg-info"><?= $product['jenis_kelamin'] ?></span>
                                    </p>
                                    <p class="card-text text-danger fw-bold">Rp <?= number_format($product['price'], 0, ',', '.') ?></p>
                                    
                                    <?php if (isset($product['avg_rating'])): ?>
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="ratings me-2">
                                                <?php
                                                $rating = $product['avg_rating'] ?? 0;
                                                for ($i = 1; $i <= 5; $i++) {
                                                    if ($i <= $rating) {
                                                        echo '<i class="fa fa-star text-warning"></i>';
                                                    } elseif ($i - 0.5 <= $rating) {
                                                        echo '<i class="fa fa-star-half-alt text-warning"></i>';
                                                    } else {
                                                        echo '<i class="far fa-star text-warning"></i>';
                                                    }
                                                }
                                                ?>
                                            </div>
                                            <span class="small">(<?= $product['review_count'] ?? 0 ?>)</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="d-grid gap-2">
                                        <a href="produk_detail.php?id=<?= $product['product_id'] ?>" class="btn btn-outline-primary">Lihat Detail</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include '../assets/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>