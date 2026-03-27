<?php
require_once 'header.php';

$success = '';
$error = '';
$organisation_id = (int) $_SESSION['organisation_id'];

$stmt = $conn->prepare("SELECT * FROM organisations WHERE organisation_id = ?");
$stmt->bind_param("i", $organisation_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}

$organisation = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($current_password === '' || $new_password === '' || $confirm_password === '') {
        $error = "All password fields are required.";
    } elseif (!password_verify($current_password, $organisation['password'])) {
        $error = "Current password is incorrect.";
    } elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New password and confirm password do not match.";
    } elseif (password_verify($new_password, $organisation['password'])) {
        $error = "New password must be different from the current password.";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_stmt = $conn->prepare("UPDATE organisations SET password = ? WHERE organisation_id = ?");
        $update_stmt->bind_param("si", $hashed_password, $organisation_id);

        if ($update_stmt->execute()) {
            $success = "Password changed successfully.";
            $organisation['password'] = $hashed_password;
        } else {
            $error = "Failed to update password. Please try again.";
        }

        $update_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Rotary Membership</title>
    <?php render_client_shared_styles(); ?>
<style>
/* Dashboard Content */
        .dashboard-content { max-width: 900px;
            margin: 0 auto;
        }
        
        /* Card Styles */
        .card {
            background: var(--white);
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }
        
        .card-header {
            padding: 25px 30px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .card-header h1 {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 5px;
        }
        
        .card-header p {
            font-size: 14px;
            color: var(--text-light);
        }
        
        /* Form Styles */
        form {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 22px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-dark);
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-group label i {
            margin-right: 6px;
            color: var(--primary-color);
        }
        
        .form-group input[type="text"],
        .form-group input[type="password"],
        .form-group input[type="email"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
            outline: none;
            transition: all 0.3s ease;
            background: #f7fafc;
        }
        
        .form-group input:focus {
            border-color: var(--primary-color);
            background: var(--white);
            box-shadow: 0 0 0 3px rgba(27, 108, 168, 0.1);
        }
        
        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 25px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: var(--white);
        }
        
        .btn-primary:hover {
            background: #0f4c81;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(27, 108, 168, 0.3);
        }
        
        .btn-secondary {
            background: #e2e8f0;
            color: var(--text-dark);
        }
        
        .btn-secondary:hover {
            background: #cbd5e0;
            transform: translateY(-2px);
        }
        
        /* Alert Styles */
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
        
        .alert-success {
            background: rgba(72, 187, 120, 0.15);
            color: #2f855a;
            border: 1px solid rgba(72, 187, 120, 0.3);
        }
        
        .alert-error {
            background: rgba(229, 62, 62, 0.15);
            color: #c53030;
            border: 1px solid rgba(229, 62, 62, 0.3);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
.form-actions {
                flex-direction: column;
            }
            
            .btn {
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<?php 
$active_page = 'settings';
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
                    <h1><i class="fas fa-key"></i> Change Password</h1>
                    <p>Update your login password securely below.</p>
                </div>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="current_password"><i class="fas fa-lock"></i> Current Password *</label>
                        <input type="password" id="current_password" name="current_password" required autocomplete="current-password" placeholder="Enter current password">
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password"><i class="fas fa-lock-open"></i> New Password *</label>
                        <input type="password" id="new_password" name="new_password" required minlength="6" autocomplete="new-password" placeholder="Enter new password">
                        <small style="color: #666; display: block; margin-top: 8px;">Minimum 6 characters.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password"><i class="fas fa-check-double"></i> Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6" autocomplete="new-password" placeholder="Confirm new password">
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Password
                        </button>
                        <a href="welcome.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </form>
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
