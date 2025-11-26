<?php
session_start();

// Cek role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: home.php");
    exit();
}

require_once 'config/Database.php';
require_once 'classes/Photo.php';

 $error = "";
 $success = "";

// upload foto
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    $photo = new Photo($db);
    
    // Validasi file upload (jan ampe kirim web shell >:[ ) //
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            $error = "Hanya file JPG, PNG, dan GIF yang diperbolehkan!";
        } elseif ($_FILES['image']['size'] > $max_size) {
            $error = "Ukuran file maksimal 5MB!";
        } else {
            // Buat folder uploads jika belum ada/atmin malas
            $upload_dir = "uploads/";
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate nama file unik ala ala cloud image
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            // aplot file
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
                    unlink($upload_path); // Yah kasian
                }
            } else {
                $error = "Gagal mengupload file!"; // 
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
    <link rel="stylesheet" href="css/style.css">
    <style>
        .upload-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .upload-card {
            border-radius: var(--bs-border-radius-lg);
            box-shadow: var(--bs-box-shadow);
            overflow: hidden;
            transition: all var(--animation-normal) ease;
        }
        
        .upload-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }
        
        .preview-container {
            max-width: 400px;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
            border-radius: var(--bs-border-radius-lg);
            background-color: #f8f9fa;
            min-height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all var(--animation-normal) ease;
        }
        
        .preview-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
            transition: transform var(--animation-slow) ease;
        }
        
        .preview-image:hover {
            transform: scale(1.02);
        }
        
        .upload-placeholder {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        
        .upload-placeholder i {
            font-size: 4rem;
            margin-bottom: 15px;
            color: #dee2e6;
        }
        
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }
        
        .file-input-wrapper input[type=file] {
            position: absolute;
            left: -9999px;
        }
        
        .file-input-label {
            display: block;
            padding: 12px 15px;
            background-color: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: var(--bs-border-radius);
            cursor: pointer;
            transition: all var(--animation-normal) ease;
            text-align: center;
        }
        
        .file-input-label:hover {
            background-color: #e9ecef;
            border-color: var(--bs-primary);
        }
        
        .file-input-label.has-file {
            border-color: var(--bs-success);
            background-color: rgba(var(--bs-success-rgb), 0.1);
        }
        
        .form-floating {
            margin-bottom: 1.5rem;
        }
        
        .form-control:focus {
            border-color: var(--bs-primary);
            box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.25);
            transform: translateY(-2px);
        }
        
        .btn-upload {
            background-color: var(--bs-primary);
            border-color: var(--bs-primary);
            color: white;
            padding: 12px 20px;
            font-weight: 500;
            transition: all var(--animation-normal) ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-upload::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width var(--animation-slow), height var(--animation-slow);
        }
        
        .btn-upload:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn-upload:hover {
            background-color: var(--bs-primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(var(--bs-primary-rgb), 0.3);
        }
        
        .progress-container {
            height: 5px;
            background-color: #e9ecef;
            border-radius: 5px;
            margin-top: 10px;
            overflow: hidden;
            display: none;
        }
        
        .progress-bar {
            height: 100%;
            background-color: var(--bs-primary);
            width: 0;
            transition: width var(--animation-slow) ease;
        }
        
        .hashtag-input {
            position: relative;
        }
        
        .hashtag-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background-color: white;
            border: 1px solid #dee2e6;
            border-top: none;
            border-radius: 0 0 var(--bs-border-radius) var(--bs-border-radius);
            max-height: 200px;
            overflow-y: auto;
            z-index: 10;
            display: none;
        }
        
        .hashtag-suggestion {
            padding: 8px 12px;
            cursor: pointer;
            transition: background-color var(--animation-fast) ease;
        }
        
        .hashtag-suggestion:hover {
            background-color: #f8f9fa;
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
            animation: slideInRight var(--animation-normal) ease;
        }
        
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .file-info {
            margin-top: 10px;
            font-size: 0.85rem;
            color: #6c757d;
            display: none;
        }
        
        .file-name {
            font-weight: 500;
            color: var(--bs-primary);
        }
        
        .file-size {
            margin-left: 10px;
        }
        
        .remove-file {
            color: var(--bs-danger);
            cursor: pointer;
            margin-left: 10px;
            transition: all var(--animation-fast) ease;
        }
        
        .remove-file:hover {
            transform: scale(1.2);
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all var(--animation-normal) ease;
        }
        
        .loading-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(var(--bs-primary-rgb), 0.3);
            border-radius: 50%;
            border-top-color: var(--bs-primary);
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="bg-light">
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-spinner"></div>
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
    <div class="container my-5">
        <div class="upload-container">
            <div class="upload-card">
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

                    <form method="POST" enctype="multipart/form-data" id="uploadForm">
                        <!-- Preview Image -->
                        <div class="preview-container mb-4" id="previewContainer">
                            <div class="upload-placeholder" id="uploadPlaceholder">
                                <i class="bi bi-image"></i>
                                <p>Preview gambar akan muncul di sini</p>
                            </div>
                            <img id="imagePreview" class="preview-image d-none" alt="Preview">
                        </div>

                        <div class="file-input-wrapper mb-3">
                            <label for="imageInput" class="file-input-label" id="fileInputLabel">
                                <i class="bi bi-cloud-upload me-2"></i> Pilih Foto
                            </label>
                            <input type="file" name="image" id="imageInput" accept="image/*" required>
                            <div class="file-info" id="fileInfo">
                                <span class="file-name" id="fileName"></span>
                                <span class="file-size" id="fileSize"></span>
                                <span class="remove-file" id="removeFile">
                                    <i class="bi bi-x-circle"></i>
                                </span>
                            </div>
                        </div>
                        <small class="text-muted d-block mb-3">Format: JPG, PNG, GIF. Maksimal 5MB. [TIDAK MENERIMA WEBSHELL, BACKDOOR]</small>

                        <div class="form-floating mb-3">
                            <input type="text" name="title" class="form-control" id="titleInput" placeholder="Judul Foto" required>
                            <label for="titleInput">Judul Foto *</label>
                        </div>

                        <div class="form-floating mb-3">
                            <textarea name="description" class="form-control" id="descriptionInput" placeholder="Deskripsi" style="height: 100px"></textarea>
                            <label for="descriptionInput">Deskripsi</label>
                        </div>

                        <div class="form-floating mb-3 hashtag-input">
                            <input type="text" name="hashtag" class="form-control" id="hashtagInput" placeholder="Hashtag" value="">
                            <label for="hashtagInput">Hashtag</label>
                            <div class="hashtag-suggestions" id="hashtagSuggestions">
                                <div class="hashtag-suggestion">fyp</div>
                                <div class="hashtag-suggestion">photography</div>
                                <div class="hashtag-suggestion">nature</div>
                                <div class="hashtag-suggestion">travel</div>
                                <div class="hashtag-suggestion">portrait</div>
                            </div>
                        </div>
                        <small class="text-muted d-block mb-4">Contoh: fyp, freind, coding (pisahkan dengan koma)</small>

                        <div class="progress-container" id="progressContainer">
                            <div class="progress-bar" id="progressBar"></div>
                        </div>

                        <button type="submit" class="btn btn-upload w-100 mb-3" id="uploadButton">
                            <i class="bi bi-upload"></i> Upload Foto
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview gambar sebelum upload
        const imageInput = document.getElementById('imageInput');
        const imagePreview = document.getElementById('imagePreview');
        const uploadPlaceholder = document.getElementById('uploadPlaceholder');
        const fileInputLabel = document.getElementById('fileInputLabel');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const removeFile = document.getElementById('removeFile');
        const uploadForm = document.getElementById('uploadForm');
        const progressContainer = document.getElementById('progressContainer');
        const progressBar = document.getElementById('progressBar');
        const uploadButton = document.getElementById('uploadButton');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const hashtagInput = document.getElementById('hashtagInput');
        const hashtagSuggestions = document.getElementById('hashtagSuggestions');
        
        // Image preview functionality
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Check file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    showToast('Hanya file JPG, PNG, dan GIF yang diperbolehkan!', 'danger');
                    resetFileInput();
                    return;
                }
                
                // Check file size (5MB)
                const maxSize = 5 * 1024 * 1024;
                if (file.size > maxSize) {
                    showToast('Ukuran file maksimal 5MB!', 'danger');
                    resetFileInput();
                    return;
                }
                
                // Show file info
                fileName.textContent = file.name;
                fileSize.textContent = formatFileSize(file.size);
                fileInfo.style.display = 'block';
                fileInputLabel.classList.add('has-file');
                fileInputLabel.innerHTML = '<i class="bi bi-check-circle me-2"></i> File Dipilih';
                
                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.classList.remove('d-none');
                    uploadPlaceholder.classList.add('d-none');
                }
                reader.readAsDataURL(file);
            }
        });
        
        // Remove file
        removeFile.addEventListener('click', function() {
            resetFileInput();
        });
        
        function resetFileInput() {
            imageInput.value = '';
            imagePreview.classList.add('d-none');
            uploadPlaceholder.classList.remove('d-none');
            fileInputLabel.classList.remove('has-file');
            fileInputLabel.innerHTML = '<i class="bi bi-cloud-upload me-2"></i> Pilih Foto';
            fileInfo.style.display = 'none';
        }
        
        // Format file size
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // Hashtag suggestions
        hashtagInput.addEventListener('focus', function() {
            hashtagSuggestions.style.display = 'block';
        });
        
        hashtagInput.addEventListener('blur', function() {
            setTimeout(() => {
                hashtagSuggestions.style.display = 'none';
            }, 200);
