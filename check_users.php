<?php
require 'config/config.php';

echo "=== Checking Users ===\n";

$result = $conn->query("SELECT user_id, username, role FROM users");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['user_id'] . " - Username: " . $row['username'] . " - Role: " . $row['role'] . "\n";
    }
}

echo "\n=== Users Table Structure ===\n";
$result = $conn->query('DESCRIBE users');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . ' - ' . $row['Type'] . PHP_EOL;
    }
}

$conn->close();
?>
