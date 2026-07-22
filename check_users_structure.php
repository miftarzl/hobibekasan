<?php
require 'config/config.php';

echo "=== Users Table Structure ===\n";
$result = $conn->query("DESCRIBE users");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
}

echo "\n=== Users Data ===\n";
$result = $conn->query("SELECT * FROM users");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        foreach ($row as $key => $value) {
            echo "$key: $value | ";
        }
        echo "\n";
    }
}

$conn->close();
?>
