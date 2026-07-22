<?php
// Letakkan ini di awal setiap file halaman admin
session_start();

// Fungsi untuk memeriksa sesi admin secara konsisten
function validateAdminSession() {
    // Periksa apakah session user ada dan role-nya admin
    if (!isset($_SESSION['user']) || !isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin') {
        // Hapus semua session untuk menghindari konflik
        $_SESSION = array();
        session_unset();
        session_destroy();
        
        // Mulai session baru dan set pesan error
        session_start();
        $_SESSION['error_message'] = "Sesi admin telah berakhir. Silakan login kembali.";
        
        // Redirect ke halaman login
        header("Location: ../pengguna/login.php");
        exit();
    }
    
    // Perbarui waktu last activity di session
    $_SESSION['last_activity'] = time();
}

// Periksa juga apakah session sudah timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    // Jika sudah lebih dari 30 menit tidak ada aktivitas
    $_SESSION = array();
    session_unset();
    session_destroy();
    
    // Mulai session baru dan set pesan error
    session_start();
    $_SESSION['error_message'] = "Sesi Anda telah berakhir karena tidak ada aktivitas. Silakan login kembali.";
    
    header("Location: ../pengguna/login.php");
    exit();
}

// Validasi session admin
validateAdminSession();

// Koneksi database dan kode lainnya
require '../config/config.php';

// Query untuk mengambil data pengguna dengan role 'user'
$customerQuery = "SELECT * FROM users WHERE role = 'user'";
$customerResult = mysqli_query($conn, $customerQuery);

if (!$customerResult) {
    die("Query failed: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pengguna</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
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

/* Content Area */
#content {
    width: calc(100% - 280px);
    margin-left: 280px;
    padding: 30px;
    transition: all 0.3s ease;
    min-height: 100vh;
}

#content.expanded {
    width: calc(100% - 70px);
    margin-left: 70px;
}

/* Dashboard Header */
.dashboard-header {
    position: relative;
    margin-bottom: 25px;
    padding-left: 60px;
}

/* Judul di tengah dengan garis bawah dan ukuran diperbesar */
.dashboard-title {
    text-align: center;
    width: 100%;
    position: relative;
    padding-bottom: 15px;
    margin-bottom: 20px;
}

.dashboard-title h2 {
    font-weight: bold;
    font-size: 2.5rem;
    background: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}

.dashboard-title h2::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 120px;
    height: 3px;
    background: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%);
    border-radius: 3px;
}

/* Alert Container */
.alert-container {
    margin-bottom: 20px;
    padding-left: 60px;
}

.alert {
    display: flex;
    align-items: center;
    border-radius: 8px;
    padding: 12px 15px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
}

.alert-icon {
    margin-right: 10px;
    font-size: 18px;
}

/* Container Pengguna */
.customer-container {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    padding: 20px;
    margin-left: 60px;
}

.table-responsive {
    overflow-x: auto;
}

/* Table */
.table {
    width: 100%;
    margin-bottom: 0;
    border-collapse: separate;
    border-spacing: 0;
}

/* Thead dengan background gradient */
.table thead {
    background: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%);
}

.table thead th {
    color: white;
    padding: 12px 15px;
    font-weight: 500;
    text-align: center;
    vertical-align: middle;
    border-color: #4da3f5;
    border-bottom: 0;
}

.table thead th:first-child {
    border-top-left-radius: 8px;
}

.table thead th:last-child {
    border-top-right-radius: 8px;
}

/* tbody tr styles */
.table tbody tr {
    transition: all 0.3s ease;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}

.table tbody td {
    padding: 15px;
    vertical-align: middle;
    text-align: center;
    border-color: #eaeaea;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 10px;
    justify-content: center;
    flex-wrap: wrap;
}

.btn-info, .btn-danger {
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 8px 16px;
    border: none;
    transition: all 0.2s ease;
}

.btn-info {
    background-color: #61b2ff;
    color: white;
}

.btn-info:hover {
    background-color: #3da5ff;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(97, 178, 255, 0.3);
}

.btn-danger {
    background-color: #ff6161;
    color: white;
}

.btn-danger:hover {
    background-color: #ff4545;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(255, 69, 69, 0.3);
}

.btn-info i, .btn-danger i {
    margin-right: 5px;
}

/* No Customers Display */
.no-customers {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    text-align: center;
}

.no-customers i {
    font-size: 60px;
    color: #ccc;
    margin-bottom: 20px;
}

.no-customers p {
    color: #888;
    font-size: 18px;
    margin-bottom: 20px;
}

/* Modal Styling */
.modal-content {
    border: none;
    border-radius: 10px;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
}

.modal-header {
    background: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%);
    color: white;
    border-bottom: none;
    border-top-left-radius: 10px;
    border-top-right-radius: 10px;
    padding: 15px 20px;
}

