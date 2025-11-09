<?php
require_once '../config.php';

if(!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Get anime_id from URL if provided
$anime_id = isset($_GET['anime_id']) ? (int)$_GET['anime_id'] : 0;

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Build query based on filters
$whereClause = '';
$params = [];
$animeDetails = null;

if($anime_id > 0) {
    $whereClause = "WHERE e.anime_id = ?";
    $params[] = $anime_id;
    
    // Get anime details
    $stmt = $pdo->prepare("SELECT * FROM anime WHERE id = ?");
    $stmt->execute([$anime_id]);
    $animeDetails = $stmt->fetch();
    
    if(!$animeDetails) {
        $_SESSION['error'] = 'Anime not found';
        header('Location: anime-list.php');
        exit;
    }
}

// Get total count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM episodes e $whereClause");
$countStmt->execute($params);
$totalEpisodes = $countStmt->fetchColumn();
$totalPages = ceil($totalEpisodes / $perPage);

// Get episodes list with anime info
$stmt = $pdo->prepare("SELECT e.*, a.title as anime_title, a.slug as anime_slug,
                       (SELECT COUNT(*) FROM servers WHERE episode_id = e.id) as server_count
                       FROM episodes e 
                       JOIN anime a ON e.anime_id = a.id 
                       $whereClause
                       ORDER BY e.episode_number ASC 
                       LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params, [$perPage, $offset]));
