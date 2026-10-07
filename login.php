<?php
session_start();
include "config/koneksi.php";
if (isset($_SESSION['status']) && $_SESSION['status'] == "login") {
    header("location:index.php");
    exit();
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = md5(mysqli_real_escape_string($koneksi, $_POST['password']));

    $q = mysqli_query($koneksi, "SELECT * FROM user WHERE username='$username' AND password='$password'");
    
    if (mysqli_num_rows($q) > 0) {
        $d = mysqli_fetch_assoc($q);

        if ($d['status'] == 'non-aktif') {
            $error = "Akun Anda sudah non-aktif/resign. Silakan hubungi Admin.";
        } else {
           
            $_SESSION['userID'] = $d['userID'];
            $_SESSION['username'] = $d['username'];
            $_SESSION['nama'] = $d['nama'] ?? $d['username'];
            $_SESSION['role'] = $d['role']; 
            $_SESSION['status'] = "login";

            header("location:index.php");
            exit();
        }
    } else {
        $error = "Username atau Password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Login - Aplikasi Kasir</title>
    <link rel="stylesheet" href="asset/css/bootstrap.min.css">
</head>

<body class="bg-light d-flex align-items-center justify-content-center vh-100">
    <div class="card shadow p-4" style="width: 350px;">
        <h4 class="text-center mb-3">LOGIN SISTEM KASIR</h4>

        <?php if ($error): ?>
            <div class="alert alert-danger py-1 text-center small"><?= $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">MASUK</button>
        </form>
    </div>
    <script src="asset/js/bootstrap.bundle.min.js"></script>
</body>

</html>