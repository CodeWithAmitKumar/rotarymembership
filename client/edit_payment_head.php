<?php
require_once 'header.php';

$page_title = 'Edit Payment Head';
$success = '';
$error = '';
$organisation_id = (int) $_SESSION['organisation_id'];
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Redirect if no valid ID is provided
if ($id === 0) {
    header("Location: payment_heads.php");
    exit();
}

// Fetch the existing payment head data
$stmt = $conn->prepare("SELECT * FROM payment_heads WHERE id = ? AND organisation_id = ?");
$stmt->bind_param("ii", $id, $organisation_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Payment head not found or doesn't belong to this organization
    header("Location: payment_heads.php");
    exit();
}

$head_data = $result->fetch_assoc();
$stmt->close();

// Process the form when submitted for update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $head_name = trim($_POST['head_name']);
    $head_amount = (float) $_POST['head_amount'];

    if (empty($head_name) || $head_amount < 0) {
        $error = "Please enter a valid head name and a positive amount.";
    } else {
        $update_stmt = $conn->prepare("UPDATE payment_heads SET head_name = ?, head_amount = ? WHERE id = ? AND organisation_id = ?");
        
        if ($update_stmt) {
            $update_stmt->bind_param("sdii", $head_name, $head_amount, $id, $organisation_id);
            
            if ($update_stmt->execute()) {
                $success = "Payment Head updated successfully!";
                // Update local array to reflect changes on the screen immediately
                $head_data['head_name'] = $head_name;
                $head_data['head_amount'] = $head_amount;
            } else {
                $error = "Failed to update payment head: " . $update_stmt->error;
            }
            $update_stmt->close();
        } else {
            $error = "Database error: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Payment Head - Rotary Membership</title>
    <?php render_client_shared_styles(); ?>
<style>
/* Dashboard Content */
        .dashboard-content { max-width: 900px; margin: 0 auto; }
        
        /* Card Styles */
        .card { background: var(--white); border-radius: 16px; box-shadow: var(--card-shadow); overflow: hidden; }
        .card-header { padding: 25px 30px; border-bottom: 1px solid #e2e8f0; }
        .card-header h1 { font-size: 22px; font-weight: 700; color: var(--text-dark); margin-bottom: 5px; }
        .card-header p { font-size: 14px; color: var(--text-light); }
        
        /* Form Styles */
        form { padding: 30px; }
        .form-group { margin-bottom: 22px; }
        .form-group label { display: block; margin-bottom: 8px; color: var(--text-dark); font-weight: 600; font-size: 14px; }
        .form-group label i { margin-right: 6px; color: var(--primary-color); }
        .form-group input[type="text"], .form-group input[type="number"] { width: 100%; padding: 12px 15px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 14px; font-family: inherit; outline: none; transition: all 0.3s ease; background: #f7fafc; color: var(--text-dark); }
        .form-group input:focus { border-color: var(--primary-color); background: var(--white); box-shadow: 0 0 0 3px rgba(27, 108, 168, 0.1); }
        
        /* Form Actions & Buttons */
        .form-actions { display: flex; gap: 15px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e2e8f0; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 25px; border-radius: 10px; font-size: 14px; font-weight: 600; text-decoration: none; cursor: pointer; border: none; transition: all 0.3s ease; }
        .btn-primary { background: var(--edit-color); color: var(--white); }
        .btn-primary:hover { background: #2563eb; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(59, 130, 246, 0.3); }
        .btn-secondary { background: #e2e8f0; color: var(--text-dark); }
        .btn-secondary:hover { background: #cbd5e0; transform: translateY(-2px); }
        
        /* Alerts */
        .alert { padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 500; }
        .alert-success { background: rgba(72, 187, 120, 0.15); color: #2f855a; border: 1px solid rgba(72, 187, 120, 0.3); }
        .alert-error { background: rgba(229, 62, 62, 0.15); color: #c53030; border: 1px solid rgba(229, 62, 62, 0.3); }

        /* Responsive */
        @media (max-width: 768px) {
.form-actions { flex-direction: column; }
            .btn { justify-content: center; }
        }
    </style>
</head>
<body>

<?php 
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
                <h1><i class="fas fa-edit"></i> Edit Payment Head</h1>
                <p>Update the details for this fee category.</p>
            </div>
            
            <form action="" method="POST">
                <div class="form-group">
                    <label for="head_name"><i class="fas fa-tag"></i> Head Name *</label>
                    <input type="text" name="head_name" id="head_name" value="<?php echo htmlspecialchars($head_data['head_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="head_amount"><i class="fas fa-rupee-sign"></i> Amount *</label>
                    <input type="number" name="head_amount" id="head_amount" step="0.01" min="0" value="<?php echo htmlspecialchars($head_data['head_amount']); ?>" required>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Payment Head
                    </button>
                    <a href="payment_heads.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

    <script>
        // Sidebar logic
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
