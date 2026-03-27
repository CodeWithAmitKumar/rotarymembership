<?php
require_once 'config.php';
require_once 'functions.php';

if (!isset($_SESSION['organisation_id'])) {
    header("Location: index.php");
    exit();
}

if (!function_exists('render_client_header')) {
    function render_client_header(): void
    {
        global $conn;

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
        <?php
    }
}

if (!function_exists('render_client_shared_styles')) {
    function render_client_shared_styles(): void
    {
        ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    :root {
        --primary-gradient: linear-gradient(180deg, #0f4c81 0%, #1b6ca8 100%);
        --primary-color: #1b6ca8;
        --secondary-color: #2d8f85;
        --success-color: #10b981;
        --danger-color: #ef4444;
        --edit-color: #3b82f6;
        --border-color: #e2e8f0;
        --sidebar-width: 260px;
        --header-height: 70px;
        --bg-color: #eef3f8;
        --text-dark: #2d3748;
        --text-light: #64748b;
        --white: #ffffff;
        --card-shadow: 0 18px 40px rgba(15, 76, 129, 0.08);
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background:
            radial-gradient(circle at top right, rgba(45, 143, 133, 0.12), transparent 24%),
            radial-gradient(circle at top left, rgba(27, 108, 168, 0.12), transparent 18%),
            var(--bg-color);
        min-height: 100vh;
        color: var(--text-dark);
        overflow-x: hidden;
    }

    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        width: var(--sidebar-width);
        height: 100vh;
        background: var(--primary-gradient);
        padding: 20px;
        z-index: 250;
        overflow-y: auto;
        box-shadow: 2px 0 15px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
    }

    .sidebar-logo {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 0 30px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.15);
        margin-bottom: 25px;
    }

    .sidebar-logo .logo-icon {
        width: 45px;
        height: 45px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        color: var(--white);
    }

    .sidebar-logo .logo-text {
        font-size: 20px;
        font-weight: 700;
        color: var(--white);
        letter-spacing: 0.5px;
    }

    .sidebar-menu {
        list-style: none;
    }

    .sidebar-menu li {
        margin-bottom: 8px;
    }

    .sidebar-menu a {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px 16px;
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        border-radius: 10px;
        transition: all 0.3s ease;
        font-size: 15px;
        font-weight: 500;
    }

    .sidebar-menu a:hover,
    .sidebar-menu a.active {
        background: rgba(255, 255, 255, 0.2);
        color: var(--white);
        transform: translateX(5px);
    }

    .sidebar-menu a i {
        width: 20px;
        text-align: center;
        font-size: 18px;
    }

    .submenu {
        display: none;
        list-style: none;
        padding-left: 16px;
    }

    .submenu.show {
        display: block;
    }

    .submenu li {
        margin-bottom: 4px;
    }

    .submenu li a {
        font-size: 14px;
        padding: 10px 12px 10px 36px;
    }

    .main-content {
        margin-left: var(--sidebar-width);
        min-height: 100vh;
    }

    .header {
        position: fixed;
        top: 0;
        left: var(--sidebar-width);
        width: calc(100% - var(--sidebar-width));
        height: var(--header-height);
        background: rgba(255, 255, 255, 0.94);
        padding: 0 30px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: var(--card-shadow);
        border-bottom: 1px solid rgba(148, 163, 184, 0.18);
        backdrop-filter: blur(12px);
        z-index: 200;
    }

    .header-left {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .menu-toggle {
        display: none;
        background: none;
        border: none;
        font-size: 22px;
        color: var(--text-dark);
        cursor: pointer;
    }

    .search-box {
        position: relative;
        width: 300px;
    }

    .search-box input {
        width: 100%;
        padding: 12px 15px 12px 45px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: 14px;
        outline: none;
        transition: all 0.3s ease;
        background: #f7fafc;
    }

    .search-box input:focus {
        border-color: var(--primary-color);
        background: var(--white);
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .search-box i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-light);
    }

    .header-right {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .profile-section {
        position: relative;
    }

    .profile-btn {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 6px 12px 6px 6px;
        background: #f7fafc;
        border: none;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .profile-btn:hover {
        background: #edf2f7;
    }

    .profile-avatar {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: var(--primary-gradient);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--white);
        font-size: 16px;
        font-weight: 600;
        overflow: hidden;
    }

    .profile-dropdown {
        position: absolute;
        top: 120%;
        right: 0;
        width: 220px;
        background: var(--white);
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        padding: 10px;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.3s ease;
        z-index: 1001;
    }

    .profile-section:hover .profile-dropdown {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .dropdown-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        color: var(--text-dark);
        text-decoration: none;
        border-radius: 8px;
        transition: all 0.2s ease;
        font-size: 14px;
    }

    .dropdown-item:hover {
        background: #f7fafc;
        color: var(--primary-color);
    }

    .dropdown-item i {
        width: 18px;
        color: var(--text-light);
    }

    .dashboard-content {
        padding: 102px 30px 32px;
    }

    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
        }

        .sidebar.active {
            transform: translateX(0);
        }

        .main-content {
            margin-left: 0;
        }

        .header {
            left: 0;
            width: 100%;
            padding: 0 16px;
        }

        .menu-toggle {
            display: block;
        }

        .search-box {
            display: none;
        }

        .dashboard-content {
            padding: 90px 15px 20px;
        }
    }
</style>
        <?php
    }
}
