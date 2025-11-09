<?php
require_once 'config.php';

$page_title = 'All Anime';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 24;
$offset = ($page - 1) * $perPage;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
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

// Get anime with pagination
$stmt = $pdo->prepare("SELECT * FROM anime $whereClause ORDER BY update_date DESC LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params, [$perPage, $offset]));
$allAnime = $stmt->fetchAll();

// Helper function to get latest episode number
function getLatestEpisodeNumber($pdo, $anime_id) {
    $stmt = $pdo->prepare("SELECT MAX(episode_number) FROM episodes WHERE anime_id = ?");
    $stmt->execute([$anime_id]);
    return $stmt->fetchColumn();
}

// Helper function to get poster URL
function getPosterUrl($poster) {
    if(empty($poster)) {
        return 'uploads/default.jpg';
    }
    if(filter_var($poster, FILTER_VALIDATE_URL)) {
        return $poster;
    }
    return 'uploads/' . $poster;
}

require_once 'header.php';
?>

<style>
    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
        padding: 50px 0;
        text-align: center;
        border-radius: 15px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(102,126,234,0.3);
    }
    
    .page-header h1 {
        font-size: 42px;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 15px;
        animation: fadeInDown 0.8s;
    }
    
    .page-header p {
        opacity: 0.95;
        font-size: 18px;
        animation: fadeInUp 0.8s;
    }
    
    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Search Bar */
    .search-filter-section {
        background: #fff;
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    
    .search-wrapper {
        display: flex;
        gap: 15px;
        align-items: center;
    }
    
    .search-box {
        flex: 1;
        position: relative;
    }
    
    .search-box input {
        width: 100%;
        padding: 15px 50px 15px 20px;
        border: 2px solid #e0e0e0;
        border-radius: 12px;
        font-size: 16px;
        transition: all 0.3s;
    }
    
    .search-box input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 15px rgba(102,126,234,0.2);
    }
    
    .search-box button {
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        width: 45px;
        height: 45px;
        border-radius: 10px;
        color: #fff;
        cursor: pointer;
        font-size: 18px;
        transition: all 0.3s;
    }
    
    .search-box button:hover {
        transform: translateY(-50%) scale(1.05);
        box-shadow: 0 5px 15px rgba(102,126,234,0.4);
    }
    
    .clear-search {
        padding: 15px 25px;
        background: #f44336;
        color: #fff;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s;
    }
    
    .clear-search:hover {
        background: #d32f2f;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(244,67,54,0.4);
    }
    
    /* Stats Bar */
    .stats-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #fff;
        padding: 15px 25px;
        border-radius: 12px;
        margin-bottom: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .stats-info {
        display: flex;
        align-items: center;
        gap: 15px;
        font-size: 15px;
        color: #666;
    }
    
    .stats-info i {
        color: #667eea;
        font-size: 18px;
    }
    
    .total-count {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
        padding: 8px 20px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 14px;
    }
    
    /* Anime Grid */
    .anime-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }
    
    .anime-card {
        background: transparent;
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s;
        text-decoration: none;
        color: #333;
        position: relative;
    }
    
    .anime-card:hover {
        transform: translateY(-8px);
    }
    
    .anime-card-image {
        position: relative;
        padding-top: 140%;
        overflow: hidden;
        background: #f0f0f0;
        border-radius: 12px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    
    .anime-card-image img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s;
    }
    
    .anime-card:hover .anime-card-image img {
        transform: scale(1.1);
    }
    
    .play-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s;
        border-radius: 12px;
    }
    
    .anime-card:hover .play-overlay {
        opacity: 1;
    }
    
    .play-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: rgba(255,255,255,0.95);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: #667eea;
        box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.1);
        }
    }
    
    /* Badge Container */
    .badge-container {
        position: absolute;
        top: 8px;
        left: 8px;
        display: flex;
        flex-direction: column;
        gap: 6px;
        z-index: 2;
    }
    
    .status-badge {
        background: rgba(0,0,0,0.85);
        backdrop-filter: blur(10px);
        color: #fff;
        padding: 5px 12px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    }
    
    .status-badge.ongoing {
        background: linear-gradient(135deg, #4CAF50, #45a049);
    }
    
    .status-badge.completed {
        background: linear-gradient(135deg, #2196F3, #1976D2);
    }
    
    .feature-badge {
        background: rgba(0,0,0,0.85);
        backdrop-filter: blur(10px);
        color: #fff;
        padding: 5px 12px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        text-transform: uppercase;
    }
    
    .feature-badge.hot {
        background: linear-gradient(135deg, #FF416C, #FF4B2B);
        animation: hotPulse 2s infinite;
    }
    
    .feature-badge.new {
        background: linear-gradient(135deg, #4ECDC4, #44A08D);
    }
    
    @keyframes hotPulse {
        0%, 100% {
            box-shadow: 0 2px 8px rgba(255,65,108,0.5);
        }
        50% {
            box-shadow: 0 4px 16px rgba(255,65,108,0.8);
        }
    }
    
    .episode-badge {
        position: absolute;
        bottom: 8px;
        right: 8px;
        background: rgba(0,0,0,0.9);
        backdrop-filter: blur(10px);
        color: #fff;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
        z-index: 2;
        display: flex;
        align-items: center;
        gap: 5px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.4);
    }
    
    .episode-badge i {
        color: #4ECDC4;
    }
    
    .anime-card-info {
        padding: 12px 0;
        background: transparent;
    }
    
    .anime-card-title {
        font-size: 14px;
        font-weight: 600;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 40px;
        line-height: 1.4;
        color: #333;
        text-align: left;
    }
    
    /* Pagination */
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        margin-top: 40px;
        flex-wrap: wrap;
    }
    
    .pagination a,
    .pagination span {
        padding: 12px 18px;
        background: #fff;
        border-radius: 10px;
        text-decoration: none;
        color: #333;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        min-width: 45px;
        justify-content: center;
    }
    
    .pagination a:hover {
        background: #667eea;
        color: #fff;
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(102,126,234,0.4);
    }
    
    .pagination .active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
        box-shadow: 0 5px 15px rgba(102,126,234,0.4);
    }
    
    .pagination .disabled {
        opacity: 0.4;
        cursor: not-allowed;
        pointer-events: none;
    }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 80px 20px;
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    
    .empty-state i {
        font-size: 64px;
        color: #ddd;
        margin-bottom: 20px;
    }
    
    .empty-state h3 {
        font-size: 24px;
        color: #666;
        margin-bottom: 10px;
    }
    
    .empty-state p {
        color: #999;
        font-size: 16px;
    }
    
    /* Dark Mode */
    body.dark-mode {
        background: #0c0c0c;
        color: #e0e0e0;
    }
    
    body.dark-mode .page-header {
        background: linear-gradient(45deg, #1a0033, #000);
        box-shadow: 0 0 30px rgba(0, 255, 157, 0.2);
    }
    
    body.dark-mode .search-filter-section,
    body.dark-mode .stats-bar,
    body.dark-mode .empty-state {
        background: linear-gradient(45deg, #1a0033, #000);
        box-shadow: 0 0 20px rgba(0, 255, 157, 0.15);
    }
    
    body.dark-mode .search-box input {
        background: rgba(255,255,255,0.05);
        border-color: rgba(255,255,255,0.1);
        color: #fff;
    }
    
    body.dark-mode .search-box input::placeholder {
        color: #888;
    }
    
    body.dark-mode .search-box button {
        background: linear-gradient(135deg, #00ff9d 0%, #0066ff 100%);
    }
    
    body.dark-mode .clear-search {
        background: #ff4444;
    }
    
    body.dark-mode .stats-info {
        color: #ccc;
    }
    
    body.dark-mode .stats-info i {
        color: #00ff9d;
    }
    
    body.dark-mode .total-count {
        background: linear-gradient(135deg, #00ff9d 0%, #0066ff 100%);
    }
    
    body.dark-mode .anime-card-title {
        color: #fff;
    }
    
    body.dark-mode .play-icon {
        background: rgba(0, 255, 157, 0.95);
        color: #000;
    }
    
    body.dark-mode .anime-card-image {
        box-shadow: 0 5px 20px rgba(0, 255, 157, 0.2);
    }
    
    body.dark-mode .pagination a,
    body.dark-mode .pagination span {
        background: rgba(255,255,255,0.05);
        color: #eee;
        box-shadow: 0 2px 8px rgba(0, 255, 157, 0.2);
    }
    
    body.dark-mode .pagination a:hover {
        background: #00ff9d;
        color: #000;
    }
    
    body.dark-mode .pagination .active {
        background: linear-gradient(135deg, #00ff9d 0%, #0066ff 100%);
    }
    
    body.dark-mode .empty-state i {
        color: #333;
    }
    
    body.dark-mode .empty-state h3 {
        color: #bbb;
    }
    
    body.dark-mode .empty-state p {
        color: #777;
    }
    
    @media (max-width: 768px) {
        .page-header h1 {
            font-size: 32px;
        }
        
        .page-header p {
            font-size: 16px;
        }
        
        .search-wrapper {
            flex-direction: column;
        }
        
        .clear-search {
            width: 100%;
            justify-content: center;
        }
        
        .stats-bar {
            flex-direction: column;
            gap: 10px;
            text-align: center;
        }
        
        .anime-grid {
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 15px;
        }
        
        .anime-card-title {
            font-size: 13px;
            min-height: 36px;
        }
        
        .pagination {
            gap: 5px;
        }
        
        .pagination a,
        .pagination span {
            padding: 10px 14px;
            font-size: 14px;
        }
    }
</style>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-th"></i> All Anime</h1>
            <p>Browse through our collection of <?php echo $totalAnime; ?> anime series</p>
        </div>
        
        <!-- Search Filter -->
        <div class="search-filter-section">
            <form method="GET" class="search-wrapper">
                <div class="search-box">
                    <input type="text" 
                           name="search" 
                           placeholder="Search anime by title..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           autocomplete="off">
                    <button type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                <?php if($search): ?>
                    <a href="all-anime.php" class="clear-search">
                        <i class="fas fa-times"></i> Clear Search
                    </a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Stats Bar -->
        <div class="stats-bar">
            <div class="stats-info">
                <i class="fas fa-film"></i>
                <?php if($search): ?>
                    <span>Search results for: <strong>"<?php echo htmlspecialchars($search); ?>"</strong></span>
                <?php else: ?>
                    <span>Showing page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                <?php endif; ?>
            </div>
            <span class="total-count">
                <?php echo count($allAnime); ?> Anime on this page
            </span>
        </div>
        
        <!-- Anime Grid -->
        <?php if(count($allAnime) > 0): ?>
            <div class="anime-grid">
                <?php foreach($allAnime as $anime): ?>
                    <?php $latestEpisode = getLatestEpisodeNumber($pdo, $anime['id']); ?>
                    <a href="anime.php?slug=<?php echo $anime['slug']; ?>" class="anime-card">
                        <div class="anime-card-image">
                            <img src="<?php echo getPosterUrl($anime['poster']); ?>" 
                                 alt="<?php echo htmlspecialchars($anime['title']); ?>"
                                 onerror="this.src='uploads/default.jpg'">
                            
                            <div class="play-overlay">
                                <div class="play-icon">
                                    <i class="fas fa-play"></i>
                                </div>
                            </div>
                            
                            <!-- Top Left Badges -->
                            <div class="badge-container">
                                <span class="status-badge <?php echo $anime['status']; ?>">
                                    <i class="fas fa-<?php echo $anime['status'] === 'ongoing' ? 'circle' : 'check-circle'; ?>"></i>
                                    <?php echo ucfirst($anime['status']); ?>
                                </span>
                                <?php if($anime['is_hot']): ?>
                                    <span class="feature-badge hot">
                                        <i class="fas fa-fire"></i> Hot
                                    </span>
                                <?php elseif($anime['is_new']): ?>
                                    <span class="feature-badge new">
                                        <i class="fas fa-sparkles"></i> New
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Episode Badge - Bottom Right -->
                            <?php if($latestEpisode): ?>
                                <span class="episode-badge">
                                    <i class="fas fa-play-circle"></i> Ep <?php echo $latestEpisode; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="anime-card-info">
                            <div class="anime-card-title"><?php echo htmlspecialchars($anime['title']); ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
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
                
                if($start > 1): ?>
                    <a href="?page=1<?php echo $search ? '&search='.urlencode($search) : ''; ?>">1</a>
                    <?php if($start > 2): ?>
                        <span>...</span>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php for($i = $start; $i <= $end; $i++): ?>
                    <a href="?page=<?php echo $i; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" 
                       class="<?php echo $i === $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if($end < $totalPages): ?>
                    <?php if($end < $totalPages - 1): ?>
                        <span>...</span>
                    <?php endif; ?>
                    <a href="?page=<?php echo $totalPages; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>"><?php echo $totalPages; ?></a>
                <?php endif; ?>
                
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
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <h3>No Results Found</h3>
                <p><?php echo $search ? 'No anime found for "'.htmlspecialchars($search).'"' : 'No anime available yet'; ?></p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once 'footer.php'; ?>