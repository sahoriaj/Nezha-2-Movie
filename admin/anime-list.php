<?php
require_once '../config.php';

if(!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Handle delete
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $pdo->prepare("DELETE FROM anime WHERE id = ?")->execute([$id]);
        $success = 'Anime deleted successfully!';
    } catch(PDOException $e) {
        $error = 'Error deleting anime: ' . $e->getMessage();
    }
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Search
$search = $_GET['search'] ?? '';
$whereClause = '';
$params = [];

if($search) {
    $whereClause = "WHERE title LIKE ? OR zh_name LIKE ?";
    $params = ["%$search%", "%$search%"];
}

// Get total count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM anime $whereClause");
$countStmt->execute($params);
$totalAnime = $countStmt->fetchColumn();
$totalPages = ceil($totalAnime / $perPage);

// Get anime list
$stmt = $pdo->prepare("SELECT a.*, COUNT(e.id) as episode_count 
                       FROM anime a 
                       LEFT JOIN episodes e ON a.id = e.anime_id 
                       $whereClause
                       GROUP BY a.id 
                       ORDER BY a.update_date DESC 
                       LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params, [$perPage, $offset]));
$animeList = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Anime - Admin</title>
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
        
        .header-actions {
            display: flex;
            gap: 15px;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .page-header h1 {
            font-size: 32px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .page-header h1 i {
            color: #667eea;
        }
        
        .add-btn {
            background: linear-gradient(135deg, #4ECDC4 0%, #44A08D 100%);
            color: #fff;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(78,205,196,0.3);
        }
        
        .add-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(78,205,196,0.5);
        }
        
        .search-filter {
            background: #fff;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        .search-box {
            display: flex;
            gap: 10px;
        }
        
        .search-box input {
            flex: 1;
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .search-box button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .search-box button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.4);
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
        
        .anime-table {
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
        
        .anime-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .anime-thumb {
            width: 60px;
            height: 80px;
            border-radius: 8px;
            object-fit: cover;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .anime-details h4 {
            margin-bottom: 5px;
            color: #333;
        }
        
        .anime-details p {
            font-size: 13px;
            color: #666;
        }
        
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            margin-right: 5px;
        }
        
        .badge.hot {
            background: #fee;
            color: #c33;
        }
        
        .badge.new {
            background: #efe;
            color: #3c3;
        }
        
        .badge.ongoing {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .badge.completed {
            background: #f3e5f5;
            color: #7b1fa2;
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
        
        .action-btn.episodes {
            background: #4caf50;
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
        
        @media (max-width: 768px) {
            .anime-table {
                overflow-x: auto;
            }
            
            table {
                min-width: 800px;
            }
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="admin-header-content">
            <div class="admin-logo">
                <i class="fas fa-list"></i> Manage Anime
            </div>
            <div class="header-actions">
                <a href="anime-add.php" class="back-btn">
                    <i class="fas fa-plus"></i> Add New
                </a>
                <a href="index.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Dashboard
                </a>
            </div>
        </div>
    </header>
    
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-film"></i> All Anime (<?php echo $totalAnime; ?>)</h1>
        </div>
        
        <?php if(isset($success)): ?>
            <div class="alert success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
            <div class="alert error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="search-filter">
            <form method="GET" class="search-box">
                <input type="text" name="search" placeholder="Search anime by title..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">
                    <i class="fas fa-search"></i> Search
                </button>
                <?php if($search): ?>
                    <a href="anime-list.php" class="back-btn" style="padding: 12px 20px;">
                        <i class="fas fa-times"></i> Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>
        
        <?php if(count($animeList) > 0): ?>
        <div class="anime-table">
            <table>
                <thead>
                    <tr>
                        <th>Anime</th>
                        <th>Status</th>
                        <th>Tags</th>
                        <th>Episodes</th>
                        <th>Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($animeList as $anime): ?>
                    <tr>
                        <td>
                            <div class="anime-info">
                                <img src="../<?php echo $anime['poster'] ? 'uploads/'.$anime['poster'] : 'uploads/default.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($anime['title']); ?>" 
                                     class="anime-thumb">
                                <div class="anime-details">
                                    <h4><?php echo htmlspecialchars($anime['title']); ?></h4>
                                    <?php if($anime['zh_name']): ?>
                                        <p><?php echo htmlspecialchars($anime['zh_name']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge <?php echo $anime['status']; ?>">
                                <?php echo ucfirst($anime['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if($anime['is_hot']): ?>
                                <span class="badge hot"><i class="fas fa-fire"></i> HOT</span>
                            <?php endif; ?>
                            <?php if($anime['is_new']): ?>
                                <span class="badge new"><i class="fas fa-sparkles"></i> NEW</span>
                            <?php endif; ?>
                            <?php if(!$anime['is_hot'] && !$anime['is_new']): ?>
                                <span style="color: #999;">None</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo $anime['episode_count']; ?></strong> episodes
                        </td>
                        <td>
                            <?php echo date('M d, Y', strtotime($anime['update_date'])); ?>
                        </td>
                        <!-- Replace the action buttons section in anime-list.php (around line 280-300) -->
<td>
    <div class="action-btns">
        <a href="../anime.php?slug=<?php echo $anime['slug']; ?>" 
           target="_blank" 
           class="action-btn view" 
           title="View Anime">
            <i class="fas fa-eye"></i>
        </a>
        <a href="anime-edit.php?id=<?php echo $anime['id']; ?>" 
           class="action-btn edit" 
           title="Edit Anime">
            <i class="fas fa-edit"></i>
        </a>
        <a href="episodes-list.php?anime_id=<?php echo $anime['id']; ?>" 
           class="action-btn" 
           style="background: #9c27b0;"
           title="Manage Episodes">
            <i class="fas fa-list"></i>
        </a>
        <a href="episode-add.php?anime_id=<?php echo $anime['id']; ?>" 
           class="action-btn episodes" 
           title="Add Episode">
            <i class="fas fa-plus"></i>
        </a>
        <button onclick="confirmDelete(<?php echo $anime['id']; ?>, '<?php echo htmlspecialchars($anime['title']); ?>')" 
                class="action-btn delete" 
                title="Delete">
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
                <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>">
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
                <a href="?page=<?php echo $i; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" 
                   class="<?php echo $i === $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>">
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
        <div class="anime-table">
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>No Anime Found</h3>
                <p><?php echo $search ? 'No results for your search.' : 'Start by adding your first anime!'; ?></p>
                <a href="anime-add.php" class="add-btn">
                    <i class="fas fa-plus"></i> Add Your First Anime
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        function confirmDelete(id, title) {
            if(confirm('Are you sure you want to delete "' + title + '"?\n\nThis will also delete all episodes and servers associated with this anime.')) {
                window.location.href = 'anime-list.php?delete=' + id;
            }
        }
    </script>
</body>
</html>