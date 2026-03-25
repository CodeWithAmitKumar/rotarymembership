<?php
require_once('config.php');

$sql = "SELECT `image_path` FROM `organisations` WHERE 1";
$result = mysqli_query( $conn, $sql );
$row = mysqli_fetch_assoc($result);
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
                    <img src="<?php echo $row['image_path']; ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; display: block;">
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
