<?php
require_once 'header.php';

$page_title = 'Dashboard';
$organisation_id = (int) ($_SESSION['organisation_id'] ?? 0);

$active_members = 0;
$total_money_collected = 0.00;
$total_magazine_sent = 0;
$recent_members = [];

$active_stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM members
    WHERE organisation_id = ? AND (is_active = 1 OR is_active IS NULL)
");
$active_stmt->bind_param("i", $organisation_id);
$active_stmt->execute();
$active_result = $active_stmt->get_result();
$active_members = (int) (($active_result->fetch_assoc()['total'] ?? 0));
$active_stmt->close();

$payments_stmt = $conn->prepare("
    SELECT COALESCE(SUM(total_amount), 0) AS total_amount
    FROM payments
    WHERE organisation_id = ?
");
$payments_stmt->bind_param("i", $organisation_id);
$payments_stmt->execute();
$payments_result = $payments_stmt->get_result();
$total_money_collected = (float) (($payments_result->fetch_assoc()['total_amount'] ?? 0));
$payments_stmt->close();

$magazine_stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM magazine_reports
    WHERE organisation_id = ?
");
$magazine_stmt->bind_param("i", $organisation_id);
$magazine_stmt->execute();
$magazine_result = $magazine_stmt->get_result();
$total_magazine_sent = (int) (($magazine_result->fetch_assoc()['total'] ?? 0));
$magazine_stmt->close();

$recent_stmt = $conn->prepare("
    SELECT member_id, first_name, last_name, email, phone_no, joining_date, created_at
    FROM members
    WHERE organisation_id = ?
    ORDER BY
        CASE
            WHEN joining_date IS NULL THEN created_at
            ELSE joining_date
        END DESC,
        id DESC
    LIMIT 5
");
$recent_stmt->bind_param("i", $organisation_id);
$recent_stmt->execute();
$recent_result = $recent_stmt->get_result();
$recent_members = $recent_result->fetch_all(MYSQLI_ASSOC);
$recent_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Rotary Membership</title>

    <?php render_client_shared_styles(); ?>

    <style>
        .dashboard-content { max-width: 1200px; margin: 0 auto; }
        .welcome-banner {
            background:
                radial-gradient(circle at top right, rgba(45, 143, 133, 0.18), transparent 30%),
                linear-gradient(135deg, #ffffff 0%, #f7fbff 48%, #eef5fb 100%);
            border-radius: 20px;
            padding: 28px 30px;
            box-shadow: var(--card-shadow);
            margin-bottom: 24px;
            border: 1px solid rgba(148, 163, 184, 0.12);
        }

        .welcome-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(27, 108, 168, 0.1);
            color: var(--primary-color);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.4px;
            margin-bottom: 14px;
        }

        .page-title {
            font-size: 34px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 8px;
            line-height: 1.2;
        }

        .welcome-text {
            font-size: 15px;
            color: var(--text-light);
            line-height: 1.7;
            max-width: 700px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .stat-card,
        .card {
            background: var(--white);
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }

        .stat-card {
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .stat-icon {
            width: 58px;
            height: 58px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }

        .icon-members { background: rgba(27, 108, 168, 0.14); color: var(--primary-color); }
        .icon-money { background: rgba(16, 185, 129, 0.14); color: #059669; }
        .icon-magazine { background: rgba(245, 158, 11, 0.16); color: #d97706; }

        .stat-label {
            font-size: 13px;
            color: var(--text-light);
            margin-bottom: 6px;
            font-weight: 600;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-dark);
        }

        .card-header {
            padding: 22px 24px;
            border-bottom: 1px solid #e2e8f0;
        }

        .card-header h2 {
            font-size: 20px;
            color: var(--text-dark);
            font-weight: 700;
            margin-bottom: 4px;
        }

        .card-header p {
            font-size: 14px;
            color: var(--text-light);
        }

        .table-wrap { overflow-x: auto; }

        .member-table {
            width: 100%;
            border-collapse: collapse;
        }

        .member-table th,
        .member-table td {
            padding: 15px 18px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
            vertical-align: middle;
        }

        .member-table th {
            background: #f8fafc;
            color: var(--text-dark);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }

        .member-table tbody tr:hover {
            background: #f8fafc;
        }

        .member-table tbody tr:last-child td {
            border-bottom: none;
        }

        .empty-state {
            text-align: center;
            padding: 32px 20px;
            color: var(--text-light);
        }

        @media (max-width: 900px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .welcome-banner {
                padding: 22px 20px;
            }

            .page-title {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>

<?php
$active_page = 'dashboard';
include 'sidebar.php';
?>

<div class="main-content">
    <?php render_client_header(); ?>

    <div class="dashboard-content">
        <div class="welcome-banner">
            <h1 class="page-title">Welcome, <?php echo htmlspecialchars($_SESSION['organisation_name']); ?></h1>
            <p class="welcome-text">Here is your quick summary of members, collections, magazines, and recently joined members.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon icon-members">
                    <i class="fas fa-user-check"></i>
                </div>
                <div>
                    <div class="stat-label">Total Active Member</div>
                    <div class="stat-value"><?php echo number_format($active_members); ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-money">
                    <i class="fas fa-indian-rupee-sign"></i>
                </div>
                <div>
                    <div class="stat-label">Total Money Collect</div>
                    <div class="stat-value">Rs. <?php echo number_format($total_money_collected, 2); ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-magazine">
                    <i class="fas fa-book"></i>
                </div>
                <div>
                    <div class="stat-label">Total Magazine Sent</div>
                    <div class="stat-value"><?php echo number_format($total_magazine_sent); ?></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Recently Joined Members</h2>
                <p>Latest members list</p>
            </div>

            <div class="table-wrap">
                <table class="member-table">
                    <thead>
                        <tr>
                            <th>Member ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Joined Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recent_members)): ?>
                            <?php foreach ($recent_members as $member): ?>
                                <?php $joined_date = !empty($member['joining_date']) ? $member['joining_date'] : $member['created_at']; ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($member['member_id'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars(trim(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? '')) ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($member['email'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($member['phone_no'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars(date('d M Y', strtotime($joined_date))); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="empty-state">No members available yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleSidebar() {
        document.querySelector('.sidebar').classList.toggle('active');
    }

    document.addEventListener('click', function(e) {
        const sidebar = document.querySelector('.sidebar');
        const menuToggle = document.querySelector('.menu-toggle');

        if (window.innerWidth <= 768 && sidebar && menuToggle) {
            if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        }
    });
</script>
</body>
</html>
