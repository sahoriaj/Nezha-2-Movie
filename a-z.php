<?php
require_once 'config.php';

// Get selected letter (default: A)
$selectedLetter = isset($_GET['letter']) ? strtoupper($_GET['letter']) : 'A';

// Validate letter
if(!preg_match('/^[A-Z0-9#]$/', $selectedLetter)) {
    $selectedLetter = 'A';
}

// Get anime by letter
if($selectedLetter === '#') {
    // Get anime starting with numbers or special characters
    $stmt = $pdo->prepare("SELECT * FROM anime WHERE title REGEXP '^[^A-Za-z]' ORDER BY title ASC");
    $stmt->execute();
} else {
    $stmt = $pdo->prepare("SELECT * FROM anime WHERE title LIKE ? ORDER BY title ASC");
    $stmt->execute([$selectedLetter . '%']);
}
$animeList = $stmt->fetchAll();

// Get all letters with anime count
$letters = range('A', 'Z');
$letters[] = '#';
$letterCounts = [];

foreach($letters as $letter) {
    if($letter === '#') {
        $count = $pdo->query("SELECT COUNT(*) FROM anime WHERE title REGEXP '^[^A-Za-z]'")->fetchColumn();
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM anime WHERE title LIKE ?");
        $stmt->execute([$letter . '%']);
        $count = $stmt->fetchColumn();
    }
    $letterCounts[$letter] = $count;
}

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

$page_title = 'A-Z List';
require_once 'header.php';
?>

<style>
    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
        padding: 40px 0;
        text-align: center;
        border-radius: 15px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(102,126,234,0.3);
    }
    
    .page-header h1 {
        font-size: 36px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 15px;
    }
    
    .page-header p {
        opacity: 0.9;
        font-size: 16px;
    }
    
    /* Alphabet Filter */
    .alphabet-filter {
        background: #fff;
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    
    .filter-title {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
        color: #333;
    }
    
    .filter-title i {
        color: #667eea;
    }
    
    .letters-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(45px, 1fr));
        gap: 10px;
    }
    
    .letter-btn {
        padding: 12px;
        background: #f8f9fa;
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        text-decoration: none;
        color: #333;
        text-align: center;
        font-weight: 600;
        font-size: 16px;
        transition: all 0.3s;
        position: relative;
        cursor: pointer;
    }
    
    .letter-btn:hover {
        background: #667eea;
        color: #fff;
        border-color: #667eea;
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(102,126,234,0.3);
    }
    
    .letter-btn.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
        border-color: #667eea;
        box-shadow: 0 5px 15px rgba(102,126,234,0.4);
    }
    
    .letter-btn.disabled {
        opacity: 0.3;
        cursor: not-allowed;
        pointer-events: none;
    }
    
    .letter-count {
        position: absolute;
        top: -8px;
        right: -8px;
        background: #FF416C;
        color: #fff;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        font-size: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        box-shadow: 0 2px 8px rgba(255,65,108,0.4);
    }
    
    /* Results Section */
    .results-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding: 15px 20px;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .results-header h2 {
        font-size: 24px;
        display: flex;
        align-items: center;
        gap: 10px;
        color: #333;
    }
    
    .results-count {
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
        margin-bottom: 50px;
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
    }
    
    .feature-badge.new {
        background: linear-gradient(135deg, #4ECDC4, #44A08D);
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
    
    body.dark-mode .alphabet-filter,
    body.dark-mode .results-header,
    body.dark-mode .empty-state {
        background: linear-gradient(45deg, #1a0033, #000);
        box-shadow: 0 0 20px rgba(0, 255, 157, 0.15);
    }
    
    body.dark-mode .filter-title,
    body.dark-mode .results-header h2 {
        color: #fff;
    }
    
    body.dark-mode .filter-title i {
        color: #00ff9d;
    }
    
    body.dark-mode .letter-btn {
        background: rgba(255,255,255,0.05);
        border-color: rgba(255,255,255,0.1);
        color: #eee;
    }
    
    body.dark-mode .letter-btn:hover {
        background: #00ff9d;
        color: #000;
        border-color: #00ff9d;
    }
    
    body.dark-mode .letter-btn.active {
        background: linear-gradient(135deg, #00ff9d 0%, #0066ff 100%);
        border-color: #00ff9d;
    }
    
    body.dark-mode .results-count {
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
        .letters-grid {
            grid-template-columns: repeat(auto-fill, minmax(40px, 1fr));
            gap: 8px;
        }
        
        .letter-btn {
            padding: 10px;
            font-size: 14px;
        }
        
        .anime-grid {
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 15px;
        }
        
        .results-header {
            flex-direction: column;
            gap: 10px;
            text-align: center;
        }
    }
</style>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-sort-alpha-down"></i> A-Z Anime List</h1>
            <p>Browse all anime alphabetically</p>
        </div>
        
        <!-- Alphabet Filter -->
        <div class="alphabet-filter">
            <div class="filter-title">
                <i class="fas fa-filter"></i> Select a Letter
            </div>
            <div class="letters-grid">
                <?php foreach($letters as $letter): ?>
                    <a href="?letter=<?php echo $letter; ?>" 
                       class="letter-btn <?php echo $selectedLetter === $letter ? 'active' : ''; ?> <?php echo $letterCounts[$letter] == 0 ? 'disabled' : ''; ?>">
                        <?php echo $letter; ?>
                        <?php if($letterCounts[$letter] > 0): ?>
                            <span class="letter-count"><?php echo $letterCounts[$letter]; ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Results Header -->
        <div class="results-header">
            <h2>
                <i class="fas fa-arrow-right"></i>
                Anime starting with "<?php echo $selectedLetter; ?>"
            </h2>
            <span class="results-count">
                <?php echo count($animeList); ?> Result<?php echo count($animeList) != 1 ? 's' : ''; ?>
            </span>
        </div>
        
        <!-- Anime Grid -->
        <?php if(count($animeList) > 0): ?>
            <div class="anime-grid">
                <?php foreach($animeList as $anime): ?>
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
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>No Anime Found</h3>
                <p>There are no anime starting with "<?php echo $selectedLetter; ?>" yet.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once 'footer.php'; ?>