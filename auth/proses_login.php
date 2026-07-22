<?php
session_start();
include '../config/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];

    $sql = "SELECT user_id, username, password, role FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row["password"])) {
            $_SESSION["user_id"] = $row["user_id"];
            $_SESSION["username"] = $row["username"];
            $_SESSION["role"] = $row["role"];

            if ($row["role"] == "admin") {
                header("Location: ../admin/dashboard.php");
            } else {
                header("Location: ../pengguna/index.php");
            }
            exit();
        } else {
            $_SESSION["error"] = "Password salah!";
        }
    } else {
        $_SESSION["error"] = "Email tidak ditemukan!";
    }

    header("Location: login.php");
    exit();
}
