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
        .detail-image {
            width: 100%;
            border-radius: 8px;
        }
        .comment-box {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 10px;
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
    <div class="container my-4">
        <div class="photo-detail">
            <div class="card shadow-sm">
                <img src="<?= $photo_data['image_path'] ?>" class="card-img-top detail-image" alt="<?= htmlspecialchars($photo_data['title']) ?>">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h3><?= htmlspecialchars($photo_data['title']) ?></h3>
                            <p class="text-muted mb-2">
                                <i class="bi bi-person"></i> <?= $photo_data['username'] ?>
                                <small class="ms-2"><?= date('d M Y', strtotime($photo_data['created_at'])) ?></small>
                            </p>
                        </div>
                        <?php if ($_SESSION['user_id'] == $photo_data['user_id'] && $_SESSION['role'] == 'admin'): ?>
                            <form method="POST" onsubmit="return confirm('Yakin ingin menghapus foto ini?')">
                                <button type="submit" name="delete" class="btn btn-danger btn-sm">
                                    <i class="bi bi-trash"></i> Hapus
                                </button>
                            </form>
                        <?php endif; ?>
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
                                <a href="home.php?hashtag=<?= urlencode($tag) ?>" class="badge bg-info text-dark me-1">
                                    #<?= htmlspecialchars($tag) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Like Button -->
                    <div class="d-flex gap-2 mb-4">
                        <form method="POST" class="d-inline">
                            <button type="submit" name="like" class="btn <?= $has_liked ? 'btn-danger' : 'btn-outline-danger' ?>">
                                <i class="bi bi-heart-fill"></i> 
                                <?= $has_liked ? 'Unlike' : 'Like' ?> (<?= $like_count ?>)
                            </button>
                        </form>
                    </div>

                    <!-- Form Komentar -->
                    <div class="mb-4">
                        <h5><i class="bi bi-chat-left-text"></i> Komentar (<?= count($comments) ?>)</h5>
                        <form method="POST" class="mb-3">
                            <div class="input-group">
                                <input type="text" name="comment_text" class="form-control" placeholder="Tulis komentar..." required>
                                <button type="submit" name="comment" class="btn btn-primary">
                                    <i class="bi bi-send"></i> Kirim
                                </button>
                            </div>
                        </form>

                        <!-- Daftar Komentar -->
                        <?php foreach ($comments as $c): ?>
                            <div class="comment-box">
                                <strong><?= $c['username'] ?></strong>
                                <small class="text-muted"><?= date('d M Y H:i', strtotime($c['created_at'])) ?></small>
                                <p class="mb-0 mt-1"><?= htmlspecialchars($c['comment_text']) ?></p>
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
</body>
</html>