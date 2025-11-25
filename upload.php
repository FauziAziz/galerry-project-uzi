<?php
session_start();

// Cek apakah sudah login dan role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: home.php");
    exit();
}

require_once 'config/Database.php';
require_once 'classes/Photo.php';

$error = "";
$success = "";

// Proses upload foto
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    $photo = new Photo($db);
    
    // Validasi file upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            $error = "Hanya file JPG, PNG, dan GIF yang diperbolehkan!";
        } elseif ($_FILES['image']['size'] > $max_size) {
            $error = "Ukuran file maksimal 5MB!";
        } else {
            // Buat folder uploads jika belum ada
            $upload_dir = "uploads/";
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate nama file unik
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            // Upload file
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $photo->user_id = $_SESSION['user_id'];
                $photo->title = $_POST['title'];
                $photo->description = $_POST['description'];
                $photo->hashtag = $_POST['hashtag'];
                $photo->image_path = $upload_path;
                
                if ($photo->create()) {
                    $success = "Foto berhasil diupload!";
                } else {
                    $error = "Gagal menyimpan ke database!";
                    unlink($upload_path); // Hapus file jika gagal simpan ke DB
                }
            } else {
                $error = "Gagal mengupload file!";
            }
        }
    } else {
        $error = "Silakan pilih file untuk diupload!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Foto - Gallery Foto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .preview-container {
            max-width: 400px;
            margin: 0 auto;
        }
        .preview-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 8px;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="home.php">
                <i class="bi bi-images"></i> Gallery Foto
            </a>
            <div class="ms-auto">
                <a href="home.php" class="btn btn-light btn-sm">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
    </nav>

    <!-- Content -->
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h3 class="text-center mb-4">
                            <i class="bi bi-cloud-upload"></i> Upload Foto Baru
                        </h3>

                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="bi bi-exclamation-triangle"></i> <?= $error ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="bi bi-check-circle"></i> <?= $success ?>
                                <a href="home.php" class="alert-link">Lihat galeri</a>
                            </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <!-- Preview Image -->
                            <div class="preview-container mb-4">
                                <img id="imagePreview" class="preview-image d-none" alt="Preview">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Pilih Foto *</label>
                                <input type="file" name="image" id="imageInput" class="form-control" accept="image/*" required>
                                <small class="text-muted">Format: JPG, PNG, GIF. Maksimal 5MB.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Judul Foto *</label>
                                <input type="text" name="title" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Deskripsi</label>
                                <textarea name="description" class="form-control" rows="3"></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Hashtag</label>
                                <input type="text" name="hashtag" class="form-control" placeholder="nature, sunset, travel (pisahkan dengan koma)">
                                <small class="text-muted">Contoh: nature, sunset, travel</small>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-upload"></i> Upload Foto
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview gambar sebelum upload
        document.getElementById('imageInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('imagePreview');
                    preview.src = e.target.result;
                    preview.classList.remove('d-none');
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>