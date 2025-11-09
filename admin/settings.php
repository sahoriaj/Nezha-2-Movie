<?php
require_once '../config.php';

if(!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$admin_id = $_SESSION['admin_id'];

// Get current admin info
$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch();

$success = '';
$error = '';

// Handle password change
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if(!password_verify($current_password, $admin['password'])) {
        $error = 'Current password is incorrect';
    } elseif($new_password !== $confirm_password) {
        $error = 'New passwords do not match';
    } elseif(strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
        $stmt->execute([$hashed, $admin_id]);
        $success = 'Password changed successfully!';
    }
}

// Handle profile update
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    
    // Check if username is taken by another admin
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
    $stmt->execute([$username, $admin_id]);
    
    if($stmt->fetch()) {
        $error = 'Username is already taken';
    } else {
        $stmt = $pdo->prepare("UPDATE admins SET username = ?, email = ? WHERE id = ?");
        $stmt->execute([$username, $email, $admin_id]);
        $_SESSION['admin_username'] = $username;
        $success = 'Profile updated successfully!';
        
        // Refresh admin data
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
        $stmt->execute([$admin_id]);
        $admin = $stmt->fetch();
    }
}

// Database cleanup
if(isset($_POST['cleanup_database'])) {
    try {
        // Delete episodes without anime
        $pdo->query("DELETE e FROM episodes e LEFT JOIN anime a ON e.anime_id = a.id WHERE a.id IS NULL");
        
        // Delete servers without episodes
        $pdo->query("DELETE s FROM servers s LEFT JOIN episodes e ON s.episode_id = e.id WHERE e.id IS NULL");
        
        $success = 'Database cleaned successfully!';
    } catch(PDOException $e) {
        $error = 'Cleanup error: ' . $e->getMessage();
    }
}

