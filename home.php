<?php
session_start();

// Cek apakah sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/Database.php';
require_once 'classes/Photo.php';
require_once 'classes/Interaction.php';

$database = new Database();
$db = $database->getConnection();

$photo = new Photo($db);
$interaction = new Interaction($db);

// Ambil filter hashtag jika ada
$filter_hashtag = isset($_GET['hashtag']) ? $_GET['hashtag'] : null;

// Ambil semua foto
$stmt = $photo->getAll($filter_hashtag);
$photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Gallery Foto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .photo-card {
            transition: transform 0.2s;
            cursor: pointer;
        }
        .photo-card:hover {
            transform: translateY(-5px);
        }
        .photo-img {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }
        .hashtag-badge {
            font-size: 0.85rem;
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
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="upload.php">
                                <i class="bi bi-cloud-upload"></i> Upload Foto
                            </a>
                            <a class="nav-link" href="dashboard.php">
                                <i class="bi bi-menu-button-wide"></i>Dashboard
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?= $_SESSION['username'] ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">Role: <?= ucfirst($_SESSION['role']) ?></a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Content -->
    <div class="container my-4">
        <!-- Filter Section -->
        <div class="row mb-4">
            <div class="col-md-6">
                <h4>
                    <i class="bi bi-grid-3x3-gap"></i> 
                    <?= $filter_hashtag ? "Foto dengan #{$filter_hashtag}" : "Semua Foto" ?>
                </h4>
            </div>
            <div class="col-md-6">
                <form method="GET" class="d-flex">
                    <input type="text" name="hashtag" class="form-control me-2" 
                           placeholder="Filter hashtag..." value="<?= $filter_hashtag ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i>
                    </button>
                    <?php if ($filter_hashtag): ?>
                        <a href="home.php" class="btn btn-secondary ms-2">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Photos Grid -->
        <div class="row g-3">
            <?php if (count($photos) > 0): ?>
                <?php foreach ($photos as $p): ?>
                    <div class="col-md-3">
                        <div class="card photo-card shadow-sm" onclick="window.location.href='detail.php?id=<?= $p['id'] ?>'">
                            <img src="<?= $p['image_path'] ?>" class="card-img-top photo-img" alt="<?= htmlspecialchars($p['title']) ?>">
                            <div class="card-body">
                                <h6 class="card-title"><?= htmlspecialchars($p['title']) ?></h6>
                                <p class="card-text text-muted small">
                                    <i class="bi bi-person"></i> <?= $p['username'] ?>
                                </p>
                                
                                <?php if ($p['hashtag']): ?>
                                    <?php 
                                    $hashtags = explode(',', $p['hashtag']);
                                    foreach ($hashtags as $tag): 
                                        $tag = trim($tag);
                                    ?>
                                        <a href="home.php?hashtag=<?= urlencode($tag) ?>" 
                                           class="badge bg-info text-dark hashtag-badge me-1" 
                                           onclick="event.stopPropagation()">
                                            #<?= htmlspecialchars($tag) ?>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="bi bi-heart-fill text-danger"></i> <?= $p['like_count'] ?>
                                        <i class="bi bi-chat-fill text-primary ms-2"></i> <?= $p['comment_count'] ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <i class="bi bi-info-circle"></i> Belum ada foto yang diupload.
                        <?php if ($_SESSION['role'] == 'admin'): ?>
                            <a href="upload.php">Upload foto pertama?</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>