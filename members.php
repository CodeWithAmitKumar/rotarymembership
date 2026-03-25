<?php
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in
if (!isset($_SESSION['organisation_id'])) {
    header("Location: index.php");
    exit();
}

$page_title = 'Members Management';
$success = '';
$error = '';
$organisation_id = (int) $_SESSION['organisation_id'];

// Check for success/error messages passed via URL
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'deleted') $success = "Member deleted successfully.";
    if ($_GET['msg'] == 'imported') $success = "Members imported successfully.";
    if ($_GET['msg'] == 'error') $error = "An error occurred. Please try again.";
}

// Fetch members from database
$stmt = $conn->prepare("SELECT * FROM members WHERE organisation_id = ? ORDER BY first_name ASC, last_name ASC");
$stmt->bind_param("i", $organisation_id);
$stmt->execute();
$members_result = $stmt->get_result();
$members = $members_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Members - Rotary Membership</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --primary-gradient: linear-gradient(180deg, #0f4c81 0%, #1b6ca8 100%);
            --primary-color: #1b6ca8;
            --secondary-color: #2d8f85;
            --sidebar-width: 260px;
            --header-height: 70px;
            --bg-color: #eef3f8;
            --text-dark: #2d3748;
            --text-light: #64748b;
            --white: #ffffff;
            --card-shadow: 0 18px 40px rgba(15, 76, 129, 0.08);
            --danger-color: #ef4444;
            --edit-color: #3b82f6;
            --success-color: #10b981;
        }
        
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: radial-gradient(circle at top right, rgba(45, 143, 133, 0.12), transparent 24%), radial-gradient(circle at top left, rgba(27, 108, 168, 0.12), transparent 18%), var(--bg-color); min-height: 100vh; color: var(--text-dark); overflow-x: hidden; }
        
        /* Sidebar Styles */
        .sidebar { position: fixed; left: 0; top: 0; width: var(--sidebar-width); height: 100vh; background: var(--primary-gradient); padding: 20px; z-index: 250; overflow-y: auto; box-shadow: 2px 0 15px rgba(0, 0, 0, 0.1); transition: transform 0.3s ease; }
        .sidebar-logo { display: flex; align-items: center; gap: 12px; padding: 10px 0 30px; border-bottom: 1px solid rgba(255, 255, 255, 0.15); margin-bottom: 25px; }
        .sidebar-logo .logo-icon { width: 45px; height: 45px; background: rgba(255, 255, 255, 0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; color: var(--white); }
        .sidebar-logo .logo-text { font-size: 20px; font-weight: 700; color: var(--white); letter-spacing: 0.5px; }
        .sidebar-menu { list-style: none; }
        .sidebar-menu li { margin-bottom: 8px; }
        .sidebar-menu a { display: flex; align-items: center; gap: 14px; padding: 14px 16px; color: rgba(255, 255, 255, 0.8); text-decoration: none; border-radius: 10px; transition: all 0.3s ease; font-size: 15px; font-weight: 500; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: rgba(255, 255, 255, 0.2); color: var(--white); transform: translateX(5px); }
        .sidebar-menu a i { width: 20px; text-align: center; font-size: 18px; }
        
        /* Main Content Area */
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; }
        
        /* Header Styles */
        .header { position: fixed; top: 0; left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); height: var(--header-height); background: rgba(255, 255, 255, 0.94); padding: 0 30px; display: flex; align-items: center; justify-content: space-between; box-shadow: var(--card-shadow); border-bottom: 1px solid rgba(148, 163, 184, 0.18); backdrop-filter: blur(12px); z-index: 200; }
        .header-left { display: flex; align-items: center; gap: 20px; }
        .menu-toggle { display: none; background: none; border: none; font-size: 22px; color: var(--text-dark); cursor: pointer; }
        .search-box { position: relative; width: 300px; }
        .search-box input { width: 100%; padding: 12px 15px 12px 45px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 14px; outline: none; transition: all 0.3s ease; background: #f7fafc; }
        .search-box input:focus { border-color: var(--primary-color); background: var(--white); box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        .search-box i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-light); }
        .header-right { display: flex; align-items: center; gap: 15px; }
        
        /* Profile Dropdown */
        .profile-section { position: relative; }
        .profile-btn { display: flex; align-items: center; gap: 12px; padding: 6px 12px 6px 6px; background: #f7fafc; border: none; border-radius: 12px; cursor: pointer; transition: all 0.3s ease; }
        .profile-btn:hover { background: #edf2f7; }
        .profile-avatar { width: 38px; height: 38px; border-radius: 10px; background: var(--primary-gradient); display: flex; align-items: center; justify-content: center; color: var(--white); font-size: 16px; font-weight: 600; }
        .profile-dropdown { position: absolute; top: 120%; right: 0; width: 220px; background: var(--white); border-radius: 12px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15); padding: 10px; opacity: 0; visibility: hidden; transform: translateY(-10px); transition: all 0.3s ease; z-index: 1001; }
        .profile-section:hover .profile-dropdown { opacity: 1; visibility: visible; transform: translateY(0); }
        .dropdown-item { display: flex; align-items: center; gap: 12px; padding: 12px; color: var(--text-dark); text-decoration: none; border-radius: 8px; transition: all 0.2s ease; font-size: 14px; }
        .dropdown-item:hover { background: #f7fafc; color: var(--primary-color); }
        .dropdown-item i { width: 18px; color: var(--text-light); }

        /* Dashboard Content */
        .dashboard-content { padding: 102px 30px 32px; max-width: 1200px; margin: 0 auto; }
        
        /* Card Styles */
        .card { background: var(--white); border-radius: 16px; box-shadow: var(--card-shadow); overflow: hidden; }
        .card-header { padding: 25px 30px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .card-header-text h1 { font-size: 22px; font-weight: 700; color: var(--text-dark); margin-bottom: 5px; }
        .card-header-text p { font-size: 14px; color: var(--text-light); }
        
        /* Card Header Actions Container */
        .card-header-actions { display: flex; gap: 12px; align-items: center; }

        /* Form Actions & Buttons */
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 20px; border-radius: 10px; font-size: 14px; font-weight: 600; text-decoration: none; cursor: pointer; border: none; transition: all 0.3s ease; }
        .btn-primary { background: var(--primary-color); color: var(--white); }
        .btn-primary:hover { background: #0f4c81; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(27, 108, 168, 0.3); }
        
        .btn-success { background: var(--success-color); color: var(--white); }
        .btn-success:hover { background: #059669; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3); }

        /* Action Buttons in Table */
        .actions-cell { display: flex; gap: 8px; }
        .btn-icon { display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 8px; color: var(--white); text-decoration: none; transition: all 0.2s ease; font-size: 14px; }
        .btn-edit { background: var(--edit-color); }
        .btn-edit:hover { background: #2563eb; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(59, 130, 246, 0.3); }
        .btn-delete { background: var(--danger-color); }
        .btn-delete:hover { background: #dc2626; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3); }

        /* Table Styles */
        .table-responsive { overflow-x: auto; padding: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px 20px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 14px; vertical-align: middle; }
        th { background: #f7fafc; color: var(--text-dark); font-weight: 600; text-transform: uppercase; font-size: 13px; letter-spacing: 0.5px; border-top: 1px solid #e2e8f0; border-bottom: 2px solid #e2e8f0; white-space: nowrap;}
        tbody tr:hover { background: #f8fafc; }
        tbody tr:last-child td { border-bottom: none; }
        .empty-state { text-align: center; padding: 40px 20px; color: var(--text-light); }
        
        /* Status Badge */
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background: rgba(45, 143, 133, 0.15); color: var(--secondary-color); display: inline-block; }

        /* Alerts */
        .alert { padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 500; }
        .alert-success { background: rgba(72, 187, 120, 0.15); color: #2f855a; border: 1px solid rgba(72, 187, 120, 0.3); }
        .alert-error { background: rgba(229, 62, 62, 0.15); color: #c53030; border: 1px solid rgba(229, 62, 62, 0.3); }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .header { left: 0; width: 100%; padding: 0 16px; }
            .menu-toggle { display: block; }
            .search-box { display: none; }
            .dashboard-content { padding: 90px 15px 20px; }
            .card-header { flex-direction: column; align-items: flex-start; gap: 15px; }
            .card-header-actions { width: 100%; flex-direction: column; }
            .btn { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>

<?php 
$active_page = 'members';
include 'sidebar.php'; 
?>

<div class="main-content">
<?php include 'header.php'; ?>

    <div class="dashboard-content">
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <div class="card-header-text">
                    <h1><i class="fas fa-users"></i> Member Directory</h1>
                    <p>View and manage all members in your organization</p>
                </div>
                
                <div class="card-header-actions">
                    <a href="import_members.php" class="btn btn-success">
                        <i class="fas fa-file-excel"></i> Import Members
                    </a>
                    <a href="add_member.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Add New Member
                    </a>
                </div>
            </div>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Sl. No.</th>
                            <th>Member ID</th>
                            <th>Name</th>
                            <th>Contact Info</th>
                            <th>Joining Date</th>
                            <th style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($members) > 0): ?>
                            <?php $count = 1; ?>
                            <?php foreach ($members as $member): ?>
                                <tr>
                                    <td><strong><?php echo $count++; ?></strong></td>
                                    <td><strong><span class="badge"><?php echo htmlspecialchars($member['member_id']); ?></span></strong></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></strong><br>
                                        <?php if ($member['satellite_member'] === 'Y'): ?>
                                            <span style="font-size: 12px; color: var(--secondary-color);"><i class="fas fa-satellite"></i> Satellite</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="margin-bottom: 4px;"><i class="fas fa-envelope" style="color: var(--text-light); width: 16px;"></i> <?php echo htmlspecialchars($member['email'] ?? 'N/A'); ?></div>
                                        <div><i class="fas fa-phone" style="color: var(--text-light); width: 16px;"></i> <?php echo htmlspecialchars($member['phone_no'] ?? 'N/A'); ?></div>
                                    </td>
                                    <td><?php echo $member['joining_date'] ? date('M d, Y', strtotime($member['joining_date'])) : 'N/A'; ?></td>
                                    <td>
                                        <div class="actions-cell">
                                            <a href="edit_member.php?id=<?php echo $member['id']; ?>" class="btn-icon btn-edit" title="Edit Member">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete_member.php?id=<?php echo $member['id']; ?>" class="btn-icon btn-delete" title="Delete Member" onclick="return confirm('Are you sure you want to delete this member?');">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <i class="fas fa-users-slash" style="font-size: 32px; margin-bottom: 15px; color: #cbd5e0;"></i><br>
                                    No members found. Click "Add New Member" or "Import Members" to get started.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

    <script>
        function toggleSidebar() { document.querySelector('.sidebar').classList.toggle('active'); }
        document.addEventListener('click', function(e) {
            const sidebar = document.querySelector('.sidebar');
            const menuToggle = document.querySelector('.menu-toggle');
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });
    </script>
</body>
</html>