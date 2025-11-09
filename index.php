<?php
require_once 'config.php';

// Get hot anime
$hotAnime = $pdo->query("SELECT * FROM anime WHERE is_hot = 1 ORDER BY update_date DESC LIMIT 12")->fetchAll();

// Get new anime
$newAnime = $pdo->query("SELECT * FROM anime WHERE is_new = 1 ORDER BY update_date DESC LIMIT 12")->fetchAll();

// Get recently updated anime
$recentAnime = $pdo->query("SELECT * FROM anime ORDER BY update_date DESC LIMIT 24")->fetchAll();

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

$page_title = 'Home';
require_once 'header.php';
?>

<style>
    .hero-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
        padding: 60px 0;
        text-align: center;
        border-radius: 15px;
        margin-bottom: 40px;
        box-shadow: 0 10px 30px rgba(102,126,234,0.3);
    }
    
    .hero-section h1 {
        font-size: 48px;
        margin-bottom: 15px;
        animation: fadeInDown 1s;
    }
    
    .hero-section p {
        font-size: 20px;
        opacity: 0.9;
        animation: fadeInUp 1s;
    }
    
    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }
    
    .section-header h2 {
        font-size: 28px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .section-header h2 i {
        color: #667eea;
    }
    
    .view-all {
        color: #667eea;
        text-decoration: none;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s;
    }
    
    .view-all:hover {
        color: #764ba2;
        gap: 12px;
    }
    
    .anime-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
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
    
    /* Badge Container for Top Left */
    .badge-container {
        position: absolute;
        top: 8px;
        left: 8px;
        display: flex;
        flex-direction: column;
        gap: 6px;
        z-index: 2;
    }
    
    /* Status Badge (Ongoing/Completed) */
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
        background: linear-gradient(45deg, #00ff42, #000);
    }
    
    .status-badge.completed {
        background: linear-gradient(45deg, #4100cd, #0e002d);
    }
    
    

/* Badge styling */
/* Individual Badges */
.feature-badge {
  position: absolute;       /* makes it float inside the card */
  top: -1px;                 /* distance from top */
  right: -35px;               /* distance from right */
  background: rgba(0, 0, 0, 0.85);
  backdrop-filter: blur(10px);
  color: #fff;
  padding: 3px 8px;
  border-radius: 6px;
  font-size: 10px;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 4px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
  text-transform: uppercase;
  z-index: 2;
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
    
    /* Episode Badge - Bottom Right */
    .episode-badge {
        position: absolute;
        bottom: 8px;
        right: 8px;
        background: transparent;
box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
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
    
    /* Anime Card Info - Clean Title Only */
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
        text-align: center;
    }
    
    /* Dark Mode */
    body.dark-mode {
        background: #0c0c0c;
        color: #e0e0e0;
    }
    
    body.dark-mode .hero-section {
        background: linear-gradient(45deg, #1a0033, #000);
        box-shadow: 0 0 30px rgba(0, 255, 157, 0.2);
    }
    
    body.dark-mode .section-header h2 {
        color: #fff;
    }
    
    body.dark-mode .section-header h2 i {
        color: #00ff9d;
    }
    
    body.dark-mode .view-all {
        color: #00ff9d;
    }
    
    body.dark-mode .view-all:hover {
        color: #00c3ff;
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
    
    @media (max-width: 768px) {
        .hero-section h1 {
            font-size: 32px;
        }
        
        .hero-section p {
            font-size: 16px;
        }
        
        .anime-grid {
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 15px;
        }
        
        .anime-card-title {
            font-size: 13px;
            min-height: 36px;
            
        }
        
        .badge-container {
            gap: 4px;
        }
        
        .status-badge,
        .feature-badge {
            padding: 4px 8px;
            font-size: 10px;
        }
        
        .episode-badge {
            padding: 4px 8px;
            font-size: 11px;
        }
    }
</style>

<main class="main-content">
    <div class="container">
        
        <?php if(count($hotAnime) > 0): ?>
        <div class="section-header">
            <h2><i class="fas fa-fire"></i> Hot Series</h2>
            <a href="all-anime.php" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
        </div>
        
        <div class="anime-grid">
            <?php foreach($hotAnime as $anime): ?>
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
                            <span class="feature-badge hot">
                                <i class="fas fa-fire"></i> 
                            </span>
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
        <?php endif; ?>
        
        <?php if(count($newAnime) > 0): ?>
        <div class="section-header">
            <h2><i class="fas fa-sparkles"></i> New Releases</h2>
            <a href="all-anime.php" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
        </div>
        
        <div class="anime-grid">
            <?php foreach($newAnime as $anime): ?>
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
                            <span class="feature-badge new">
                                <i class="fas fa-bolt"></i> 
                            </span>
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
        <?php endif; ?>
        
        <div class="section-header">
            <h2><i class="fas fa-clock"></i> Recently Updated</h2>
            <a href="all-anime.php" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
        </div>
        
        <div class="anime-grid">
            <?php foreach($recentAnime as $anime): ?>
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
                                    <i class="fas fa-fire"></i> 
                                </span>
                            <?php elseif($anime['is_new']): ?>
                                <span class="feature-badge new">
                                    <i class="fas fa-bolt"></i> 
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
    </div>
</main>

<?php require_once 'footer.php'; ?>