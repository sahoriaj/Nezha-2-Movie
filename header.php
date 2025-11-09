<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
<link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    <?php
// ==== SITE INFO ====
$site_name = "NovaDonghua";
$base_url  = "https://novadonghua.top";

// ==== CANONICAL URL ====
$current_url = $base_url . $_SERVER['REQUEST_URI'];

// ==== DEFAULT (Homepage) ====
$page_title = $site_name;
$page_description = "Watch the latest Chinese anime (Donghua) in HD with English subtitles. Daily updates, ongoing series, and trending new releases on $site_name.";

// ==== ANIME PAGE ====
if (isset($anime) && is_array($anime) && empty($_GET['slug'])) {
    $page_title = !empty($anime['title']) ? $anime['title'] : $site_name;
    $page_description = !empty($anime['synopsis'])
        ? substr(strip_tags($anime['synopsis']), 0, 155)
        : $page_description;
    $current_url = $base_url . '/' . $anime['slug'];
}

// ==== EPISODE PAGE ====
if (isset($episode) && is_array($episode) && isset($anime) && isset($_GET['slug'])) {
    $page_title = $anime['title'] . " Episode " . $episode['episode_number'];
    $page_description = "Watch " . $anime['title'] . " Episode " . $episode['episode_number'] . 
                        " in HD with English subtitles. Stream the latest donghua episodes now on $site_name.";
    $current_url = $base_url . '/watch.php?slug=' . urlencode($_GET['slug']);
}
?>
<title><?php echo htmlspecialchars($page_title); ?></title>
<meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
<meta name="robots" content="index, follow">
<link rel="canonical" href="<?php echo htmlspecialchars($current_url); ?>">

<!-- Open Graph / Social -->
<meta property="og:title" content="<?php echo htmlspecialchars($page_title); ?>">
<meta property="og:description" content="<?php echo htmlspecialchars($page_description); ?>">
<meta property="og:url" content="<?php echo htmlspecialchars($current_url); ?>">
<meta property="og:site_name" content="<?php echo htmlspecialchars($site_name); ?>">
<meta property="og:type" content="website">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo htmlspecialchars($page_title); ?>">
<meta name="twitter:description" content="<?php echo htmlspecialchars($page_description); ?>">
    <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            text-decoration: none;
        }
        
        .logo-text {
            font-size: 32px;
            font-weight: bold;
            color: #fff;
        }
        
        .logo-text span:first-child {
            color: #FF6B35;
        }
        
        
        .social-links {
            display: flex;
            gap: 15px;
        }
        
        .social-links a {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 18px;
            transition: all 0.3s;
        }
        
        .social-links a:hover {
            background: #FF6B35;
            transform: translateY(-2px);
        }
        
/* Light Mode (default) */
.search-section {
  background: #fff; /* pure white background */
  padding: 30px 0;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
  transition: background 0.5s ease, box-shadow 0.5s ease;
}

/* Dark Mode */
body.dark-mode .search-section {
  background: #0c0c0c;
}

/* Section title (optional styling) */
.search-section h2 {
  font-size: 28px;
  color: #fff;
  display: flex;
  align-items: center;
  gap: 10px;
  justify-content: center;
  font-weight: 700;
  margin: 0 0 15px;
  text-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
}

