<?php
require_once 'header.php';

$page_title = 'Payment Heads Management';
$success = '';
$error = '';
$organisation_id = (int) $_SESSION['organisation_id'];

// Check for success/error messages passed via URL
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'deleted') $success = "Payment head deleted successfully.";
    if ($_GET['msg'] == 'error') $error = "An error occurred. Please try again.";
}

// Fetch payment heads from database
$stmt = $conn->prepare("SELECT * FROM payment_heads WHERE organisation_id = ? ORDER BY id DESC");
$stmt->bind_param("i", $organisation_id);
$stmt->execute();
$heads_result = $stmt->get_result();
$payment_heads = $heads_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Heads - Rotary Membership</title>
    <?php render_client_shared_styles(); ?>
<style>
/* Dashboard Content */
        .dashboard-content { max-width: 1000px; margin: 0 auto; }
        
        /* Card Styles */
        .card { background: var(--white); border-radius: 16px; box-shadow: var(--card-shadow); overflow: hidden; }
        .card-header { padding: 25px 30px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .card-header-text h1 { font-size: 22px; font-weight: 700; color: var(--text-dark); margin-bottom: 5px; }
        .card-header-text p { font-size: 14px; color: var(--text-light); }

        /* Form Actions & Buttons */
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 25px; border-radius: 10px; font-size: 14px; font-weight: 600; text-decoration: none; cursor: pointer; border: none; transition: all 0.3s ease; }
        .btn-primary { background: var(--primary-color); color: var(--white); }
        .btn-primary:hover { background: #0f4c81; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(27, 108, 168, 0.3); }

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
        th { background: #f7fafc; color: var(--text-dark); font-weight: 600; text-transform: uppercase; font-size: 13px; letter-spacing: 0.5px; border-top: 1px solid #e2e8f0; border-bottom: 2px solid #e2e8f0;}
        tbody tr:hover { background: #f8fafc; }
        tbody tr:last-child td { border-bottom: none; }
        .empty-state { text-align: center; padding: 40px 20px; color: var(--text-light); }
        
        /* Status Badge */
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background: rgba(45, 143, 133, 0.15); color: var(--secondary-color); display: inline-block; }
        .amount-badge { font-weight: 700; color: #0f4c81; font-size: 15px; }

        /* Alerts */
        .alert { padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 500; }
        .alert-success { background: rgba(72, 187, 120, 0.15); color: #2f855a; border: 1px solid rgba(72, 187, 120, 0.3); }
        .alert-error { background: rgba(229, 62, 62, 0.15); color: #c53030; border: 1px solid rgba(229, 62, 62, 0.3); }

        /* Responsive */
        @media (max-width: 768px) {
.card-header { flex-direction: column; align-items: flex-start; gap: 15px; }
        }
    </style>
</head>
<body>

<?php 
// Ensure 'payment_heads' is registered in your sidebar.php to highlight the active menu
$active_page = 'payment_heads';
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
                    <h1><i class="fas fa-file-invoice-dollar"></i> Manage Payment Heads</h1>
                    <p>View and configure your organization's fee structures</p>
                </div>
                <a href="add_payment_head.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Payment Head
                </a>
            </div>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Sl No</th>
                            <th>Head Name</th>
                            <th>Amount</th>
                            <th style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($payment_heads) > 0): ?>
                            <?php $count = 1; foreach ($payment_heads as $head): ?>
                                <tr>
                                    <td><?php echo $count++; ?></td>
                                    <td><strong><span class="badge"><?php echo htmlspecialchars($head['head_name']); ?></span></strong></td>
                                    <td class="amount-badge"><?php echo number_format($head['head_amount'], 2); ?></td>
                                    <td>
                                        <div class="actions-cell">
                                            <a href="edit_payment_head.php?id=<?php echo $head['id']; ?>" class="btn-icon btn-edit" title="Edit Payment Head">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete_payment_head.php?id=<?php echo $head['id']; ?>" class="btn-icon btn-delete" title="Delete Payment Head" onclick="return confirm('Are you sure you want to delete this payment head?');">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="empty-state">
                                    <i class="fas fa-folder-open" style="font-size: 32px; margin-bottom: 15px; color: #cbd5e0;"></i><br>
                                    No payment heads found. Click "Add Payment Head" to get started.
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