.modal-title {
    font-weight: 500;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    border-top: 1px solid #eaeaea;
    padding: 15px 20px;
}

/* Profile Image in Modal */
.profile-image {
    width: 150px;
    height: 150px;
    object-fit: cover;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
    border: 2px solid #61b2ff;
}

.profile-image:hover {
    transform: scale(1.05);
}

/* Modal Footer Button */
.btn-secondary {
    background: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%);
    color: white;
    border: none;
    border-radius: 6px;
    padding: 8px 16px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-secondary:hover {
    background: linear-gradient(135deg, #4da3f5 30%, #1a70c0 70%);
    box-shadow: 0 4px 8px rgba(97, 178, 255, 0.3);
    transform: translateY(-2px);
}

/* Responsive Adjustments */
@media (max-width: 992px) {
    #content {
        width: calc(100% - 250px);
        margin-left: 250px;
    }
    
    #content.expanded {
        width: calc(100% - 70px);
        margin-left: 70px;
    }
    
    .dashboard-header, .alert-container, .customer-container {
        padding-left: 45px;
    }
}

@media (max-width: 768px) {
    #content {
        width: calc(100% - 70px);
        margin-left: 70px;
    }
    
    .dashboard-header, .alert-container, .customer-container {
        padding-left: 30px;
    }
    
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-bottom: 15px;
    }
    
    .table {
        width: 100%;
        min-width: 600px;
    }
    
    .action-buttons {
        flex-direction: column;
        gap: 5px;
    }
}

@media (max-width: 576px) {
    .modal-dialog {
        margin: 10px;
    }
    
    .dashboard-header, .alert-container, .customer-container {
        padding-left: 20px;
    }
    
    .dashboard-title h2 {
        font-size: 1.8rem;
    }
    
    .profile-image {
        width: 120px;
        height: 120px;
    }
}
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include '../assets/sidebar_admin.php'; ?>
        
        <div id="content">
            <?php if(isset($_SESSION['message'])): ?>
                <div class="alert-container">
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle alert-icon"></i>
                        <span><?= $_SESSION['message']; ?></span>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>

            <div class="container-fluid">
                <div class="dashboard-header">
                    <div class="dashboard-title">
                        <h2>Data Pengguna</h2>
                    </div>
                </div>

                <div class="customer-container">
                    <?php if (mysqli_num_rows($customerResult) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th width="10%">No</th>
                                        <th width="30%">Nama Pengguna</th>
                                        <th width="30%">Email</th>
                                        <th width="30%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; while ($customerRow = mysqli_fetch_assoc($customerResult)): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><?= htmlspecialchars($customerRow['username']) ?></td>
                                            <td><?= htmlspecialchars($customerRow['email']) ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#detailModal<?= $customerRow['user_id'] ?>">
                                                        <i class="fas fa-eye"></i> Detail
                                                    </button>
                                                    <a href="hapus_pelanggan.php?id=<?= $customerRow['user_id'] ?>" class="btn btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus pengguna ini?')">
                                                        <i class="bi bi-trash"></i> Hapus
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Modal Detail Pengguna -->
                                        <div class="modal fade" id="detailModal<?= $customerRow['user_id'] ?>" tabindex="-1" aria-labelledby="detailModalLabel<?= $customerRow['user_id'] ?>" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="detailModalLabel<?= $customerRow['user_id'] ?>">Detail Pengguna</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row mb-3">
                                                            <div class="col-md-4 text-center">
                                                                <img src="../assets/img/profiles/<?= htmlspecialchars($customerRow['profile_photo'] ?? 'default.jpg') ?>"
                                                                    alt="Foto Profil"
                                                                    class="img-thumbnail profile-image">
                                                            </div>
                                                            <div class="col-md-8">
                                                                <p><strong>Nama:</strong> <?= htmlspecialchars($customerRow['username']) ?></p>
                                                                <p><strong>Email:</strong> <?= htmlspecialchars($customerRow['email']) ?></p>
                                                                <p><strong>Telepon:</strong> <?= htmlspecialchars($customerRow['phone_number']) ?></p>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-12">
                                                                <p><strong>Alamat:</strong><br><?= nl2br(htmlspecialchars($customerRow['address'])) ?></p>
                                                                <p><strong>Role:</strong> <?= htmlspecialchars($customerRow['role']) ?></p>
                                                                <p><strong>Tanggal Bergabung:</strong> <?= date('d F Y', strtotime($customerRow['created_at'])) ?></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="no-customers">
                            <i class="fas fa-users"></i>
                            <p>Belum ada data pengguna.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script untuk sidebar toggle
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.querySelector('#sidebarCollapse');
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    document.querySelector('#sidebar').classList.toggle('active');
                    document.querySelector('#content').classList.toggle('expanded');
                });
            }
        });
    </script>
</body>
</html>