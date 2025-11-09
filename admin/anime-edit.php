<?php
require_once '../config.php';

if(!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$id = $_GET['id'] ?? 0;

// Get anime details
$stmt = $pdo->prepare("SELECT * FROM anime WHERE id = ?");
$stmt->execute([$id]);
$anime = $stmt->fetch();

if(!$anime) {
    header('Location: anime-list.php');
    exit;
}

$success = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $zh_name = sanitize($_POST['zh_name']);
    $synopsis = sanitize($_POST['synopsis']);
    $type = sanitize($_POST['type']);
    $status = $_POST['status'];
    $is_hot = isset($_POST['is_hot']) ? 1 : 0;
    $is_new = isset($_POST['is_new']) ? 1 : 0;
    $slug = createSlug($title);
    
    // Handle poster (file upload or URL)
    $poster = $anime['poster'];
    $poster_url = sanitize($_POST['poster_url'] ?? '');
    
    if(!empty($poster_url)) {
        // Use URL
        $poster = $poster_url;
    } elseif(isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
        // Upload new file
        $new_poster = uploadImage($_FILES['poster']);
        if($new_poster) {
            // Delete old poster if it's a local file
            if($anime['poster'] && !filter_var($anime['poster'], FILTER_VALIDATE_URL)) {
                @unlink(UPLOAD_DIR . $anime['poster']);
            }
            $poster = $new_poster;
        } else {
            $error = 'Failed to upload image';
        }
    }
    
    if(!$error) {
        try {
            $stmt = $pdo->prepare("UPDATE anime SET title = ?, zh_name = ?, slug = ?, synopsis = ?, poster = ?, status = ?, type = ?, is_hot = ?, is_new = ?, update_date = NOW() WHERE id = ?");
            $stmt->execute([$title, $zh_name, $slug, $synopsis, $poster, $status, $type, $is_hot, $is_new, $id]);
            $success = 'Anime updated successfully!';
            
            // Refresh anime data
            $stmt = $pdo->prepare("SELECT * FROM anime WHERE id = ?");
            $stmt->execute([$id]);
            $anime = $stmt->fetch();
        } catch(PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Anime - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        
        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .admin-header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-logo {
            color: #fff;
            font-size: 24px;
            font-weight: bold;
        }
        
        .back-btn {
            color: #fff;
            text-decoration: none;
            background: rgba(255,255,255,0.2);
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .back-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 900px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .form-card {
            background: #fff;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .form-card h2 {
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #333;
        }
        
        .form-card h2 i {
            color: #667eea;
        }
        
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-group label i {
            color: #667eea;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.3s;
        }
        
        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 10px rgba(102,126,234,0.2);
        }
        
        .checkbox-group {
            display: flex;
            gap: 30px;
            margin-top: 10px;
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }
        
        .checkbox-label input {
            width: auto;
            cursor: pointer;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .btn {
            padding: 15px 35px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102,126,234,0.4);
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
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            background: #f8f9fa;
            border: 2px dashed #e0e0e0;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .file-input-label:hover {
            border-color: #667eea;
            background: #f0f4ff;
        }
        
        .file-input-label i {
            color: #667eea;
            font-size: 20px;
        }
        
        .current-poster {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .current-poster img {
            max-width: 200px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .poster-options {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .poster-option {
            flex: 1;
        }
        
        .poster-option input[type="radio"] {
            margin-right: 8px;
        }
        
        .poster-input-group {
            display: none;
        }
        
        .poster-input-group.active {
            display: block;
        }
        
        .divider {
            text-align: center;
            margin: 15px 0;
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background: #e0e0e0;
        }
        
        .divider span {
            background: #fff;
            padding: 0 15px;
            position: relative;
            color: #666;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="admin-header-content">
            <div class="admin-logo">
                <i class="fas fa-edit"></i> Edit Anime
            </div>
            <a href="anime-list.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </header>
    
    <div class="container">
        <div class="form-card">
            <h2><i class="fas fa-film"></i> Edit: <?php echo htmlspecialchars($anime['title']); ?></h2>
            
            <?php if($success): ?>
                <div class="alert success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="alert error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label><i class="fas fa-heading"></i> Title *</label>
                    <input type="text" name="title" required value="<?php echo htmlspecialchars($anime['title']); ?>">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-language"></i> Chinese Name</label>
                    <input type="text" name="zh_name" value="<?php echo htmlspecialchars($anime['zh_name']); ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Type</label>
                        <input type="text" name="type" placeholder="e.g. TV Series, Movie, OVA" value="<?php echo htmlspecialchars($anime['type']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-info-circle"></i> Status *</label>
                        <select name="status" required>
                            <option value="ongoing" <?php echo $anime['status'] == 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                            <option value="completed" <?php echo $anime['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-align-left"></i> Synopsis</label>
                    <textarea name="synopsis"><?php echo htmlspecialchars($anime['synopsis']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-image"></i> Poster Image</label>
                    
                    <div class="poster-options">
                        <label class="poster-option">
                            <input type="radio" name="poster_method" value="file" checked onchange="togglePosterMethod()">
                            Upload File
                        </label>
                        <label class="poster-option">
                            <input type="radio" name="poster_method" value="url" onchange="togglePosterMethod()">
                            Use URL
                        </label>
                    </div>
                    
                    <div id="fileInput" class="poster-input-group active">
                        <div class="file-input-wrapper">
                            <input type="file" name="poster" id="posterInput" accept="image/*">
                            <label for="posterInput" class="file-input-label">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span id="fileName">Click to upload new poster image</span>
                            </label>
                        </div>
                    </div>
                    
                    <div id="urlInput" class="poster-input-group">
                        <input type="url" name="poster_url" placeholder="https://example.com/image.jpg">
                    </div>
                    
                    <?php if($anime['poster']): ?>
                    <div class="current-poster">
                        <p style="margin-bottom: 10px; color: #666; font-size: 14px;">Current Poster:</p>
                        <img src="<?php echo filter_var($anime['poster'], FILTER_VALIDATE_URL) ? $anime['poster'] : '../uploads/'.$anime['poster']; ?>" alt="Current Poster">
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-tags"></i> Tags</label>
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_hot" <?php echo $anime['is_hot'] ? 'checked' : ''; ?>>
                            <span><i class="fas fa-fire"></i> Hot Series</span>
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_new" <?php echo $anime['is_new'] ? 'checked' : ''; ?>>
                            <span><i class="fas fa-sparkles"></i> New Series</span>
                        </label>
                    </div>
                </div>
                
                <button type="submit" class="btn">
                    <i class="fas fa-save"></i> Update Anime
                </button>
            </form>
        </div>
    </div>
    
    <script>
        document.getElementById('posterInput').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || 'Click to upload new poster image';
            document.getElementById('fileName').textContent = fileName;
        });
        
        function togglePosterMethod() {
            const method = document.querySelector('input[name="poster_method"]:checked').value;
            document.getElementById('fileInput').classList.toggle('active', method === 'file');
            document.getElementById('urlInput').classList.toggle('active', method === 'url');
        }
    </script>
</body>
</html>