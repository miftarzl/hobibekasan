<?php
require 'config/config.php';

echo "=== ADMIN ACCOUNTS ===\n";
$result = mysqli_query($conn, 'SELECT username, email FROM users WHERE role = "admin"');
while ($row = mysqli_fetch_assoc($result)) {
    echo "Username: " . $row['username'] . "\n";
    echo "Email: " . $row['email'] . "\n";
    echo "---\n";
}
?>
