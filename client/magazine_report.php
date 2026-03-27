<?php
require_once 'header.php';

$page_title = 'Magazine Report';
$organisation_id = (int) ($_SESSION['organisation_id'] ?? 0);
$success = '';
$error = '';
$selected_year = isset($_GET['year']) ? (int) $_GET['year'] : 0;
$selected_month = isset($_GET['month']) ? (int) $_GET['month'] : 0;

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

if (isset($_GET['delete_id'])) {
    $delete_id = (int) $_GET['delete_id'];
    $redirect_query = [];

    if ($selected_year > 0) {
        $redirect_query['year'] = $selected_year;
    }

    if ($selected_month >= 1 && $selected_month <= 12) {
        $redirect_query['month'] = $selected_month;
    }

    if ($delete_id > 0) {
        $delete_stmt = $conn->prepare("
            DELETE FROM magazine_reports
            WHERE id = ? AND organisation_id = ?
            LIMIT 1
        ");
        $delete_stmt->bind_param("ii", $delete_id, $organisation_id);
        $delete_stmt->execute();
        $deleted_rows = $delete_stmt->affected_rows;
        $delete_stmt->close();

        if ($deleted_rows > 0) {
            $redirect_query['msg'] = 'deleted';
            header("Location: magazine_report.php?" . http_build_query($redirect_query));
            exit();
        }
    }

    $redirect_query['msg'] = 'delete_failed';
    header("Location: magazine_report.php?" . http_build_query($redirect_query));
    exit();
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'sent') {
        $success = 'Magazine sent successfully.';
    } elseif ($_GET['msg'] === 'deleted') {
        $success = 'Magazine report deleted successfully.';
    } elseif ($_GET['msg'] === 'delete_failed') {
        $error = 'Unable to delete the selected magazine report.';
    } elseif ($_GET['msg'] === 'error') {
        $error = 'Unable to load magazine report.';
    }
}

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

if ($selected_month < 1 || $selected_month > 12) {
    $selected_month = 0;
}

