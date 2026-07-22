<?php
require '../vendor/autoload.php';
require '../config/config.php';

use Dompdf\Dompdf;
use Dompdf\Options;

session_start();

// Ambil data dari POST
$tgl_mulai = $_POST["start_date"];
$tgl_selesai = $_POST["end_date"];
$status = $_POST["status"];

$semuadata = [];
$total_semua = 0;

if (!empty($tgl_mulai) && !empty($tgl_selesai)) {
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
    }
}

// Buat konten HTML
ob_start();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Pembelian</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        h2 { text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #999; padding: 8px; text-align: center; }
        th { background-color: #f2f2f2; }
        .total { font-weight: bold; }
    </style>
</head>
<body>
    <h2>Laporan Pembelian<br>
        Periode: <?= date("d-m-Y", strtotime($tgl_mulai)) ?> s.d. <?= date("d-m-Y", strtotime($tgl_selesai)) ?><br>
        Status: <?= $status === "all" ? "Semua Proses" : ucfirst($status) ?>
    </h2>

    
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Nama Pembeli</th>
                <th>Metode Pembayaran</th>
                <th>Total Harga</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php if (count($semuadata) > 0): ?>
            <?php foreach ($semuadata as $key => $value): 
                $user_id = $value['user_id'];
                $get_user = $conn->query("SELECT username FROM users WHERE user_id = '$user_id'");
                $user = $get_user->fetch_assoc();
            ?>
                <tr>
                    <td><?= $key + 1 ?></td>
                    <td><?= date("d-m-Y", strtotime($value['created_at'])) ?></td>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= htmlspecialchars($value['payment_method']) ?></td>
                    <td>Rp<?= number_format($value['total_price'], 0, ',', '.') ?></td>
                    <td><?= htmlspecialchars($value['status']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="6">Tidak ada data transaksi pada periode ini.</td>
            </tr>
        <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="total">Total Keseluruhan</td>
                <td colspan="2" class="total">Rp<?= number_format($total_semua, 0, ',', '.') ?></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>

<?php
$html = ob_get_clean();

// Set opsi Dompdf
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

// Load dan render HTML
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape'); // Bisa diganti 'portrait' kalau mau vertikal
$dompdf->render();

// Output PDF ke browser
$dompdf->stream("laporan_pembelian_" . date("Ymd_His") . ".pdf", ["Attachment" => false]);
exit;
?>
