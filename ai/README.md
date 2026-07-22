# Random Recommendation API untuk hobibekasan

## Overview
API sederhana untuk memberikan rekomendasi produk acak kepada pengguna yang login di halaman index. Setiap pengguna akan mendapatkan produk yang berbeda setiap kali mereka refresh halaman.

## Cara Menggunakan

### 1. Jalankan API Server
Buka terminal/command prompt dan jalankan:
```bash
cd c:\xampp\htdocs\hobibekasan\ai
python start_api.py
```

Atau langsung:
```bash
python random_recommendation_api.py
```

### 2. Integrasi ke Halaman Index
Tambahkan kode berikut di `pengguna/index.php` setelah hero section:

```php
<?php
// Include helper untuk random recommendation
require_once 'ai/random_integration_helper.php';

// Cek user login
if (isset($_SESSION['user']['user_id'])) {
    $userId = $_SESSION['user']['user_id'];
    $recommendation = getRandomRecommendation($userId);
    
    if ($recommendation['success']) {
        ?>
        <!-- Random Recommendation Section -->
        <section class="random-recommendation-section py-5">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-4 col-md-6">
                        <div class="text-center mb-4">
                            <h3 class="fw-bold">
                                <i class="fas fa-dice text-primary me-2"></i>
                                Rekomendasi Khusus Untuk Anda
                            </h3>
                            <p class="text-muted">Produk pilihan khusus untuk <?php echo htmlspecialchars($_SESSION['user']['username']); ?>!</p>
                        </div>
                        
                        <?php echo displayRecommendationCard($recommendation); ?>
                    </div>
                </div>
            </div>
        </section>
        <?php
    }
}
?>
```

## Fitur

### 1. Random Recommendation Logic
- **Weighted Random**: Produk dengan rating lebih tinggi punya kesempatan lebih besar
- **Stock Check**: Hanya produk dengan stok tersedia
- **User Tracking**: Mencatat interaksi user untuk analytics

### 2. Fallback System
- Jika API offline, otomatis menggunakan query langsung ke database
- Jika database error, menampilkan pesan yang sesuai

### 3. API Endpoints

#### GET `/api/random-recommendation/<user_id>`
Mendapatkan rekomendasi produk untuk user tertentu.

**Response:**
```json
{
    "success": true,
    "product": {
        "product_id": 1,
        "name": "Sepatu Sneakers Vintage",
        "price": 250000,
        "image": "sepatu1.jpg",
        "category_name": "Sepatu",
        "stock": 5,
        "description": "Sepatu vintage yang nyaman..."
    },
    "message": "Produk rekomendasi khusus untuk User 1!",
    "timestamp": "2026-04-23T08:00:00"
}
```

#### GET `/api/health`
Check jika API berjalan dengan baik.

## Database Requirements

Pastikan tabel berikut ada di database `hobibekasan`:

```sql
-- Tabel products (sudah ada)
CREATE TABLE products (
    product_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    category_name VARCHAR(100),
    stock INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    description TEXT,
    rating DECIMAL(2,1) DEFAULT 0
);

-- Tabel untuk analytics (opsional)
CREATE TABLE ai_analytics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    product_id INT,
    event_type VARCHAR(50),
    event_data TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## Konfigurasi

### Database Connection
Edit `random_recommendation_api.py` jika menggunakan database config berbeda:

```python
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',  # Ganti jika ada password
    'database': 'hobibekasan'
}
```

### Port API
Default port adalah 5000. Untuk mengubah:

```python
app.run(debug=True, host='0.0.0.0', port=5000)  # Ganti port
```

## Troubleshooting

### 1. API Tidak Bisa Dijalankan
```bash
# Install dependencies manual
pip install flask mysql-connector-python
```

### 2. Database Connection Error
- Pastikan XAMPP MySQL sudah running
- Check database name, user, password
- Pastikan tabel products ada dan ada data

### 3. PHP Error di Website
- Pastikan API server running di terminal
- Check firewall tidak block port 5000
- Test API manual: `http://localhost:5000/api/health`

### 4. Produk Tidak Muncul
- Pastikan ada produk dengan stock > 0
- Check status produk = 'active'
- Test API dengan user ID yang valid

## Testing

### Test API Manual
```bash
# Health check
curl http://localhost:5000/api/health

# Get recommendation untuk user 1
curl http://localhost:5000/api/random-recommendation/1
```

### Test di Browser
1. Login ke website
2. Buka halaman index
3. Harus muncul section "Rekomendasi Khusus Untuk Anda"
4. Refresh halaman untuk produk yang berbeda

## Keunggulan

- **Sederhana**: Tidak perlu ML kompleks, cukup random dengan weight
- **Cepat**: Response time < 1 detik
- **Reliable**: Ada fallback system
- **Scalable**: Mudah ditambah feature nanti
- **User-Friendly**: Setiap user dapat produk berbeda

## Next Steps (Opsional)

1. **Add ML**: Tambahkan collaborative filtering
2. **Cache System**: Redis untuk performance
3. **Analytics Dashboard**: Tracking user behavior
4. **A/B Testing**: Test different recommendation algorithms
