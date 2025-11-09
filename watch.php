<?php
require_once 'config.php';

$slug = $_GET['slug'] ?? '';

if(empty($slug)) {
    header('Location: index.php');
    exit;
}

// Get episode details
$stmt = $pdo->prepare("SELECT e.*, a.title as anime_title, a.slug as anime_slug 
                       FROM episodes e 
                       JOIN anime a ON e.anime_id = a.id 
                       WHERE e.slug = ?");
$stmt->execute([$slug]);
$episode = $stmt->fetch();

if(!$episode) {
    header('Location: index.php');
    exit;
}

// Get servers for this episode
$stmt = $pdo->prepare("SELECT * FROM servers WHERE episode_id = ? ORDER BY is_default DESC");
$stmt->execute([$episode['id']]);
$servers = $stmt->fetchAll();

// Get all episodes of this anime
$stmt = $pdo->prepare("SELECT * FROM episodes WHERE anime_id = ? ORDER BY episode_number ASC");
$stmt->execute([$episode['anime_id']]);
$allEpisodes = $stmt->fetchAll();

// Find previous and next episodes
$currentIndex = array_search($episode['episode_number'], array_column($allEpisodes, 'episode_number'));
$prevEpisode = $currentIndex > 0 ? $allEpisodes[$currentIndex - 1] : null;
$nextEpisode = $currentIndex < count($allEpisodes) - 1 ? $allEpisodes[$currentIndex + 1] : null;

$page_title = $episode['anime_title'] . ' - Episode ' . $episode['episode_number'];
require_once 'header.php';
?>

<style>
/* ========== LIGHT MODE (default) ========== */
.watch-container {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 20px;
}

/* Player Section */
.player-section {
    background: #fff;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.player-wrapper {
    position: relative;
    padding-top: 56.25%;
    background: #000;
}

.player-wrapper iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border: none;
}

.player-info {
    padding: 20px;
}

