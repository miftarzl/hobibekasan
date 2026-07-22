<?php
session_start();
include '../config/config.php';

if (!isset($_SESSION['user']['user_id']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../pengguna/login.php");
    exit();
}

$semuadata = [];
$tgl_mulai = "";
$tgl_selesai = "";
$status = "";
$total_semua = 0;
$chart_data = [];

if (isset($_POST["filter"])) {
    $tgl_mulai = $_POST["start_date"];
    $tgl_selesai = $_POST["end_date"];
    $status = isset($_POST["status"]) ? $_POST["status"] : "";

    if (!empty($tgl_mulai) && !empty($tgl_selesai)) {
        if (strtotime($tgl_mulai) > strtotime($tgl_selesai)) {
            $error_message = "Tanggal awal tidak boleh melebihi tanggal akhir.";
        } else {
            if ($status === "all") {
                $stmt = $conn->prepare("SELECT * FROM transactions WHERE DATE(created_at) BETWEEN ? AND ? ORDER BY created_at DESC");
                $stmt->bind_param("ss", $tgl_mulai, $tgl_selesai);
            } else {
                $stmt = $conn->prepare("SELECT * FROM transactions WHERE status = ? AND DATE(created_at) BETWEEN ? AND ? ORDER BY created_at DESC");
                $stmt->bind_param("sss", $status, $tgl_mulai, $tgl_selesai);
            }
            $stmt->execute();
            $result = $stmt->get_result();
    
            while ($row = $result->fetch_assoc()) {
                $semuadata[] = $row;
                $total_semua += $row['total_price'];
    
                $tanggal = date("d-m-Y", strtotime($row['created_at']));
                if (!isset($chart_data[$tanggal])) {
                    $chart_data[$tanggal] = 0;
                }
                $chart_data[$tanggal] += $row['total_price'];
            }
        }
    }    
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pembelian</title>
    <!-- Modal CSS dan JS Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    color: #1e7fd6;
    font-size: 2.5rem;
    font-weight: 600;
    margin: 0;
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

/* Container untuk report */
.report-container {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    padding: 20px;
    margin-left: 60px;
}

/* Styling Filter Form */
.filter-form-wrapper {
    margin-bottom: 20px;
}

.filter-form {
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.form-label {
    font-weight: 500;
    color: #555;
    margin-bottom: 8px;
}

.form-control {
    border-radius: 6px;
    border: 1px solid #ddd;
    padding: 10px 15px;
    transition: all 0.2s ease;
}

.form-control:focus {
    border-color: #61b2ff;
    box-shadow: 0 0 0 3px rgba(97, 178, 255, 0.25);
}

/* Tombol */
.btn-primary {
    background: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%);
    border: none;
    border-radius: 6px;
    padding: 10px 16px;
    font-weight: 500;
    transition: all 0.3s ease;
    box-shadow: 0 4px 10px rgba(30, 127, 214, 0.2);
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary i {
    font-size: 16px;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #4da3f5 30%, #1a70c0 70%);
    box-shadow: 0 6px 15px rgba(30, 127, 214, 0.3);
    transform: translateY(-2px);
}

/* Tombol Export PDF */
.btn-danger {
    background-color: #dc3545;
    border: none;
    color: white;
    font-size: 15px;
    font-weight: 600;
    padding: 10px 16px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    transition: all 0.3s ease;
    box-shadow: 0 4px 10px rgba(220, 53, 69, 0.2);
}

.btn-danger:hover {
    background-color: #c82333;
    box-shadow: 0 6px 15px rgba(220, 53, 69, 0.3);
    transform: translateY(-2px);
}

.btn-danger i {
    font-size: 16px;
    margin-right: 8px;
}

/* Table responsive */
.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
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

/* No data display */
.no-data {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    text-align: center;
}

.no-data i {
    font-size: 60px;
    color: #ccc;
    margin-bottom: 20px;
}

.no-data p {
    color: #888;
    font-size: 18px;
    margin-bottom: 20px;
}

/* Badge styling */
.badge {
    font-size: 0.8rem;
    padding: 0.5em 0.8em;
    border-radius: 6px;
}

/* Action buttons */
.action-buttons {
    display: flex;
    gap: 10px;
    justify-content: center;
    flex-wrap: wrap;
}

.btn-info {
    background-color: #17a2b8;
    color: white;
    border: none;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 8px 16px;
    transition: all 0.2s ease;
}

.btn-info:hover {
    background-color: #138496;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(19, 132, 150, 0.3);
}

.btn-info i {
    margin-right: 5px;
}

/* Modal Custom Styles */
.custom-modal {
    border-radius: 10px;
    background-color: #f8f9fa;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
    border: none;
}

.custom-modal-header {
    background: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%);
    color: white;
    border-bottom: none;
    border-top-left-radius: 10px;
    border-top-right-radius: 10px;
    padding: 15px 20px;
}

.custom-modal-body {
    background-color: #ffffff;
    color: #333;
    font-size: 16px;
    padding: 20px;
}

.custom-list-group {
    list-style-type: none;
    padding: 0;
}

.custom-list-item {
    background-color: #e6f7ff;
    margin-bottom: 10px;
    padding: 10px;
    border-radius: 5px;
    font-size: 14px;
    color: #4fa3d1;
}

.custom-list-item:hover {
    background-color: #b3d9ff;
    cursor: pointer;
}

.custom-modal-footer {
    background-color: #e6f7ff;
    border-top: 2px solid #4fa3d1;
    padding: 15px 20px;
}

.btn-secondary {
    background-color: #6c757d;
    border: none;
    color: white;
    font-size: 16px;
    transition: all 0.2s ease;
}

.btn-secondary:hover {
    background-color: #5a6268;
}

/* Chart section */
.chart-section {
    margin-top: 30px;
}

.chart-title {
    text-align: center;
    color: #1e7fd6;
    margin-bottom: 20px;
    font-weight: 600;
}

.card {
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    margin-bottom: 30px;
}

.card-body {
    padding: 20px;
}

/* Responsive styles */
@media (max-width: 992px) {
    #content {
        width: calc(100% - 250px);
        margin-left: 250px;
    }
    
    #content.expanded {
        width: calc(100% - 70px);
        margin-left: 70px;
    }
    
    .dashboard-header, 
    .alert-container, 
    .report-container {
        padding-left: 0;
        margin-left: 0;
    }
}

