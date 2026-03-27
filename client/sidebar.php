<?php
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


<!-- Sidebar -->
<style>
.sidebar {
    width: 250px;
    background: #1e293b;
    height: 100vh;
    color: #fff;
    padding: 20px;
}

.sidebar-logo {
    display: flex;
    align-items: center;
    margin-bottom: 30px;
}

.logo-icon {
    margin-right: 10px;
}

.sidebar-menu {
    list-style: none;
    padding: 0;
}

.sidebar-menu li {
    margin-bottom: 10px;
}

.sidebar-menu a {
    color: #fff;
    text-decoration: none;
    display: flex;
    align-items: center;
    padding: 10px;
    border-radius: 5px;
}

.sidebar-menu a:hover,
.sidebar-menu a.active {
    background: #334155;
}

.sidebar-menu i {
    margin-right: 10px;
}

/* ✅ Submenu hidden by default */
.submenu {
    display: none;
    list-style: none;
    padding-left: 20px;
}

/* ✅ Show submenu when active */
.submenu.show {
    display: block;
}

.submenu li a {
    font-size: 14px;
    padding: 6px 0;
}
</style>

<aside class="sidebar">
    <div class="sidebar-logo">
      <div class="profile-avatar" style="overflow: hidden;">
                    <?php if (!empty($row['image_path'])): ?>
                        <img src="<?php echo htmlspecialchars(app_url($row['image_path'])); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; display: block;" onerror="this.remove();">
                    <?php else: ?>
                        <i class="fas fa-building"></i>
                    <?php endif; ?>
                </div>
        <span class="logo-text"><?php echo $_SESSION['organisation_name']; ?></span>
    </div>
    
    <ul class="sidebar-menu">
        <li>
            <a href="welcome.php" <?php echo $active_page === 'dashboard' ? 'class="active"' : ''; ?>>
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <!-- Settings Menu -->
        <li class="menu-item">
            <a href="#" onclick="toggleMenu(event)" <?php echo $active_page === 'settings' ? 'class="active"' : ''; ?>>
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>

            <ul class="submenu">
                <li><a href="sessions.php">Sessions</a></li>
                <li><a href="payment_heads.php">Payment Head</a></li>
                <li><a href="upload_Qr.php">Upload QR</a></li>

            </ul>
        </li>

        <li>
            <a href="members.php">
                <i class="fas fa-users"></i>
                <span>Members</span>
            </a>
        </li>

        <li class="menu-item">
            <a href="#" onclick="toggleMenu(event)" <?php echo $active_page === 'payments' ? 'class="active"' : ''; ?>>
                <i class="fas fa-cog"></i>
                <span>Payments</span>
            </a>

            <ul class="submenu">
                <li><a href="payments.php">Make Payment</a></li>
                <li><a href="all_payments.php">All Payments</a></li>

            </ul>
        </li>




        <!-- <li>
            <a href="create_payment.php">
                <i class="fas fa-building"></i>
                <span>Payments</span>
            </a>
        </li> -->
    </ul>
</aside>

<script>
function toggleMenu(event) {
    event.preventDefault();

    let submenu = event.currentTarget.nextElementSibling;

    // Close other submenus (optional)
    document.querySelectorAll('.submenu').forEach(menu => {
        if (menu !== submenu) {
            menu.classList.remove('show');
        }
    });

    // Toggle current submenu
    submenu.classList.toggle('show');
}
</script>
