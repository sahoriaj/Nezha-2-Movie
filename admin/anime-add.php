<?php
require_once '../config.php';

if(!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
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
    
    // Handle poster - either file upload or URL
    $poster = '';
    $poster_url = sanitize($_POST['poster_url'] ?? '');
    
    // Priority: File upload > URL
    if(isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
        $poster = uploadImage($_FILES['poster']);
        if(!$poster) {
            $error = 'Failed to upload image';
        }
    } elseif(!empty($poster_url)) {
        // Validate URL
        if(filter_var($poster_url, FILTER_VALIDATE_URL)) {
            $poster = $poster_url;
        } else {
            $error = 'Invalid poster URL';
        }
    }
    
    if(!$error) {
        try {
            $stmt = $pdo->prepare("INSERT INTO anime (title, zh_name, slug, synopsis, poster, status, type, is_hot, is_new, update_date) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$title, $zh_name, $slug, $synopsis, $poster, $status, $type, $is_hot, $is_new]);
            $success = 'Anime added successfully!';
            
            // Reset form
            $_POST = array();
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
    <title>Add Anime - Admin</title>
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
        
        .poster-options {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .poster-options h3 {
            font-size: 16px;
            margin-bottom: 15px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .option-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .tab-btn {
            padding: 10px 20px;
            background: #fff;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .tab-btn:hover {
            border-color: #667eea;
            color: #667eea;
        }
        
        .tab-btn.active {
            background: #667eea;
            color: #fff;
            border-color: #667eea;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .help-text {
            font-size: 13px;
            color: #666;
            margin-top: 8px;
            display: flex;
            align-items: start;
            gap: 8px;
        }
        
        .help-text i {
            color: #2196f3;
            margin-top: 2px;
        }
        
        .preview-container {
            margin-top: 15px;
            text-align: center;
        }
        
        .preview-container img {
            max-width: 200px;
            max-height: 280px;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
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
                <i class="fas fa-plus-circle"></i> Add New Anime
            </div>
            <a href="index.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </header>
    
    <div class="container">
        <div class="form-card">
            <h2><i class="fas fa-film"></i> Anime Information</h2>
            
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
                    <input type="text" name="title" required value="<?php echo $_POST['title'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-language"></i> Chinese Name</label>
                    <input type="text" name="zh_name" value="<?php echo $_POST['zh_name'] ?? ''; ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Type</label>
                        <input type="text" name="type" placeholder="e.g. TV Series, Movie, OVA" value="<?php echo $_POST['type'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-info-circle"></i> Status *</label>
                        <select name="status" required>
                            <option value="ongoing" <?php echo (isset($_POST['status']) && $_POST['status'] == 'ongoing') ? 'selected' : ''; ?>>Ongoing</option>
                            <option value="completed" <?php echo (isset($_POST['status']) && $_POST['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-align-left"></i> Synopsis</label>
                    <textarea name="synopsis"><?php echo $_POST['synopsis'] ?? ''; ?></textarea>
                </div>
                
                <div class="poster-options">
                    <h3><i class="fas fa-image"></i> Poster Image</h3>
                    
                    <div class="option-tabs">
                        <button type="button" class="tab-btn active" onclick="switchTab('upload')">
                            <i class="fas fa-upload"></i> Upload File
                        </button>
                        <button type="button" class="tab-btn" onclick="switchTab('url')">
                            <i class="fas fa-link"></i> Use URL
                        </button>
                    </div>
                    
                    <!-- Upload Tab -->
                    <div id="upload-tab" class="tab-content active">
                        <div class="file-input-wrapper">
                            <input type="file" name="poster" id="posterInput" accept="image/*" onchange="previewFile(this)">
                            <label for="posterInput" class="file-input-label">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span id="fileName">Click to upload poster image</span>
                            </label>
                        </div>
                        <div class="help-text">
                            <i class="fas fa-info-circle"></i>
                            <span>Upload an image file (JPG, PNG, GIF, WebP). Recommended size: 300x420px</span>
                        </div>
                    </div>
                    
                    <!-- URL Tab -->
                    <div id="url-tab" class="tab-content">
                        <input type="url" 
                               name="poster_url" 
                               id="posterUrl" 
                               placeholder="https://example.com/poster.jpg"
                               onchange="previewUrl(this)"
                               style="width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 15px;">
                        <div class="help-text">
                            <i class="fas fa-info-circle"></i>
                            <span>Enter a direct link to the poster image. Make sure the URL ends with .jpg, .png, .gif, or .webp</span>
                        </div>
                    </div>
                    
                    <!-- Preview -->
                    <div id="preview-container" class="preview-container" style="display: none;">
                        <p style="font-size: 13px; color: #666; margin-bottom: 10px;">Preview:</p>
                        <img id="preview-image" src="" alt="Preview">
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-tags"></i> Tags</label>
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_hot" <?php echo isset($_POST['is_hot']) ? 'checked' : ''; ?>>
                            <span><i class="fas fa-fire"></i> Hot Series</span>
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_new" <?php echo isset($_POST['is_new']) ? 'checked' : ''; ?>>
                            <span><i class="fas fa-sparkles"></i> New Series</span>
                        </label>
                    </div>
                </div>
                
                <button type="submit" class="btn">
                    <i class="fas fa-save"></i> Add Anime
                </button>
            </form>
        </div>
    </div>
    
    <script>
        function switchTab(tab) {
            // Update buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.closest('.tab-btn').classList.add('active');
            
            // Update content
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById(tab + '-tab').classList.add('active');
            
            // Clear preview
            document.getElementById('preview-container').style.display = 'none';
            
            // Clear the other input
            if(tab === 'upload') {
                document.getElementById('posterUrl').value = '';
            } else {
                document.getElementById('posterInput').value = '';
                document.getElementById('fileName').textContent = 'Click to upload poster image';
            }
        }
        
        function previewFile(input) {
            const fileName = input.files[0]?.name || 'Click to upload poster image';
            document.getElementById('fileName').textContent = fileName;
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview-image').src = e.target.result;
                    document.getElementById('preview-container').style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        function previewUrl(input) {
            const url = input.value.trim();
            if(url) {
                // Validate URL format
                try {
                    new URL(url);
                    document.getElementById('preview-image').src = url;
                    document.getElementById('preview-container').style.display = 'block';
                    
                    // Handle image load error
                    document.getElementById('preview-image').onerror = function() {
                        alert('Failed to load image from URL. Please check if the URL is correct and accessible.');
                        document.getElementById('preview-container').style.display = 'none';
                    };
                } catch(e) {
                    alert('Please enter a valid URL');
                    document.getElementById('preview-container').style.display = 'none';
                }
            } else {
                document.getElementById('preview-container').style.display = 'none';
            }
        }
    </script>
</body>
</html>