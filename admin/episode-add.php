<?php
require_once '../config.php';

if(!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Get all anime for dropdown
$animeList = $pdo->query("SELECT id, title FROM anime ORDER BY title ASC")->fetchAll();

$success = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $anime_id = (int)$_POST['anime_id'];
    $episode_number = (int)$_POST['episode_number'];
    $episode_title = sanitize($_POST['episode_title']);
    
    // Create episode slug
    $anime = $pdo->prepare("SELECT title, slug FROM anime WHERE id = ?");
    $anime->execute([$anime_id]);
    $anime = $anime->fetch();
    
    $slug = $anime['slug'] . '-episode-' . $episode_number;
    
    // Servers data
    $server_names = $_POST['server_name'] ?? [];
    $server_urls = $_POST['server_url'] ?? [];
    $server_qualities = $_POST['server_quality'] ?? [];
    
    if(count($server_names) === 0) {
        $error = 'Please add at least one server';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Insert episode
            $stmt = $pdo->prepare("INSERT INTO episodes (anime_id, episode_number, title, slug) VALUES (?, ?, ?, ?)");
            $stmt->execute([$anime_id, $episode_number, $episode_title, $slug]);
            $episode_id = $pdo->lastInsertId();
            
            // Insert servers
            $serverStmt = $pdo->prepare("INSERT INTO servers (episode_id, server_name, server_url, quality, is_default) VALUES (?, ?, ?, ?, ?)");
            foreach($server_names as $index => $name) {
                if(!empty($name) && !empty($server_urls[$index])) {
                    $is_default = ($index === 0) ? 1 : 0;
                    $serverStmt->execute([
                        $episode_id,
                        $name,
                        $server_urls[$index],
                        $server_qualities[$index] ?? '',
                        $is_default
                    ]);
                }
            }
            
            // Update anime update_date
            $pdo->prepare("UPDATE anime SET update_date = NOW() WHERE id = ?")->execute([$anime_id]);
            
            $pdo->commit();
            $success = 'Episode added successfully!';
            $_POST = array();
        } catch(PDOException $e) {
            $pdo->rollBack();
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Pre-select anime if passed in URL
$selected_anime = $_GET['anime_id'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Episode - Admin</title>
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
        }
        
        .alert.error {
            background: #f8d7da;
            color: #721c24;
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
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 10px rgba(102,126,234,0.2);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .servers-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .servers-section h3 {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .server-item {
            background: #fff;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .server-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .server-header h4 {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .remove-server {
            background: #ff4b5c;
            color: #fff;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .remove-server:hover {
            background: #ff3344;
            transform: scale(1.1);
        }
        
        .add-server-btn {
            background: linear-gradient(135deg, #4ECDC4 0%, #44A08D 100%);
            color: #fff;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .add-server-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(78,205,196,0.4);
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
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="admin-header-content">
            <div class="admin-logo">
                <i class="fas fa-video"></i> Add New Episode
            </div>
            <a href="index.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </header>
    
    <div class="container">
        <div class="form-card">
            <h2><i class="fas fa-play-circle"></i> Episode Information</h2>
            
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
            
            <form method="POST" id="episodeForm">
                <div class="form-group">
                    <label><i class="fas fa-film"></i> Select Anime *</label>
                    <select name="anime_id" required>
                        <option value="">-- Select Anime --</option>
                        <?php foreach($animeList as $anime): ?>
                            <option value="<?php echo $anime['id']; ?>" <?php echo $selected_anime == $anime['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($anime['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-hashtag"></i> Episode Number *</label>
                        <input type="number" name="episode_number" required min="1">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-heading"></i> Episode Title (Optional)</label>
                        <input type="text" name="episode_title">
                    </div>
                </div>
                
                <div class="servers-section">
                    <h3><i class="fas fa-server"></i> Video Servers</h3>
                    <div id="serversContainer">
                        <div class="server-item">
                            <div class="server-header">
                                <h4><i class="fas fa-hdd"></i> Server 1</h4>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label><i class="fas fa-tag"></i> Server Name *</label>
                                    <input type="text" name="server_name[]" placeholder="e.g. Server 1, HD Server" required>
                                </div>
                                <div class="form-group">
                                    <label><i class="fas fa-signal"></i> Quality</label>
                                    <input type="text" name="server_quality[]" placeholder="e.g. 1080p, 720p">
                                </div>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-link"></i> Video URL *</label>
                                <input type="url" name="server_url[]" placeholder="https://..." required>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="add-server-btn" onclick="addServer()">
                        <i class="fas fa-plus"></i> Add Another Server
                    </button>
                </div>
                
                <button type="submit" class="btn">
                    <i class="fas fa-save"></i> Add Episode
                </button>
            </form>
        </div>
    </div>
    
    <script>
        let serverCount = 1;
        
        function addServer() {
            serverCount++;
            const container = document.getElementById('serversContainer');
            const serverHtml = `
                <div class="server-item">
                    <div class="server-header">
                        <h4><i class="fas fa-hdd"></i> Server ${serverCount}</h4>
                        <button type="button" class="remove-server" onclick="removeServer(this)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-tag"></i> Server Name *</label>
                            <input type="text" name="server_name[]" placeholder="e.g. Server ${serverCount}, HD Server" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-signal"></i> Quality</label>
                            <input type="text" name="server_quality[]" placeholder="e.g. 1080p, 720p">
                        </div>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-link"></i> Video URL *</label>
                        <input type="url" name="server_url[]" placeholder="https://..." required>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', serverHtml);
        }
        
        function removeServer(btn) {
            btn.closest('.server-item').remove();
        }
    </script>
</body>
</html>