$years_stmt = $conn->prepare("
    SELECT DISTINCT magazine_year
    FROM magazine_reports
    WHERE organisation_id = ?
    ORDER BY magazine_year DESC
");
$years_stmt->bind_param("i", $organisation_id);
$years_stmt->execute();
$years_result = $years_stmt->get_result();
$available_years = [];

while ($year_row = $years_result->fetch_assoc()) {
    $available_years[] = (int) $year_row['magazine_year'];
}

$years_stmt->close();

$query = "
    SELECT
        mr.id,
        mr.magazine_year,
        mr.magazine_month,
        mr.sent_at,
        m.member_id AS rotary_member_id,
        m.first_name,
        m.last_name,
        m.email,
        m.phone_no,
        m.city_state
    FROM magazine_reports mr
    INNER JOIN members m ON mr.member_id = m.id
    WHERE mr.organisation_id = ?
";

$param_types = "i";
$param_values = [$organisation_id];

if ($selected_year > 0) {
    $query .= " AND mr.magazine_year = ?";
    $param_types .= "i";
    $param_values[] = $selected_year;
}

if ($selected_month > 0) {
    $query .= " AND mr.magazine_month = ?";
    $param_types .= "i";
    $param_values[] = $selected_month;
}

$query .= " ORDER BY mr.magazine_year DESC, mr.magazine_month DESC, mr.sent_at DESC, mr.id DESC";

$report_stmt = $conn->prepare($query);
$report_stmt->bind_param($param_types, ...$param_values);
$report_stmt->execute();
$report_result = $report_stmt->get_result();
$reports = $report_result->fetch_all(MYSQLI_ASSOC);
$report_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Magazine Report</title>

    <?php render_client_shared_styles(); ?>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

    <style>
        .dashboard-content { max-width: 1200px; margin: 0 auto; }
        .card { background: var(--white); border-radius: 16px; box-shadow: var(--card-shadow); overflow: hidden; }
        .card-header { padding: 25px 30px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; gap: 18px; }
        .card-header-text h1 { font-size: 22px; font-weight: 700; color: var(--text-dark); margin-bottom: 5px; }
        .card-header-text p { font-size: 14px; color: var(--text-light); }
        .card-header-badge { padding: 10px 16px; border-radius: 999px; background: rgba(45, 143, 133, 0.15); color: var(--secondary-color); font-size: 13px; font-weight: 700; }
        .filter-bar {
            padding: 20px 30px 0;
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            align-items: end;
        }
        .filter-group { display: grid; gap: 8px; min-width: 180px; }
        .filter-group label { font-size: 13px; font-weight: 600; color: var(--text-dark); }
        .filter-control {
            width: 100%;
            border: 1px solid #d7e0ea;
            border-radius: 10px;
            padding: 10px 12px;
            outline: none;
            font-size: 14px;
            background: var(--white);
        }
        .filter-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(27, 108, 168, 0.12);
        }
        .filter-actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .btn-filter {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 42px;
            padding: 10px 16px;
            border-radius: 10px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .btn-apply { background: var(--primary-color); color: var(--white); }
        .btn-apply:hover { background: #0f4c81; }
        .btn-reset { background: #e2e8f0; color: var(--text-dark); }
        .btn-reset:hover { background: #cbd5e1; }
        .table-responsive { overflow-x: auto; padding: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px 20px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 14px; vertical-align: middle; }
        th { background: #f7fafc; color: var(--text-dark); font-weight: 600; text-transform: uppercase; font-size: 13px; letter-spacing: 0.5px; border-top: 1px solid #e2e8f0; border-bottom: 2px solid #e2e8f0; white-space: nowrap; }
        tbody tr:hover { background: #f8fafc; }
        tbody tr:last-child td { border-bottom: none; }
        .badge { display: inline-block; padding: 6px 12px; border-radius: 999px; background: rgba(27, 108, 168, 0.12); color: var(--primary-color); font-size: 12px; font-weight: 700; }
        .member-name { font-weight: 700; color: var(--text-dark); margin-bottom: 4px; }
        .member-meta { font-size: 12px; color: var(--text-light); }
        .actions-cell { display: flex; gap: 8px; }
        .btn-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            height: 34px;
            padding: 0 12px;
            border-radius: 8px;
            color: var(--white);
            text-decoration: none;
            transition: all 0.2s ease;
            font-size: 13px;
            font-weight: 600;
            gap: 6px;
        }
        .btn-delete { background: var(--danger-color); }
        .btn-delete:hover { background: #dc2626; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3); }
        .alert { padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 500; }
        .alert-success { background: rgba(16, 185, 129, 0.15); color: #059669; border: 1px solid rgba(16, 185, 129, 0.3); }
        .alert-error { background: rgba(229, 62, 62, 0.15); color: #c53030; border: 1px solid rgba(229, 62, 62, 0.3); }
        .empty-state { text-align: center; padding: 40px 20px; color: var(--text-light); }
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px 12px;
            margin-left: 8px;
            outline: none;
        }
        .dataTables_wrapper .dataTables_filter input:focus { border-color: var(--primary-color); }
        .dt-buttons .dt-button {
            background: var(--primary-color) !important;
            color: white !important;
            border: none !important;
            border-radius: 8px !important;
            padding: 8px 16px !important;
            font-size: 13px !important;
            font-weight: 600 !important;
        }
        .dt-buttons .dt-button:hover { background: #0f4c81 !important; }
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
            .card-header { flex-direction: column; align-items: flex-start; }
            .filter-bar { padding: 20px 20px 0; }
            .filter-group { width: 100%; min-width: 100%; }
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
                    <h1><i class="fas fa-book-open-reader"></i> Magazine Report</h1>
                    <p>All sent magazine records are listed here.</p>
                </div>
                <div class="card-header-badge">Showing: <?php echo count($reports); ?></div>
            </div>

            <form method="GET" class="filter-bar">
                <div class="filter-group">
                    <label for="year">Filter By Year</label>
                    <select name="year" id="year" class="filter-control">
                        <option value="">All Years</option>
                        <?php foreach ($available_years as $year): ?>
                            <option value="<?php echo $year; ?>" <?php echo $selected_year === $year ? 'selected' : ''; ?>>
                                <?php echo $year; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="month">Filter By Month</label>
                    <select name="month" id="month" class="filter-control">
                        <option value="">All Months</option>
                        <?php foreach ($months as $month_number => $month_name): ?>
                            <option value="<?php echo $month_number; ?>" <?php echo $selected_month === $month_number ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($month_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn-filter btn-apply">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="magazine_report.php" class="btn-filter btn-reset">
                        <i class="fas fa-rotate-left"></i> Reset
                    </a>
                </div>
            </form>

            <div class="table-responsive">
                <table id="magazineReportTable">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Member ID</th>
                            <th>Month</th>
                            <th>Year</th>
                            <th>Contact</th>
                            <th>City / State</th>
                            <th>Sent At</th>
                            <th class="no-export">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($reports)): ?>
                            <?php foreach ($reports as $report): ?>
                                <tr>
                                    <td>
                                        <div class="member-name">
                                            <?php echo htmlspecialchars(trim($report['first_name'] . ' ' . $report['last_name'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge"><?php echo htmlspecialchars($report['rotary_member_id'] ?: 'N/A'); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($months[(int) $report['magazine_month']] ?? 'N/A'); ?></td>
                                    <td><?php echo (int) $report['magazine_year']; ?></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($report['email'] ?: 'N/A'); ?></div>
                                        <div class="member-meta"><?php echo htmlspecialchars($report['phone_no'] ?: 'N/A'); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($report['city_state'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars(date('d M Y h:i A', strtotime($report['sent_at']))); ?></td>
                                    <td>
                                        <div class="actions-cell">
                                            <a
                                                href="magazine_report.php?<?php echo htmlspecialchars(http_build_query(array_filter([
                                                    'delete_id' => (int) $report['id'],
                                                    'year' => $selected_year > 0 ? $selected_year : null,
                                                    'month' => $selected_month > 0 ? $selected_month : null,
                                                ]))); ?>"
                                                class="btn-icon btn-delete"
                                                title="Delete Record"
                                                onclick="return confirm('Are you sure you want to delete this magazine report?');"
                                            >
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="empty-state">
                                    <i class="fas fa-book-reader" style="font-size: 32px; margin-bottom: 15px; color: #cbd5e0;"></i><br>
                                    No magazine records found yet.
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
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

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
        <?php if (!empty($reports)): ?>
        $('#magazineReportTable').DataTable({
            dom: '<"top"Bf>rt<"bottom"lip><"clear">',
            buttons: [
                { extend: 'copyHtml5', exportOptions: { columns: ':not(.no-export)' } },
                { extend: 'excelHtml5', exportOptions: { columns: ':not(.no-export)' } },
                { extend: 'csvHtml5', exportOptions: { columns: ':not(.no-export)' } },
                { extend: 'pdfHtml5', exportOptions: { columns: ':not(.no-export)' } },
                { extend: 'print', exportOptions: { columns: ':not(.no-export)' } }
            ],
            pageLength: 10,
            order: [[6, 'desc']],
            language: {
                search: "Filter:",
                searchPlaceholder: "Search magazine reports..."
            }
        });
        <?php endif; ?>
    });
</script>
</body>
</html>