$episodes = $stmt->fetchAll();

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Episodes - Admin</title>
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
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .admin-logo {
            color: #fff;
            font-size: 24px;
            font-weight: bold;
        }
        
        .header-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
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
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .page-header {
            background: #fff;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .page-header h1 {
            font-size: 28px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .page-header h1 i {
            color: #667eea;
        }
        
        .anime-info {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            margin-top: 15px;
        }
        
        .anime-info img {
            width: 80px;
            height: 110px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        }
        
        .anime-info-text h2 {
            font-size: 22px;
            margin-bottom: 8px;
            color: #333;
        }
        
        .anime-info-text p {
            color: #666;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .alert {
            padding: 15px 20px;
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
        
        .episodes-table {
            background: #fff;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
        }
        
        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        td {
            padding: 15px;
        }
        
        .episode-number {
            font-size: 20px;
            font-weight: bold;
            color: #667eea;
            min-width: 60px;
        }
        
        .episode-title {
            color: #333;
            font-weight: 500;
        }
        
        .episode-title.untitled {
            color: #999;
            font-style: italic;
        }
        
        .server-count {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #e3f2fd;
            color: #1976d2;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .action-btns {
            display: flex;
            gap: 8px;
        }
        
        .action-btn {
            width: 35px;
            height: 35px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            text-decoration: none;
            color: #fff;
            font-size: 14px;
        }
        
        .action-btn.view {
            background: #2196f3;
        }
        
        .action-btn.edit {
            background: #ff9800;
        }
        
        .action-btn.delete {
            background: #f44336;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0,0,0,0.2);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 30px;
        }
        
        .pagination a,
        .pagination span {
            padding: 10px 15px;
            background: #fff;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .pagination a:hover {
            background: #667eea;
            color: #fff;
            transform: translateY(-2px);
        }
        
        .pagination .active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            font-weight: 600;
        }
        
        .pagination .disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state i {
            font-size: 64px;
            color: #ccc;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            font-size: 24px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: #999;
            margin-bottom: 20px;
        }
        
        .add-btn {
            background: linear-gradient(135deg, #4ECDC4 0%, #44A08D 100%);
            color: #fff;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .add-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(78,205,196,0.4);
        }
        
        @media (max-width: 768px) {
            .episodes-table {
                overflow-x: auto;
            }
            
            table {
                min-width: 600px;
            }
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="admin-header-content">
            <div class="admin-logo">
                <i class="fas fa-play-circle"></i> Manage Episodes
            </div>
            <div class="header-actions">
                <a href="episode-add.php<?php echo $anime_id ? '?anime_id='.$anime_id : ''; ?>" class="back-btn">
                    <i class="fas fa-plus"></i> Add Episode
                </a>
                <a href="anime-list.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Anime List
                </a>
            </div>
        </div>
    </header>
    
    <div class="container">
        <?php if($animeDetails): ?>
        <div class="page-header">
            <h1>
                <i class="fas fa-film"></i> 
                Episodes for: <?php echo htmlspecialchars($animeDetails['title']); ?>
            </h1>
            <div class="anime-info">
                <img src="../<?php echo $animeDetails['poster'] ? 'uploads/'.$animeDetails['poster'] : 'uploads/default.jpg'; ?>" 
                     alt="<?php echo htmlspecialchars($animeDetails['title']); ?>">
                <div class="anime-info-text">
                    <h2><?php echo htmlspecialchars($animeDetails['title']); ?></h2>
                    <?php if($animeDetails['zh_name']): ?>
                        <p><i class="fas fa-language"></i> <?php echo htmlspecialchars($animeDetails['zh_name']); ?></p>
                    <?php endif; ?>
                    <p>
                        <i class="fas fa-info-circle"></i> 
                        <?php echo ucfirst($animeDetails['status']); ?>
                        <?php if($animeDetails['type']): ?>
                            | <?php echo htmlspecialchars($animeDetails['type']); ?>
                        <?php endif; ?>
                    </p>
                    <p><i class="fas fa-play-circle"></i> Total Episodes: <strong><?php echo $totalEpisodes; ?></strong></p>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="page-header">
            <h1>
                <i class="fas fa-film"></i> 
                All Episodes (<?php echo $totalEpisodes; ?>)
            </h1>
        </div>
        <?php endif; ?>
        
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
        
        <?php if(count($episodes) > 0): ?>
        <div class="episodes-table">
            <table>
                <thead>
                    <tr>
                        <th>Episode</th>
                        <th>Title</th>
                        <th>Servers</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($episodes as $episode): ?>
                    <tr>
                        <td>
                            <div class="episode-number">
                                <i class="fas fa-play"></i> <?php echo $episode['episode_number']; ?>
                            </div>
                        </td>
                        <td>
                            <div class="episode-title <?php echo empty($episode['title']) ? 'untitled' : ''; ?>">
                                <?php echo $episode['title'] ? htmlspecialchars($episode['title']) : 'Untitled Episode'; ?>
                            </div>
                        </td>
                        <td>
                            <span class="server-count">
                                <i class="fas fa-server"></i>
                                <?php echo $episode['server_count']; ?> 
                                <?php echo $episode['server_count'] == 1 ? 'server' : 'servers'; ?>
                            </span>
                        </td>
                        <td>
                            <?php echo date('M d, Y', strtotime($episode['created_at'])); ?>
                        </td>
                        <td>
                            <div class="action-btns">
                                <a href="../watch.php?slug=<?php echo $episode['slug']; ?>" 
                                   target="_blank" 
                                   class="action-btn view" 
                                   title="Watch Episode">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="episode-edit.php?id=<?php echo $episode['id']; ?>" 
                                   class="action-btn edit" 
                                   title="Edit Episode">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="confirmDelete(<?php echo $episode['id']; ?>, '<?php echo $episode['episode_number']; ?>')" 
                                        class="action-btn delete" 
                                        title="Delete Episode">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if($totalPages > 1): ?>
        <div class="pagination">
            <?php if($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?><?php echo $anime_id ? '&anime_id='.$anime_id : ''; ?>">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
            <?php else: ?>
                <span class="disabled">
                    <i class="fas fa-chevron-left"></i> Previous
                </span>
            <?php endif; ?>
            
            <?php
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);
            
            for($i = $start; $i <= $end; $i++):
            ?>
                <a href="?page=<?php echo $i; ?><?php echo $anime_id ? '&anime_id='.$anime_id : ''; ?>" 
                   class="<?php echo $i === $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?><?php echo $anime_id ? '&anime_id='.$anime_id : ''; ?>">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            <?php else: ?>
                <span class="disabled">
                    Next <i class="fas fa-chevron-right"></i>
                </span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php else: ?>
        <div class="episodes-table">
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>No Episodes Found</h3>
                <p>This anime doesn't have any episodes yet.</p>
                <a href="episode-add.php?anime_id=<?php echo $anime_id; ?>" class="add-btn">
                    <i class="fas fa-plus"></i> Add First Episode
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        function confirmDelete(id, episodeNum) {
            if(confirm('Are you sure you want to delete Episode ' + episodeNum + '?\n\nThis will also delete all servers associated with this episode.')) {
                // Create a form and submit it
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'episode-edit.php?id=' + id;
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'delete_episode';
                input.value = '1';
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>