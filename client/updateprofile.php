<?php
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in (use correct session variable)
if (!isset($_SESSION['organisation_id'])) {
    header("Location: index.php");
    exit();
}

$page_title = 'Update Profile';
$success = '';
$error = '';

// Get organisation details using the correct session variable
$stmt = $conn->prepare("SELECT * FROM organisations WHERE organisation_id = ?");
$stmt->bind_param("i", $_SESSION['organisation_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: welcome.php");
    exit();
}

$organisation = $result->fetch_assoc();
$stmt->close();

// Handle update request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $organisation_name = clean_input($_POST['organisation_name']);
    $contact_no = clean_input($_POST['contact_no']);
    $email = $organisation['email']; // Email is not editable, use existing value
    $whatsapp_no = clean_input($_POST['whatsapp_no']);
    $address = clean_input($_POST['address']);
    
    // Handle image upload
    $image_path = $organisation['image_path']; // Keep existing image by default
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['image']['type'];
        $file_size = $_FILES['image']['size'];
        $file_tmp = $_FILES['image']['tmp_name'];
        
        // Validate file
        if (!in_array($file_type, $allowed_types)) {
            $error = "Invalid file type. Only JPG, PNG, and GIF files are allowed.";
        } elseif ($file_size > 5000000) { // 5MB limit
            $error = "File size too large. Maximum size is 5MB.";
        } else {
            // Generate unique filename
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $new_filename = 'org_' . $organisation['organisation_id'] . '_' . time() . '.' . $file_extension;
            $upload_dir = app_path('uploads');
            $target_path = $upload_dir . DIRECTORY_SEPARATOR . $new_filename;
            $db_image_path = 'uploads/' . $new_filename;
            
            // Create uploads directory if it doesn't exist
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Move uploaded file
            if (move_uploaded_file($file_tmp, $target_path)) {
                // Remove old image if it exists
                if ($organisation['image_path'] && file_exists(app_path($organisation['image_path']))) {
                    unlink(app_path($organisation['image_path']));
                }
                
                $image_path = $db_image_path;
            } else {
                $error = "Failed to upload image.";
            }
        }
    }
    
    if (empty($error)) {
        // Check if email already exists (excluding current organisation)
        $check_email = $conn->prepare("SELECT organisation_id FROM organisations WHERE email = ? AND organisation_id != ?");
        $check_email->bind_param("si", $email, $organisation['organisation_id']);
        $check_email->execute();
        
        if ($check_email->get_result()->num_rows > 0) {
            $error = "Email already exists!";
        } else {
            // Update organisation
            $stmt = $conn->prepare("UPDATE organisations SET organisation_name = ?, contact_no = ?, email = ?, whatsapp_no = ?, address = ?, image_path = ? WHERE organisation_id = ?");
            $stmt->bind_param("ssssssi", $organisation_name, $contact_no, $email, $whatsapp_no, $address, $image_path, $organisation['organisation_id']);
            
            if ($stmt->execute()) {
                $success = "Profile updated successfully!";
                // Refresh session data
                $_SESSION['organisation_name'] = $organisation_name;
                $_SESSION['organisation_email'] = $email;
                // Refresh local data
                $organisation['organisation_name'] = $organisation_name;
                $organisation['contact_no'] = $contact_no;
                $organisation['email'] = $email;
                $organisation['whatsapp_no'] = $whatsapp_no;
                $organisation['address'] = $address;
                $organisation['image_path'] = $image_path;
            } else {
                $error = "Failed to update profile!";
            }
            $stmt->close();
        }
        $check_email->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile - Rotary Membership</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary-gradient: linear-gradient(180deg, #0f4c81 0%, #1b6ca8 100%);
            --primary-color: #1b6ca8;
            --secondary-color: #2d8f85;
            --sidebar-width: 260px;
            --header-height: 70px;
            --bg-color: #eef3f8;
            --text-dark: #2d3748;
            --text-light: #64748b;
            --white: #ffffff;
            --card-shadow: 0 18px 40px rgba(15, 76, 129, 0.08);
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background:
                radial-gradient(circle at top right, rgba(45, 143, 133, 0.12), transparent 24%),
                radial-gradient(circle at top left, rgba(27, 108, 168, 0.12), transparent 18%),
                var(--bg-color);
            min-height: 100vh;
            color: var(--text-dark);
            overflow-x: hidden;
        }
        
        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--primary-gradient);
            padding: 20px;
            z-index: 250;
            overflow-y: auto;
            box-shadow: 2px 0 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            margin-bottom: 25px;
        }
        
        .sidebar-logo .logo-icon {
            width: 45px;
            height: 45px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: var(--white);
        }
        
        .sidebar-logo .logo-text {
            font-size: 20px;
            font-weight: 700;
            color: var(--white);
            letter-spacing: 0.5px;
        }
        
        .sidebar-menu {
            list-style: none;
        }
        
        .sidebar-menu li {
            margin-bottom: 8px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-size: 15px;
            font-weight: 500;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.2);
            color: var(--white);
            transform: translateX(5px);
        }
        
        .sidebar-menu a i {
            width: 20px;
            text-align: center;
            font-size: 18px;
        }
        
        /* Main Content Area */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }
        
        /* Header Styles */
        .header {
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            height: var(--header-height);
            background: rgba(255, 255, 255, 0.94);
            padding: 0 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--card-shadow);
            border-bottom: 1px solid rgba(148, 163, 184, 0.18);
            backdrop-filter: blur(12px);
            z-index: 200;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 22px;
            color: var(--text-dark);
            cursor: pointer;
        }
        
        .search-box {
            position: relative;
            width: 300px;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            outline: none;
            transition: all 0.3s ease;
            background: #f7fafc;
        }
        
        .search-box input:focus {
            border-color: var(--primary-color);
            background: var(--white);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        /* Profile Dropdown */
        .profile-section {
            position: relative;
        }
        
        .profile-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 6px 12px 6px 6px;
            background: #f7fafc;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .profile-btn:hover {
            background: #edf2f7;
        }
        
        .profile-avatar {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 16px;
            font-weight: 600;
        }
        
        .profile-dropdown {
            position: absolute;
            top: 120%;
            right: 0;
            width: 220px;
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            padding: 10px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1001;
        }
        
        .profile-section:hover .profile-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            color: var(--text-dark);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s ease;
            font-size: 14px;
        }
        
        .dropdown-item:hover {
            background: #f7fafc;
            color: var(--primary-color);
        }
        
        .dropdown-item i {
            width: 18px;
            color: var(--text-light);
        }
        
        /* Dashboard Content */
        .dashboard-content {
            padding: 102px 30px 32px;
            max-width: 900px;
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
        .form-group input[type="email"],
        .form-group textarea {
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
        
        .form-group input:focus,
        .form-group textarea:focus {
            border-color: var(--primary-color);
            background: var(--white);
            box-shadow: 0 0 0 3px rgba(27, 108, 168, 0.1);
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        /* Image Preview */
        .image-preview {
            display: flex;
            align-items: center;
            gap: 25px;
            padding: 20px;
            background: #f7fafc;
            border-radius: 12px;
            margin-bottom: 25px;
        }
        
        .current-image {
            width: 100px;
            height: 100px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid #e2e8f0;
        }
        
        .image-upload {
            flex: 1;
        }
        
        .image-upload label {
            display: inline-block;
            padding: 10px 20px;
            background: var(--primary-color);
            color: var(--white);
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
            transition: all 0.3s ease;
        }
        
        .image-upload label:hover {
            background: #0f4c81;
        }
        
        .file-input {
            display: block;
            margin-top: 8px;
            font-size: 13px;
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
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .header {
                left: 0;
                width: 100%;
                padding: 0 16px;
            }
            
            .menu-toggle {
                display: block;
            }
            
            .search-box {
                display: none;
            }
            
            .dashboard-content {
                padding: 90px 15px 20px;
            }
            
            .image-preview {
                flex-direction: column;
                text-align: center;
            }
            
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
$active_page = 'profile';
include 'sidebar.php'; 
?>

<!-- Main Content Wrapper -->
<div class="main-content">
<?php include 'header.php'; ?>

    <div class="dashboard-content">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h1><i class="fas fa-user-edit"></i> Update Profile</h1>
                    <p>Update your organisation details below</p>
                </div>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="image-preview">
                        <?php if (!empty($organisation['image_path']) && file_exists(app_path($organisation['image_path']))): ?>
                            <img src="<?php echo htmlspecialchars(app_url($organisation['image_path'])); ?>" alt="Current Image" class="current-image">
                        <?php else: ?>
                            <div class="current-image" style="background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #666;">
                                <i class="fas fa-image" style="font-size: 40px;"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="image-upload">
                            <label><i class="fas fa-upload"></i> Upload New Image</label>
                            <input type="file" name="image" class="file-input" accept="image/*">
                            <small style="color: #666;">Max file size: 5MB. Supported formats: JPG, PNG, GIF</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-building"></i> Organisation Name *</label>
                        <input type="text" name="organisation_name" required value="<?php echo htmlspecialchars($organisation['organisation_name']); ?>" placeholder="Enter organisation name">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($organisation['email']); ?>" placeholder="Enter email address" readonly style="background: #e2e8f0; cursor: not-allowed;">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Contact No *</label>
                        <input type="text" name="contact_no" required value="<?php echo htmlspecialchars($organisation['contact_no']); ?>" placeholder="Enter contact number">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fab fa-whatsapp"></i> WhatsApp No</label>
                        <input type="text" name="whatsapp_no" value="<?php echo htmlspecialchars($organisation['whatsapp_no'] ?? ''); ?>" placeholder="Enter WhatsApp number">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-map-marker-alt"></i> Address</label>
                        <textarea name="address" placeholder="Enter address"><?php echo htmlspecialchars($organisation['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Profile
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
