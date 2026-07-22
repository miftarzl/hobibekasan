<?php
include 'config/config.php';
$conn = new mysqli($servername, $username, $password, $dbname);
$result = $conn->query('DESCRIBE cart');
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . PHP_EOL;
}
?>
