<?php
session_start();

// Jika sudah login, redirect ke home
if (isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}

require_once 'config/Database.php';
require_once 'classes/User.php';

 $error = "";

// Proses login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);
    
    $user->username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $user->login();
    
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verifikasi password
        if (password_verify($password, $row['password'])) {
            // Set session
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            
            header("Location: home.php");
            exit();
        } else {
            $error = "Username atau password salah!";
        }
    } else {
        $error = "Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PixelVault</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-5">
                <div class="card shadow-sm">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="bi bi-camera text-primary" style="font-size: 3rem;"></i>
                            <h2 class="mt-3">PixelVault</h2>
                            <p class="text-muted">Silakan login untuk melanjutkan</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="bi bi-exclamation-triangle"></i> <?= $error ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="bi bi-box-arrow-in-right"></i> Login
                            </button>
                        </form>

                        <div class="text-center">
                            <small class="text-muted">
                                Belum punya akun? <a href="register.php">Register di sini</a>
                            </small>
                        </div>

                        <hr class="my-4">
                        <div class="alert alert-info small mb-0">
                            <strong>Info Testing:</strong><br>
                            Admin - username: <code>admin</code> password: <code>password</code><br>
                            User - username: <code>user1</code> password: <code>password</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap/5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
