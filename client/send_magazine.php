<?php
require_once 'header.php';

$page_title = 'Send Magazine';
$organisation_id = (int) $_SESSION['organisation_id'];
$error = '';

if (isset($_GET['msg']) && $_GET['msg'] === 'invalid_member') {
    $error = 'Selected member was not found or is not active.';
}

$stmt = $conn->prepare("
    SELECT id, member_id, first_name, last_name, email, phone_no, city_state, original_rotary_date, satellite_member
    FROM members
    WHERE organisation_id = ? AND (is_active = 1 OR is_active IS NULL)
    ORDER BY first_name ASC, last_name ASC
");
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
    <title>Send Magazine - Rotary Membership</title>

    <?php render_client_shared_styles(); ?>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

    <style>
        .dashboard-content { max-width: 1200px; margin: 0 auto; }

        .card {
            background: var(--white);
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }

        .card-header {
            padding: 25px 30px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .card-header-text h1 {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 5px;
        }

        .card-header-text p {
            font-size: 14px;
            color: var(--text-light);
        }

        .card-header-badge {
            padding: 10px 16px;
            border-radius: 999px;
            background: rgba(27, 108, 168, 0.1);
            color: var(--primary-color);
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
        }

        .table-responsive {
            overflow-x: auto;
            padding: 20px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 500;
        }

        .alert-error {
            background: rgba(229, 62, 62, 0.15);
            color: #c53030;
            border: 1px solid rgba(229, 62, 62, 0.3);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
            vertical-align: middle;
        }

        th {
            background: #f7fafc;
            color: var(--text-dark);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 0.5px;
            border-top: 1px solid #e2e8f0;
            border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .member-id-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(45, 143, 133, 0.15);
            color: var(--secondary-color);
            font-size: 12px;
            font-weight: 700;
        }

        .member-name {
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 4px;
        }

        .member-note {
            font-size: 12px;
            color: var(--secondary-color);
            font-weight: 600;
        }

        .contact-line {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
            color: var(--text-dark);
        }

        .contact-line:last-child {
            margin-bottom: 0;
        }

        .contact-line i {
            width: 16px;
            color: var(--text-light);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            border: none;
            transition: all 0.3s ease;
            cursor: pointer;
            white-space: nowrap;
        }

        .btn-primary {
            background: var(--primary-color);
            color: var(--white);
        }

        .btn-primary:hover {
            background: #0f4c81;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(27, 108, 168, 0.25);
        }

        .empty-state {
            text-align: center;
            padding: 45px 20px;
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 34px;
            margin-bottom: 14px;
            color: #cbd5e0;
        }

        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px 12px;
            margin-left: 8px;
            outline: none;
        }

        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: var(--primary-color);
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background: var(--primary-color) !important;
            color: white !important;
            border: none !important;
            border-radius: 6px !important;
        }

        .dataTables_wrapper .dataTables_length select {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 4px 8px;
            outline: none;
        }

        @media (max-width: 768px) {
            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<?php
$active_page = 'magazine';
include 'sidebar.php';
?>

<div class="main-content">
    <?php render_client_header(); ?>

    <div class="dashboard-content">
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <div class="card-header-text">
                    <h1><i class="fas fa-book-open"></i> Send Magazine</h1>
                    <p>Select an active member and continue to the member-wise magazine page.</p>
                </div>
                <div class="card-header-badge">
                    Active Members: <?php echo count($members); ?>
                </div>
            </div>

            <div class="table-responsive">
                <table id="magazineMembersTable">
                    <thead>
                        <tr>
                            <th>Sl. No.</th>
                            <th>Member ID</th>
                            <th>Name</th>
                            <th>Contact Info</th>
                            <th>City / State</th>
                            <th>Rotary Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($members) > 0): ?>
                            <?php $count = 1; ?>
                            <?php foreach ($members as $member): ?>
                                <tr>
                                    <td><strong><?php echo $count++; ?></strong></td>
                                    <td>
                                        <span class="member-id-badge">
                                            <?php echo htmlspecialchars($member['member_id'] ?: 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="member-name">
                                            <?php echo htmlspecialchars(trim($member['first_name'] . ' ' . $member['last_name'])); ?>
                                        </div>
                                        <?php if (($member['satellite_member'] ?? '') === 'Y'): ?>
                                            <div class="member-note">
                                                <i class="fas fa-satellite"></i> Satellite Member
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="contact-line">
                                            <i class="fas fa-envelope"></i>
                                            <span><?php echo htmlspecialchars($member['email'] ?: 'N/A'); ?></span>
                                        </div>
                                        <div class="contact-line">
                                            <i class="fas fa-phone"></i>
                                            <span><?php echo htmlspecialchars($member['phone_no'] ?: 'N/A'); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($member['city_state'] ?: 'N/A'); ?></td>
                                    <td>
                                        <?php echo !empty($member['original_rotary_date']) ? htmlspecialchars(date('M d, Y', strtotime($member['original_rotary_date']))) : 'N/A'; ?>
                                    </td>
                                    <td>
                                        <a href="send_magazine_member.php?id=<?php echo (int) $member['id']; ?>" class="btn btn-primary">
                                            <i class="fas fa-paper-plane"></i> Send Magazine
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="empty-state">
                                    <i class="fas fa-book-reader"></i><br>
                                    No active members found. Activate members first to send magazines.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

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

    $(document).ready(function() {
        <?php if (count($members) > 0): ?>
        $('#magazineMembersTable').DataTable({
            pageLength: 10,
            order: [],
            language: {
                search: "Filter:",
                searchPlaceholder: "Search active members..."
            }
        });
        <?php endif; ?>
    });
</script>
</body>
</html>
