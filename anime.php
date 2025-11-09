<?php
require_once 'config.php';

$slug = $_GET['slug'] ?? '';

if(empty($slug)) {
    header('Location: index.php');
    exit;
}

// Get anime details
$stmt = $pdo->prepare("SELECT * FROM anime WHERE slug = ?");
$stmt->execute([$slug]);
$anime = $stmt->fetch();

if(!$anime) {
    header('Location: index.php');
    exit;
}

// Get episodes
$stmt = $pdo->prepare("SELECT * FROM episodes WHERE anime_id = ? ORDER BY episode_number ASC");
$stmt->execute([$anime['id']]);
$episodes = $stmt->fetchAll();

// Helper function to get poster URL
function getPosterUrl($poster) {
    if(empty($poster)) {
        return 'uploads/default.jpg';
    }
    // Check if it's a full URL
    if(filter_var($poster, FILTER_VALIDATE_URL)) {
        return $poster;
    }
    // Otherwise it's a local file
    return 'uploads/' . $poster;
}

$page_title = $anime['title'];
require_once 'header.php';
?>

<style>
/* ========== LIGHT MODE (default) ========== */
.anime-detail {
    background: #fff;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.anime-detail-header {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 30px;
    padding: 30px;
}

.anime-poster {
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.anime-poster img {
    width: 50%;
    height: auto;
    display: block;
    object-fit: cover;
}

.anime-info h1 {
    font-size: 32px;
    margin-bottom: 10px;
    color: #333;
    display: flex;
    align-items: center;
    gap: 15px;
}

.anime-info .zh-name {
    font-size: 18px;
    color: #666;
    margin-bottom: 20px;
}

.anime-meta {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f8f9fa;
    padding: 8px 15px;
    border-radius: 8px;
    font-size: 14px;
}

.meta-item i {
    color: #667eea;
}

.play-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    padding: 15px 35px;
    border-radius: 50px;
    text-decoration: none;
    font-size: 18px;
    font-weight: 600;
    box-shadow: 0 10px 30px rgba(102,126,234,0.4);
    transition: all 0.3s;
    border: none;
    cursor: pointer;
}

.play-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 40px rgba(102,126,234,0.6);
}

.synopsis {
    padding: 30px;
    border-top: 1px solid #f0f0f0;
}

.synopsis h2 {
    font-size: 24px;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.synopsis h2 i {
    color: #667eea;
}

.synopsis p {
    line-height: 1.8;
    color: #555;
    font-size: 15px;
}

.episodes-section {
    background: #fff;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.episodes-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.episodes-header h2 {
    font-size: 24px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.episodes-header i {
    color: #667eea;
}

.order-toggle {
    background: #f8f9fa;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    transition: all 0.3s;
}

.order-toggle:hover {
    background: #667eea;
    color: #fff;
}

.episodes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 15px;
}

.episode-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    padding: 15px;
    border-radius: 10px;
    text-decoration: none;
    text-align: center;
    font-weight: 600;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    box-shadow: 0 5px 15px rgba(102,126,234,0.3);
}

.episode-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(102,126,234,0.5);
}

@media (max-width: 768px) {
    .anime-detail-header {
        grid-template-columns: 1fr;
    }

    .episodes-grid {
        grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
    }
}

/* ========== DARK MODE ========== */
body.dark-mode {
    background: #0c0c0c;
    color: #e0e0e0;
}