@media (max-width: 768px) {
    #content {
        width: calc(100% - 70px);
        margin-left: 70px;
        padding: 20px 15px;
    }
    
    .dashboard-header, 
    .alert-container, 
    .report-container {
        padding-left: 0;
        margin-left: 0;
    }
    
    .dashboard-title h2 {
        font-size: 1.8rem;
    }
    
    .filter-form .row {
        flex-direction: column;
    }
    
    .col-md-3 {
        width: 100%;
        margin-bottom: 10px;
    }
    
    .btn-text {
        display: none; /* Hide text on buttons on small screens */
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
    
    .dashboard-title h2 {
        font-size: 1.6rem;
    }
    
    .btn-primary, 
    .btn-danger {
        width: 100%;
        justify-content: center;
    }
}
    </style>
</head>
<body>

<div class="wrapper">
    <?php include '../assets/sidebar_admin.php'; ?> <!-- Sidebar -->

    <div id="content">
        <?php if(isset($error_message)): ?>
            <div class="alert-container">
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle alert-icon"></i>
                    <span><?= $error_message; ?></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>

        <div class="container-fluid">
            <div class="dashboard-header">
                <div class="dashboard-title">
                    <h2>Laporan Pembelian</h2>
                </div>
            </div>

            <div class="report-container">
                <!-- Form Filter -->
                <div class="filter-form-wrapper">
                    <form method="POST" class="filter-form">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="start_date" class="form-label">Dari Tanggal:</label>
                                <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($tgl_mulai) ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label for="end_date" class="form-label">Sampai Tanggal:</label>
                                <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($tgl_selesai) ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status:</label>
                                <select name="status" class="form-control" required>
                                    <option value="">Pilih Status</option>
                                    <option value="all" <?= $status == "all" ? "selected" : "" ?>>Semua Status</option>
                                    <option value="pending" <?= $status == "pending" ? "selected" : "" ?>>Tertunda</option>
                                    <option value="paid" <?= $status == "paid" ? "selected" : "" ?>>Dibayar</option>
                                    <option value="shipped" <?= $status == "shipped" ? "selected" : "" ?>>Dikirim</option>
                                    <option value="completed" <?= $status == "completed" ? "selected" : "" ?>>Selesai</option>
                                    <option value="canceled" <?= $status == "canceled" ? "selected" : "" ?>>Dibatalkan</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" name="filter" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i> Cari Laporan
                                </button>
                            </div>
                        </div>
                    </form>
                
                    <!-- Export button -->
                    <form action="../admin/export_pdf.php" method="POST" target="_blank" class="mb-4 mt-3">
                        <input type="hidden" name="start_date" value="<?= $tgl_mulai ?>">
                        <input type="hidden" name="end_date" value="<?= $tgl_selesai ?>">
                        <input type="hidden" name="status" value="<?= $status ?>">
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-file-earmark-pdf-fill me-2"></i> Export PDF
                        </button>
                    </form>
                </div>

                <!-- Tabel Laporan -->
