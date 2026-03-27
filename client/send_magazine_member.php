<?php
require_once 'header.php';

$page_title = 'Send Magazine Member';
$organisation_id = (int) ($_SESSION['organisation_id'] ?? 0);
$success = '';
$error = '';

$create_table_sql = "
    CREATE TABLE IF NOT EXISTS magazine_reports (
        id INT(11) NOT NULL AUTO_INCREMENT,
        organisation_id INT(11) NOT NULL,
        member_id INT(11) NOT NULL,
        magazine_year INT(4) NOT NULL,
        magazine_month TINYINT(2) NOT NULL,
        sent_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_magazine_send (organisation_id, member_id, magazine_year, magazine_month),
        KEY idx_magazine_member (member_id),
        KEY idx_magazine_org_date (organisation_id, magazine_year, magazine_month),
        CONSTRAINT fk_magazine_reports_org FOREIGN KEY (organisation_id) REFERENCES organisations (organisation_id) ON DELETE CASCADE,
        CONSTRAINT fk_magazine_reports_member FOREIGN KEY (member_id) REFERENCES members (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
";
$conn->query($create_table_sql);

$member_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'already_sent') {
        $error = 'Magazine already sent for the selected year and month.';
    } elseif ($_GET['msg'] === 'invalid_period') {
        $error = 'Please select a valid year and month.';
    } elseif ($_GET['msg'] === 'send_failed') {
        $error = 'Unable to send magazine right now. Please try again.';
    }
}

