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
        
        // veriv pass
        if (password_verify($password, $row['password'])) {
            // Set sesi
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
    <title>Login - Gallery Foto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: var(--bs-font-sans-serif);
        }
        
        .login-container {
            width: 100%;
            max-width: 450px;
            padding: 0 15px;
        }
        
        .login-card {
            background: white;
            border-radius: var(--bs-border-radius-lg);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: all var(--animation-normal) ease;
        }
        
        .login-card:hover {
            transform: translateY(-5px);
        }
