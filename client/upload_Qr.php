<?php
require_once 'header.php';

$page_title = 'Upload QR Code';
$success = '';
$error = '';
$organisation_id = (int) $_SESSION['organisation_id'];

// Fetch current QR code if it exists
$stmt = $conn->prepare("SELECT qr_code_path FROM organisations WHERE organisation_id = ?");
$stmt->bind_param("i", $organisation_id);
$stmt->execute();
$result = $stmt->get_result();
$org_data = $result->fetch_assoc();
$current_qr = $org_data['qr_code_path'] ?? null;
$stmt->close();

// Handle File Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['qr_image'])) {
    $file = $_FILES['qr_image'];
    
    // Check for upload errors
    if ($file['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        // Validate file type and size
        if (!in_array($file['type'], $allowed_types)) {
            $error = "Invalid file type. Please upload a JPG or PNG image.";
        } elseif ($file['size'] > $max_size) {
            $error = "File is too large. Maximum size is 2MB.";
        } else {
            // Ensure directories exist
            $upload_dir = app_path('uploads/qr_codes');
            if (!is_dir(app_path('uploads'))) mkdir(app_path('uploads'), 0755, true);
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            
            // Generate a safe, unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = 'org_' . $organisation_id . '_qr_' . time() . '.' . $extension;
            $destination = $upload_dir . DIRECTORY_SEPARATOR . $new_filename;
            $db_path = 'uploads/qr_codes/' . $new_filename;
            
            // Move file and update database
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                // Delete old QR code file if it exists to save space
                if ($current_qr && file_exists(app_path($current_qr))) {
                    unlink(app_path($current_qr));
                }
                
                // Update DB
                $update = $conn->prepare("UPDATE organisations SET qr_code_path = ? WHERE organisation_id = ?");
                $update->bind_param("si", $db_path, $organisation_id);
                
                if ($update->execute()) {
                    $success = "QR Code updated successfully!";
                    $current_qr = $db_path; // Update view
                } else {
                    $error = "Database error: Could not save file path.";
                }
                $update->close();
            } else {
                $error = "Failed to move uploaded file to destination folder. Check folder permissions.";
            }
        }
    } else {
        $error = "Please select a file to upload.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload QR Code - Rotary Membership</title>
    <?php render_client_shared_styles(); ?>
<style>
/* Dashboard Content */
        .dashboard-content { max-width: 800px; margin: 0 auto; }
        .card { background: var(--white); border-radius: 16px; box-shadow: var(--card-shadow); overflow: hidden; margin-bottom: 25px; padding: 30px; border: 1px solid var(--border-color); }
        .card-header { border-bottom: 1px dashed var(--border-color); padding-bottom: 20px; margin-bottom: 30px; text-align: center;}
        .card-header h1 { font-size: 22px; font-weight: 700; color: var(--text-dark); margin-bottom: 8px; }
        .card-header p { font-size: 14px; color: var(--text-light); }

        /* QR Display */
        .qr-display-area { background: #f8fafc; border: 2px dashed #cbd5e0; border-radius: 12px; padding: 30px; text-align: center; margin-bottom: 30px; }
        .qr-image { max-width: 250px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); margin-bottom: 15px; }
        .no-qr-icon { font-size: 80px; color: #cbd5e0; margin-bottom: 15px; }

        /* File Upload */
        .file-upload-container { display: flex; flex-direction: column; align-items: center; gap: 15px; }
        .file-input { display: none; }
        .custom-file-btn { display: inline-block; padding: 12px 25px; background: var(--white); border: 2px solid var(--primary-color); border-radius: 8px; color: var(--primary-color); font-weight: 600; font-size: 15px; cursor: pointer; transition: all 0.3s ease; }
        .custom-file-btn:hover { background: var(--primary-color); color: var(--white); box-shadow: 0 4px 10px rgba(27, 108, 168, 0.2); }
        #file-name { font-size: 14px; font-weight: 600; color: var(--success-color); }

        .btn-submit { background: var(--success-color); color: var(--white); border: none; padding: 14px 40px; border-radius: 10px; font-size: 16px; font-weight: 700; cursor: pointer; transition: 0.3s; margin-top: 20px; width: 100%; max-width: 300px; display: none; margin-left: auto; margin-right: auto;}
        .btn-submit:hover { background: #059669; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3); }

        /* Alerts */
        .alert { padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 600; }
        .alert-success { background: rgba(16, 185, 129, 0.1); color: #059669; border: 1px solid rgba(16, 185, 129, 0.3); }
        .alert-error { background: rgba(229, 62, 62, 0.1); color: #c53030; border: 1px solid rgba(229, 62, 62, 0.3); }

        /* Responsive */
        @media (max-width: 768px) {
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
                <h1><i class="fas fa-qrcode" style="color: var(--primary-color);"></i> UPI / Bank QR Code</h1>
                <p>Upload your organization's official payment QR code here. It will automatically display during checkout.</p>
            </div>
            
            <div class="qr-display-area">
                <?php if ($current_qr && file_exists(app_path($current_qr))): ?>
                    <img src="<?php echo htmlspecialchars(app_url($current_qr)); ?>" alt="Current QR Code" class="qr-image">
                    <p style="color: var(--success-color); font-weight: 600;"><i class="fas fa-check-circle"></i> Active QR Code</p>
                <?php else: ?>
                    <i class="fas fa-qrcode no-qr-icon"></i>
                    <p style="color: var(--text-light); font-weight: 500;">No QR Code uploaded yet.</p>
                <?php endif; ?>
            </div>

            <form action="" method="POST" enctype="multipart/form-data" class="file-upload-container">
                <input type="file" name="qr_image" id="qr_image" class="file-input" accept="image/png, image/jpeg, image/jpg" onchange="showSubmitButton(this)">
                
                <label for="qr_image" class="custom-file-btn">
                    <i class="fas fa-upload"></i> <?php echo $current_qr ? 'Upload New QR Code' : 'Select QR Image'; ?>
                </label>
                
                <span id="file-name"></span>
                <p style="font-size: 13px; color: var(--text-light); font-weight: 500;">Supported formats: JPG, PNG (Max 2MB)</p>

                <button type="submit" class="btn-submit" id="saveBtn">
                    <i class="fas fa-save"></i> Save QR Code
                </button>
            </form>
        </div>
    </div>
</div>

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

    // File Upload Handler
    function showSubmitButton(input) {
        if (input.files && input.files[0]) {
            document.getElementById('file-name').innerHTML = '<i class="fas fa-image"></i> ' + input.files[0].name;
            document.getElementById('saveBtn').style.display = 'block';
        } else {
            document.getElementById('file-name').innerHTML = '';
            document.getElementById('saveBtn').style.display = 'none';
        }
    }
</script>
</body>
</html>
