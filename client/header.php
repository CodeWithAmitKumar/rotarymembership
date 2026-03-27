<?php
require_once('config.php');

$row = ['image_path' => ''];

if (!empty($_SESSION['organisation_id'])) {
    $stmt = $conn->prepare("SELECT image_path FROM organisations WHERE organisation_id = ?");
    $stmt->bind_param("i", $_SESSION['organisation_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc() ?: $row;
    $stmt->close();
}
?>

<header class="header">
    <div class="header-left">
        <button class="menu-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Search members, events...">
        </div>
    </div>
    
    <div class="header-right">
        
        <div class="profile-section">
            <button class="profile-btn">
                <div class="profile-avatar" style="overflow: hidden;">
                    <?php if (!empty($row['image_path'])): ?>
                        <img src="<?php echo htmlspecialchars(app_url($row['image_path'])); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; display: block;" onerror="this.remove();">
                    <?php else: ?>
                        <i class="fas fa-building"></i>
                    <?php endif; ?>
                </div>
              
            </button>
            
            <div class="profile-dropdown">
                <a href="updateprofile.php" class="dropdown-item">
                    <i class="fas fa-user-circle"></i>
                    <span>My Profile</span>
                </a>
                <a href="changepass.php" class="dropdown-item">
                    <i class="fas fa-lock"></i>
                    <span>Change Password</span>
                </a>
                <a href="logout.php" class="dropdown-item" style="color: #e53e3e;">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
</header>
