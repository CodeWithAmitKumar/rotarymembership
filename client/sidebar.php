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

<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="profile-avatar">
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
                <i class="fa fa-rupee-sign"></i>
                <span>Payments</span>
            </a>

            <ul class="submenu">
                <li><a href="payments.php">Make Payment</a></li>
                <li><a href="all_payments.php">All Payments</a></li>
            </ul>
        </li>

        <li>
            <a href="#">
                <i class="fas fa-users"></i>
                <span>Magazine-report</span>
            </a>
        </li>
    </ul>
</aside>

<script>
function toggleMenu(event) {
    event.preventDefault();

    let submenu = event.currentTarget.nextElementSibling;

    document.querySelectorAll('.submenu').forEach(menu => {
        if (menu !== submenu) {
            menu.classList.remove('show');
        }
    });

    submenu.classList.toggle('show');
}
</script>
