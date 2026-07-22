<?php
// Include file koneksi database
include('../config/config.php');

// Hitung jumlah transaksi 7 hari terakhir untuk notifikasi
$countQuery = "SELECT COUNT(*) AS total_transactions FROM transactions 
               WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
$countResult = mysqli_query($conn, $countQuery);
$countData = mysqli_fetch_assoc($countResult);
$totalTransactions = $countData['total_transactions'];
?>
    <!-- Font Awesome untuk icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- CSS eksternal -->
    <style>
/* 
   Sidebar Admin CSS
   Dibuat untuk ItsYourThriftt Admin
*/

/* Reset dan Pengaturan Umum */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Poppins', sans-serif;
    background-color: #f8f9fa;
    margin: 0;
    overflow-x: hidden;
}

/* Wrapper Layout */
.wrapper {
    display: flex;
    position: relative;
    min-height: 100vh;
}

/* ===== SIDEBAR ===== */
#sidebar {
    width: 280px;
    height: 100vh;
    background: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%);
    color: #fff;
    position: fixed;
    top: 0;
    left: 0;
    z-index: 1000;
    transition: all 0.3s ease;
    box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
    overflow-y: auto;
    position: relative;
}

#sidebar.collapsed {
    width: 70px;
}

/* Logo dan Header */
.sidebar-header {
    padding: 20px;
    text-align: center;
    background: rgba(0, 0, 0, 0.05);
}

.logo-admin {
    width: 150px;
    max-width: 100%;
    transition: transform 0.3s ease;
}

#sidebar.collapsed .logo-admin {
    width: 40px;
    transform: scale(0.8);
}

/* Menu Links */
.menu-links {
    padding: 10px 0;
    list-style: none;
}

.menu-links li {
    margin: 5px 0;
}

.menu-item {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    color: #fff;
    text-decoration: none;
    font-size: 16px;
    border-radius: 0 30px 30px 0;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.menu-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.1);
    transition: all 0.4s ease;
    z-index: -1;
}

.menu-item:hover {
    background: rgba(255, 255, 255, 0.1);
}

.menu-item:hover::before {
    left: 0;
}

.menu-item i {
    min-width: 30px;
    font-size: 18px;
    text-align: center;
    margin-right: 10px;
    transition: all 0.3s ease;
}

#sidebar.collapsed .menu-text {
    display: none;
}

#sidebar.collapsed .menu-item {
    padding: 15px 20px;
    justify-content: center;
}

#sidebar.collapsed .menu-item i {
    margin-right: 0;
    font-size: 20px;
}

/* Logout Button */
.logout-container {
    margin-top: 20px;
    padding: 0 15px;
}

.logout-btn {
    background: linear-gradient(to right, #ff4b5c, #ff1e42);
    margin-top: 10px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 12px;
    color: white;
    text-decoration: none;
    transition: all 0.3s ease;
}

.logout-btn:hover {
    background: linear-gradient(to right, #ff1e42, #d60028);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(255, 30, 66, 0.3);
}

#sidebar.collapsed .logout-btn {
    padding: 12px;
    justify-content: center;
}

/* ===== SIDEBAR CONTROLS ===== */
.sidebar-controls {
    position: fixed;
    top: 15px;
    left: 295px;
    z-index: 1001;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

#sidebar.collapsed ~ .sidebar-controls {
    left: 85px;
}

/* Container untuk tombol notifikasi dan popup */
.notif-container {
    position: relative;
}

.btn-menu, .btn-notif {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    background: #ffffff;
    color: #1e7fd6;
    border: none;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.btn-menu:hover, .btn-notif:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.btn-menu i, .btn-notif i {
    font-size: 18px;
}

/* Badge pada tombol notifikasi */
.badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #ff4b5c;
    color: white;
    font-size: 11px;
    font-weight: 600;
    height: 18px;
    width: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    border: 2px solid #fff;
}

/* ===== NOTIFICATION POPUP ===== */
.notif-popup {
    position: absolute;
    top: calc(100% + 10px);
    left: auto; /* Hapus left positioning */
    right: -260px; /* Atur posisi ke kanan, nilai negatif untuk memindahkannya lebih ke kanan */
    width: 320px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15);
    visibility: hidden;
    opacity: 0;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    overflow: hidden;
    z-index: 1002;
}

.notif-popup.visible {
    visibility: visible;
    opacity: 1;
    transform: translateY(0);
}

.notif-header {
    padding: 15px;
    border-bottom: 1px solid #eaeaea;
    text-align: center;
}

.notif-header h4 {
    font-size: 16px;
    color: #333;
    margin: 0;
}

.notif-content {
    max-height: 320px;
    overflow-y: auto;
}