$member = null;
if ($member_id > 0) {
    $member_stmt = $conn->prepare("
        SELECT id, member_id, first_name, last_name, email, phone_no, city_state, original_rotary_date, satellite_member
        FROM members
        WHERE id = ? AND organisation_id = ? AND (is_active = 1 OR is_active IS NULL)
        LIMIT 1
    ");
    $member_stmt->bind_param("ii", $member_id, $organisation_id);
    $member_stmt->execute();
    $member_result = $member_stmt->get_result();
    $member = $member_result->fetch_assoc();
    $member_stmt->close();
}

if (!$member) {
    header("Location: send_magazine.php?msg=invalid_member");
    exit();
}

$current_year = (int) date('Y');
$selected_year = $current_year;
$selected_month = (int) date('n');
$months = [
    1 => 'January',
    2 => 'February',
    3 => 'March',
    4 => 'April',
    5 => 'May',
    6 => 'June',
    7 => 'July',
    8 => 'August',
    9 => 'September',
    10 => 'October',
    11 => 'November',
    12 => 'December',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_year = isset($_POST['magazine_year']) ? (int) $_POST['magazine_year'] : 0;
    $selected_month = isset($_POST['magazine_month']) ? (int) $_POST['magazine_month'] : 0;

    if ($selected_year < 2000 || $selected_year > ($current_year + 5) || !isset($months[$selected_month])) {
        header("Location: send_magazine_member.php?id={$member_id}&msg=invalid_period");
        exit();
    }

    $exists_stmt = $conn->prepare("
        SELECT id
        FROM magazine_reports
        WHERE organisation_id = ? AND member_id = ? AND magazine_year = ? AND magazine_month = ?
        LIMIT 1
    ");
    $exists_stmt->bind_param("iiii", $organisation_id, $member_id, $selected_year, $selected_month);
    $exists_stmt->execute();
    $exists_stmt->store_result();
    $already_sent = $exists_stmt->num_rows > 0;
    $exists_stmt->close();

    if ($already_sent) {
        header("Location: send_magazine_member.php?id={$member_id}&msg=already_sent");
        exit();
    }

    $insert_stmt = $conn->prepare("
        INSERT INTO magazine_reports (organisation_id, member_id, magazine_year, magazine_month)
        VALUES (?, ?, ?, ?)
    ");
    $insert_stmt->bind_param("iiii", $organisation_id, $member_id, $selected_year, $selected_month);

    if ($insert_stmt->execute()) {
        $insert_stmt->close();
        header("Location: magazine_report.php?msg=sent");
        exit();
    }

    $insert_stmt->close();
    header("Location: send_magazine_member.php?id={$member_id}&msg=send_failed");
    exit();
}

$history_stmt = $conn->prepare("
    SELECT magazine_year, magazine_month, sent_at
    FROM magazine_reports
    WHERE organisation_id = ? AND member_id = ?
    ORDER BY magazine_year DESC, magazine_month DESC, sent_at DESC
    LIMIT 10
");
$history_stmt->bind_param("ii", $organisation_id, $member_id);
$history_stmt->execute();
$history_result = $history_stmt->get_result();
$history_rows = $history_result->fetch_all(MYSQLI_ASSOC);
$history_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Magazine - Member</title>

    <?php render_client_shared_styles(); ?>

    <style>
        .dashboard-content { max-width: 1100px; margin: 0 auto; }
        .page-stack { display: grid; gap: 24px; }
        .card { background: var(--white); border-radius: 16px; box-shadow: var(--card-shadow); overflow: hidden; }
        .card-header { padding: 25px 30px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; gap: 18px; }
        .card-header-text h1 { font-size: 22px; font-weight: 700; color: var(--text-dark); margin-bottom: 5px; }
        .card-header-text p { font-size: 14px; color: var(--text-light); }
        .card-badge { padding: 10px 14px; border-radius: 999px; background: rgba(27, 108, 168, 0.12); color: var(--primary-color); font-size: 13px; font-weight: 700; }
        .content-grid { display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 24px; padding: 24px; }
        .panel { border: 1px solid #e2e8f0; border-radius: 14px; padding: 22px; background: #fcfdff; }
        .panel h2 { font-size: 18px; margin-bottom: 18px; color: var(--text-dark); }
        .member-card { display: grid; gap: 16px; }
        .member-name { font-size: 24px; font-weight: 700; color: var(--text-dark); }
        .member-subtitle { display: flex; flex-wrap: wrap; gap: 10px; }
        .pill { display: inline-flex; align-items: center; gap: 6px; padding: 7px 12px; border-radius: 999px; font-size: 12px; font-weight: 700; }
        .pill-id { background: rgba(45, 143, 133, 0.15); color: var(--secondary-color); }
        .pill-satellite { background: rgba(27, 108, 168, 0.12); color: var(--primary-color); }
        .detail-list { display: grid; gap: 12px; }
        .detail-item { display: flex; align-items: flex-start; gap: 12px; color: var(--text-dark); }
        .detail-item i { color: var(--text-light); width: 16px; margin-top: 3px; }
        .send-form { display: grid; gap: 18px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-group { display: grid; gap: 8px; }
        .form-group label { font-size: 14px; font-weight: 600; color: var(--text-dark); }
        .form-control {
            width: 100%;
            border: 1px solid #d7e0ea;
            border-radius: 10px;
            padding: 12px 14px;
            outline: none;
            font-size: 14px;
            background: var(--white);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(27, 108, 168, 0.12);
        }
        .form-note { font-size: 13px; color: var(--text-light); line-height: 1.6; }
        .form-actions { display: flex; gap: 12px; flex-wrap: wrap; }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 18px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-primary { background: var(--primary-color); color: var(--white); }
        .btn-primary:hover { background: #0f4c81; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(27, 108, 168, 0.25); }
        .btn-secondary { background: #e2e8f0; color: var(--text-dark); }
        .btn-secondary:hover { background: #cbd5e1; }
        .alert { padding: 15px 18px; border-radius: 12px; display: flex; align-items: flex-start; gap: 10px; margin-bottom: 20px; font-size: 14px; font-weight: 500; }
        .alert-error { background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.22); color: #b91c1c; }
        .history-table-wrap { overflow-x: auto; }
        .history-table { width: 100%; border-collapse: collapse; }
        .history-table th, .history-table td { text-align: left; padding: 14px 16px; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        .history-table th { background: #f8fafc; font-size: 13px; text-transform: uppercase; letter-spacing: 0.4px; }
        .history-table tbody tr:hover { background: #f8fafc; }
        .empty-state { text-align: center; padding: 26px 18px; color: var(--text-light); }

        @media (max-width: 900px) {
            .content-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            .card-header { flex-direction: column; align-items: flex-start; }
            .form-row { grid-template-columns: 1fr; }
            .btn { width: 100%; }
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
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <div class="page-stack">
            <div class="card">
                <div class="card-header">
                    <div class="card-header-text">
                        <h1><i class="fas fa-paper-plane"></i> Send Magazine To Member</h1>
                        <p>Select year and month, then send the magazine for this member.</p>
                    </div>
                    <div class="card-badge">Member Wise Send</div>
                </div>

                <div class="content-grid">
                    <div class="panel">
                        <h2>Member Details</h2>

                        <div class="member-card">
                            <div>
                                <div class="member-name">
                                    <?php echo htmlspecialchars(trim($member['first_name'] . ' ' . $member['last_name'])); ?>
                                </div>
                                <div class="member-subtitle">
                                    <span class="pill pill-id">
                                        <i class="fas fa-id-card"></i>
                                        <?php echo htmlspecialchars($member['member_id'] ?: 'N/A'); ?>
                                    </span>
                                    <?php if (($member['satellite_member'] ?? '') === 'Y'): ?>
                                        <span class="pill pill-satellite">
                                            <i class="fas fa-satellite"></i>
                                            Satellite Member
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="detail-list">
                                <div class="detail-item">
                                    <i class="fas fa-envelope"></i>
                                    <span><?php echo htmlspecialchars($member['email'] ?: 'N/A'); ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-phone"></i>
                                    <span><?php echo htmlspecialchars($member['phone_no'] ?: 'N/A'); ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-location-dot"></i>
                                    <span><?php echo htmlspecialchars($member['city_state'] ?: 'N/A'); ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-calendar-day"></i>
                                    <span>
                                        <?php echo !empty($member['original_rotary_date']) ? htmlspecialchars(date('M d, Y', strtotime($member['original_rotary_date']))) : 'N/A'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="panel">
                        <h2>Send Magazine</h2>

                        <form method="POST" class="send-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="magazine_year">Year</label>
                                    <select name="magazine_year" id="magazine_year" class="form-control" required>
                                        <?php for ($year = $current_year + 1; $year >= 2020; $year--): ?>
                                            <option value="<?php echo $year; ?>" <?php echo $selected_year === $year ? 'selected' : ''; ?>>
                                                <?php echo $year; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="magazine_month">Month</label>
                                    <select name="magazine_month" id="magazine_month" class="form-control" required>
                                        <?php foreach ($months as $month_number => $month_name): ?>
                                            <option value="<?php echo $month_number; ?>" <?php echo $selected_month === $month_number ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($month_name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-note">
                                If the same member already has a magazine entry for the same year and month, the page will show "already sent" and prevent duplicate records.
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i>
                                    Send
                                </button>
                                <a href="send_magazine.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i>
                                    Back
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-header-text">
                        <h1><i class="fas fa-clock-rotate-left"></i> Recent Send History</h1>
                        <p>Latest magazine records for this member.</p>
                    </div>
                    <div class="card-badge"><?php echo count($history_rows); ?> Records</div>
                </div>

                <div class="history-table-wrap">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Year</th>
                                <th>Month</th>
                                <th>Sent At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($history_rows)): ?>
                                <?php foreach ($history_rows as $history): ?>
                                    <tr>
                                        <td><?php echo (int) $history['magazine_year']; ?></td>
                                        <td><?php echo htmlspecialchars($months[(int) $history['magazine_month']] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars(date('d M Y h:i A', strtotime($history['sent_at']))); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="empty-state">
                                        No magazine has been sent to this member yet.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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
