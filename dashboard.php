<?php
session_start();

// cek role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: home.php");
    exit();
}

require_once 'config/Database.php';
require_once 'classes/Photo.php';

$database = new Database();
$db = $database->getConnection();
$photo = new Photo($db);

$success = "";
$error = "";

// delete foto
if (isset($_POST['delete_photo'])) {
    $photo->id = $_POST['photo_id'];
    $stmt = $photo->getById();
    $photo_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Hapus file
    if ($photo_data && file_exists($photo_data['image_path'])) {
        unlink($photo_data['image_path']);
    }
    
    if ($photo->delete()) {
        $success = "Foto berhasil dihapus!";
    } else {
        $error = "Gagal menghapus foto!";
    }
}

// select
$query = "SELECT p.*, 
          (SELECT COUNT(*) FROM interactions WHERE photo_id = p.id AND type = 'like') as like_count,
          (SELECT COUNT(*) FROM interactions WHERE photo_id = p.id AND type = 'comment') as comment_count
          FROM photos p 
          WHERE p.user_id = :user_id 
          ORDER BY p.created_at DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $_SESSION['user_id']);
$stmt->execute();
$my_photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Gallery Foto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .photo-thumbnail {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        .action-buttons {
            white-space: nowrap;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="home.php">
                <i class="bi bi-images"></i> <img src="assets/title.png"><br><small>share and like foto </small>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="home.php">
                            <i class="bi bi-house"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="upload.php">
                            <i class="bi bi-cloud-upload"></i> Upload
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <!-- atas nav bar -->
    <!-- Content -->
    <div class="container my-4">
        <div class="row mb-4">
            <div class="col-md-8">
                <h3><i class="bi bi-speedometer2"></i> Dashboard Admin</h3>
                <p class="text-muted">Kelola semua foto yang Anda upload</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="upload.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Upload Foto Baru
                </a>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistik Card -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-center shadow-sm">
                    <div class="card-body">
                        <h3 class="text-primary"><?= count($my_photos) ?></h3>
                        <p class="mb-0 text-muted">Total Foto</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center shadow-sm">
                    <div class="card-body">
                        <h3 class="text-danger">
                            <?= array_sum(array_column($my_photos, 'like_count')) ?>
                        </h3>
                        <p class="mb-0 text-muted">Total Like</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center shadow-sm">
                    <div class="card-body">
                        <h3 class="text-success">
                            <?= array_sum(array_column($my_photos, 'comment_count')) ?>
                        </h3>
                        <p class="mb-0 text-muted">Total Komentar</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Foto -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-grid-3x3-gap"></i> Daftar Fotoku</h5>
            </div>
            <div class="card-body p-0">
                <?php if (count($my_photos) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Foto</th>
                                    <th>Judul</th>
                                    <th>Deskripsi</th>
                                    <th>Hashtag</th>
                                    <th class="text-center">Like</th>
                                    <th class="text-center">Komentar</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($my_photos as $p): ?>
                                    <tr>
                                        <td>
                                            <img src="<?= $p['image_path'] ?>" class="photo-thumbnail" alt="<?= htmlspecialchars($p['title']) ?>">
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($p['title']) ?></strong><br>
                                            <small class="text-muted"><?= date('d M Y', strtotime($p['created_at'])) ?></small>
                                        </td>
                                        <td>
                                            <small><?= htmlspecialchars(substr($p['description'], 0, 50)) ?><?= strlen($p['description']) > 50 ? '...' : '' ?></small>
                                        </td>
                                        <td>
                                            <?php if ($p['hashtag']): ?>
                                                <?php 
                                                $hashtags = explode(',', $p['hashtag']);
                                                foreach (array_slice($hashtags, 0, 2) as $tag): 
                                                    $tag = trim($tag);
                                                ?>
                                                    <span class="badge bg-info text-dark">#<?= htmlspecialchars($tag) ?></span>
                                                <?php endforeach; ?>
                                                <?php if (count($hashtags) > 2): ?>
                                                    <span class="badge bg-secondary">+<?= count($hashtags) - 2 ?></span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-danger"><?= $p['like_count'] ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary"><?= $p['comment_count'] ?></span>
                                        </td>
                                        <td class="text-center action-buttons">
                                            <a href="detail.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-info" title="Lihat Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="edit_photo.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-warning" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus foto ini?')">
                                                <input type="hidden" name="photo_id" value="<?= $p['id'] ?>">
                                                <button type="submit" name="delete_photo" class="btn btn-sm btn-danger" title="Hapus">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                        <h5 class="text-muted mt-3">Belum ada foto</h5>
                        <p class="text-muted">Upload foto pertama Anda sekarang!</p>
                        <a href="upload.php" class="btn btn-primary">
                            <i class="bi bi-cloud-upload"></i> Upload Foto
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>