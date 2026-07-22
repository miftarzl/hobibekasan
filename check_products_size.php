<?php
require 'config/config.php';

echo "<h2>Struktur Tabel Products</h2>";
$sql = "DESCRIBE products";
$result = $conn->query($sql);

if ($result) {
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
} else {
    echo "Error: " . $conn->error;
}

echo "<h2>Cek apakah ada kolom size/ukuran</h2>";
$sql = "SHOW COLUMNS FROM products LIKE '%size%'";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo "<p>Kolom size/ukuran sudah ada:</p>";
    while ($row = $result->fetch_assoc()) {
        echo "<p>- " . $row['Field'] . " (" . $row['Type'] . ")</p>";
    }
} else {
    echo "<p>Belum ada kolom size/ukuran. Menambahkan kolom sizes...</p>";
    
    // Tambahkan kolom sizes untuk menyimpan multiple ukuran
    $sql = "ALTER TABLE products ADD COLUMN sizes TEXT NULL AFTER description";
    if ($conn->query($sql)) {
        echo "<p>Kolom sizes berhasil ditambahkan!</p>";
    } else {
        echo "<p>Error menambahkan kolom sizes: " . $conn->error . "</p>";
    }
}

$conn->close();
?>