.player-info h1 {
    font-size: 24px;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.player-info h1 i {
    color: #667eea;
}

.episode-title {
    color: #666;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Server Selector */
.server-selector {
    margin-bottom: 20px;
}

.server-selector h3 {
    font-size: 16px;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.server-selector h3 i {
    color: #667eea;
}

.servers {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.server-btn {
    padding: 10px 20px;
    background: #f8f9fa;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
}

.server-btn:hover {
    background: #667eea;
    color: #fff;
}

.server-btn.active {
    background: linear-gradient(45deg, red, #000);
    color: #fff;
    border-color: #667eea;
    box-shadow: 0px 6px 15px -2px red;
}

/* Navigation Buttons */
.navigation-btns {
    display: flex;
    gap: 15px;
    justify-content: space-between;
}

.nav-btn {
    flex: 1;
    padding: 15px;
    background: linear-gradient(45deg, red, #000);
    color: #fff;
    text-decoration: none;
    border-radius: 10px;
    text-align: center;
    font-weight: 600;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    box-shadow: 0 5px 15px rgba(102,126,234,0.3);
}

.nav-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(102,126,234,0.5);
}



/* Episodes Sidebar */
.episodes-sidebar {
    background: #fff;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    height: fit-content;
    max-height: calc(100vh - 100px);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.sidebar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.sidebar-header h3 {
    font-size: 18px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.sidebar-header i {
    color: #667eea;
}

.drawer-toggle {
    background: #667eea;
    color: #fff;
    border: none;
    width: 35px;
    height: 35px;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    transition: all 0.3s;
}

.drawer-toggle:hover {
    background: #764ba2;
    transform: rotate(180deg);
}

/* Episode Search */
.episode-search {
    margin-bottom: 15px;
}

.episode-search input {
    width: 100%;
    padding: 10px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
}

.episode-search input:focus {
    outline: none;
    border-color: #667eea;
}

/* Episode List */
.episodes-list {
    overflow-y: auto;
    flex: 1;
    padding-right: 10px;
}

.episodes-list::-webkit-scrollbar {
    width: 6px;
}

.episodes-list::-webkit-scrollbar-thumb {
    background: #667eea;
    border-radius: 3px;
}

.episode-item {
    padding: 12px 15px;
    background: #f8f9fa;
    margin-bottom: 10px;
    border-radius: 8px;
    text-decoration: none;
    color: #333;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: all 0.3s;
    cursor: pointer;
}

.episode-item:hover {
    background: #667eea;
    color: #fff;
    transform: translateX(5px);
}

.episode-item.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    font-weight: 600;
    box-shadow: 0 3px 10px rgba(102,126,234,0.4);
}

.episode-item i {
    font-size: 14px;
}

.drawer-collapsed .episodes-list,
.drawer-collapsed .episode-search {
    display: none;
}

/* Responsive */
@media (max-width: 1024px) {
    .watch-container {
        grid-template-columns: 1fr;
    }
}

/* ========== DARK MODE ========== */
body.dark-mode {
    background: #0c0c0c;
    color: #e0e0e0;
}

body.dark-mode .player-section,
body.dark-mode .episodes-sidebar {
    background: linear-gradient(45deg, #1a0033, #000);
    box-shadow: 0 0 20px rgba(0, 255, 157, 0.15);
}

body.dark-mode .player-info h1,
body.dark-mode .sidebar-header h3 {
    color: #fff;
}

body.dark-mode .player-info h1 i,
body.dark-mode .sidebar-header i,
body.dark-mode .server-selector h3 i {
    color: #00ff9d;
}

body.dark-mode .episode-title {
    color: #ccc;
}

body.dark-mode .server-btn {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    color: #ddd;
}

body.dark-mode .server-btn:hover {
    background: #00ff9d;
    color: #000;
    border-color: #00ff9d;
}

body.dark-mode .server-btn.active {
    background: linear-gradient(135deg, #00ff9d 0%, #0066ff 100%);
    border-color: #00ff9d;
    box-shadow: 0 5px 15px rgba(0,255,157,0.3);
}

body.dark-mode .nav-btn {
    background: linear-gradient(135deg, #00ff9d 0%, #0066ff 100%);
    box-shadow: 0 5px 15px rgba(0,255,157,0.3);
}

body.dark-mode .nav-btn:hover {
    box-shadow: 0 10px 25px rgba(0,255,157,0.5);
}

body.dark-mode .episode-item {
    background: rgba(255,255,255,0.05);
    color: #eee;
}

body.dark-mode .episode-item:hover {
    background: #00ff9d;
    color: #000;
}

body.dark-mode .episode-item.active {
    background: linear-gradient(135deg, #00ff9d 0%, #0066ff 100%);
    color: #fff;
}

body.dark-mode .episode-search input {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    color: #fff;
}

body.dark-mode .drawer-toggle {
    background: #00ff9d;
    color: #000;
}

body.dark-mode .drawer-toggle:hover {
    background: #0066ff;
    color: #fff;
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
            <a href="anime.php?slug=<?php echo $episode['anime_slug']; ?>">
                <?php echo htmlspecialchars($episode['anime_title']); ?>
            </a>
            <i class="fas fa-chevron-right"></i>
            <span>Episode <?php echo $episode['episode_number']; ?></span>
        </div>
        
        <div class="watch-container">
            <div class="player-section">
                <div class="player-wrapper">
                    <iframe id="playerFrame" src="<?php echo !empty($servers) ? $servers[0]['server_url'] : ''; ?>" allowfullscreen></iframe>
                </div>
                
                <div class="player-info">
                    <h1>
                        <i class="fas fa-tv"></i>
                        <?php echo htmlspecialchars($episode['anime_title']); ?>
                    </h1>
                    <div class="episode-title">
                        <i class="fas fa-play-circle"></i>
                        Episode <?php echo $episode['episode_number']; ?>
                        <?php if($episode['title']): ?>
                            - <?php echo htmlspecialchars($episode['title']); ?>
                        <?php endif; ?>
                    </div>
                    
                    <?php if(!empty($servers)): ?>
                    <div class="server-selector">
                        <h3><i class="fas fa-server"></i> Select Server</h3>
                        <div class="servers">
                            <?php foreach($servers as $index => $server): ?>
                                <button class="server-btn <?php echo $index === 0 ? 'active' : ''; ?>" 
                                        onclick="changeServer('<?php echo htmlspecialchars($server['server_url']); ?>', this)">
                                    <i class="fas fa-hdd"></i>
                                    <?php echo htmlspecialchars($server['server_name']); ?>
                                    <?php if($server['quality']): ?>
                                        <span style="font-size: 11px; opacity: 0.8;">(<?php echo $server['quality']; ?>)</span>
                                    <?php endif; ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="navigation-btns">
                        <?php if($prevEpisode): ?>
                            <a href="watch.php?slug=<?php echo $prevEpisode['slug']; ?>" class="nav-btn">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php else: ?>
                            <a class="nav-btn disabled">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php if($nextEpisode): ?>
                            <a href="watch.php?slug=<?php echo $nextEpisode['slug']; ?>" class="nav-btn">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php else: ?>
                            <a class="nav-btn disabled">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="episodes-sidebar" id="episodesSidebar">
                <div class="sidebar-header">
                    <h3><i class="fas fa-list"></i> All Episodes</h3>
                    <button class="drawer-toggle" onclick="toggleDrawer()">
                        <i class="fas fa-chevron-up" id="drawerIcon"></i>
                    </button>
                </div>
                
                <div class="episode-search">
                    <input type="text" id="episodeSearch" placeholder="Search episodes..." onkeyup="searchEpisodes()">
                </div>
                
                <div class="episodes-list" id="episodesList">
                    <?php foreach($allEpisodes as $ep): ?>
                        <a href="watch.php?slug=<?php echo $ep['slug']; ?>" 
                           class="episode-item <?php echo $ep['id'] === $episode['id'] ? 'active' : ''; ?>" 
                           data-episode="<?php echo $ep['episode_number']; ?>">
                            <i class="fas fa-play"></i>
                            <span>Episode <?php echo $ep['episode_number']; ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    function changeServer(url, btn) {
        document.getElementById('playerFrame').src = url;
        document.querySelectorAll('.server-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
    }
    
    function toggleDrawer() {
        const sidebar = document.getElementById('episodesSidebar');
        const icon = document.getElementById('drawerIcon');
        sidebar.classList.toggle('drawer-collapsed');
        icon.classList.toggle('fa-chevron-up');
        icon.classList.toggle('fa-chevron-down');
    }
    
    function searchEpisodes() {
        const searchValue = document.getElementById('episodeSearch').value.toLowerCase();
        const episodes = document.querySelectorAll('.episode-item');
        
        episodes.forEach(episode => {
            const episodeNumber = episode.getAttribute('data-episode');
            if(episodeNumber.includes(searchValue)) {
                episode.style.display = 'flex';
            } else {
                episode.style.display = 'none';
            }
        });
    }
</script>

<?php require_once 'footer.php'; ?>