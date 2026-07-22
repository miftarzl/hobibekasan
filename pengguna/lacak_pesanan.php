<?php
session_start();

// Cek apakah user login atau belum
if (!isset($_SESSION['user']['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
include '../config/config.php';

// Ambil user ID
$user_id = $_SESSION['user']['user_id'];

// Ambil order ID dari parameter atau input
$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : '';

// Handle form submission
$order_data = null;
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['order_number'])) {
    $order_number = $_POST['order_number'];
    
    // Cari order berdasarkan order_number dan user_id
    $query = "SELECT o.*, u.username, u.email 
              FROM orders o 
              JOIN users u ON o.user_id = u.id 
              WHERE o.order_number = ? AND o.user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $order_number, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $order_data = $result->fetch_assoc();
        $order_id = $order_data['id'];
    } else {
        $error_message = 'Order tidak ditemukan atau bukan milik Anda';
    }
} elseif (!empty($order_id)) {
    // Jika order_id diberikan dari URL
    $query = "SELECT o.*, u.username, u.email 
              FROM orders o 
              JOIN users u ON o.user_id = u.id 
              WHERE o.id = ? AND o.user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $order_data = $result->fetch_assoc();
    } else {
        $error_message = 'Order tidak ditemukan atau bukan milik Anda';
    }
}

// Function untuk mendapatkan koordinat dari alamat (simulasi)
function getCoordinatesFromAddress($address) {
    // Koordinat default (Jakarta)
    $coordinates = ['lat' => -6.2088, 'lng' => 106.8456];
    
    $address_lower = strtolower($address);
    
    // Simulasi koordinat berdasarkan kota
    if (strpos($address_lower, 'bekasi') !== false) {
        $coordinates = ['lat' => -6.2382, 'lng' => 106.9756];
    } elseif (strpos($address_lower, 'jakarta') !== false) {
        $coordinates = ['lat' => -6.2088, 'lng' => 106.8456];
    } elseif (strpos($address_lower, 'bandung') !== false) {
        $coordinates = ['lat' => -6.9175, 'lng' => 107.6191];
    } elseif (strpos($address_lower, 'surabaya') !== false) {
        $coordinates = ['lat' => -7.2575, 'lng' => 112.7521];
    } elseif (strpos($address_lower, 'medan') !== false) {
        $coordinates = ['lat' => 3.5952, 'lng' => 98.6722];
    } elseif (strpos($address_lower, 'bogor') !== false) {
        $coordinates = ['lat' => -6.5952, 'lng' => 106.7892];
    } elseif (strpos($address_lower, 'tangerang') !== false) {
        $coordinates = ['lat' => -6.1783, 'lng' => 106.6319];
    } elseif (strpos($address_lower, 'depok') !== false) {
        $coordinates = ['lat' => -6.4025, 'lng' => 106.8194];
    }
    
    return $coordinates;
}

