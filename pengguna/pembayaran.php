<?php
// Enable output buffering
ob_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Debug: Log session
error_log("Session data: " . print_r($_SESSION, true));

// Cek apakah user login atau belum
if (!isset($_SESSION['user']['user_id'])) {
    error_log("User not logged in, redirecting to login.php");
    header("Location: login.php");
    exit();
}

// Debug: Log user ID
error_log("User ID: " . $_SESSION['user']['user_id']);

// Cek apakah request method adalah POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Request method is not POST: " . $_SERVER['REQUEST_METHOD']);
    header("Location: checkout.php");
    exit();
}

// Debug: Log request method
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("POST data: " . print_r($_POST, true));

// Database connection
include '../config/config.php';

// Debug: Log database connection
if ($conn) {
    error_log("Database connection successful");
} else {
    error_log("Database connection failed");
}

// Function untuk mengirim email konfirmasi order
function sendOrderConfirmationEmail($user_email, $user_name, $order_number, $cart_items, $total_price, $shipping_cost, $service_fee, $total_payment, $shipping_address, $payment_method) {
    $to = $user_email;
    $subject = "Konfirmasi Pesanan #" . $order_number . " - hobiBekasan";
    
    // Format payment method name
    $payment_names = [
        'transfer_bca' => 'Transfer Bank BCA',
        'transfer_mandiri' => 'Transfer Bank Mandiri',
        'transfer_bni' => 'Transfer Bank BNI',
        'transfer_bri' => 'Transfer Bank BRI',
        'gopay' => 'GoPay',
        'ovo' => 'OVO',
        'dana' => 'DANA',
        'shopeepay' => 'ShopeePay',
        'qris' => 'QRIS',
        'cod' => 'Bayar di Tempat (COD)'
    ];
    $payment_name = $payment_names[$payment_method] ?? $payment_method;
    
    // Build product list HTML
    $products_html = '';
    foreach ($cart_items as $item) {
        $subtotal = $item['price'] * $item['quantity'];
        $products_html .= "
            <tr style='border-bottom: 1px solid #e0e0e0;'>
                <td style='padding: 12px; border-bottom: 1px solid #e0e0e0;'>
                    <strong>" . htmlspecialchars($item['name']) . "</strong><br>
                    <small style='color: #666;'>Qty: " . $item['quantity'] . "</small>
                </td>
                <td style='padding: 12px; text-align: right; border-bottom: 1px solid #e0e0e0;'>
                    Rp " . number_format($item['price'], 0, ',', '.') . "
                </td>
                <td style='padding: 12px; text-align: right; border-bottom: 1px solid #e0e0e0;'>
                    <strong>Rp " . number_format($subtotal, 0, ',', '.') . "</strong>
                </td>
            </tr>
        ";
    }
    
    // Email body HTML
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #003366, #005b99); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .header h1 { margin: 0; font-size: 24px; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .order-info { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e0e0e0; }
            .order-info h3 { margin-top: 0; color: #003366; border-bottom: 2px solid #003366; padding-bottom: 10px; }
            .table-container { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e0e0e0; }
            table { width: 100%; border-collapse: collapse; }
            th { background: #003366; color: white; padding: 12px; text-align: left; }
            .summary { background: #003366; color: white; padding: 20px; border-radius: 8px; }
            .summary-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
            .summary-total { font-size: 20px; font-weight: bold; border-top: 2px solid white; padding-top: 15px; margin-top: 15px; }
            .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
            .btn { display: inline-block; padding: 12px 30px; background: #003366; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎉 Pesanan Anda Berhasil!</h1>
                <p>Terima kasih telah berbelanja di hobiBekasan</p>
            </div>
            
            <div class='content'>
                <div class='order-info'>
                    <h3>📋 Informasi Pesanan</h3>
                    <p><strong>Nomor Order:</strong> #" . $order_number . "</p>
                    <p><strong>Nama:</strong> " . htmlspecialchars($user_name) . "</p>
                    <p><strong>Email:</strong> " . htmlspecialchars($user_email) . "</p>
                    <p><strong>Alamat Pengiriman:</strong><br>" . nl2br(htmlspecialchars($shipping_address)) . "</p>
                    <p><strong>Metode Pembayaran:</strong> " . htmlspecialchars($payment_name) . "</p>
                </div>
                
                <div class='table-container'>
                    <h3>🛒 Detail Produk</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th style='text-align: right;'>Harga</th>
                                <th style='text-align: right;'>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            " . $products_html . "
                        </tbody>
                    </table>
                </div>
                
                <div class='summary'>
                    <div class='summary-row'>
                        <span>Subtotal Produk:</span>
                        <span>Rp " . number_format($total_price, 0, ',', '.') . "</span>
                    </div>
                    <div class='summary-row'>
                        <span>Biaya Pengiriman:</span>
                        <span>Rp " . number_format($shipping_cost, 0, ',', '.') . "</span>
                    </div>
                    <div class='summary-row'>
                        <span>Biaya Layanan:</span>
                        <span>Rp " . number_format($service_fee, 0, ',', '.') . "</span>
                    </div>
                    <div class='summary-total summary-row'>
                        <span>Total Pembayaran:</span>
                        <span>Rp " . number_format($total_payment, 0, ',', '.') . "</span>
                    </div>
                </div>
                
                <div style='text-align: center;'>
                    <a href='" . BASE_URL . "/pengguna/struk_pembelian.php?order_id=" . $order_number . "' class='btn'>📄 Lihat Struk Pembelian</a>
                </div>
            </div>
            
            <div class='footer'>
                <p>&copy; 2024 hobiBekasan. Semua hak dilindungi.</p>
                <p>Jika Anda memiliki pertanyaan, silakan hubungi kami.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Headers
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: hobiBekasan <noreply@hobibekasan.com>\r\n";
    $headers .= "Reply-To: noreply@hobibekasan.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    // Send email
    if (mail($to, $subject, $message, $headers)) {
        error_log("Email sent successfully to: " . $to);
        return true;
    } else {
        error_log("Failed to send email to: " . $to);
        return false;
    }
}

// Function untuk menghitung biaya pengiriman
function calculateShippingCost($user_data) {
    // Store location (alamat toko) - Bekasi
    $store_location = [
        'address' => 'JL. Bintara Jaya Gang Masjid sebrang komplek Puri Idaman',
        'district' => 'Kec. Bintara Jaya',
        'city' => 'Kota Bekasi',
        'province' => 'Jawa Barat',
        'coordinates' => ['lat' => -6.2382, 'lng' => 106.9756]
    ];
    
    // Default shipping zones and costs based on distance from Bekasi store
    $shipping_zones = [
        'bekasi' => ['base_cost' => 8000, 'max_distance' => 15],
        'jabodetabek' => ['base_cost' => 12000, 'max_distance' => 40],
        'jakarta' => ['base_cost' => 15000, 'max_distance' => 60],
        'jabar' => ['base_cost' => 18000, 'max_distance' => 100],
        'banten' => ['base_cost' => 20000, 'max_distance' => 120],
        'bali' => ['base_cost' => 35000, 'max_distance' => 200],
        'sumatera' => ['base_cost' => 40000, 'max_distance' => 300],
        'kalimantan' => ['base_cost' => 45000, 'max_distance' => 400],
        'sulawesi' => ['base_cost' => 40000, 'max_distance' => 300],
        'papua' => ['base_cost' => 50000, 'max_distance' => 600],
        'ntt' => ['base_cost' => 45000, 'max_distance' => 400],
        'default' => ['base_cost' => 25000, 'max_distance' => 150]
    ];
    
    // Get user address
    $user_address = strtolower($user_data['address'] ?? '');
    
    // Check for city/province indicators in address
    if (strpos($user_address, 'jakarta') !== false || strpos($user_address, 'dki jakarta') !== false) {
        $zone = 'jakarta';
        $distance = 35; // Distance from Bekasi to Jakarta (realistic)
    } elseif (strpos($user_address, 'bekasi') !== false) {
        $zone = 'bekasi';
        $distance = 8; // Average distance within Bekasi city
    } elseif (strpos($user_address, 'tangerang') !== false || strpos($user_address, 'banten') !== false) {
        $zone = 'banten';
        $distance = 45; // Distance from Bekasi to Tangerang/Banten
    } elseif (strpos($user_address, 'bandung') !== false || strpos($user_address, 'jabar') !== false) {
        $zone = 'jabar';
        $distance = 120; // Distance from Bekasi to Bandung
    } elseif (strpos($user_address, 'bogor') !== false || strpos($user_address, 'depok') !== false || strpos($user_address, 'cibinong') !== false) {
        $zone = 'jabodetabek';
        $distance = 20; // Distance from Bekasi to Bogor/Depok area
    } elseif (strpos($user_address, 'surabaya') !== false || strpos($user_address, 'malang') !== false) {
        $zone = 'jabar';
        $distance = 700; // Distance from Bekasi to Surabaya/Malang (East Java)
    } elseif (strpos($user_address, 'medan') !== false || strpos($user_address, 'sumatera') !== false || strpos($user_address, 'palembang') !== false) {
        $zone = 'sumatera';
        $distance = 1000; // Distance from Bekasi to Sumatera (via ferry/flight)
    } elseif (strpos($user_address, 'bali') !== false || strpos($user_address, 'denpasar') !== false) {
        $zone = 'bali';
        $distance = 600; // Distance from Bekasi to Bali (via ferry/flight)
    } elseif (strpos($user_address, 'makassar') !== false || strpos($user_address, 'sulawesi') !== false) {
        $zone = 'sulawesi';
        $distance = 1500; // Distance from Bekasi to Sulawesi (via flight)
    } elseif (strpos($user_address, 'kalimantan') !== false || strpos($user_address, 'borneo') !== false) {
        $zone = 'kalimantan';
        $distance = 1200; // Distance from Bekasi to Kalimantan
    } elseif (strpos($user_address, 'jayapura') !== false || strpos($user_address, 'papua') !== false) {
        $zone = 'papua';
        $distance = 2800; // Distance from Bekasi to Papua (via flight)
    } elseif (strpos($user_address, 'kupang') !== false || strpos($user_address, 'ntt') !== false) {
        $zone = 'ntt';
        $distance = 2000; // Distance from Bekasi to NTT (via flight)
    } else {
        $zone = 'default';
        $distance = 100; // Default distance for unknown locations
    }
    
    // Get base cost for the zone
    $base_cost = $shipping_zones[$zone]['base_cost'];
    
    // Calculate additional cost based on distance
    $additional_cost = 0;
    if ($distance > $shipping_zones[$zone]['max_distance']) {
        $extra_distance = $distance - $shipping_zones[$zone]['max_distance'];
        $additional_cost = ceil($extra_distance / 10) * 2000; // Rp 2,000 per 10km extra
    }
    
    // Calculate total shipping cost
    $total_cost = $base_cost + $additional_cost;
    
    // Calculate estimated delivery days
    $estimated_days = '1-2 hari';
    if ($distance > 100) {
        $estimated_days = '3-5 hari';
    }
    if ($distance > 500) {
        $estimated_days = '5-7 hari';
    }
    if ($distance > 1000) {
        $estimated_days = '7-10 hari';
    }
    
    return [
        'zone' => $zone,
        'distance' => $distance,
        'base_cost' => $base_cost,
        'additional_cost' => $additional_cost,
        'total_cost' => $total_cost,
        'estimated_days' => $estimated_days,
        'store_location' => $store_location
    ];
}

// Ambil user ID
$user_id = $_SESSION['user']['user_id'];

// Ambil data dari form
$shipping_address = trim($_POST['shipping_address'] ?? '');
$notes = trim($_POST['notes'] ?? '');
$payment_method = $_POST['payment_method'] ?? '';

// Debug: Log received data
error_log("Received data - shipping_address: " . $shipping_address);
error_log("Received data - payment_method: " . $payment_method);

// Validasi input
if (empty($shipping_address)) {
    error_log("Validation failed: empty shipping_address");
    $_SESSION['error_message'] = "Alamat pengiriman harus diisi.";
    header("Location: checkout.php");
    exit();
}

if (empty($payment_method)) {
    error_log("Validation failed: empty payment_method");
    $_SESSION['error_message'] = "Metode pembayaran harus dipilih.";
    header("Location: checkout.php");
    exit();
}

// Ambil data keranjang
$cart_query = "SELECT c.*, p.name, p.price, p.image, p.stock, p.description
               FROM cart c
               JOIN products p ON c.product_id = p.product_id
               WHERE c.user_id = ?
               ORDER BY c.created_at DESC";
$cart_stmt = $conn->prepare($cart_query);
$cart_stmt->bind_param("i", $user_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();
$cart_items = [];

$total_items = 0;
$total_price = 0;

while ($row = $cart_result->fetch_assoc()) {
    $cart_items[] = $row;
    $total_items += $row['quantity'];
    $total_price += ($row['price'] * $row['quantity']);
}

// Debug: Log cart data
error_log("Cart items count: " . count($cart_items));
error_log("Total items: " . $total_items);
error_log("Total price: " . $total_price);

// Cek apakah keranjang kosong
if (empty($cart_items)) {
    error_log("Cart is empty, redirecting to keranjang.php");
    $_SESSION['error_message'] = "Keranjang belanja Anda kosong.";
    header("Location: keranjang.php");
    exit();
}

// Ambil data user
$user_query = "SELECT id, username, email, address FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();

// Calculate shipping cost
$shipping_cost = calculateShippingCost($user_data);

// Calculate total payment
$service_fee = 5000;
$total_payment = $total_price + $shipping_cost['total_cost'] + $service_fee;

// Generate order number
$order_number = 'ORD' . date('Ymd') . str_pad($user_id, 4, '0', STR_PAD_LEFT) . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

// Start transaction
$conn->begin_transaction();

try {
    // Insert order
    $order_query = "INSERT INTO orders (order_number, user_id, total_amount, shipping_cost, service_fee, payment_method, shipping_address, notes, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
    $order_stmt = $conn->prepare($order_query);
    $order_stmt->bind_param("sidddsss", $order_number, $user_id, $total_price, $shipping_cost['total_cost'], $service_fee, $payment_method, $shipping_address, $notes);
    $order_stmt->execute();
    
    $order_id = $conn->insert_id;
    
    // Insert order items
    foreach ($cart_items as $item) {
        $order_item_query = "INSERT INTO order_items (order_id, product_id, quantity, price, subtotal) 
                            VALUES (?, ?, ?, ?, ?)";
        $order_item_stmt = $conn->prepare($order_item_query);
        $subtotal = $item['price'] * $item['quantity'];
        $order_item_stmt->bind_param("iiidd", $order_id, $item['product_id'], $item['quantity'], $item['price'], $subtotal);
        $order_item_stmt->execute();
        
        // Update product stock
        $update_stock_query = "UPDATE products SET stock = stock - ? WHERE product_id = ?";
        $update_stock_stmt = $conn->prepare($update_stock_query);
        $update_stock_stmt->bind_param("ii", $item['quantity'], $item['product_id']);
        $update_stock_stmt->execute();
    }
    
    // Clear cart
    $clear_cart_query = "DELETE FROM cart WHERE user_id = ?";
    $clear_cart_stmt = $conn->prepare($clear_cart_query);
    $clear_cart_stmt->bind_param("i", $user_id);
    $clear_cart_stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    // Kirim email konfirmasi order
    $email_sent = sendOrderConfirmationEmail(
        $user_data['email'],
        $user_data['username'],
        $order_number,
        $cart_items,
        $total_price,
        $shipping_cost['total_cost'],
        $service_fee,
        $total_payment,
        $shipping_address,
        $payment_method
    );
    
    if ($email_sent) {
        error_log("Order confirmation email sent successfully to: " . $user_data['email']);
    } else {
        error_log("Failed to send order confirmation email to: " . $user_data['email']);
    }
    
    // Set success message
    $_SESSION['success_message'] = "Order berhasil dibuat! Nomor order: " . $order_number;
    
    // Debug: Log redirect
    error_log("About to redirect to: struk_pembelian.php?order_id=" . $order_id);
    error_log("Order ID: " . $order_id);
    error_log("Order Number: " . $order_number);
    
    // Clear any output buffer
    if (ob_get_length()) {
        ob_clean();
    }
    
    // Redirect to struk pembelian page with success parameter
    header("Location: struk_pembelian.php?order_id=" . $order_id . "&success=1");
    exit();
    
} catch (Exception $e) {
    // Debug: Log error
    error_log("Error in payment processing: " . $e->getMessage());
    error_log("Error trace: " . $e->getTraceAsString());
    
    // Rollback transaction
    $conn->rollback();
    
    // Set error message
    $_SESSION['error_message'] = "Terjadi kesalahan saat memproses order. Silakan coba lagi. Error: " . $e->getMessage();
    
    // Debug: Log redirect to checkout
    error_log("Redirecting to checkout.php due to error");
    
    // Clear any output buffer
    if (ob_get_length()) {
        ob_clean();
    }
    
    // Redirect back to checkout
    header("Location: checkout.php");
    exit();
}
?>
