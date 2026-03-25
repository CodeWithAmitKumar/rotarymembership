<?php
require_once '../config.php';
require_once '../functions.php';
requireAdminLogin();

$success = '';
$error = '';

// Check if settings exist
$check_query = "SELECT * FROM email_settings LIMIT 1";
$result = mysqli_query($conn, $check_query);
$settings_exist = mysqli_num_rows($result) > 0;
$current_settings = $settings_exist ? mysqli_fetch_assoc($result) : null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $smtp_username = clean_input($_POST['smtp_username']);
    $smtp_password = clean_input($_POST['smtp_password']);
    $smtp_from_email = clean_input($_POST['smtp_from_email']);
    $smtp_from_name = clean_input($_POST['smtp_from_name']);
    $smtp_host = clean_input($_POST['smtp_host']);
    $smtp_port = intval($_POST['smtp_port']);
    
    // Validate email
    if (!filter_var($smtp_username, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid sender email address!";
    } elseif (!filter_var($smtp_from_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid from email address!";
    } else {
        if ($settings_exist) {
            // UPDATE existing settings
            $stmt = $conn->prepare("UPDATE email_settings SET smtp_host = ?, smtp_port = ?, smtp_username = ?, smtp_password = ?, smtp_from_email = ?, smtp_from_name = ? WHERE setting_id = ?");
            $stmt->bind_param("sissssi", $smtp_host, $smtp_port, $smtp_username, $smtp_password, $smtp_from_email, $smtp_from_name, $current_settings['setting_id']);
            
            if ($stmt->execute()) {
                $success = "Email settings updated successfully! ✅";
                // Refresh current settings
                $result = mysqli_query($conn, $check_query);
                $current_settings = mysqli_fetch_assoc($result);
            } else {
                $error = "Error updating settings: " . $stmt->error;
            }
            $stmt->close();
        } else {
            // INSERT new settings
            $stmt = $conn->prepare("INSERT INTO email_settings (smtp_host, smtp_port, smtp_username, smtp_password, smtp_from_email, smtp_from_name) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sissss", $smtp_host, $smtp_port, $smtp_username, $smtp_password, $smtp_from_email, $smtp_from_name);
            
            if ($stmt->execute()) {
                $success = "Email settings saved successfully! ✅";
                $settings_exist = true;
                // Get newly inserted settings
                $result = mysqli_query($conn, $check_query);
                $current_settings = mysqli_fetch_assoc($result);
            } else {
                $error = "Error saving settings: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Settings</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-size: 24px;
            font-weight: 700;
        }
        
        .navbar-menu {
            display: flex;
            gap: 30px;
            align-items: center;
        }
        
        .navbar-menu a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .navbar-menu a:hover {
            opacity: 0.8;
        }
        
        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .card {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .card-header {
            margin-bottom: 30px;
            text-align: center;
        }
        
        .card-icon {
            font-size: 60px;
            color: #667eea;
            margin-bottom: 20px;
        }
        
        .card-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .card-header p {
            color: #666;
        }
        
        .success {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.5s ease;
        }
        
        .error {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.5s ease;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .info-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }
        
        .info-box i {
            font-size: 28px;
            margin-top: 5px;
        }
        
        .info-box h3 {
            margin-bottom: 10px;
        }
        
        .info-box ul {
            margin-left: 20px;
            margin-top: 10px;
        }
        
        .info-box li {
            margin-bottom: 5px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        
        label i {
            color: #667eea;
            margin-right: 5px;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="number"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .section-title {
            color: #667eea;
            font-size: 18px;
            font-weight: 600;
            margin: 30px 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .btn-submit {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        .password-toggle {
            position: relative;
        }
        
        .toggle-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
            font-size: 18px;
        }
        
        .toggle-icon:hover {
            color: #667eea;
        }
        
        .help-text {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
        
        .link-button {
            display: inline-block;
            margin-top: 10px;
            color: #667eea;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }
        
        .link-button:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .navbar {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }
            .navbar-menu {
                flex-direction: column;
                gap: 10px;
            }
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">
            <i class="fas fa-user-shield"></i> Admin Panel
        </div>
        <div class="navbar-menu">
            <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="create_studio.php"><i class="fas fa-plus"></i> Create Studio</a>
            <a href="manage_studios.php"><i class="fas fa-store"></i> Manage Studios</a>
            <a href="email_settings.php"><i class="fas fa-envelope-open-text"></i> Email Settings</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="card">
            <div class="card-header">
                <div class="card-icon">
                    <i class="fas fa-envelope-open-text"></i>
                </div>
                <h1>Email Settings</h1>
                <p>Configure SMTP settings for sending emails</p>
            </div>
            
            <?php if ($success): ?>
                <div class="success">
                    <i class="fas fa-check-circle" style="font-size: 24px;"></i>
                    <span><?php echo $success; ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error">
                    <i class="fas fa-exclamation-circle" style="font-size: 24px;"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>
            
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <div>
                    <h3>How to get Gmail App Password:</h3>
                    <ul>
                        <li>Go to: <a href="https://myaccount.google.com/apppasswords" target="_blank" style="color: #fff; text-decoration: underline;">Google App Passwords</a></li>
                        <li>Sign in to your Gmail account</li>
                        <li>Create App Password (App: Mail, Device: Other)</li>
                        <li>Copy the 16-character password</li>
                        <li>Paste it in the "App Password" field below</li>
                    </ul>
                </div>
            </div>
            
            <form method="POST">
                <!-- Hidden SMTP Settings with default values for Gmail -->
                <input type="hidden" name="smtp_host" value="<?php echo $current_settings ? htmlspecialchars($current_settings['smtp_host']) : 'smtp.gmail.com'; ?>">
                <input type="hidden" name="smtp_port" value="<?php echo $current_settings ? $current_settings['smtp_port'] : '587'; ?>">
                
                <div class="section-title">
                    <i class="fas fa-user-lock"></i> Gmail Credentials
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Sending Email (Gmail) *</label>
                    <input type="email" name="smtp_username" value="<?php echo $current_settings ? htmlspecialchars($current_settings['smtp_username']) : ''; ?>" placeholder="your-email@gmail.com" required>
                    <p class="help-text">The Gmail account that will send emails</p>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-key"></i> App Password *</label>
                    <div class="password-toggle">
                        <input type="password" id="appPassword" name="smtp_password" value="<?php echo $current_settings ? htmlspecialchars($current_settings['smtp_password']) : ''; ?>" placeholder="16-character app password" required>
                        <i class="fas fa-eye toggle-icon" id="togglePassword" onclick="togglePasswordVisibility()"></i>
                    </div>
                    <p class="help-text">16-character Google App Password (not your Gmail password)</p>
                    <a href="https://myaccount.google.com/apppasswords" target="_blank" class="link-button">
                        <i class="fas fa-external-link-alt"></i> Get App Password
                    </a>
                </div>
                
                <div class="section-title">
                    <i class="fas fa-paper-plane"></i> From Email Settings
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-at"></i> From Email Address *</label>
                    <input type="email" name="smtp_from_email" value="<?php echo $current_settings ? htmlspecialchars($current_settings['smtp_from_email']) : ''; ?>" placeholder="noreply@yourdomain.com" required>
                    <p class="help-text">Email address shown as sender (can be same as sending email)</p>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-signature"></i> From Name</label>
                    <input type="text" name="smtp_from_name" value="<?php echo $current_settings ? htmlspecialchars($current_settings['smtp_from_name']) : 'Photo Album System'; ?>" placeholder="Photo Album System" required>
                    <p class="help-text">Name shown as sender (e.g., "Photo Album System")</p>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-<?php echo $settings_exist ? 'sync' : 'save'; ?>"></i> 
                    <?php echo $settings_exist ? 'Update Email Settings' : 'Save Email Settings'; ?>
                </button>
            </form>
            
            <?php if ($current_settings): ?>
                <div style="margin-top: 20px; padding: 15px; background: #e8f5e9; border-radius: 10px; font-size: 13px; color: #2e7d32;">
                    <i class="fas fa-check-circle"></i> <strong>Last Updated:</strong> <?php echo date('M d, Y h:i A', strtotime($current_settings['updated_at'])); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('appPassword');
            const toggleIcon = document.getElementById('togglePassword');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
