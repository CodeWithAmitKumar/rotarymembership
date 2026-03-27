<?php
require_once 'header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Admin Panel</title>
    <?php render_client_shared_styles(); ?>
<style>
.sidebar-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.15);
            margin: 20px 0;
        }
        
        .sidebar-section-title {
            color: rgba(255, 255, 255, 0.5);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            padding: 0 16px;
            margin-bottom: 12px;
        }
.header-icon-btn {
            position: relative;
            width: 44px;
            height: 44px;
            border: none;
            background: #f7fafc;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            color: var(--text-dark);
            font-size: 18px;
        }
        
        .header-icon-btn:hover {
            background: var(--primary-color);
            color: var(--white);
        }
        
        .notification-badge {
            position: absolute;
            top: 6px;
            right: 6px;
            width: 18px;
            height: 18px;
            background: #e53e3e;
            color: var(--white);
            font-size: 10px;
            font-weight: 600;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
.profile-info {
            text-align: left;
        }
        
        .profile-name {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .profile-role {
            font-size: 12px;
            color: var(--text-light);
        }
.dropdown-divider {
            height: 1px;
            background: #e2e8f0;
            margin: 8px 0;
        }
        
        /* Dashboard Content */
        .dashboard-content { max-width: 1180px;
            margin: 0 auto;
        }
        
        .welcome-hero {
            text-align: center;
            padding: 48px 32px;
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.96), rgba(248, 250, 252, 0.96));
            border-radius: 28px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(148, 163, 184, 0.12);
            position: relative;
            overflow: hidden;
        }

        .welcome-hero::after {
            content: "";
            position: absolute;
            inset: auto -60px -70px auto;
            width: 220px;
            height: 220px;
            background: radial-gradient(circle, rgba(45, 143, 133, 0.16), transparent 65%);
            pointer-events: none;
        }
        
        .welcome-subtitle {
            font-size: 18px;
            color: var(--text-light);
            margin: 0 auto 40px;
            max-width: 620px;
            line-height: 1.7;
        }
        
        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            max-width: 900px;
            margin: 0 auto;
        }
        
        .quick-link-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            padding: 30px 20px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbfd 100%);
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(148, 163, 184, 0.12);
            text-decoration: none;
            color: var(--text-dark);
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
        }
        
        .quick-link-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 32px rgba(15, 76, 129, 0.12);
            color: var(--primary-color);
        }
        
        .quick-link-card i {
            font-size: 36px;
            color: var(--primary-color);
            width: 72px;
            height: 72px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(27, 108, 168, 0.12);
        }
        
        .quick-link-card span {
            font-size: 16px;
            font-weight: 600;
        }
        
        .welcome-hero {
            text-align: center;
            padding: 48px 32px;
            max-width: 100%;
            width: 100%;
        }
        
        .welcome-hero .page-title,
        .welcome-hero .welcome-subtitle,
        .welcome-hero .quick-links {
            max-width: 100%;
        }
        
        .welcome-subtitle {
            font-size: 18px;
            color: var(--text-light);
            margin-bottom: 40px;
        }
        
        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            max-width: 900px;
            margin: 0 auto;
        }
        
        .quick-link-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            padding: 30px 20px;
            background: var(--white);
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            text-decoration: none;
            color: var(--text-dark);
            transition: all 0.3s ease;
        }
        
        .quick-link-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
            color: var(--primary-color);
        }
        
        .quick-link-card i {
            font-size: 36px;
            color: var(--primary-color);
        }
        
        .page-title {
            font-size: clamp(2rem, 4vw, 2.8rem);
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 12px;
        }
        
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: var(--text-light);
            margin-bottom: 30px;
        }
        
        .breadcrumb a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .breadcrumb span {
            color: var(--text-dark);
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 35px;
        }
        
        .stat-card {
            background: var(--white);
            padding: 25px;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
        }
        
        .stat-icon.blue {
            background: rgba(102, 126, 234, 0.15);
            color: var(--primary-color);
        }
        
        .stat-icon.green {
            background: rgba(72, 187, 120, 0.15);
            color: #48bb78;
        }
        
        .stat-icon.orange {
            background: rgba(237, 137, 54, 0.15);
            color: #ed8936;
        }
        
        .stat-icon.purple {
            background: rgba(118, 75, 162, 0.15);
            color: var(--secondary-color);
        }
        
        .stat-info h3 {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 4px;
        }
        
        .stat-info p {
            font-size: 14px;
            color: var(--text-light);
        }
        
        .stat-trend {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            margin-top: 8px;
        }
        
        .stat-trend.up {
            color: #48bb78;
        }
        
        .stat-trend.down {
            color: #e53e3e;
        }
        
        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
        }
        
        /* Cards */
        .card {
            background: var(--white);
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }
        
        .card-header {
            padding: 20px 25px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .card-header h3 {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .card-header a {
            font-size: 13px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .card-body {
            padding: 25px;
        }
        
        /* Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th,
        .data-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .data-table th {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .data-table td {
            font-size: 14px;
            color: var(--text-dark);
        }
        
        .data-table tr:last-child td {
            border-bottom: none;
        }
        
        .data-table tr:hover td {
            background: #f7fafc;
        }
        
        .user-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-weight: 600;
            font-size: 14px;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-badge.active {
            background: rgba(72, 187, 120, 0.15);
            color: #48bb78;
        }
        
        .status-badge.pending {
            background: rgba(237, 137, 54, 0.15);
            color: #ed8936;
        }
        
        .status-badge.inactive {
            background: rgba(160, 174, 192, 0.2);
            color: #a0aec0;
        }
        
        /* Activity List */
        .activity-list {
            list-style: none;
        }
        
        .activity-item {
            display: flex;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }
        
        .activity-icon.blue {
            background: rgba(102, 126, 234, 0.15);
            color: var(--primary-color);
        }
        
        .activity-icon.green {
            background: rgba(72, 187, 120, 0.15);
            color: #48bb78;
        }
        
        .activity-icon.orange {
            background: rgba(237, 137, 54, 0.15);
            color: #ed8936;
        }
        
        .activity-content p {
            font-size: 14px;
            color: var(--text-dark);
            margin-bottom: 4px;
        }
        
        .activity-content span {
            font-size: 12px;
            color: var(--text-light);
        }
        
        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 25px;
        }
        
        .quick-action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            padding: 20px;
            background: #f7fafc;
            border: 2px solid transparent;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: var(--text-dark);
        }
        
        .quick-action-btn:hover {
            background: var(--white);
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .quick-action-btn i {
            font-size: 24px;
        }
        
        .quick-action-btn span {
            font-size: 13px;
            font-weight: 500;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
.dashboard-content {
                padding-left: 24px;
                padding-right: 24px;
            }
        }
        
        @media (max-width: 768px) {
.profile-info {
                display: none;
            }
.welcome-hero {
                padding: 32px 20px;
                border-radius: 22px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
   

<?php 
$active_page = 'dashboard';
include 'sidebar.php'; 
?>

<!-- Main Content Wrapper -->
<div class="main-content">
<?php render_client_header(); ?>

    <div class="dashboard-content">
        <div class="welcome-hero">
            <h1 class="page-title">Welcome <?php echo $_SESSION['organisation_name']; ?></h1>
            
           
        </div>
    </div>
</div>

    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }
        
        // Close sidebar when clicking outside on mobile
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