<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Pelanggan</th>
                <th>Tanggal Pembelian</th>
                <th>Jumlah Pembelian</th>
                <th>Status Pembelian</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($semuadata) > 0): ?>
                <?php foreach ($semuadata as $key => $row): ?>
                    <tr>
                        <td><?= $key + 1 ?></td>
                        <td><?= htmlspecialchars($row['customer_name']) ?></td>
                        <td><?= date("d-m-Y", strtotime($row['created_at'])) ?></td>
                        <td>Rp<?= number_format($row['total_price'], 0, ',', '.') ?></td>
                        <td>
                            <?php 
                            $status_text = '';
                            $badge_class = '';
                            
                            switch($row['status']) {
                                case 'pending':
                                    $status_text = 'Tertunda';
                                    $badge_class = 'bg-warning text-dark';
                                    break;
                                case 'paid':
                                    $status_text = 'Dibayar';
                                    $badge_class = 'bg-info';
                                    break;
                                case 'shipped':
                                    $status_text = 'Dikirim';
                                    $badge_class = 'bg-primary';
                                    break;
                                case 'completed':
                                    $status_text = 'Selesai';
                                    $badge_class = 'bg-success';
                                    break;
                                case 'canceled':
                                    $status_text = 'Dibatalkan';
                                    $badge_class = 'bg-danger';
                                    break;
                                default:
                                    $status_text = ucfirst($row['status']);
                                    $badge_class = 'bg-secondary';
                            }
                            ?>
                            <span class="badge <?= $badge_class ?>"><?= $status_text ?></span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#detailModal<?= $row['transaction_id']; ?>">
                                    <i class="fas fa-eye"></i> <span class="btn-text">Detail</span>
                                </button>
                            </div>
                        </td>
                    </tr>

                    <?php
                    $user_id = $row['user_id'];
                    $user_query = "SELECT * FROM users WHERE user_id = '$user_id'";
                    $user_result = mysqli_query($conn, $user_query);
                    $user_data = mysqli_fetch_assoc($user_result);

                    // Periksa apakah ongkir_id ada sebelum melakukan query
                    $ongkir_data = null;
                    if (!empty($row['ongkir_id'])) {
                        $ongkir_id = $row['ongkir_id'];
                        $ongkir_query = "SELECT * FROM ongkir WHERE ongkir_id = '$ongkir_id'";
                        $ongkir_result = mysqli_query($conn, $ongkir_query);
                        $ongkir_data = mysqli_fetch_assoc($ongkir_result);
                    }
                    ?>

                    <!-- Modal Detail Pembelian -->
                    <div class="modal fade" id="detailModal<?= $row['transaction_id']; ?>" tabindex="-1" aria-labelledby="modalLabel<?= $row['transaction_id']; ?>" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content custom-modal">
                                <div class="modal-header custom-modal-header">
                                    <h5 class="modal-title" id="modalLabel<?= $row['transaction_id']; ?>">Detail Pembelian</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body custom-modal-body">
                                    <p><strong>Nama Pelanggan:</strong> <?= $user_data['username']; ?></p>
                                    <p><strong>Email:</strong> <?= $user_data['email']; ?></p>
                                    <p><strong>No. Telepon:</strong> <?= $user_data['phone_number']; ?></p>
                                    <p><strong>Alamat Pengiriman:</strong> <?= $user_data['address']; ?></p>
                                    <p><strong>Tanggal Pembelian:</strong> <?= date("d M Y", strtotime($row['created_at'])); ?></p>
                                    <p><strong>Tarif Ongkir:</strong> 
                                        <?php if ($ongkir_data): ?>
                                            Rp<?= number_format($ongkir_data['shipping_cost'], 0, ',', '.'); ?>
                                        <?php else: ?>
                                            Rp0
                                        <?php endif; ?>
                                    </p>
                                    <p><strong>Total Harga:</strong> Rp<?= number_format($row['total_price'], 0, ',', '.'); ?></p>
                                    <p><strong>Status:</strong> 
                                        <?php 
                                        switch($row['status']) {
                                            case 'pending':
                                                echo 'Tertunda';
                                                break;
                                            case 'paid':
                                                echo 'Dibayar';
                                                break;
                                            case 'shipped':
                                                echo 'Dikirim';
                                                break;
                                            case 'completed':
                                                echo 'Selesai';
                                                break;
                                            case 'canceled':
                                                echo 'Dibatalkan';
                                                break;
                                            default:
                                                echo ucfirst($row['status']);
                                        }
                                        ?>
                                    </p>
                                    <hr>
                                    <h5>Produk yang Dibeli</h5>
                                    <ul class="list-group custom-list-group">
                                        <?php
                                        $trx_id = $row['transaction_id'];
                                        $detail_query = "SELECT pd.quantity, pd.price, p.name FROM purchase_details pd 
                                                        JOIN products p ON pd.product_id = p.product_id 
                                                        WHERE pd.transaction_id = '$trx_id'";
                                        $detail_result = mysqli_query($conn, $detail_query);
                                        while ($detail = mysqli_fetch_assoc($detail_result)) :
                                        ?>
                                            <li class="list-group-item custom-list-item">
                                                <?= $detail['name']; ?> - <?= $detail['quantity']; ?> x Rp<?= number_format($detail['price'], 0, ',', '.'); ?>
                                            </li>
                                        <?php endwhile; ?>
                                    </ul>
                                </div>
                                <div class="modal-footer custom-modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center no-data">
                        <i class="fas fa-folder-open"></i>
                        <p>Tidak ada data pembelian ditemukan.</p>
                    </td>
                </tr>
            <?php endif ?>
        </tbody>
    </table>
</div>

                <!-- Chart -->
                <?php if (count($chart_data) > 0): ?>
                <div class="chart-section">
                    <h3 class="chart-title">📊 Chart Pembelian</h3>
                    <div class="card">
                        <div class="card-body">
                            <canvas id="laporanChart"></canvas>
                        </div>
                    </div>
                </div>
                <script>
                    const ctx = document.getElementById('laporanChart').getContext('2d');
                    const laporanChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: <?= json_encode(array_keys($chart_data)) ?>,
                            datasets: [{
                                label: 'Total Pembelian (Rp)',
                                data: <?= json_encode(array_values($chart_data)) ?>,
                                backgroundColor: '#d9ecff', 
                                borderColor: '#61b2ff',
                                hoverBackgroundColor: '#61b2ff',
                                hoverBorderColor: '#61b2ff',
                                borderWidth: 2,

                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: { display: false },
                                title: {
                                    display: true,
                                    text: 'Grafik Total Pembelian per Hari',
                                    color: '#333',
                                    font: {
                                        size: 18
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return 'Rp' + value.toLocaleString('id-ID');
                                        }
                                    }
                                }
                            }
                        }
                    });
                </script>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

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