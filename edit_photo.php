<?php
session_start();

// Cek apakah sudah login dan role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: home.php");
    exit();
}

require_once 'config/Database.php';
require_once 'classes/Photo.php';

$database = new Database();
$db = $database->getConnection();
$photo = new Photo($db);

$error = "";
$success = "";

// Ambil ID foto dari URL
$photo_id = isset($_GET['id']) ? $_GET['id'] : die('ID foto tidak ditemukan');

// Ambil data foto
$photo->id = $photo_id;
$stmt = $photo->getById();
$photo_data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$photo_data) {
    die('Foto tidak ditemukan');
}

// Cek apakah foto milik user yang login
if ($photo_data['user_id'] != $_SESSION['user_id']) {
    die('Anda tidak memiliki akses untuk mengedit foto ini');
}

// Proses update foto
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $hashtag = trim($_POST['hashtag']);
    
    // Validasi input
    if (empty($title)) {
        $error = "Judul tidak boleh kosong!";
    } else {
        // Update data foto
        $query = "UPDATE photos 
                  SET title = :title, 
                      description = :description, 
                      hashtag = :hashtag 
                  WHERE id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":title", $title);
        $stmt->bindParam(":description", $description);
        $stmt->bindParam(":hashtag", $hashtag);
        $stmt->bindParam(":id", $photo_id);
        
        if ($stmt->execute()) {
            $success = "Foto berhasil diupdate!";
            // Refresh data foto
            $photo->id = $photo_id;
            $stmt = $photo->getById();
            $photo_data = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = "Gagal mengupdate foto!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Foto - Gallery Foto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .preview-image {
            width: 100%;
            max-width: 400px;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="home.php">
                <i class="bi bi-images"></i> <img src="assets/title.png"><br><small>share and like foto </small>
            </a>
            <div class="ms-auto">
                <a href="dashboard.php" class="btn btn-light btn-sm">
                    <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
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
                            <i class="bi bi-pencil-square"></i> Edit Foto
                        </h3>

                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="bi bi-exclamation-triangle"></i> <?= $error ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="bi bi-check-circle"></i> <?= $success ?>
                                <a href="dashboard.php" class="alert-link">Kembali ke Dashboard</a> atau 
                                <a href="detail.php?id=<?= $photo_id ?>" class="alert-link">Lihat Detail</a>
                            </div>
                        <?php endif; ?>

                        <!-- Preview Foto -->
                        <div class="text-center mb-4">
                            <img src="<?= $photo_data['image_path'] ?>" class="preview-image" alt="<?= htmlspecialchars($photo_data['title']) ?>">
                            <p class="text-muted small mt-2">
                                <i class="bi bi-info-circle"></i> Gambar tidak dapat diubah, hanya judul, deskripsi, dan hashtag (mending hapus)
                            </p>
                        </div>

                        <!-- Form Edit Foto-->
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="bi bi-card-heading"></i> Judul Foto *
                                </label>
                                <input type="text" 
                                       name="title" 
                                       class="form-control" 
                                       value="<?= htmlspecialchars($photo_data['title']) ?>" 
                                       required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="bi bi-text-paragraph"></i> Deskripsi
                                </label>
                                <textarea name="description" 
                                          class="form-control" 
                                          rows="4"><?= htmlspecialchars($photo_data['description']) ?></textarea>
                                <small class="text-muted">Deskripsikn tentang foto ini (opsional)</small>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">
                                    <i class="bi bi-hash"></i> Hashtag
                                </label>
                                <input type="text" 
                                       name="hashtag" 
                                       class="form-control" 
                                       value="<?= htmlspecialchars($photo_data['hashtag']) ?>"
                                       placeholder="nature, sunset, travel">
                                <small class="text-muted">Pisahkan dengan koma untuk beberapa hashtag</small>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-save"></i> Simpan Perubahanya
                                </button>
                                <a href="dashboard.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle"></i> Batal
                                </a>
                            </div>
                        </form>

                        <!-- Info Tambahan -->
                        <hr class="my-4">
                        <div class="row text-muted small">
                            <div class="col-md-6">
                                <i class="bi bi-calendar"></i> Diupload: 
                                <strong><?= date('d M Y H:i', strtotime($photo_data['created_at'])) ?></strong>
                            </div>
                            <div class="col-md-6 text-end">
                                <i class="bi bi-person"></i> Oleh: 
                                <strong><?= $photo_data['username'] ?></strong>
                            </div>
                        </div>
                    </div>
                </div>>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>