.notif-list {
    max-height: 275px; /* Tinggi maksimum untuk 5 notifikasi (sekitar 55px per item) */
    overflow-y: auto; /* Tambahkan scrollbar jika melebihi tinggi maksimum */
    padding: 0;
    margin: 0;
    list-style-type: none;
}

.notif-item {
    display: flex;
    padding: 15px;
    border-bottom: 1px solid #eaeaea;
    transition: background 0.3s ease;
}

.notif-item:hover {
    background: #f8f9fa;
}

.notif-icon {
    margin-right: 15px;
    width: 40px;
    height: 40px;
    background: #e9f2ff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #1e7fd6;
}

.notif-info {
    flex: 1;
}

.notif-title {
    font-size: 14px;
    margin: 0 0 5px;
    color: #333;
}

.notif-message {
    font-size: 13px;
    color: #666;
    margin: 0 0 5px;
}

.notif-time {
    font-size: 11px;
    color: #999;
    margin: 0;
}

.notif-empty {
    padding: 30px 20px;
    text-align: center;
    color: #999;
}

.notif-empty i {
    font-size: 40px;
    margin-bottom: 10px;
    opacity: 0.3;
}

.notif-empty p {
    font-size: 14px;
}

/* ===== CONTENT AREA ===== */
#content {
    width: calc(100% - 280px);
    margin-left: 280px;
    padding: 20px;
    transition: all 0.3s ease;
    min-height: 100vh;
    flex: 1;
}

#content.expanded {
    width: calc(100% - 70px);
    margin-left: 70px;
}

/* ===== RESPONSIVE DESIGN ===== */
@media (max-width: 992px) {
    #sidebar {
        width: 250px;
    }
    
    #content {
        width: calc(100% - 280px);
        margin-left: 280px;
}
    #sidebar ~ .sidebar-controls {
        left: 265px;
    }
    
    #sidebar.collapsed ~ .sidebar-controls {
        left: 85px;
    }
}

@media (max-width: 768px) {
    #sidebar {
        width: 70px;
        transform: translateX(0);
    }
    
    #sidebar .menu-text {
        display: none;
    }
    
    #sidebar .menu-item {
        padding: 15px 20px;
        justify-content: center;
    }
    
    #sidebar .menu-item i {
        margin-right: 0;
        font-size: 20px;
    }
    
    #sidebar.expanded {
        width: 250px;
    }
    
    #sidebar.expanded .menu-text {
        display: block;
    }
    
    #sidebar.expanded .menu-item {
        padding: 12px 20px;
        justify-content: flex-start;
    }
    
    #sidebar.expanded .menu-item i {
        margin-right: 10px;
        font-size: 18px;
    }
    
    .logo-admin {
        width: 40px;
    }
    
    #sidebar.expanded .logo-admin {
        width: 150px;
    }
    
    #content {
        width: calc(100% - 70px);
        margin-left: 70px;
    }
    
    #content.full {
        width: 100%;
        margin-left: 0;
    }
    
    #sidebar ~ .sidebar-controls {
        left: 85px;
    }
    
    #sidebar.expanded ~ .sidebar-controls {
        left: 265px;
    }
    
    #sidebar.hidden {
        transform: translateX(-100%);
    }
    
    #sidebar.hidden + #content {
        margin-left: 0;
        width: 100%;
    }

    .notif-popup {
        right: -240px; /* Sesuaikan untuk tablet */
        width: 280px;
    }
}

/* === PERUBAHAN UTAMA UNTUK MOBILE === */
@media (max-width: 576px) {
    /* Atur sidebar dalam keadaan default (collapsed/mini) */
    #sidebar {
        width: 70px;
        transform: translateX(0);
    }
    
    /* Sembunyikan teks menu seperti pada tampilan tablet */
    #sidebar .menu-text {
        display: none;
    }
    
    /* Atur posisi icon menu ke tengah */
    #sidebar .menu-item {
        padding: 15px 20px;
        justify-content: center;
    }
    
    #sidebar .menu-item i {
        margin-right: 0;
        font-size: 20px;
    }
    
    /* Atur logo menjadi kecil seperti ikon */
    .logo-admin {
        width: 40px;
    }
    
    /* Ketika sidebar expanded (diklik) */
    #sidebar.expanded {
        width: 250px;
        transform: translateX(0);
        z-index: 1500;
    }
    
    #sidebar.expanded .menu-text {
        display: block;
    }
    
    #sidebar.expanded .menu-item {
        padding: 12px 20px;
        justify-content: flex-start;
    }
    
    #sidebar.expanded .menu-item i {
        margin-right: 10px;
        font-size: 18px;
    }
    
    #sidebar.expanded .logo-admin {
        width: 150px;
    }
    
    /* Atur konten untuk menyesuaikan sidebar kecil */
    #content {
        width: calc(100% - 70px);
        margin-left: 70px;
    }
    
    /* Sesuaikan posisi notifikasi popup untuk mobile */
    #sidebar ~ .sidebar-controls {
        left: 85px;
        z-index: 1600;
    }
    
    #sidebar.expanded ~ .sidebar-controls {
        left: 265px;
    }
    
    /* Pastikan notifikasi tetap terlihat di layar */
     .notif-popup {
        right: -220px; /* Sesuaikan untuk mobile */
        width: 250px;
        max-width: 80vw; /* Batasi lebar maksimum notifikasi */
    }
    
    /* Penyesuaian untuk tombol notifikasi pada mobile */
    .notif-container {
        position: relative;
    }

    /* Pastikan popup tetap dalam viewport */
    .notif-popup.visible {
        right: -220px; /* Konsisten dengan sebelumnya */
    }
}

