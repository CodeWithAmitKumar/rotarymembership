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
        <div class="logo-icon">
            <i class="fas fa-shield-alt"></i>
        </div>
        <span class="logo-text">Rotary Admin</span>
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
            </ul>
        </li>

        <li>
            <a href="members.php">
                <i class="fas fa-users"></i>
                <span>Members</span>
            </a>
        </li>

        <li>
            <a href="#">
                <i class="fas fa-building"></i>
                <span>Payments</span>
            </a>
        </li>
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