<?php
// Execute database setup from SQL file
$host = "localhost";
$db_user = "root"; 
$db_pass = ""; 

// Connect to MySQL without database
$conn = new mysqli($host, $db_user, $db_pass);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Read SQL file
$sql_file = 'setup_database_from_image.sql';
$sql = file_get_contents($sql_file);

if ($sql === false) {
    die("Gagal membaca file SQL: $sql_file");
}

// Execute multi-query
if ($conn->multi_query($sql)) {
    echo "Database setup berhasil dijalankan!<br>";
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    echo "Database hobibekasan telah dibuat sesuai dengan schema gambar.<br>";
} else {
    echo "Error: " . $conn->error . "<br>";
}

$conn->close();
?>
