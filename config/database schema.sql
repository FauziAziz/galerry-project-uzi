-- Database: gallery_foto
CREATE DATABASE IF NOT EXISTS gallery_foto;
USE gallery_foto;

-- Tabel 1: users (untuk login admin dan user)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel 2: photos (untuk menyimpan foto yang diupload admin)
CREATE TABLE photos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    hashtag VARCHAR(255),
    image_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel 3: interactions (untuk menyimpan like dan komentar user)
CREATE TABLE interactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    photo_id INT NOT NULL,
    user_id INT NOT NULL,
    type ENUM('like', 'comment') NOT NULL,
    comment_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Relasi menggunakan ALTER TABLE (sesuai requirement)
ALTER TABLE photos 
ADD CONSTRAINT fk_photo_user 
FOREIGN KEY (user_id) REFERENCES users(id) 
ON DELETE CASCADE;

ALTER TABLE interactions 
ADD CONSTRAINT fk_interaction_photo 
FOREIGN KEY (photo_id) REFERENCES photos(id) 
ON DELETE CASCADE;

ALTER TABLE interactions 
ADD CONSTRAINT fk_interaction_user 
FOREIGN KEY (user_id) REFERENCES users(id) 
ON DELETE CASCADE;

-- Data dummy untuk testing
INSERT INTO users (username, password, email, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@gallery.com', 'admin'),
('user1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user1@gallery.com', 'user');
-- Password untuk testing: password