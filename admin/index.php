<?php
require_once '../config.php';

if(!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Get statistics
$totalAnime = $pdo->query("SELECT COUNT(*) FROM anime")->fetchColumn();
$totalEpisodes = $pdo->query("SELECT COUNT(*) FROM episodes")->fetchColumn();
$hotAnime = $pdo->query("SELECT COUNT(*) FROM anime WHERE is_hot = 1")->fetchColumn();
$newAnime = $pdo->query("SELECT COUNT(*) FROM anime WHERE is_new = 1")->fetchColumn();

// Get recent anime
$recentAnime = $pdo->query("SELECT * FROM anime ORDER BY created_at DESC LIMIT 10")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
        
        .admin-logo i {
            margin-right: 10px;
        }
        
        .admin-user {
            display: flex;
            align-items: center;
            gap: 20px;
            color: #fff;
        }
        
        .admin-user span {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .admin-user a {
            color: #fff;
            text-decoration: none;
            background: rgba(255,255,255,0.2);
            padding: 8px 20px;
            border-radius: 8px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .admin-user a:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .admin-container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: #fff;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #fff;
        }
        
        .stat-icon.purple {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .stat-icon.orange {
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
        }
        
        .stat-icon.green {
            background: linear-gradient(135deg, #4ECDC4 0%, #44A08D 100%);
        }
        
        .stat-icon.red {
            background: linear-gradient(135deg, #FF416C 0%, #FF4B2B 100%);
        }
        
        .stat-info h3 {
            font-size: 32px;
            margin-bottom: 5px;
        }
        
        .stat-info p {
            color: #666;
            font-size: 14px;
        }
        
        .quick-actions {
            background: #fff;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        .quick-actions h2 {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .action-btn {
            padding: 15px 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            text-decoration: none;
            border-radius: 10px;
            text-align: center;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-weight: 600;
        }
        
        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102,126,234,0.4);
        }
        
        .recent-table {
            background: #fff;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .recent-table h2 {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .badge.hot {
            background: #fee;
            color: #c33;
        }
        
        .badge.new {
            background: #efe;
            color: #3c3;
        }
        
        .table-actions {
            display: flex;
            gap: 10px;
        }
        
        .table-actions a {
            color: #667eea;
            text-decoration: none;
            font-size: 18px;
        }
        
        .table-actions a:hover {
            color: #764ba2;
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="admin-header-content">
            <div class="admin-logo">
                <i class="fas fa-shield-alt"></i>ADMIN
            </div>
            <div class="admin-user">
                <span><i class="fas fa-user"></i> <?php echo $_SESSION['admin_username']; ?></span>
                <a href="../index.php" target="_blank"><i class="fas fa-external-link-alt"></i> View Site</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </header>
    
    <div class="admin-container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="fas fa-film"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $totalAnime; ?></h3>
                    <p>Total Anime</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-play-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $totalEpisodes; ?></h3>
                    <p>Total Episodes</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon red">
                    <i class="fas fa-fire"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $hotAnime; ?></h3>
                    <p>Hot Series</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-sparkles"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $newAnime; ?></h3>
                    <p>New Series</p>
                </div>
            </div>
        </div>
        
        <div class="quick-actions">
            <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
            <div class="action-buttons">
                <a href="anime-add.php" class="action-btn">
                    <i class="fas fa-plus"></i> Add Anime
                </a>
                <a href="anime-edit.php" class="action-btn">
                    <i class="fas fa-plus"></i> edit Anime
                </a>
                <a href="anime-list.php" class="action-btn">
                    <i class="fas fa-list"></i> Manage Anime
                </a>
                <a href="episode-add.php" class="action-btn">
                    <i class="fas fa-video"></i> Add Episode
                </a>
                <a href="episode-edit.php" class="action-btn">
                    <i class="fas fa-video"></i> edit Episode
                </a>
                <a href="settings.php" class="action-btn">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </div>
        </div>
        
        <div class="recent-table">
            <h2><i class="fas fa-clock"></i> Recent Anime</h2>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Tags</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($recentAnime as $anime): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($anime['title']); ?></td>
                        <td><?php echo ucfirst($anime['status']); ?></td>
                        <td>
                            <?php if($anime['is_hot']): ?>
                                <span class="badge hot">HOT</span>
                            <?php endif; ?>
                            <?php if($anime['is_new']): ?>
                                <span class="badge new">NEW</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($anime['created_at'])); ?></td>
                        <td class="table-actions">
                            <a href="anime-edit.php?id=<?php echo $anime['id']; ?>" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="episode-add.php?anime_id=<?php echo $anime['id']; ?>" title="Add Episode">
                                <i class="fas fa-plus-circle"></i>
                            </a>
                            <a href="anime-delete.php?id=<?php echo $anime['id']; ?>" title="Delete" onclick="return confirm('Are you sure?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>