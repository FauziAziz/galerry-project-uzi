<?php
session_start();

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

// Ambil detail foto
 $photo->id = isset($_GET['id']) ? $_GET['id'] : die('ID foto tidak ditemukan');
 $stmt = $photo->getById();
 $photo_data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$photo_data) {
    die('Foto tidak ditemukan');
}

// Proses like
if (isset($_POST['like'])) {
    $interaction->photo_id = $photo->id;
    $interaction->user_id = $_SESSION['user_id'];
    $interaction->type = 'like';
    
    // Cek sudah like atau belum
    if ($interaction->hasLiked()) {
        $interaction->unlike();
    } else {
        $interaction->create();
    }
    
    header("Location: detail.php?id=" . $photo->id);
    exit();
}

// Proses komentar
if (isset($_POST['comment'])) {
    $interaction->photo_id = $photo->id;
    $interaction->user_id = $_SESSION['user_id'];
    $interaction->type = 'comment';
    $interaction->comment_text = $_POST['comment_text'];
    
    if ($interaction->create()) {
        header("Location: detail.php?id=" . $photo->id);
        exit();
    }
}

// Proses delete foto (hanya admin yang upload)
if (isset($_POST['delete']) && $_SESSION['user_id'] == $photo_data['user_id'] && $_SESSION['role'] == 'admin') {
    // Hapus file
    if (file_exists($photo_data['image_path'])) {
        unlink($photo_data['image_path']);
    }
    
    if ($photo->delete()) {
        header("Location: home.php");
        exit();
    }
}

// Cek apakah user sudah like
 $interaction->photo_id = $photo->id;
 $interaction->user_id = $_SESSION['user_id'];
 $has_liked = $interaction->hasLiked();

