<?php
// config.php - Database Configuration

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'novadong_lol');
define('DB_PASS', 'mfu@jER[8J=~');
define('DB_NAME', 'novadong_lol');

// Site Configuration
define('SITE_URL', 'https://www.novadonghua.top');
define('SITE_NAME', 'Nova Donghua');
define('UPLOAD_DIR', 'uploads/');

// Database Connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Helper Functions
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function createSlug($string) {
    $slug = preg_replace('/[^A-Za-z0-9-]+/', '-', strtolower($string));
    return trim($slug, '-');
}

function uploadImage($file) {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $filename = $file['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) {
        return false;
    }
    
    $newname = uniqid() . '.' . $ext;
    $destination = UPLOAD_DIR . $newname;
    
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return $newname;
    }
    
    return false;
}

// Session Start
session_start();
?>