<?php
session_start();
include '../config/config.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : '';

// Ambil data pesanan
$queryOrder = "SELECT * FROM orders WHERE order_id = '$order_id'";
$resultOrder = mysqli_query($conn, $queryOrder);

// Jika order tidak ditemukan
if (mysqli_num_rows($resultOrder) == 0) {
    header("Location: index.php");
    exit();
}

$order = mysqli_fetch_assoc($resultOrder);

// Update status pesanan jika belum diupdate
if ($order['status'] == 'pending') {
    $updateQuery = "UPDATE orders SET status = 'paid' WHERE order_id = '$order_id'";
    mysqli_query($conn, $updateQuery);
    
    // Update juga status di tabel transactions
    $updateTransQuery = "UPDATE transactions SET status = 'paid' WHERE order_id = '$order_id'";
    mysqli_query($conn, $updateTransQuery);
    
    // Kosongkan keranjang setelah pembayaran berhasil
    $user_id = $_SESSION['user']['user_id'];
    $deleteCartQuery = "DELETE FROM cart WHERE user_id = '$user_id'";
    mysqli_query($conn, $deleteCartQuery);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Berhasil</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #1e7fd6;
            --secondary-color: #61b2ff;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .success-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 40px;
            text-align: center;
            margin-top: 50px;
            margin-bottom: 50px;
        }
        
        .success-icon {
            font-size: 80px;
            color: #28a745;
            margin-bottom: 20px;
        }
        
        .btn-shop {
            background: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%);
            color: white;
            border: none;
            padding: 12px 25px;
            font-weight: bold;
            border-radius: 50px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .btn-shop:hover {
            background: linear-gradient(135deg, #52a3f0 30%, #1973c7 70%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(30, 127, 214, 0.3);
            color: white;
        }
    </style>
</head>

<?php include '../assets/navbar.php'; ?>

<body>
    <div class="container">
        <div class="success-container">
            <i class="fas fa-check-circle success-icon"></i>
            <h2 class="mb-4">Pembayaran Berhasil!</h2>
            <p class="lead">Terima kasih telah berbelanja di toko kami.</p>
            <p>Pesanan Anda dengan ID <strong><?php echo $order_id; ?></strong> sedang diproses.</p>
            <p>Kami akan mengirimkan konfirmasi melalui email yang Anda daftarkan.</p>
            
            <div class="mt-5">
                <a href="kategori.php" class="btn btn-shop">
                    <i class="fas fa-shopping-bag me-2"></i> Lanjutkan Belanja
                </a>
            </div>
        </div>
    </div>

    <?php include '../assets/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>