// Ambil komentar
 $comments_stmt = $interaction->getCommentsByPhoto();
 $comments = $comments_stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung total like
 $like_query = "SELECT COUNT(*) as total FROM interactions WHERE photo_id = :photo_id AND type = 'like'";
 $like_stmt = $db->prepare($like_query);
 $like_stmt->bindParam(":photo_id", $photo->id);
 $like_stmt->execute();
 $like_count = $like_stmt->fetch(PDO::FETCH_ASSOC)['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($photo_data['title']) ?> - Gallery Foto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .photo-detail {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .detail-image-container {
            position: relative;
            overflow: hidden;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .detail-image {
            width: 100%;
            transition: transform 0.5s;
            cursor: zoom-in;
        }
        
        .detail-image:hover {
            transform: scale(1.05);
        }
        
        .photo-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .action-btn {
            padding: 8px 16px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }
        
        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .like-btn.liked {
            background-color: var(--bs-danger);
            color: white;
        }
        
        .comment-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid var(--bs-primary);
            transition: all 0.3s;
            animation: fadeIn 0.5s;
        }
        
        .comment-box:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .comment-form {
            position: relative;
            margin-bottom: 20px;
        }
        
        .comment-input {
            padding-right: 50px;
        }
        
        .comment-submit {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
        }
        
        .hashtag-pill {
            display: inline-block;
            padding: 5px 12px;
            background-color: rgba(var(--bs-info-rgb), 0.1);
            color: var(--bs-info);
            border-radius: 20px;
            font-size: 0.85rem;
            margin-right: 5px;
            margin-bottom: 5px;
            transition: all 0.2s;
        }
        
        .hashtag-pill:hover {
            background-color: rgba(var(--bs-info-rgb), 0.2);
            transform: translateY(-2px);
        }
        
        .photo-info {
            margin-bottom: 20px;
        }
        
        .photo-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .photo-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--bs-light);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }
        
        .user-avatar:hover {
            transform: scale(1.1);
            border-color: var(--bs-primary);
        }
        
        .zoom-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1050;
            cursor: zoom-out;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }
        
        .zoom-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .zoom-image {
            max-width: 90%;
            max-height: 90%;
            transform: scale(0.9);
            transition: transform 0.3s;
        }
        
        .zoom-overlay.active .zoom-image {
            transform: scale(1);
        }
        
        .delete-btn {
            transition: all 0.3s;
        }
        
        .delete-btn:hover {
            transform: scale(1.1);
        }
        
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }
        
        .toast {
            min-width: 250px;
            border-radius: var(--bs-border-radius);
            box-shadow: var(--bs-box-shadow);
            animation: slideInRight 0.3s;
        }
        
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
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
    
    <!-- Image Zoom Overlay -->
    <div id="zoomOverlay" class="zoom-overlay">
        <img src="<?= $photo_data['image_path'] ?>" class="zoom-image" alt="<?= htmlspecialchars($photo_data['title']) ?>">
    </div>

    <!-- Toast Container -->
    <div class="toast-container">
        <!-- Toast notifications will be added here dynamically -->
    </div>

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
    <div class="container my-4">
        <div class="photo-detail">
            <div class="card shadow-sm fade-in">
                <div class="detail-image-container">
                    <img src="<?= $photo_data['image_path'] ?>" class="detail-image" alt="<?= htmlspecialchars($photo_data['title']) ?>">
                </div>
                <div class="card-body">
                    <div class="photo-info">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h3 class="photo-title"><?= htmlspecialchars($photo_data['title']) ?></h3>
                                <div class="photo-meta">
                                    <img src="https://picsum.photos/seed/<?= $photo_data['username'] ?>/40/40.jpg" alt="<?= $photo_data['username'] ?>" class="user-avatar">
                                    <div>
                                        <div><?= $photo_data['username'] ?></div>
                                        <small><?= date('d M Y', strtotime($photo_data['created_at'])) ?></small>
                                    </div>
                                </div>
                            </div>
                            <?php if ($_SESSION['user_id'] == $photo_data['user_id'] && $_SESSION['role'] == 'admin'): ?>
                                <form method="POST" onsubmit="return confirm('Yakin ingin menghapus foto ini?')">
                                    <button type="submit" name="delete" class="btn btn-danger btn-sm delete-btn">
                                        <i class="bi bi-trash"></i> Hapus
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($photo_data['description']): ?>
                        <p class="mb-3"><?= nl2br(htmlspecialchars($photo_data['description'])) ?></p>
                    <?php endif; ?>

                    <?php if ($photo_data['hashtag']): ?>
                        <div class="mb-3">
                            <?php 
                            $hashtags = explode(',', $photo_data['hashtag']);
                            foreach ($hashtags as $tag): 
                                $tag = trim($tag);
                            ?>
                                <a href="home.php?hashtag=<?= urlencode($tag) ?>" class="hashtag-pill">
                                    #<?= htmlspecialchars($tag) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Like Button -->
                    <div class="photo-actions">
                        <form method="POST" class="d-inline">
                            <button type="submit" name="like" class="btn action-btn like-btn <?= $has_liked ? 'liked' : 'btn-outline-danger' ?>">
                                <i class="bi bi-heart-fill"></i> 
                                <?= $has_liked ? 'Unlike' : 'Like' ?> (<?= $like_count ?>)
                            </button>
                        </form>
                    </div>

                    <!-- Form Komentar -->
                    <div class="mb-4">
                        <h5><i class="bi bi-chat-left-text"></i> Komentar (<?= count($comments) ?>)</h5>
                        <form method="POST" class="comment-form">
                            <div class="input-group">
                                <input type="text" name="comment_text" class="form-control comment-input" placeholder="Tulis komentar..." required>
                                <button type="submit" name="comment" class="btn btn-primary comment-submit">
                                    <i class="bi bi-send"></i>
                                </button>
                            </div>
                        </form>

                        <!-- Daftar Komentar -->
                        <?php foreach ($comments as $index => $c): ?>
                            <div class="comment-box" style="animation-delay: <?= $index * 0.1 ?>s;">
                                <div class="d-flex">
                                    <img src="https://picsum.photos/seed/<?= $c['username'] ?>/40/40.jpg" alt="<?= $c['username'] ?>" class="user-avatar me-3">
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between">
                                            <strong><?= $c['username'] ?></strong>
                                            <small class="text-muted"><?= date('d M Y H:i', strtotime($c['created_at'])) ?></small>
                                        </div>
                                        <p class="mb-0 mt-1"><?= htmlspecialchars($c['comment_text']) ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (count($comments) == 0): ?>
                            <p class="text-muted text-center py-3">Belum ada komentar. Jadilah yang pertama!</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Image zoom functionality
        const detailImage = document.querySelector('.detail-image');
        const zoomOverlay = document.getElementById('zoomOverlay');
        
        detailImage.addEventListener('click', function() {
            zoomOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
        
        zoomOverlay.addEventListener('click', function() {
            zoomOverlay.classList.remove('active');
            document.body.style.overflow = 'auto';
        });
        
        // Show loading overlay when submitting forms
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
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
        
        // Like button animation
        const likeButton = document.querySelector('button[name="like"]');
        if (likeButton) {
            likeButton.addEventListener('click', function() {
                this.classList.add('animate-pulse');
                setTimeout(() => {
                    this.classList.remove('animate-pulse');
                }, 500);
            });
        }
        
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Add animation to comments when they come into view
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
        
        document.querySelectorAll('.comment-box').forEach(el => {
            observer.observe(el);
        });
    </script>
</body>
</html>
