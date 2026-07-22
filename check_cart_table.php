<?php
require 'config/config.php';

echo "<h2>Daftar Tabel di Database</h2>";
$sql = "SHOW TABLES";
$result = $conn->query($sql);

if ($result) {
    echo "<ul>";
    while ($row = $result->fetch_array()) {
        echo "<li>" . $row[0] . "</li>";
    }
    echo "</ul>";
} else {
    echo "Error: " . $conn->error;
}

echo "<h2>Cek Tabel Cart</h2>";

try {
    $sql = "DESCRIBE cart";
    $result = $conn->query($sql);
    
    if ($result) {
        echo "<p>Tabel cart ada dengan struktur:</p>";
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (mysqli_sql_exception $e) {
    echo "<p>Tabel cart tidak ada. Membuat tabel cart...</p>";
    
    // Buat tabel cart
    $sql = "CREATE TABLE cart (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) NOT NULL,
        product_id INT(11) NOT NULL,
        quantity INT(11) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_product (user_id, product_id)
    )";
    
    if ($conn->query($sql)) {
        echo "<p>Tabel cart berhasil dibuat!</p>";
    } else {
        echo "<p>Error membuat tabel cart: " . $conn->error . "</p>";
    }
}

$conn->close();
?>
