<?php
session_start();

// Jika sudah login, redirect ke home.php
if (isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}

require_once 'config/Database.php';
require_once 'classes/User.php';

$error = "";
$success = "";

// Proses register
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);
    
    $user->username = $_POST['username'];
    $user->email = $_POST['email'];
    $user->password = $_POST['password'];
    $user->role = $_POST['role'];
    
    // Cek username
    if ($user->usernameExists()) {
        $error = "Username sudah digunakan!";
    } else {
        if ($user->register()) {
            $success = "Registrasi berhasil! Silakan login.";
        } else {
            $error = "Terjadi kesalahan. Coba lagi.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Gallery Foto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="bi bi-person-plus text-success" style="font-size: 3rem;"></i>
                            <h2 class="mt-3">Register</h2>
                            <p class="text-muted">Buat akun baru</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="bi bi-exclamation-triangle"></i> <?= $error ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="bi bi-check-circle"></i> <?= $success ?>
                                <a href="login.php" class="alert-link">Login sekarang</a>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Daftar Sebagai</label>
                                <select name="role" class="form-select" required>
                                    <option value="user">User</option>
                                    <option value="admin">Admin (BUAT TEST DOANG)</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-success w-100 mb-3">
                                <i class="bi bi-person-check"></i> Register
                            </button>
                        </form>

                        <div class="text-center">
                            <small class="text-muted">
                                Sudah punya akun? <a href="login.php">Login di sini</a>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
