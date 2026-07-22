<?php
session_start();
include('../config/config.php');

// Mengecek jika id transaksi ada di URL
if (isset($_GET['id'])) {
    $transaction_id = $_GET['id'];

    // Query untuk mendapatkan data transaksi
    $query = "SELECT * FROM transactions WHERE transaction_id = '$transaction_id'";
    $result = mysqli_query($conn, $query);
    $transaction = mysqli_fetch_assoc($result);

    if (!$transaction) {
        die("Transaksi tidak ditemukan.");
    }

    // Query untuk mendapatkan data pengguna (pembeli)
    $user_id = $transaction['user_id'];
    $user_query = "SELECT * FROM users WHERE user_id = '$user_id'";
    $user_result = mysqli_query($conn, $user_query);
    $user_data = mysqli_fetch_assoc($user_result);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Pembayaran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin_style.css"> <!-- Gaya khusus admin -->

    <style>
/* Wrapper Styling */
.wrapper {
    display: flex;
    align-items: stretch;
}

#content {
    width: 100%;
    padding: 20px;
    margin-left: 250px;  /* Menggeser konten agar sejajar dengan sidebar */
    transition: all 0.3s ease;
    margin-top: 10px; /* Ditambahkan agar sama dengan kategori.php */
}

/* Styling Heading (h2) untuk Kelola Pembelian */
#content h2 {
    font-size: 2.5rem; /* Ukuran font lebih besar */
    font-weight: 700;
    color: #61b2ff; /* Biru muda untuk judul */
    margin-bottom: 20px;
    text-align: center;
    text-transform: uppercase;
    position: relative; /* Agar garis bawah bisa ditambahkan di tengah */
}

/* Garis Bawah Pendek di Tengah Judul */
#content h2::after {
    content: "";
    position: absolute;
    bottom: -5px; /* Menurunkan garis lebih jauh ke bawah */
    left: 50%;
    transform: translateX(-50%);
    width: 80px; /* Panjang garis bawah */
    height: 3px;
    background-color: #61b2ff; /* Biru muda untuk garis bawah */
}

/* Menambahkan warna biru muda untuk tulisan "Detail Transaksi Pembelian" */
.card-title {
    color: #61b2ff; /* Biru muda untuk judul di dalam card */
    font-weight: bold;
}

.card {
    margin-top: 85px; /* Atur jarak sesuai kebutuhan */
}

    </style>
</head>
<body>
    <div class="wrapper">
        <?php include '../assets/sidebar_admin.php'; ?>

        <div id="content">
            <div class="container mt-5">
                <h2 class="text-center mb-4">Detail Transaksi Pembelian</h2>
                <div class="card shadow-lg">
                    <div class="card-body">
                        <h5 class="card-title">Data Pembeli</h5><br>
                        <p><strong>Nama Pembeli:</strong> <?= $user_data['username']; ?></p>
                        <p><strong>Email:</strong> <?= $user_data['email']; ?></p>
                        <p><strong>No. Telepon:</strong> <?= $user_data['phone_number']; ?></p>
                        <p><strong>Alamat Pengiriman:</strong> <?= $user_data['address']; ?></p>

                        <hr>

                        <p><strong>Metode Pembayaran:</strong> <?= ucfirst($transaction['payment_method']); ?></p>
                        <p><strong>Total Harga:</strong> Rp<?= number_format($transaction['total_price'], 0, ',', '.'); ?></p>
                        <p><strong>Tanggal Pembelian:</strong> <?= date("d M Y", strtotime($transaction['created_at'])); ?></p>


                        <!-- <div class="mb-3">
                        <h5 class="card-title">Bukti Pembayaran</h5><br>
                            <?php if ($transaction['payment_proof']) { ?>
                                <img src="../assets/bukti_pembayaran/<?= $transaction['payment_proof']; ?>" alt="Bukti Pembayaran" class="img-fluid">
                            <?php } else { ?>
                                <p>Belum ada bukti pembayaran yang diunggah.</p>
                            <?php } ?>
                        </div> -->

                        <hr>

                        <h5 class="card-title">Status Pengiriman</h5><br>
                        <form action="../admin/update_status_pengiriman.php" method="post">
                            <input type="hidden" name="transaction_id" value="<?= $transaction['transaction_id']; ?>">
                            <div class="form-group">
                                <label for="shipping_status">Status Pengiriman</label>
                                <select name="shipping_status" id="shipping_status" class="form-control">
                                    <option value="pending" <?= ($transaction['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                    <option value="paid" <?= ($transaction['status'] == 'paid') ? 'selected' : ''; ?>>Dibayar</option>
                                    <option value="shipped" <?= ($transaction['status'] == 'shipped') ? 'selected' : ''; ?>>Dikirim</option>
                                    <option value="completed" <?= ($transaction['status'] == 'completed') ? 'selected' : ''; ?>>Selesai</option>
                                    <option value="canceled" <?= ($transaction['status'] == 'canceled') ? 'selected' : ''; ?>>Dibatalkan</option>
                                </select>
                            </div>

                            <div class="form-group mt-3">
                                <label for="tracking_number">Nomor Resi</label>
                                <input type="text" name="tracking_number" id="tracking_number" class="form-control" value="<?= $transaction['tracking_number']; ?>" required>
                            </div>
                            <br><br>

                            <button type="submit" class="btn btn-primary mt-3">Perbarui Status</button>
                            <a href="pembelian.php" class="btn btn-secondary mt-3">Kembali ke Pembelian</a>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
