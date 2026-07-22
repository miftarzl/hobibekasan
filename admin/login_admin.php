<?php
session_start();
require '../config/config.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username=? AND role='admin'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {

        $stmt->bind_result($id, $username_db, $hashed_password, $role);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {

            $_SESSION['admin'] = $username_db;

            header("Location: admin_dashboard.php");
            exit();

        } else {
            $error = "Password salah!";
        }

    } else {
        $error = "Username admin tidak ditemukan!";
    }
}
?>


<!DOCTYPE html>
<html>
<head>
<title>Login Admin</title>
</head>

<body>

<h2>Login Admin</h2>

<?php if($error){ echo "<p style='color:red;'>$error</p>"; } ?>

<form method="POST">

<input type="text" name="username" placeholder="Username Admin" required><br><br>

<input type="password" name="password" placeholder="Password" required><br><br>

<button type="submit">Login</button>

</form>

</body>
</html>