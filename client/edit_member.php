<?php
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in
if (!isset($_SESSION['organisation_id'])) {
    header("Location: index.php");
    exit();
}

$page_title = 'Edit Member';
$success = '';
$error = '';
$organisation_id = (int) $_SESSION['organisation_id'];
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Redirect if no valid ID is provided
if ($id === 0) {
    header("Location: members.php");
    exit();
}

// Fetch the existing member data
$stmt = $conn->prepare("SELECT * FROM members WHERE id = ? AND organisation_id = ?");
$stmt->bind_param("ii", $id, $organisation_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: members.php");
    exit();
}

$member_data = $result->fetch_assoc();
$stmt->close();

// Process the form when submitted for update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect fields
    $member_id = trim($_POST['member_id']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $joining_date = !empty($_POST['joining_date']) ? $_POST['joining_date'] : null;
    $original_rotary_date = !empty($_POST['original_rotary_date']) ? $_POST['original_rotary_date'] : null;
    $address = trim($_POST['address']);
    $city_state = trim($_POST['city_state']);
    $postal_code = trim($_POST['postal_code']);
    $phone_no = trim($_POST['phone_no']);
    $email = trim($_POST['email']);
    $online_account_with_rotary = $_POST['online_account_with_rotary'];
    $age_data_available = $_POST['age_data_available'];
    $satellite_member = $_POST['satellite_member'];

    if (empty($member_id) || empty($first_name) || empty($last_name)) {
        $error = "Member ID, First Name, and Last Name are required.";
    } else {
        
        $email_exists = false;
        
        // 1. Check for duplicate email (excluding THIS member's ID)
        if (!empty($email)) {
            $check_email = $conn->prepare("SELECT id FROM members WHERE email = ? AND id != ? AND organisation_id = ?");
            if ($check_email) {
                $check_email->bind_param("sii", $email, $id, $organisation_id);
                $check_email->execute();
                if ($check_email->get_result()->num_rows > 0) {
                    $email_exists = true;
                    $error = "Another member is already using this email address!";
                }
                $check_email->close();
            }
        }

        // 2. If email is unique (or empty), proceed with update
        if (!$email_exists) {
            $update_query = "UPDATE members SET 
                member_id = ?, first_name = ?, last_name = ?, joining_date = ?, 
                original_rotary_date = ?, address = ?, city_state = ?, postal_code = ?, 
                phone_no = ?, email = ?, online_account_with_rotary = ?, 
                age_data_available = ?, satellite_member = ? 
                WHERE id = ? AND organisation_id = ?";
                
            $update_stmt = $conn->prepare($update_query);
            
            if ($update_stmt) {
                $update_stmt->bind_param("sssssssssssssii", 
                    $member_id, $first_name, $last_name, $joining_date, 
                    $original_rotary_date, $address, $city_state, $postal_code, 
                    $phone_no, $email, $online_account_with_rotary, 
                    $age_data_available, $satellite_member, $id, $organisation_id
                );
                
                try {
                    if ($update_stmt->execute()) {
                        $success = "Member updated successfully!";
                        
                        // Update local array to reflect changes on the screen immediately
                        $member_data['member_id'] = $member_id;
                        $member_data['first_name'] = $first_name;
                        $member_data['last_name'] = $last_name;
                        $member_data['joining_date'] = $joining_date;
                        $member_data['original_rotary_date'] = $original_rotary_date;
                        $member_data['address'] = $address;
                        $member_data['city_state'] = $city_state;
                        $member_data['postal_code'] = $postal_code;
                        $member_data['phone_no'] = $phone_no;
                        $member_data['email'] = $email;
                        $member_data['online_account_with_rotary'] = $online_account_with_rotary;
                        $member_data['age_data_available'] = $age_data_available;
                        $member_data['satellite_member'] = $satellite_member;
                    }
                } catch (mysqli_sql_exception $e) {
                    // Catch duplicate Member ID
                    if ($e->getCode() == 1062) {
                        $error = "Another member with this Rotary ID already exists!";
                    } else {
                        $error = "Failed to update member: " . $e->getMessage();
                    }
                }
                $update_stmt->close();
            } else {
                $error = "Database error: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Member - Rotary Membership</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
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
        
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: radial-gradient(circle at top right, rgba(45, 143, 133, 0.12), transparent 24%), radial-gradient(circle at top left, rgba(27, 108, 168, 0.12), transparent 18%), var(--bg-color); min-height: 100vh; color: var(--text-dark); overflow-x: hidden; }
        
        .sidebar { position: fixed; left: 0; top: 0; width: var(--sidebar-width); height: 100vh; background: var(--primary-gradient); padding: 20px; z-index: 250; overflow-y: auto; box-shadow: 2px 0 15px rgba(0, 0, 0, 0.1); transition: transform 0.3s ease; }
        .sidebar-logo { display: flex; align-items: center; gap: 12px; padding: 10px 0 30px; border-bottom: 1px solid rgba(255, 255, 255, 0.15); margin-bottom: 25px; }
        .sidebar-logo .logo-icon { width: 45px; height: 45px; background: rgba(255, 255, 255, 0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; color: var(--white); }
        .sidebar-logo .logo-text { font-size: 20px; font-weight: 700; color: var(--white); letter-spacing: 0.5px; }
        .sidebar-menu { list-style: none; }
        .sidebar-menu li { margin-bottom: 8px; }
        .sidebar-menu a { display: flex; align-items: center; gap: 14px; padding: 14px 16px; color: rgba(255, 255, 255, 0.8); text-decoration: none; border-radius: 10px; transition: all 0.3s ease; font-size: 15px; font-weight: 500; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: rgba(255, 255, 255, 0.2); color: var(--white); transform: translateX(5px); }
        .sidebar-menu a i { width: 20px; text-align: center; font-size: 18px; }
        
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; }
        
        .header { position: fixed; top: 0; left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); height: var(--header-height); background: rgba(255, 255, 255, 0.94); padding: 0 30px; display: flex; align-items: center; justify-content: space-between; box-shadow: var(--card-shadow); border-bottom: 1px solid rgba(148, 163, 184, 0.18); backdrop-filter: blur(12px); z-index: 200; }
        .header-left { display: flex; align-items: center; gap: 20px; }
        .menu-toggle { display: none; background: none; border: none; font-size: 22px; color: var(--text-dark); cursor: pointer; }
        .search-box { position: relative; width: 300px; }
        .search-box input { width: 100%; padding: 12px 15px 12px 45px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 14px; outline: none; transition: all 0.3s ease; background: #f7fafc; }
        .search-box input:focus { border-color: var(--primary-color); background: var(--white); box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        .search-box i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-light); }
        .header-right { display: flex; align-items: center; gap: 15px; }
        
        .profile-section { position: relative; }
        .profile-btn { display: flex; align-items: center; gap: 12px; padding: 6px 12px 6px 6px; background: #f7fafc; border: none; border-radius: 12px; cursor: pointer; transition: all 0.3s ease; }
        .profile-btn:hover { background: #edf2f7; }
        .profile-avatar { width: 38px; height: 38px; border-radius: 10px; background: var(--primary-gradient); display: flex; align-items: center; justify-content: center; color: var(--white); font-size: 16px; font-weight: 600; }
        .profile-dropdown { position: absolute; top: 120%; right: 0; width: 220px; background: var(--white); border-radius: 12px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15); padding: 10px; opacity: 0; visibility: hidden; transform: translateY(-10px); transition: all 0.3s ease; z-index: 1001; }
        .profile-section:hover .profile-dropdown { opacity: 1; visibility: visible; transform: translateY(0); }
        .dropdown-item { display: flex; align-items: center; gap: 12px; padding: 12px; color: var(--text-dark); text-decoration: none; border-radius: 8px; transition: all 0.2s ease; font-size: 14px; }
        .dropdown-item:hover { background: #f7fafc; color: var(--primary-color); }
        .dropdown-item i { width: 18px; color: var(--text-light); }
        
        .dashboard-content { padding: 102px 30px 32px; max-width: 900px; margin: 0 auto; }
        
        .card { background: var(--white); border-radius: 16px; box-shadow: var(--card-shadow); overflow: hidden; }
        .card-header { padding: 25px 30px; border-bottom: 1px solid #e2e8f0; }
        .card-header h1 { font-size: 22px; font-weight: 700; color: var(--text-dark); margin-bottom: 5px; }
        .card-header p { font-size: 14px; color: var(--text-light); }
        
        form { padding: 30px; }
        .form-row { display: flex; gap: 20px; }
        .form-row .form-group { flex: 1; }
        .form-group { margin-bottom: 22px; }
        .form-group label { display: block; margin-bottom: 8px; color: var(--text-dark); font-weight: 600; font-size: 14px; }
        .form-group label i { margin-right: 6px; color: var(--primary-color); }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="date"],
        .form-group input[type="tel"],
        .form-group select,
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
            color: var(--text-dark);
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: var(--primary-color);
            background: var(--white);
            box-shadow: 0 0 0 3px rgba(27, 108, 168, 0.1);
        }
        .form-group textarea { resize: vertical; min-height: 80px; }
        
        .form-actions { display: flex; gap: 15px; margin-top: 10px; padding-top: 20px; border-top: 1px solid #e2e8f0; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 25px; border-radius: 10px; font-size: 14px; font-weight: 600; text-decoration: none; cursor: pointer; border: none; transition: all 0.3s ease; }
        .btn-primary { background: var(--primary-color); color: var(--white); }
        .btn-primary:hover { background: #0f4c81; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(27, 108, 168, 0.3); }
        .btn-secondary { background: #e2e8f0; color: var(--text-dark); }
        .btn-secondary:hover { background: #cbd5e0; transform: translateY(-2px); }
        
        .alert { padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 500; }
        .alert-success { background: rgba(72, 187, 120, 0.15); color: #2f855a; border: 1px solid rgba(72, 187, 120, 0.3); }
        .alert-error { background: rgba(229, 62, 62, 0.15); color: #c53030; border: 1px solid rgba(229, 62, 62, 0.3); }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .header { left: 0; width: 100%; padding: 0 16px; }
            .menu-toggle { display: block; }
            .search-box { display: none; }
            .dashboard-content { padding: 90px 15px 20px; }
            .form-row { flex-direction: column; gap: 0; }
            .form-actions { flex-direction: column; }
            .btn { justify-content: center; }
        }
    </style>
</head>
<body>

<?php 
$active_page = 'members';
include 'sidebar.php'; 
?>

<div class="main-content">
<?php include 'header.php'; ?>

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
                <h1><i class="fas fa-edit"></i> Edit Member</h1>
                <p>Update the information for this member.</p>
            </div>
            
            <form action="" method="POST">
                <div class="form-group">
                    <label for="member_id"><i class="fas fa-id-card"></i> Rotary Member ID *</label>
                    <input type="text" name="member_id" id="member_id" value="<?php echo htmlspecialchars($member_data['member_id']); ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name"><i class="fas fa-user"></i> First Name *</label>
                        <input type="text" name="first_name" id="first_name" value="<?php echo htmlspecialchars($member_data['first_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name"><i class="fas fa-user"></i> Last Name *</label>
                        <input type="text" name="last_name" id="last_name" value="<?php echo htmlspecialchars($member_data['last_name']); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($member_data['email'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="phone_no"><i class="fas fa-phone"></i> Phone Number</label>
                        <input type="tel" name="phone_no" id="phone_no" value="<?php echo htmlspecialchars($member_data['phone_no'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="joining_date"><i class="fas fa-calendar-day"></i> Joining Date (This Club)</label>
                        <input type="date" name="joining_date" id="joining_date" value="<?php echo htmlspecialchars($member_data['joining_date'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="original_rotary_date"><i class="fas fa-calendar-alt"></i> Original Rotary Date</label>
                        <input type="date" name="original_rotary_date" id="original_rotary_date" value="<?php echo htmlspecialchars($member_data['original_rotary_date'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="address"><i class="fas fa-map-marker-alt"></i> Address</label>
                    <textarea name="address" id="address"><?php echo htmlspecialchars($member_data['address'] ?? ''); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="city_state"><i class="fas fa-city"></i> City / State</label>
                        <input type="text" name="city_state" id="city_state" value="<?php echo htmlspecialchars($member_data['city_state'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="postal_code"><i class="fas fa-mail-bulk"></i> Postal Code</label>
                        <input type="text" name="postal_code" id="postal_code" value="<?php echo htmlspecialchars($member_data['postal_code'] ?? ''); ?>">
                    </div>
                </div>

                <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 25px 0;">

                <div class="form-row">
                    <div class="form-group">
                        <label for="online_account_with_rotary"><i class="fas fa-globe"></i> Online Account with Rotary?</label>
                        <select name="online_account_with_rotary" id="online_account_with_rotary">
                            <option value="N" <?php echo (($member_data['online_account_with_rotary'] ?? 'N') === 'N') ? 'selected' : ''; ?>>No (N)</option>
                            <option value="Y" <?php echo (($member_data['online_account_with_rotary'] ?? 'N') === 'Y') ? 'selected' : ''; ?>>Yes (Y)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="age_data_available"><i class="fas fa-birthday-cake"></i> Age Data Available?</label>
                        <select name="age_data_available" id="age_data_available">
                            <option value="N" <?php echo (($member_data['age_data_available'] ?? 'N') === 'N') ? 'selected' : ''; ?>>No (N)</option>
                            <option value="Y" <?php echo (($member_data['age_data_available'] ?? 'N') === 'Y') ? 'selected' : ''; ?>>Yes (Y)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="satellite_member"><i class="fas fa-satellite"></i> Satellite Member?</label>
                        <select name="satellite_member" id="satellite_member">
                            <option value="N" <?php echo (($member_data['satellite_member'] ?? 'N') === 'N') ? 'selected' : ''; ?>>No (N)</option>
                            <option value="Y" <?php echo (($member_data['satellite_member'] ?? 'N') === 'Y') ? 'selected' : ''; ?>>Yes (Y)</option>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Member
                    </button>
                    <a href="members.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </form>
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
