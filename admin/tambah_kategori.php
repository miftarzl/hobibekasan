<?php
include '../config/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kategori = trim($_POST["kategori"]);

    if (!empty($kategori)) {
        $query_check = "SELECT * FROM categories WHERE name = ?";
        $stmt_check = $conn->prepare($query_check);
        $stmt_check->bind_param("s", $kategori);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows == 0) {
            $query = "INSERT INTO categories (name) VALUES (?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $kategori);

            if ($stmt->execute()) {
                header("Location: kategori.php");
            } else {
                echo "Gagal menambahkan kategori.";
            }
        } else {
            echo "Kategori sudah ada!";
        }
    } else {
        echo "Nama kategori tidak boleh kosong!";
    }
}
?>
