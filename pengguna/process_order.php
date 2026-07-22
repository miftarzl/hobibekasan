<?php
// Force JSON content type and prevent any HTML output
header('Content-Type: application/json');

// Start output buffering to catch any unexpected output
ob_start();

session_start();
include '../config/config.php';

try {
    // Check for Midtrans library
    if (!file_exists('../vendor/midtrans/midtrans-php/Midtrans.php')) {
        throw new Exception('Midtrans library not found. Please run composer require midtrans/midtrans-php');
    }

    // Pastikan ada user yang login
    if (!isset($_SESSION['user'])) {
        throw new Exception('Sesi login tidak valid');
    }

    // Terima data dari AJAX dengan error handling
    $input = file_get_contents('php://input');
    $requestData = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Data pesanan tidak valid: ' . json_last_error_msg());
    }

    // Validasi data yang diterima
    if (!isset($requestData['items']) || !isset($requestData['customer']) || 
        !isset($requestData['totalPrice']) || !isset($requestData['shipping']) || 
        !isset($requestData['grandTotal'])) {
        throw new Exception('Format data pesanan tidak lengkap');
    }

    // Ambil data user
    $user_id = $_SESSION['user']['user_id'];
    $items = $requestData['items'];
    $customer = $requestData['customer'];
    $totalPrice = $requestData['totalPrice'];
    $shipping = $requestData['shipping'];
    $grandTotal = $requestData['grandTotal'];
    $ongkir_id = $customer['area'];

    // Validasi ongkir_id
    if (!is_numeric($ongkir_id)) {
        throw new Exception('ID ongkir tidak valid');
    }

    // Ambil data wilayah pengiriman dari tabel ongkir
    $queryOngkir = "SELECT city_name, shipping_cost FROM ongkir WHERE ongkir_id = '$ongkir_id'";
    $resultOngkir = mysqli_query($conn, $queryOngkir);
    
    if (!$resultOngkir || mysqli_num_rows($resultOngkir) == 0) {
        throw new Exception('Wilayah pengiriman tidak ditemukan');
    }
    
    $ongkirData = mysqli_fetch_assoc($resultOngkir);
    $city_name = $ongkirData['city_name'];
    
    // Verifikasi biaya pengiriman
    if ($shipping != $ongkirData['shipping_cost']) {
        throw new Exception('Biaya pengiriman tidak sesuai dengan data wilayah');
    }

    // Buat ID order unik
    $order_id = 'ORDER-' . time() . '-' . $user_id;

    // 1. Simpan ke tabel orders
    $queryOrder = "INSERT INTO orders (user_id, total_price, status) 
                VALUES ('$user_id', '$grandTotal', 'pending')";
    if (!mysqli_query($conn, $queryOrder)) {
        throw new Exception('Gagal menyimpan order: ' . mysqli_error($conn));
    }
    $order_db_id = mysqli_insert_id($conn);

    // 2. Siapkan data untuk Midtrans
    require_once '../vendor/midtrans/midtrans-php/Midtrans.php';

    // Konfigurasi Midtrans
    \Midtrans\Config::$serverKey = 'SB-Mid-server-OZu327rt0pNNBLNzh0qZvqwt';
    \Midtrans\Config::$isProduction = false;
    \Midtrans\Config::$isSanitized = true;
    \Midtrans\Config::$is3ds = true;

    // Siapkan item details untuk Midtrans
    $itemDetails = [];
    foreach ($items as $item) {
        $itemDetails[] = [
            'id' => $item['id'],
            'price' => $item['price'],
            'quantity' => $item['quantity'],
            'name' => $item['name']
        ];
    }

    // Tambahkan ongkir sebagai item
    $itemDetails[] = [
        'id' => 'SHIPPING',
        'price' => $shipping,
        'quantity' => 1,
        'name' => 'Ongkos Kirim ke ' . $city_name
    ];

    // Siapkan parameter transaksi
    $transaction_details = [
        'order_id' => $order_id,
        'gross_amount' => $grandTotal
    ];

    $customer_details = [
        'first_name' => $customer['name'],
        'email' => $customer['email'],
        'phone' => $customer['phone'],
        'billing_address' => [
            'first_name' => $customer['name'],
            'phone' => $customer['phone'],
            'address' => $customer['address'],
            'city' => $city_name,
            'postal_code' => '12345',
            'country_code' => 'IDN'
        ],
        'shipping_address' => [
            'first_name' => $customer['name'],
            'phone' => $customer['phone'],
            'address' => $customer['address'],
            'city' => $city_name,
            'postal_code' => '12345',
            'country_code' => 'IDN'
        ]
    ];

    $transaction = [
        'transaction_details' => $transaction_details,
        'customer_details' => $customer_details,
        'item_details' => $itemDetails
    ];

    // Dapatkan token dari Midtrans
    try {
        $snapToken = \Midtrans\Snap::getSnapToken($transaction);
    } catch (Exception $e) {
        throw new Exception('Error from Midtrans: ' . $e->getMessage());
    }
    
    // 3. Simpan data transaksi ke tabel transactions
    $customer_name = mysqli_real_escape_string($conn, $customer['name']);

    $queryTransaction = "INSERT INTO transactions 
                    (user_id, order_id, ongkir_id, customer_name, total_price, status, payment_method, transaction_unique_id, shipping_cost) 
                    VALUES ('$user_id', '$order_db_id', '$ongkir_id', '$customer_name', '$grandTotal', 'pending', 'midtrans', '$order_id', '$shipping')";
    
    if (!mysqli_query($conn, $queryTransaction)) {
        throw new Exception('Gagal menyimpan transaksi: ' . mysqli_error($conn));
    }
    $transaction_id = mysqli_insert_id($conn);
    
    // 4. Simpan detail pembelian ke tabel purchase_details
    foreach ($items as $item) {
        $product_id = $item['id'];
        $quantity = $item['quantity'];
        $price = $item['price'];
        $itemTotalPrice = $price * $quantity;
        
        $queryPurchaseDetail = "INSERT INTO purchase_details 
                            (transaction_id, product_id, quantity, price, total_price) 
                            VALUES ('$transaction_id', '$product_id', '$quantity', '$price', '$itemTotalPrice')";
        
        if (!mysqli_query($conn, $queryPurchaseDetail)) {
            throw new Exception('Gagal menyimpan detail pembelian: ' . mysqli_error($conn));
        }
    }
    
    // Kosongkan keranjang setelah checkout berhasil
    $clearCartQuery = "DELETE FROM cart WHERE user_id = '$user_id'";
    mysqli_query($conn, $clearCartQuery);
    
    // Discard any unexpected output
    ob_end_clean();
    
    // Respon sukses
    echo json_encode([
        'status' => 'success',
        'snap_token' => $snapToken,
        'order_id' => $order_db_id
    ]);
    
} catch (Exception $e) {
    // Discard any unexpected output
    ob_end_clean();
    
    // Return error as JSON
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
exit;
?>