body.dark-mode .anime-detail,
body.dark-mode .episodes-section {
    background: linear-gradient(45deg, #1a0033, #000);
    box-shadow: 0 0 20px rgba(102,126,234,0.3);
}

body.dark-mode .anime-info h1 {
    color: #fff;
}

body.dark-mode .zh-name {
    color: #bbb;
}

body.dark-mode .meta-item {
    background: rgba(255,255,255,0.05);
    color: #ddd;
}

body.dark-mode .meta-item i {
    color: #00ff9d;
}

body.dark-mode .synopsis {
    border-top: 1px solid rgba(255,255,255,0.1);
}

body.dark-mode .synopsis h2 i,
body.dark-mode .episodes-header i {
    color: #00ff9d;
}

body.dark-mode .synopsis p {
    color: #ccc;
}

body.dark-mode .order-toggle {
    background: rgba(255,255,255,0.1);
    color: #eee;
}

body.dark-mode .order-toggle:hover {
    background: #00ff9d;
    color: #000;
}

body.dark-mode .episode-btn {
    background: linear-gradient(135deg, #00ff9d 0%, #0066ff 100%);
    box-shadow: 0 5px 15px rgba(0,255,157,0.3);
}

body.dark-mode .episode-btn:hover {
    box-shadow: 0 10px 25px rgba(0,255,157,0.5);
}

/* Breadcrumb wrapper */
.breadcrumb {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
  font-size: 15px;
  background: #fff;
  color: #333;
  padding: 12px 20px;
  border-radius: 8px;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
  margin-bottom: 25px;
  transition: background 0.4s ease, color 0.4s ease, box-shadow 0.4s ease;
}

/* Links */
.breadcrumb a {
  color: #667eea;
  text-decoration: none;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: color 0.3s ease;
}

.breadcrumb a:hover {
  color: #764ba2;
}

/* Separator icon */
.breadcrumb i {
  color: #999;
  font-size: 13px;
}

/* Current page text */
.breadcrumb span {
  color: #666;
  font-weight: 500;
}

/* ========== DARK MODE ========== */
body.dark-mode .breadcrumb {
  background: linear-gradient(45deg, #1a0033, #000);
  color: #eee;
  box-shadow: 0 5px 20px rgba(0, 255, 157, 0.15);
}

body.dark-mode .breadcrumb a {
  color: #00ff9d;
}

body.dark-mode .breadcrumb a:hover {
  color: #00c3ff;
}

body.dark-mode .breadcrumb i {
  color: #00ff9d;
}

body.dark-mode .breadcrumb span {
  color: #ccc;
}

</style>


<main class="main-content">
    <div class="container">
        <div class="breadcrumb">
            <a href="/"><i class="fas fa-home"></i> Home</a>
            <i class="fas fa-chevron-right"></i>
            <span><?php echo htmlspecialchars($anime['title']); ?></span>
        </div>
        
        <div class="anime-detail">
            <div class="anime-detail-header">
                <div class="anime-poster">
                    <img src="<?php echo getPosterUrl($anime['poster']); ?>" 
                         alt="<?php echo htmlspecialchars($anime['title']); ?>"
                         onerror="this.src='uploads/default.jpg'">
                </div>
                <div class="anime-info">
                    <h1>
                        <?php echo htmlspecialchars($anime['title']); ?>
                        <?php if($anime['is_hot']): ?>
                            <span style="font-size: 20px; color: #FF416C;"><i class="fas fa-fire"></i></span>
                        <?php endif; ?>
                    </h1>
                    <?php if($anime['zh_name']): ?>
                        <div class="zh-name"><i class="fas fa-language"></i> <?php echo htmlspecialchars($anime['zh_name']); ?></div>
                    <?php endif; ?>
                    
                    <div class="anime-meta">
                        <div class="meta-item">
                            <i class="fas fa-calendar"></i>
                            <span>Updated: <?php echo date('M d, Y', strtotime($anime['update_date'])); ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-info-circle"></i>
                            <span><?php echo ucfirst($anime['status']); ?></span>
                        </div>
                        <?php if($anime['type']): ?>
                        <div class="meta-item">
                            <i class="fas fa-tag"></i>
                            <span><?php echo htmlspecialchars($anime['type']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="meta-item">
                            <i class="fas fa-film"></i>
                            <span><?php echo count($episodes); ?> Episodes</span>
                        </div>
                    </div>
                    
                    <?php if(count($episodes) > 0): ?>
                        <a href="watch.php?slug=<?php echo $episodes[0]['slug']; ?>" class="play-btn">
                            <i class="fas fa-play-circle"></i> Play Now
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if($anime['synopsis']): ?>
            <div class="synopsis">
                <h2><i class="fas fa-align-left"></i> Synopsis</h2>
                <p><?php echo nl2br(htmlspecialchars($anime['synopsis'])); ?></p>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if(count($episodes) > 0): ?>
        <div class="episodes-section">
            <div class="episodes-header">
                <h2><i class="fas fa-list-ol"></i> Episodes</h2>
                <button class="order-toggle" onclick="toggleOrder()">
                    <i class="fas fa-sort"></i> <span id="orderText">Reverse Order</span>
                </button>
            </div>
            <div class="episodes-grid" id="episodesGrid">
                <?php foreach($episodes as $episode): ?>
                    <a href="watch.php?slug=<?php echo $episode['slug']; ?>" class="episode-btn">
                        <i class="fas fa-play"></i> <?php echo $episode['episode_number']; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<script>
    function toggleOrder() {
        const grid = document.getElementById('episodesGrid');
        const items = Array.from(grid.children);
        items.reverse();
        grid.innerHTML = '';
        items.forEach(item => grid.appendChild(item));
        
        const orderText = document.getElementById('orderText');
        orderText.textContent = orderText.textContent === 'Reverse Order' ? 'Normal Order' : 'Reverse Order';
    }
</script>

<?php require_once 'footer.php'; ?>