/* Jika masih ada masalah, tambahkan rule khusus untuk kondisi sidebar collapsed */
#sidebar.collapsed ~ .sidebar-controls .notif-popup {
    right: -260px; /* Pastikan konsisten dengan posisi default */
}

/* Custom scrollbar untuk tampilan yang lebih baik */
        .notif-list::-webkit-scrollbar {
            width: 6px;
        }
        
        .notif-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .notif-list::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }
        
        .notif-list::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
    </style>
</head>
        <!-- Sidebar -->
        <nav id="sidebar">
            <div class="sidebar-header">
                <a href="admin_dashboard.php">
                    <img src="../assets/img/logo.jpg" alt="ItsYourThriftt Admin" class="logo-admin">
                </a>
            </div>

            <ul class="menu-links">
                <li>
                    <a href="tentangkita.php" class="menu-item">
                        <i class="fas fa-info-circle"></i>
                        <span class="menu-text">Tentang Kita</span>
                    </a>
                </li>
                <li>
                    <a href="kategori.php" class="menu-item">
                        <i class="fas fa-tags"></i>
                        <span class="menu-text">Kategori Produk</span>
                    </a>
                </li>
                <li>
                    <a href="produk.php" class="menu-item">
                        <i class="fas fa-tshirt"></i>
                        <span class="menu-text">Produk</span>
                    </a>
                </li>
                <li>
                    <a href="pembelian.php" class="menu-item">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="menu-text">Pembelian Produk</span>
                    </a>
                </li>
                <li>
                    <a href="laporan.php" class="menu-item">
                        <i class="fas fa-file-alt"></i>
                        <span class="menu-text">Laporan Pembelian</span>
                    </a>
                </li>
                <li>
                    <a href="pengguna.php" class="menu-item">
                        <i class="fas fa-users"></i>
                        <span class="menu-text">Pengguna</span>
                    </a>
                </li>
                <li>
                    <a href="rating.php" class="menu-item">
                        <i class="fas fa-star"></i>
                        <span class="menu-text">Kelola Rating</span>
                    </a>
                </li>
                <li class="logout-container">
                    <a href="../pengguna/logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="menu-text">Logout</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Toggle Button dan Notifikasi -->
        <div class="sidebar-controls">
            <button id="sidebarToggle" class="btn-menu">
                <i class="fas fa-bars"></i>
            </button>
            <div class="notif-container">
                <button id="notifButton" class="btn-notif">
                    <i class="fas fa-bell"></i>
                    <!-- Selalu tampilkan badge jika ada transaksi -->
                    <?php if($totalTransactions > 0): ?>
                    <span class="badge" id="notifCount"><?= $totalTransactions ?></span>
                    <?php endif; ?>
                </button>

                <!-- Popup Notifikasi -->
                <div class="notif-popup" id="notifPopup">
                    <div class="notif-header">
                        <h4>Notifikasi Pembelian</h4>
                    </div>
                    <div class="notif-content">
                        <?php
                        // Ambil semua transaksi dengan status apapun, urutkan dari yang terbaru
                        $query = "SELECT t.customer_name, t.total_price, t.status, t.created_at, u.username 
                            FROM transactions t 
                            LEFT JOIN users u ON t.user_id = u.user_id
                            WHERE t.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                            ORDER BY t.created_at DESC 
                            LIMIT 10";
                        
                        $result = mysqli_query($conn, $query);

                        if (mysqli_num_rows($result) > 0): ?>
                            <ul class="notif-list">
                                <?php while ($row = mysqli_fetch_assoc($result)): 
                                    // Tentukan ikon dan warna berdasarkan status
                                    $icon = 'shopping-cart';
                                    $iconBg = '#e9f2ff';
                                    $iconColor = '#1e7fd6';
                                    
                                    switch($row['status']) {
                                        case 'pending':
                                            $icon = 'clock';
                                            $iconBg = '#fff2cc';
                                            $iconColor = '#d68c1e';
                                            break;
                                        case 'paid':
                                            $icon = 'check-circle';
                                            $iconBg = '#d4edda';
                                            $iconColor = '#28a745';
                                            break;
                                        case 'shipped':
                                            $icon = 'truck';
                                            $iconBg = '#cce5ff';
                                            $iconColor = '#007bff';
                                            break;
                                        case 'completed':
                                            $icon = 'check-double';
                                            $iconBg = '#c3e6cb';
                                            $iconColor = '#155724';
                                            break;
                                        case 'canceled':
                                            $icon = 'times-circle';
                                            $iconBg = '#f8d7da';
                                            $iconColor = '#dc3545';
                                            break;
                                    }
                                ?>
                                <li class="notif-item">
                                    <div class="notif-icon" style="background-color: <?= $iconBg ?>; color: <?= $iconColor ?>">
                                        <i class="fas fa-<?= $icon ?>"></i>
                                    </div>
                                    <div class="notif-info">
                                        <p class="notif-title">
                                            <strong><?= htmlspecialchars($row['customer_name']) ?></strong>
                                            <?php if (!empty($row['username'])): ?> 
                                                (<?= htmlspecialchars($row['username']) ?>)
                                            <?php endif; ?>
                                        </p>
                                        <p class="notif-message">
                                            Rp<?= number_format($row['total_price'], 0, ',', '.') ?> 
                                            <span class="badge-status" style="
                                                background-color: <?= $iconBg ?>; 
                                                color: <?= $iconColor ?>;
                                                padding: 2px 6px;
                                                border-radius: 3px;
                                                font-size: 10px;
                                                text-transform: uppercase;
                                            ">
                                                <?= $row['status'] ?>
                                            </span>
                                        </p>
                                        <p class="notif-time">
                                            <?= date("d M Y, H:i", strtotime($row['created_at'])) ?>
                                        </p>
                                    </div>
                                </li>
                                <?php endwhile; ?>
                            </ul>
                            <div style="text-align: center; padding: 10px;">
                                <a href="pembelian.php" style="color: #1e7fd6; text-decoration: none; font-size: 14px;">
                                    Lihat Semua Transaksi
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="notif-empty">
                                <i class="fas fa-inbox"></i>
                                <p>Tidak ada transaksi baru dalam 7 hari terakhir</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <!-- JavaScript -->
    <script>