// Get statistics
$stats = [
    'anime' => $pdo->query("SELECT COUNT(*) FROM anime")->fetchColumn(),
    'episodes' => $pdo->query("SELECT COUNT(*) FROM episodes")->fetchColumn(),
    'servers' => $pdo->query("SELECT COUNT(*) FROM servers")->fetchColumn(),
    'hot' => $pdo->query("SELECT COUNT(*) FROM anime WHERE is_hot = 1")->fetchColumn(),
    'new' => $pdo->query("SELECT COUNT(*) FROM anime WHERE is_new = 1")->fetchColumn(),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin</title>
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
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .settings-grid {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
        }
        
        .settings-menu {
            background: #fff;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            height: fit-content;
        }
        
        .menu-item {
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #333;
            text-decoration: none;
        }
        
        .menu-item:hover {
            background: #f8f9fa;
        }
        
        .menu-item.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            font-weight: 600;
        }
        
        .menu-item i {
            font-size: 18px;
        }
        
        .settings-content {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }
        
        .settings-card {
            background: #fff;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .settings-card h2 {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #333;
        }
        
        .settings-card h2 i {
            color: #667eea;
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
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 10px rgba(102,126,234,0.2);
        }
        
        .btn {
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102,126,234,0.4);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #FF416C 0%, #FF4B2B 100%);
        }
        
        .btn-danger:hover {
            box-shadow: 0 10px 25px rgba(255,65,108,0.4);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }
        
        .stat-box h3 {
            font-size: 36px;
            margin-bottom: 5px;
        }
        
        .stat-box p {
            opacity: 0.9;
        }
        
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .info-box i {
            color: #2196f3;
            margin-right: 10px;
        }
        
        .danger-zone {
            border: 2px solid #ff4b5c;
            border-radius: 12px;
            padding: 20px;
            background: #fff5f5;
        }
        
        .danger-zone h3 {
            color: #ff4b5c;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        @media (max-width: 968px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }
            
            .settings-menu {
                display: flex;
                overflow-x: auto;
                padding: 15px;
            }
            
            .menu-item {
                white-space: nowrap;
                margin-bottom: 0;
                margin-right: 10px;
            }
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="admin-header-content">
            <div class="admin-logo">
                <i class="fas fa-cog"></i> Settings
            </div>
            <a href="index.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </header>
    
    <div class="container">
        <div class="settings-grid">
            <div class="settings-menu">
                <a href="#profile" class="menu-item active" onclick="showSection('profile')">
                    <i class="fas fa-user"></i> Profile
                </a>
                <a href="#password" class="menu-item" onclick="showSection('password')">
                    <i class="fas fa-key"></i> Change Password
                </a>
                <a href="#database" class="menu-item" onclick="showSection('database')">
                    <i class="fas fa-database"></i> Database
                </a>
                <a href="#site" class="menu-item" onclick="showSection('site')">
                    <i class="fas fa-globe"></i> Site Info
                </a>
            </div>
            
            <div class="settings-content">
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
                
                <!-- Profile Section -->
                <div id="profile-section" class="settings-card">
                    <h2><i class="fas fa-user-circle"></i> Profile Information</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Username</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($admin['username']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>">
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </form>
                </div>
                
                <!-- Password Section -->
                <div id="password-section" class="settings-card" style="display: none;">
                    <h2><i class="fas fa-lock"></i> Change Password</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label><i class="fas fa-key"></i> Current Password</label>
                            <input type="password" name="current_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-lock"></i> New Password</label>
                            <input type="password" name="new_password" required minlength="6">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-check-circle"></i> Confirm New Password</label>
                            <input type="password" name="confirm_password" required minlength="6">
                        </div>
                        
                        <button type="submit" name="change_password" class="btn">
                            <i class="fas fa-save"></i> Change Password
                        </button>
                    </form>
                </div>
                
                <!-- Database Section -->
                <div id="database-section" class="settings-card" style="display: none;">
                    <h2><i class="fas fa-database"></i> Database Management</h2>
                    
                    <div class="stats-grid">
                        <div class="stat-box">
                            <h3><?php echo $stats['anime']; ?></h3>
                            <p>Anime</p>
                        </div>
                        <div class="stat-box">
                            <h3><?php echo $stats['episodes']; ?></h3>
                            <p>Episodes</p>
                        </div>
                        <div class="stat-box">
                            <h3><?php echo $stats['servers']; ?></h3>
                            <p>Servers</p>
                        </div>
                    </div>
                    
                    <div class="danger-zone">
                        <h3><i class="fas fa-exclamation-triangle"></i> Danger Zone</h3>
                        <p style="margin-bottom: 15px; color: #666;">Clean up orphaned database records (episodes without anime, servers without episodes)</p>
                        <form method="POST" onsubmit="return confirm('Are you sure you want to clean the database?')">
                            <button type="submit" name="cleanup_database" class="btn btn-danger">
                                <i class="fas fa-broom"></i> Clean Database
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Site Info Section -->
                <div id="site-section" class="settings-card" style="display: none;">
                    <h2><i class="fas fa-info-circle"></i> Site Information</h2>
                    
                    <div class="info-box">
                        <i class="fas fa-globe"></i>
                        <strong>Site URL:</strong> <?php echo SITE_URL; ?>
                    </div>
                    
                    <div class="info-box">
                        <i class="fas fa-folder"></i>
                        <strong>Upload Directory:</strong> <?php echo UPLOAD_DIR; ?>
                    </div>
                    
                    <div class="info-box">
                        <i class="fas fa-database"></i>
                        <strong>Database:</strong> <?php echo DB_NAME; ?>
                    </div>
                    
                    <div class="info-box">
                        <i class="fas fa-user"></i>
                        <strong>Admin Username:</strong> <?php echo htmlspecialchars($admin['username']); ?>
                    </div>
                    
                    <div class="info-box">
                        <i class="fas fa-calendar"></i>
                        <strong>Account Created:</strong> <?php echo date('F d, Y', strtotime($admin['created_at'])); ?>
                    </div>
                    
                    <h3 style="margin-top: 30px; margin-bottom: 15px;">
                        <i class="fas fa-chart-line"></i> Statistics
                    </h3>
                    
                    <div class="stats-grid">
                        <div class="stat-box" style="background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);">
                            <h3><?php echo $stats['hot']; ?></h3>
                            <p>Hot Series</p>
                        </div>
                        <div class="stat-box" style="background: linear-gradient(135deg, #4ECDC4 0%, #44A08D 100%);">
                            <h3><?php echo $stats['new']; ?></h3>
                            <p>New Series</p>
                        </div>
                        <div class="stat-box" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <h3><?php echo $stats['anime'] > 0 ? number_format($stats['episodes'] / $stats['anime'], 1) : 0; ?></h3>
                            <p>Avg Episodes</p>
                        </div>
                    </div>
                    
                    <div class="info-box" style="background: #fff9e6; border-left-color: #ffc107;">
                        <i class="fas fa-lightbulb" style="color: #ffc107;"></i>
                        <strong>Tip:</strong> Keep your database organized by regularly cleaning up orphaned records and updating anime information.
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function showSection(section) {
            // Hide all sections
            document.getElementById('profile-section').style.display = 'none';
            document.getElementById('password-section').style.display = 'none';
            document.getElementById('database-section').style.display = 'none';
            document.getElementById('site-section').style.display = 'none';
            
            // Remove active class from all menu items
            document.querySelectorAll('.menu-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Show selected section
            document.getElementById(section + '-section').style.display = 'block';
            
            // Add active class to clicked menu item
            event.target.closest('.menu-item').classList.add('active');
            
            // Update URL hash
            window.location.hash = section;
        }
        
        // Load section from URL hash on page load
        window.addEventListener('load', function() {
            const hash = window.location.hash.substr(1);
            if(hash && ['profile', 'password', 'database', 'site'].includes(hash)) {
                const menuItem = document.querySelector(`[href="#${hash}"]`);
                if(menuItem) {
                    menuItem.click();
                }
            }
        });
    </script>
</body>
</html>