// Function untuk mendapatkan status tracking
function getTrackingStatus($status) {
    $tracking_steps = [
        'pending' => [
            'step' => 1,
            'title' => 'Pesanan Diterima',
            'description' => 'Pesanan Anda telah diterima dan menunggu konfirmasi',
            'completed' => true
        ],
        'processing' => [
            'step' => 2,
            'title' => 'Sedang Diproses',
            'description' => 'Pesanan sedang disiapkan oleh penjual',
            'completed' => true
        ],
        'shipped' => [
            'step' => 3,
            'title' => 'Dalam Pengiriman',
            'description' => 'Pesanan sedang dalam perjalanan ke lokasi Anda',
            'completed' => false
        ],
        'completed' => [
            'step' => 4,
            'title' => 'Pesanan Selesai',
            'description' => 'Pesanan telah sampai di tujuan',
            'completed' => false
        ]
    ];
    
    return $tracking_steps;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lacak Pesanan - hobiBekasan</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Leaflet CSS untuk Maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        :root {
            --primary-color: #4f46e5;
            --secondary-color: #7c3aed;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
            --dark-color: #1f2937;
            --light-color: #f8f9fa;
        }
        
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }
        
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 40px 20px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 20px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }
        
        .page-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: white;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        
        .page-subtitle {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.9);
            position: relative;
            z-index: 1;
        }
        
        .search-section {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 30px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.1);
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .search-form {
            display: flex;
            gap: 1rem;
            align-items: end;
        }
        
        .search-group {
            flex: 1;
        }
        
        .search-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .search-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .btn-search {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 12px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(79, 70, 229, 0.3);
        }

        .btn-back {
            background: white;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-back:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(79, 70, 229, 0.3);
        }
        
        .tracking-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .tracking-info {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 15px 50px rgba(0,0,0,0.1);
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .map-container {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 15px 50px rgba(0,0,0,0.1);
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .order-details {
            margin-bottom: 2rem;
        }
        
        .order-detail-item {
            display: flex;
            justify-content: space-between;
            padding: 1rem 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .order-detail-item:last-child {
            border-bottom: none;
        }
        
        .order-detail-label {
            color: #6b7280;
            font-weight: 500;
        }
        
        .order-detail-value {
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .tracking-timeline {
            position: relative;
            padding-left: 2rem;
        }
        
        .tracking-timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e5e7eb;
        }
        
        .tracking-step {
            position: relative;
            margin-bottom: 2rem;
        }
        
        .tracking-step:last-child {
            margin-bottom: 0;
        }
        
        .tracking-step::before {
            content: '';
            position: absolute;
            left: -2rem;
            top: 0;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: white;
            border: 3px solid #e5e7eb;
            transform: translateX(-8px);
        }
        
        .tracking-step.completed::before {
            background: var(--success-color);
            border-color: var(--success-color);
        }
        
        .tracking-step.active::before {
            background: var(--warning-color);
            border-color: var(--warning-color);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: translateX(-8px) scale(1);
            }
            50% {
                transform: translateX(-8px) scale(1.2);
            }
        }
        
        .tracking-step-title {
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }
        
        .tracking-step-description {
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        #map {
            height: 400px;
            border-radius: 12px;
            border: 2px solid #e5e7eb;
        }
        
        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            border: 1px solid #fecaca;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
        }
        
        .status-pending {
            background: #fed7aa;
            color: #92400e;
        }
        
        .status-processing {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .status-shipped {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }
        
        @media (max-width: 768px) {
            .tracking-container {
                grid-template-columns: 1fr;
            }
            
            .search-form {
                flex-direction: column;
            }
            
            .page-header {
                padding: 30px 15px;
            }
            
            .page-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>

<!-- Sertakan navbar -->
<?php include "../assets/navbar.php"; ?>

<!-- Main Content -->
<div class="main-container">
    <!-- Page Header -->
    <div class="page-header">
        <div style="display: flex; justify-content: space-between; align-items: center; position: relative; z-index: 1;">
            <div>
                <h1 class="page-title">
                    <i class="fas fa-map-marker-alt me-3"></i>Lacak Pesanan
                </h1>
                <p class="page-subtitle">Masukkan nomor order untuk melacak status pengiriman</p>
            </div>
            <a href="riwayat_pesanan.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Kembali ke Riwayat Pesanan
            </a>
        </div>
    </div>

    <!-- Search Section -->
    <div class="search-section">
        <form method="POST" class="search-form">
            <div class="search-group">
                <label class="search-label">Nomor Order</label>
                <input type="text" name="order_number" class="search-input" placeholder="Contoh: ORD-20240520-001" required>
            </div>
            <button type="submit" class="btn-search">
                <i class="fas fa-search"></i> Lacak
            </button>
        </form>
    </div>
    
    <?php if ($error_message): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($order_data): ?>
        <!-- Tracking Container -->
        <div class="tracking-container">
            <!-- Tracking Info -->
            <div class="tracking-info">
                <h2 class="section-title">
                    <i class="fas fa-info-circle"></i> Detail Pesanan
                </h2>
                
                <div class="order-details">
                    <div class="order-detail-item">
                        <span class="order-detail-label">Nomor Order</span>
                        <span class="order-detail-value">#<?php echo htmlspecialchars($order_data['order_number']); ?></span>
                    </div>
                    <div class="order-detail-item">
                        <span class="order-detail-label">Tanggal Pesanan</span>
                        <span class="order-detail-value"><?php echo date('d M Y, H:i', strtotime($order_data['created_at'])); ?></span>
                    </div>
                    <div class="order-detail-item">
                        <span class="order-detail-label">Status</span>
                        <span class="status-badge status-<?php echo $order_data['status']; ?>">
                            <?php 
                            $status_text = [
                                'pending' => 'Menunggu',
                                'processing' => 'Diproses',
                                'shipped' => 'Dikirim',
                                'completed' => 'Selesai',
                                'cancelled' => 'Dibatalkan'
                            ];
                            echo $status_text[$order_data['status']] ?? ucfirst($order_data['status']);
                            ?>
                        </span>
                    </div>
                    <div class="order-detail-item">
                        <span class="order-detail-label">Total Harga</span>
                        <span class="order-detail-value">Rp <?php echo number_format($order_data['total_amount'], 0, ',', '.'); ?></span>
                    </div>
                    <div class="order-detail-item">
                        <span class="order-detail-label">Alamat Pengiriman</span>
                        <span class="order-detail-value"><?php echo htmlspecialchars($order_data['shipping_address']); ?></span>
                    </div>
                </div>
                
                <h2 class="section-title">
                    <i class="fas fa-truck"></i> Status Pengiriman
                </h2>
                
                <div class="tracking-timeline">
                    <?php
                    $tracking_steps = getTrackingStatus($order_data['status']);
                    $current_step = 0;
                    
                    if ($order_data['status'] == 'pending') $current_step = 1;
                    elseif ($order_data['status'] == 'processing') $current_step = 2;
                    elseif ($order_data['status'] == 'shipped') $current_step = 3;
                    elseif ($order_data['status'] == 'completed') $current_step = 4;
                    
                    $steps = [
                        ['title' => 'Pesanan Diterima', 'description' => 'Pesanan Anda telah diterima dan menunggu konfirmasi'],
                        ['title' => 'Sedang Diproses', 'description' => 'Pesanan sedang disiapkan oleh penjual'],
                        ['title' => 'Dalam Pengiriman', 'description' => 'Pesanan sedang dalam perjalanan ke lokasi Anda'],
                        ['title' => 'Pesanan Selesai', 'description' => 'Pesanan telah sampai di tujuan']
                    ];
                    
                    foreach ($steps as $index => $step):
                        $step_num = $index + 1;
                        $step_class = '';
                        if ($step_num < $current_step) $step_class = 'completed';
                        elseif ($step_num == $current_step) $step_class = 'active';
                    ?>
                        <div class="tracking-step <?php echo $step_class; ?>">
                            <div class="tracking-step-title"><?php echo $step['title']; ?></div>
                            <div class="tracking-step-description"><?php echo $step['description']; ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Map Container -->
            <div class="map-container">
                <h2 class="section-title">
                    <i class="fas fa-map"></i> Lokasi Pengiriman
                </h2>
                <div id="map"></div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
<?php if ($order_data): ?>
    // Inisialisasi map
    const map = L.map('map').setView([<?php echo getCoordinatesFromAddress($order_data['shipping_address'])['lat']; ?>, <?php echo getCoordinatesFromAddress($order_data['shipping_address'])['lng']; ?>], 13);
    
    // Tambahkan tile layer (OpenStreetMap)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    // Tambahkan marker lokasi pengiriman
    const deliveryLocation = [<?php echo getCoordinatesFromAddress($order_data['shipping_address'])['lat']; ?>, <?php echo getCoordinatesFromAddress($order_data['shipping_address'])['lng']; ?>];
    
    const customIcon = L.divIcon({
        className: 'custom-marker',
        html: '<div style="background-color: #4f46e5; width: 30px; height: 30px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"></div>',
        iconSize: [30, 30],
        iconAnchor: [15, 15]
    });
    
    L.marker(deliveryLocation, {icon: customIcon})
        .addTo(map)
        .bindPopup('<b>Lokasi Pengiriman</b><br><?php echo htmlspecialchars($order_data['shipping_address']); ?>')
        .openPopup();
    
    // Tambahkan marker lokasi toko (Bekasi)
    const storeLocation = [-6.2382, 106.9756];
    
    const storeIcon = L.divIcon({
        className: 'custom-marker',
        html: '<div style="background-color: #10b981; width: 30px; height: 30px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"></div>',
        iconSize: [30, 30],
        iconAnchor: [15, 15]
    });
    
    L.marker(storeLocation, {icon: storeIcon})
        .addTo(map)
        .bindPopup('<b>Lokasi Toko</b><br>JL. Bintara Jaya, Bekasi');
    
    // Draw line between store and delivery location
    const polyline = L.polyline([storeLocation, deliveryLocation], {
        color: '#4f46e5',
        weight: 3,
        opacity: 0.7,
        dashArray: '10, 10'
    }).addTo(map);
    
    // Fit map to show both markers
    map.fitBounds(polyline.getBounds(), {padding: [50, 50]});
<?php endif; ?>
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
