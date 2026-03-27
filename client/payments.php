<?php
require_once 'header.php';

$page_title = 'Members Management';
$success = '';
$error = '';
$organisation_id = (int) $_SESSION['organisation_id'];

// Check for success/error messages passed via URL
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'deleted') $success = "Member deleted successfully.";
    if ($_GET['msg'] == 'error') $error = "An error occurred. Please try again.";
}

// Fetch ONLY ACTIVE members from database
$stmt = $conn->prepare("SELECT * FROM members WHERE organisation_id = ? AND (is_active = 1 OR is_active IS NULL) ORDER BY first_name ASC, last_name ASC");
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
    
    <?php render_client_shared_styles(); ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

    <style>
/* Dashboard Content */
        .dashboard-content { max-width: 1200px; margin: 0 auto; }
        
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

        /* DataTables Custom Styling */
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
        .dt-buttons .dt-button {
            background: var(--primary-color) !important;
            color: white !important;
            border: none !important;
            border-radius: 8px !important;
            padding: 8px 16px !important;
            font-size: 13px !important;
            font-weight: 600 !important;
            transition: all 0.3s ease;
        }
        .dt-buttons .dt-button:hover {
            background: #0f4c81 !important;
            box-shadow: 0 4px 10px rgba(27, 108, 168, 0.2) !important;
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

        /* Responsive */
        @media (max-width: 768px) {
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
                    <h1><i class="fas fa-users"></i> Payments</h1>
                    <p>View And Make Payments Here</p>
                </div>
                
               
            </div>
            
            <div class="table-responsive">
                <table id="membersTable">
                    <thead>
                        <tr>
                            <th>Sl. No.</th>    
                            <th>Member Id</th>
                            <th>Rotary Date</th>
                            <th>Name</th>
                            <th>Contact Info</th>
                            <th>City/State/Country</th>
                            <th style="width: 140px;" class="no-export">Actions</th> 
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
                                        <?php echo $member['original_rotary_date'] ? date('M d, Y', strtotime($member['original_rotary_date'])) : 'N/A'; ?>
                                    </td>
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
                                    <td><?php echo htmlspecialchars($member['city_state'] ?? 'N/A'); ?></td>
                                    
                                    <td>
                                        <div class="actions-cell">
                                            <a href="create_payment.php?member_id=<?php echo $member['id']; ?>" class="btn btn-success" style="padding: 8px 12px; font-size: 13px;" title="Make Payment">
                                                <i class="fas fa-credit-card"></i> Make Payment
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="empty-state">
                                    <i class="fas fa-users-slash" style="font-size: 32px; margin-bottom: 15px; color: #cbd5e0;"></i><br>
                                    No active members found. 
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
        // Sidebar Toggle Logic
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

        // Initialize DataTable
        $(document).ready(function() {
            <?php if (count($members) > 0): ?>
            $('#membersTable').DataTable({
                dom: '<"top"Bf>rt<"bottom"lip><"clear">', // Puts buttons & search on top
                buttons: [
                    {
                        extend: 'copyHtml5',
                        exportOptions: { columns: ':not(.no-export)' } // Excludes the 'Actions' column
                    },
                    {
                        extend: 'excelHtml5',
                        exportOptions: { columns: ':not(.no-export)' }
                    },
                    {
                        extend: 'csvHtml5',
                        exportOptions: { columns: ':not(.no-export)' }
                    },
                    {
                        extend: 'pdfHtml5',
                        exportOptions: { columns: ':not(.no-export)' }
                    },
                    {
                        extend: 'print',
                        exportOptions: { columns: ':not(.no-export)' }
                    }
                ],
                pageLength: 10,
                language: {
                    search: "Filter:",
                    searchPlaceholder: "Search members..."
                }
            });
            <?php endif; ?>
        });
    </script>
</body>
</html>