/* Adjust heading color for Light Mode */
body:not(.dark-mode) .search-section h2 {
  color: #fff;
}

        
        .search-box {
            position: relative;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .search-box input {
            width: 100%;
            padding: 15px 50px 15px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 50px;
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
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: linear-gradient(45deg, cyan, #00251c);
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            color: #fff;
            cursor: pointer;
            font-size: 18px;
            transition: all 0.3s;
        }
        
        .search-box button:hover {
            transform: translateY(-50%) scale(1.1);
        }
        
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            max-height: 400px;
            overflow-y: auto;
            margin-top: 10px;
            display: none;
            z-index: 1000;
        }
        
        .search-result-item {
            display: flex;
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            text-decoration: none;
            color: #333;
            transition: background 0.3s;
        }
        
        .search-result-item:hover {
            background: #f8f9fa;
        }
        
        .search-result-item img {
            width: 60px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 15px;
        }
        
        .search-result-info h4 {
            margin-bottom: 5px;
            color: #667eea;
        }
        
        .search-result-info p {
            font-size: 13px;
            color: #666;
        }
        
        .main-content {
            padding: 30px 0;
        }
        
        .breadcrumb {
            background: #fff;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .breadcrumb a {
            color: #667eea;
            text-decoration: none;
            margin-right: 10px;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .breadcrumb i {
            margin: 0 10px;
            color: #999;
        }
        
/* Header with Blue Gradient */
.site-header {
  background: linear-gradient(45deg, #000125, #0e00c7);
  padding: 10px 20px;
  position: sticky;
  top: 0;
  z-index: 999;
  border-bottom: none;
}

.header-container {
  display: flex;
  align-items: center;
  justify-content: space-between;
  max-width: 1100px;
  margin: 0 auto;
}

.logo img {
  height: 40px;
  width: auto;
}

.header-controls {
  display: flex;
  align-items: center;
  gap: 15px;
}

/* === Your slider switch (unchanged) === */
.switch {
  position: relative;
  display: inline-block;
  width: 60px;
  height: 34px;
}

.switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #ccc;
  transition: .4s;
}

.slider:before {
  position: absolute;
  content: "";
  height: 26px;
  width: 26px;
  left: 4px;
  bottom: 4px;
  background-color: white;
  transition: .4s;
}

input:checked + .slider {
  background-color: #2196F3;
}

input:focus + .slider {
  box-shadow: 0 0 1px #2196F3;
}

input:checked + .slider:before {
  transform: translateX(26px);
}

/* Rounded sliders */
.slider.round {
  border-radius: 34px;
}
.slider.round:before {
  border-radius: 50%;
}

/* Menu icon */
.nav-toggle {
  color: #fff;
  font-size: 22px;
  cursor: pointer;
}

/* Dark mode styles */
body.dark-mode {
  background: #0c0c0c;
  color: #eee;
}
body.dark-mode .site-header {
  background: #000;
}
body.dark-mode .slider {
  background-color: #444;
}
body.dark-mode input:checked + .slider {
  background-color: #00aaff;
}

/* Toggle */
.nav-toggle {
  color: #fff;
  font-size: 22px;
  cursor: pointer;
}

/* Overlay (background blur) */
.overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  backdrop-filter: blur(3px);
  background: rgba(0, 0, 0, 0.45);
  z-index: 900;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.3s ease;
}
.overlay.active {
  opacity: 1;
  visibility: visible;
}

/* Right Slide Menu */
.nav-menu {
  position: fixed;
  top: -100;
  right: -200px;
  width: 120px;
  height: 29vh; /* Full height fix */
  background: linear-gradient(45deg, #000125, #0e00c7);
  border-radius: 12px 0 0 12px; /* Only top-left rounded */
  box-shadow: -3px 0 10px rgba(0, 0, 0, 0.3);
  transition: right 0.3s ease;
  z-index: 1000;
  display: flex;
  flex-direction: column;
  overflow-y: auto; /* scrolls if content grows */
}
.nav-menu.active {
  right: 0;
}

/* Close Button */
.menu-header {
  display: flex;
  justify-content: flex-end;
  padding: 12px 16px 6px;
}
.close-btn {
  color: #fff;
  font-size: 20px;
  cursor: pointer;
}

/* Menu Items */
.nav-menu ul {
  list-style: none;
  margin: 0;
  padding: 0 0 15px;
}
.nav-menu li {
  margin: 3px 0;
}
.nav-menu a {
  display: flex;
  align-items: center;
  color: #fff;
  padding: 10px 20px;
  text-decoration: none;
  font-weight: 500;
  font-size: 15px;
  border-radius: 8px;
  transition: background 0.3s ease;
}
.nav-menu a:hover {
  background: rgba(255, 255, 255, 0.15);
}
.nav-menu i {
  font-size: 16px;
  width: 24px;
  text-align: center;
}

/* Desktop View */
@media (min-width: 769px) {
  .overlay {
    display: none;
  }
  .nav-toggle {
    display: none;
  }
  .nav-menu {
    position: static;
    background: linear-gradient(45deg, #00ff42, #000);
    box-shadow: none;
    height: auto;
    width: auto;
    flex-direction: row;
    border-radius: 0;
  }
  .nav-menu ul {
    display: flex;
  }
  .nav-menu li {
    margin: 0 10px;
  }
  .menu-header {
    display: none;
  }
}
    </style>
</head>
<body>
<!-- Header -->
<header class="site-header">
  <div class="header-container">
    <!-- Logo -->
    <a href="/" class="logo" aria-label="Nova Donghua">
      <img
        src="https://blogger.googleusercontent.com/img/a/AVvXsEiM-qT1Af7WlPoO55lrQ67eZ9EviqkgBjB5GwosWl-N3iK5Yp9zZpkRgzm4Yitl78EAFV2wvNNKAJ6TlmNp6i7slTpXylHiVZN8lnAptigt0KXkJUVqnFiX5GHofLIilFHit4sZTcu5Nam58cCp25WZ4yJJ5KR9jSTJzqbchhoUjmUxDg0uedGnvlh7C_ZF=w800"
        alt="Nova Donghua"
        height="40"
      />
    </a>

    <!-- Right side: toggle + menu -->
    <div class="header-controls">
      <!-- Rounded Dark Mode Switch -->
      <label class="switch">
        <input type="checkbox" id="themeToggle">
        <span class="slider round"></span>
      </label>

      <!-- Menu Icon -->
      <div class="nav-toggle" id="navToggle">
        <i class="fa fa-bars"></i>
      </div>
    </div>
  </div>
</header>


<!-- Overlay -->
  <div class="overlay" id="overlay"></div>

  <!-- Right Slide Menu -->
  <nav class="nav-menu" id="navMenu">
    <div class="menu-header">
      <span class="close-btn" id="closeBtn"><i class="fa fa-close"></i></span>
    </div>
    <ul>
      <li><a href="a-z.php"><i class="fa fa-list"></i>A-Z List</a></li>
      <li><a href="bookmarks.php"><i class="fa fa-bookmark"></i>Bookmarks</a></li>
      <li><a href="history.php"><i class="fa fa-history"></i>History</a></li>
      <li><a href="all-anime.php"><i class="fa fa-shield"></i>All anime</a></li>
      <li><a href="privacy.php"><i class="fa fa-lock"></i>Privacy</a></li>
    </ul>
  </nav>
    <section class="search-section">
        <div class="container">
            <div class="search-box">
                <input type="text" id="liveSearch" placeholder="Search anime..." autocomplete="off">
                <button><i class="fas fa-search"></i></button>
                <div class="search-results" id="searchResults"></div>
            </div>
        </div>
    </section>
    <script>
   document.addEventListener("DOMContentLoaded", function () {
  // === DARK MODE TOGGLE ===
  const themeToggle = document.getElementById('themeToggle');
  const body = document.body;

  if (themeToggle) {
    // Load saved theme
    if (localStorage.getItem('theme') === 'dark') {
      body.classList.add('dark-mode');
      themeToggle.checked = true;
    }

    // Toggle switch
    themeToggle.addEventListener('change', () => {
      if (themeToggle.checked) {
        body.classList.add('dark-mode');
        localStorage.setItem('theme', 'dark');
      } else {
        body.classList.remove('dark-mode');
        localStorage.setItem('theme', 'light');
      }
    });
  }

  // === NAV MENU TOGGLE ===
  const navToggle = document.getElementById('navToggle');
  const navMenu = document.getElementById('navMenu');
  const overlay = document.getElementById('overlay');
  const closeBtn = document.getElementById('closeBtn');

  // Make sure all elements exist before using them
  if (navToggle && navMenu && overlay && closeBtn) {
    navToggle.addEventListener('click', () => {
      navMenu.classList.add('active');
      overlay.classList.add('active');
    });

    closeBtn.addEventListener('click', () => {
      navMenu.classList.remove('active');
      overlay.classList.remove('active');
    });

    overlay.addEventListener('click', () => {
      navMenu.classList.remove('active');
      overlay.classList.remove('active');
    });
  }
});
        // Live Search
        const searchInput = document.getElementById('liveSearch');
        const searchResults = document.getElementById('searchResults');
        
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length < 2) {
                searchResults.style.display = 'none';
                return;
            }
            
            searchTimeout = setTimeout(() => {
                fetch('search.php?q=' + encodeURIComponent(query))
                    .then(res => res.json())
                    .then(data => {
                        if (data.length > 0) {
                            searchResults.innerHTML = data.map(anime => `
                                <a href="anime.php?slug=${anime.slug}" class="search-result-item">
                                    <img src="${anime.poster || 'uploads/default.jpg'}" alt="${anime.title}">
                                    <div class="search-result-info">
                                        <h4>${anime.title}</h4>
                                        <p>${anime.synopsis ? anime.synopsis.substring(0, 100) + '...' : 'No description'}</p>
                                    </div>
                                </a>
                            `).join('');
                            searchResults.style.display = 'block';
                        } else {
                            searchResults.innerHTML = '<div style="padding: 20px; text-align: center;">No results found</div>';
                            searchResults.style.display = 'block';
                        }
                    });
            }, 300);
        });
        
        // Close search results when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });
    </script>