<?php
session_start();

// Cek apakah user login atau belum
if (!isset($_SESSION['user']['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
include '../config/config.php';

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
        $distance = 35;
    } elseif (strpos($user_address, 'bekasi') !== false) {
        $zone = 'bekasi';
        $distance = 8;
    } elseif (strpos($user_address, 'tangerang') !== false || strpos($user_address, 'banten') !== false) {
        $zone = 'banten';
        $distance = 45;
    } elseif (strpos($user_address, 'bandung') !== false || strpos($user_address, 'jabar') !== false) {
        $zone = 'jabar';
        $distance = 120;
    } elseif (strpos($user_address, 'bogor') !== false || strpos($user_address, 'depok') !== false || strpos($user_address, 'cibinong') !== false) {
        $zone = 'jabodetabek';
        $distance = 20;
    } elseif (strpos($user_address, 'surabaya') !== false || strpos($user_address, 'malang') !== false) {
        $zone = 'jabar';
        $distance = 700;
    } elseif (strpos($user_address, 'medan') !== false || strpos($user_address, 'sumatera') !== false || strpos($user_address, 'palembang') !== false) {
        $zone = 'sumatera';
        $distance = 1000;
    } elseif (strpos($user_address, 'bali') !== false || strpos($user_address, 'denpasar') !== false) {
        $zone = 'bali';
        $distance = 600;
    } elseif (strpos($user_address, 'makassar') !== false || strpos($user_address, 'sulawesi') !== false) {
        $zone = 'sulawesi';
        $distance = 1500;
    } elseif (strpos($user_address, 'kalimantan') !== false || strpos($user_address, 'borneo') !== false) {
        $zone = 'kalimantan';
        $distance = 1200;
    } elseif (strpos($user_address, 'jayapura') !== false || strpos($user_address, 'papua') !== false) {
        $zone = 'papua';
        $distance = 2800;
    } elseif (strpos($user_address, 'kupang') !== false || strpos($user_address, 'ntt') !== false) {
        $zone = 'ntt';
        $distance = 2000;
    } else {
        $zone = 'default';
        $distance = 100;
    }
    
    // Get base cost for the zone
    $base_cost = $shipping_zones[$zone]['base_cost'];
    
    // Calculate additional cost based on distance
    $additional_cost = 0;
    if ($distance > $shipping_zones[$zone]['max_distance']) {
        $extra_distance = $distance - $shipping_zones[$zone]['max_distance'];
        $additional_cost = ceil($extra_distance / 10) * 2000;
    }
    
    // Calculate total shipping cost
    $total_cost = $base_cost + $additional_cost;
    
    return [
        'zone' => $zone,
        'distance' => $distance,
        'base_cost' => $base_cost,
        'additional_cost' => $additional_cost,
        'total_cost' => $total_cost,
        'store_location' => $store_location
    ];
}

// Function untuk mengirim email konfirmasi order
function sendOrderConfirmationEmail($user_email, $user_name, $cart_items, $total_price, $shipping_cost, $service_fee, $total_payment) {
    $to = $user_email;
    $subject = "Konfirmasi Pesanan - hobiBekasan";
    
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
            .table-container { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e0e0e0; }
            table { width: 100%; border-collapse: collapse; }
            th { background: #003366; color: white; padding: 12px; text-align: left; }
            .summary { background: #003366; color: white; padding: 20px; border-radius: 8px; }
            .summary-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
            .summary-total { font-size: 20px; font-weight: bold; border-top: 2px solid white; padding-top: 15px; margin-top: 15px; }
            .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎉 Ringkasan Pesanan Anda</h1>
                <p>Terima kasih telah berbelanja di hobiBekasan</p>
            </div>
            
            <div class='content'>
                <p><strong>Nama:</strong> " . htmlspecialchars($user_name) . "</p>
                <p><strong>Email:</strong> " . htmlspecialchars($user_email) . "</p>
                
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
            </div>
            
            <div class='footer'>
                <p>&copy; 2024 hobiBekasan. Semua hak dilindungi.</p>
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

// Ambil user ID
$user_id = $_SESSION['user']['user_id'];

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

// Kirim email
$email_sent = sendOrderConfirmationEmail(
    $user_data['email'],
    $user_data['username'],
    $cart_items,
    $total_price,
    $shipping_cost['total_cost'],
    $service_fee,
    $total_payment
);

if ($email_sent) {
    $_SESSION['success_message'] = "Email konfirmasi pesanan telah dikirim ke " . $user_data['email'];
} else {
    $_SESSION['error_message'] = "Gagal mengirim email konfirmasi. Silakan cek email Anda secara manual.";
}

// Redirect ke struk pembelian
header("Location: struk_pembelian.php");
exit();
?>
