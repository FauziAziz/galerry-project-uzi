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
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
            overflow: hidden;
            position: relative;
        }
        
        .photo-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }
        
        .photo-img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            transition: transform 0.5s;
        }
        
        .photo-card:hover .photo-img {
            transform: scale(1.05);
        }
        
        .photo-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0) 100%);
            padding: 20px 15px 15px;
            color: white;
            transform: translateY(100%);
            transition: transform 0.3s;
        }
        
        .photo-card:hover .photo-overlay {
            transform: translateY(0);
        }
        
        .hashtag-badge {
            font-size: 0.85rem;
            transition: all 0.2s;
        }
        
        .hashtag-badge:hover {
            transform: scale(1.1);
            z-index: 10;
        }
        
        .filter-form {
            transition: all 0.3s;
        }
        
        .filter-form:focus-within {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .section-title {
            position: relative;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: var(--bs-primary);
            transition: width 0.3s;
        }
        
        .section-title:hover::after {
            width: 100px;
        }
        
        .photo-stats {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 10px;
        }
        
        .photo-stat {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
            color: var(--bs-secondary);
            transition: all 0.2s;
        }
        
        .photo-stat:hover {
            color: var(--bs-primary);
            transform: translateY(-2px);
        }
        
        .fab {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: var(--bs-primary);
            color: white;
            border: none;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s;
            z-index: 1000;
        }
        
        .fab:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
        }
        
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }
        
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    </style>
</head>
<body class="bg-light">
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay" style="display: none;">
        <div class="loading-spinner"></div>
    </div>

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
        <div class="row mb-4 fade-in">
            <div class="col-md-6">
                <h4 class="section-title">
                    <i class="bi bi-grid-3x3-gap"></i> 
                    <?= $filter_hashtag ? "Foto dengan #{$filter_hashtag}" : "Semua Foto" ?>
                </h4>
            </div>
            <div class="col-md-6">
                <form method="GET" class="filter-form d-flex">
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
        <div class="photo-grid">
            <?php if (count($photos) > 0): ?>
                <?php foreach ($photos as $index => $p): ?>
                    <div class="photo-grid-item fade-in" style="animation-delay: <?= $index * 0.05 ?>s;">
                        <div class="card photo-card shadow-sm" onclick="window.location.href='detail.php?id=<?= $p['id'] ?>'">
                            <div class="position-relative overflow-hidden">
                                <img src="<?= $p['image_path'] ?>" class="card-img-top photo-img" alt="<?= htmlspecialchars($p['title']) ?>">
                                <div class="photo-overlay">
                                    <h6 class="mb-1"><?= htmlspecialchars($p['title']) ?></h6>
                                    <p class="mb-2 small"><i class="bi bi-person"></i> <?= $p['username'] ?></p>
                                    
                                    <div class="photo-stats text-white">
                                        <div class="photo-stat">
                                            <i class="bi bi-heart-fill"></i> <?= $p['like_count'] ?>
                                        </div>
                                        <div class="photo-stat">
                                            <i class="bi bi-chat-fill"></i> <?= $p['comment_count'] ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
                    <div class="alert alert-info text-center fade-in">
                        <i class="bi bi-info-circle"></i> Belum ada foto yang diupload.
                        <?php if ($_SESSION['role'] == 'admin'): ?>
                            <a href="upload.php">Upload foto pertama?</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Floating Action Button for Admin Upload -->
    <?php if ($_SESSION['role'] == 'admin'): ?>
        <a href="upload.php" class="fab">
            <i class="bi bi-plus-lg"></i>
        </a>
    <?php endif; ?>

    <!-- Toast Container -->
    <div class="toast-container">
        <!-- Toast notifications will be added here dynamically -->
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show loading overlay when navigating
        document.querySelectorAll('a[href^="detail.php"], a[href^="upload.php"]').forEach(link => {
            link.addEventListener('click', function() {
                document.getElementById('loadingOverlay').style.display = 'flex';
            });
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Add animation to elements when they come into view
        const observerOptions = {
            root: null,
            rootMargin: '0px',
            threshold: 0.1
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.photo-grid-item').forEach(el => {
            observer.observe(el);
        });
    </script>
</body>
</html>
