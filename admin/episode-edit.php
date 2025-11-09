<?php
require_once '../config.php';

if(!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Debug information
error_log("Episode Edit - ID received: " . $id);

if($id === 0) {
    error_log("Episode Edit - Invalid ID, redirecting to anime-list.php");
    $_SESSION['error'] = 'Invalid episode ID';
    header('Location: anime-list.php');
    exit;
}

// Get episode details with anime info
$stmt = $pdo->prepare("SELECT e.*, a.title as anime_title, a.id as anime_id 
                       FROM episodes e 
                       JOIN anime a ON e.anime_id = a.id 
                       WHERE e.id = ?");
$stmt->execute([$id]);
$episode = $stmt->fetch();

if(!$episode) {
    $_SESSION['error'] = 'Episode not found';
    header('Location: anime-list.php');
    exit;
}

// Get servers for this episode
$stmt = $pdo->prepare("SELECT * FROM servers WHERE episode_id = ? ORDER BY id ASC");
$stmt->execute([$id]);
$servers = $stmt->fetchAll();

$success = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(isset($_POST['delete_episode'])) {
        // Handle episode deletion
        try {
            $pdo->prepare("DELETE FROM episodes WHERE id = ?")->execute([$id]);
            $_SESSION['success'] = 'Episode deleted successfully!';
            header('Location: anime-list.php');
            exit;
        } catch(PDOException $e) {
            $error = 'Error deleting episode: ' . $e->getMessage();
        }
    } else {
        // Handle episode update
        $episode_number = (int)$_POST['episode_number'];
        $episode_title = sanitize($_POST['episode_title']);
        
        // Update slug if episode number changed
        $anime_slug = createSlug($episode['anime_title']);
        $slug = $anime_slug . '-episode-' . $episode_number;
        
        // Servers data
        $server_ids = $_POST['server_id'] ?? [];
        $server_names = $_POST['server_name'] ?? [];
        $server_urls = $_POST['server_url'] ?? [];
        $server_qualities = $_POST['server_quality'] ?? [];
        
        if(count($server_names) === 0) {
            $error = 'Please add at least one server';
        } else {
            try {
                $pdo->beginTransaction();
                
                // Update episode
                $stmt = $pdo->prepare("UPDATE episodes SET episode_number = ?, title = ?, slug = ? WHERE id = ?");
                $stmt->execute([$episode_number, $episode_title, $slug, $id]);
                
                // Get existing server IDs
                $existingServers = array_column($servers, 'id');
                
                // Update existing servers and add new ones
                foreach($server_names as $index => $name) {
                    if(!empty($name) && !empty($server_urls[$index])) {
                        $server_id = $server_ids[$index] ?? null;
                        $is_default = ($index === 0) ? 1 : 0;
                        
                        if($server_id && in_array($server_id, $existingServers)) {
                            // Update existing server
                            $stmt = $pdo->prepare("UPDATE servers SET server_name = ?, server_url = ?, quality = ?, is_default = ? WHERE id = ?");
                            $stmt->execute([
                                $name,
                                $server_urls[$index],
                                $server_qualities[$index] ?? '',
                                $is_default,
                                $server_id
                            ]);
                            // Remove from existing list
                            $existingServers = array_diff($existingServers, [$server_id]);
                        } else {
                            // Insert new server
                            $stmt = $pdo->prepare("INSERT INTO servers (episode_id, server_name, server_url, quality, is_default) VALUES (?, ?, ?, ?, ?)");
                            $stmt->execute([
                                $id,
                                $name,
                                $server_urls[$index],
                                $server_qualities[$index] ?? '',
                                $is_default
                            ]);
                        }
                    }
                }
                
                // Delete servers that were removed
                if(!empty($existingServers)) {
                    $placeholders = str_repeat('?,', count($existingServers) - 1) . '?';
                    $stmt = $pdo->prepare("DELETE FROM servers WHERE id IN ($placeholders)");
                    $stmt->execute(array_values($existingServers));
                }
                
                // Update anime update_date
                $pdo->prepare("UPDATE anime SET update_date = NOW() WHERE id = ?")->execute([$episode['anime_id']]);
                
                $pdo->commit();
                $success = 'Episode updated successfully!';
                
                // Refresh episode and servers data
                $stmt = $pdo->prepare("SELECT e.*, a.title as anime_title, a.id as anime_id 
                                       FROM episodes e 
                                       JOIN anime a ON e.anime_id = a.id 
                                       WHERE e.id = ?");
                $stmt->execute([$id]);
                $episode = $stmt->fetch();
                
                $stmt = $pdo->prepare("SELECT * FROM servers WHERE episode_id = ? ORDER BY id ASC");
                $stmt->execute([$id]);
                $servers = $stmt->fetchAll();
            } catch(PDOException $e) {
                $pdo->rollBack();
                $error = 'Error: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Episode - Admin</title>
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
        
        .anime-badge {
            display: inline-block;
            background: #f0f4ff;
            color: #667eea;
            padding: 8px 15px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
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
        
        .btn-danger {
            background: linear-gradient(135deg, #FF416C 0%, #FF4B2B 100%);
            margin-left: 10px;
        }
        
        .btn-danger:hover {
            box-shadow: 0 10px 25px rgba(255,65,108,0.4);
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="admin-header-content">
            <div class="admin-logo">
                <i class="fas fa-edit"></i> Edit Episode
            </div>
            <a href="anime-list.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </header>
    
    <div class="container">
        <div class="form-card">
            <h2><i class="fas fa-play-circle"></i> Edit Episode</h2>
            
            <div class="anime-badge">
                <i class="fas fa-film"></i> <?php echo htmlspecialchars($episode['anime_title']); ?> - Episode <?php echo $episode['episode_number']; ?>
            </div>
            
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
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-hashtag"></i> Episode Number *</label>
                        <input type="number" name="episode_number" required min="1" value="<?php echo $episode['episode_number']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-heading"></i> Episode Title (Optional)</label>
                        <input type="text" name="episode_title" value="<?php echo htmlspecialchars($episode['title']); ?>">
                    </div>
                </div>
                
                <div class="servers-section">
                    <h3><i class="fas fa-server"></i> Video Servers</h3>
                    <div id="serversContainer">
                        <?php if(count($servers) > 0): ?>
                            <?php foreach($servers as $index => $server): ?>
                                <div class="server-item">
                                    <div class="server-header">
                                        <h4><i class="fas fa-hdd"></i> Server <?php echo $index + 1; ?></h4>
                                        <?php if($index > 0): ?>
                                        <button type="button" class="remove-server" onclick="removeServer(this)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                    <input type="hidden" name="server_id[]" value="<?php echo $server['id']; ?>">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label><i class="fas fa-tag"></i> Server Name *</label>
                                            <input type="text" name="server_name[]" placeholder="e.g. Server 1, HD Server" required value="<?php echo htmlspecialchars($server['server_name']); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label><i class="fas fa-signal"></i> Quality</label>
                                            <input type="text" name="server_quality[]" placeholder="e.g. 1080p, 720p" value="<?php echo htmlspecialchars($server['quality']); ?>">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label><i class="fas fa-link"></i> Video URL *</label>
                                        <input type="url" name="server_url[]" placeholder="https://..." required value="<?php echo htmlspecialchars($server['server_url']); ?>">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="server-item">
                                <div class="server-header">
                                    <h4><i class="fas fa-hdd"></i> Server 1</h4>
                                </div>
                                <input type="hidden" name="server_id[]" value="">
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
                        <?php endif; ?>
                    </div>
                    <button type="button" class="add-server-btn" onclick="addServer()">
                        <i class="fas fa-plus"></i> Add Another Server
                    </button>
                </div>
                
                <button type="submit" class="btn">
                    <i class="fas fa-save"></i> Update Episode
                </button>
                
                <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                    <i class="fas fa-trash"></i> Delete Episode
                </button>
            </form>
            
            <!-- Hidden form for deletion -->
            <form method="POST" id="deleteForm" style="display: none;">
                <input type="hidden" name="delete_episode" value="1">
            </form>
        </div>
    </div>
    
    <script>
        let serverCount = <?php echo max(count($servers), 1); ?>;
        
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
                    <input type="hidden" name="server_id[]" value="">
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
            if(document.querySelectorAll('.server-item').length > 1) {
                btn.closest('.server-item').remove();
            } else {
                alert('You must have at least one server!');
            }
        }
        
        function confirmDelete() {
            if(confirm('Are you sure you want to delete this episode? This action cannot be undone.')) {
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>