/**
 * Sidebar Admin JavaScript
 * Dibuat untuk ItsYourThriftt Admin
 */

 document.addEventListener('DOMContentLoaded', function() {
    // Elemen-elemen yang diperlukan
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const content = document.getElementById('content');
    const notifButton = document.getElementById('notifButton');
    const notifPopup = document.getElementById('notifPopup');

    // Deteksi ukuran layar untuk pengaturan awal
    function checkScreenSize() {
        if (window.innerWidth <= 768 && window.innerWidth > 576) {
            // Untuk tablet, jadikan sidebar collapsed
            sidebar.classList.add('collapsed');
            content.classList.add('expanded');
        }
        
        // Untuk mobile, juga jadikan sidebar collapsed tapi tidak hidden
        if (window.innerWidth <= 576) {
            sidebar.classList.remove('hidden'); // Pastikan tidak hidden
            sidebar.classList.remove('expanded'); // Pastikan tidak expanded
            content.classList.add('expanded');
        }
    }

    // Jalankan pengecekan ukuran layar saat halaman dimuat
    checkScreenSize();

    // Toggle sidebar saat tombol menu diklik
    sidebarToggle.addEventListener('click', function() {
        // Untuk semua ukuran layar, toggle expanded/collapsed
        if (window.innerWidth <= 768) {
            sidebar.classList.toggle('expanded');
        } else {
            sidebar.classList.toggle('collapsed');
            content.classList.toggle('expanded');
        }
        
        // Toggle icon menu
        const menuIcon = sidebarToggle.querySelector('i');
        if (menuIcon.classList.contains('fa-bars')) {
            menuIcon.classList.remove('fa-bars');
            menuIcon.classList.add('fa-times');
        } else {
            menuIcon.classList.remove('fa-times');
            menuIcon.classList.add('fa-bars');
        }
    });

    // Toggle popup notifikasi
    notifButton.addEventListener('click', function(e) {
        e.stopPropagation(); // Mencegah event klik menyebar ke document
        notifPopup.classList.toggle('visible');
    });

    // Tutup popup notifikasi saat klik di luar
    document.addEventListener('click', function(e) {
        if (!notifPopup.contains(e.target) && !notifButton.contains(e.target)) {
            notifPopup.classList.remove('visible');
        }
    });

    // Deteksi perubahan ukuran layar
    window.addEventListener('resize', function() {
        checkScreenSize();
    });
});
    </script>
