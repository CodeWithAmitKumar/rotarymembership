<?php
require_once 'header.php';

$page_title = 'Edit Session';
$success = '';
$error = '';
$organisation_id = (int) $_SESSION['organisation_id'];
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Redirect if no valid ID is provided
if ($id === 0) {
    header("Location: sessions.php");
    exit();
}

$months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

// Fetch the existing session data
$stmt = $conn->prepare("SELECT * FROM sessions WHERE id = ? AND organisation_id = ?");
$stmt->bind_param("ii", $id, $organisation_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Session not found or doesn't belong to this organization
    header("Location: sessions.php");
    exit();
}

$session_data = $result->fetch_assoc();
$stmt->close();

// Process the form when submitted for update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_month_index = (int) $_POST['start_month'];
    $duration = (int) $_POST['duration'];
    $end_month = trim($_POST['end_month']);
    $session_label = trim($_POST['session_label']);
    
    $start_month = $months[$start_month_index] ?? '';

    if (empty($start_month) || empty($end_month) || empty($session_label) || $duration < 1) {
        $error = "All fields are required. Please try again.";
    } else {
        $update_stmt = $conn->prepare("UPDATE sessions SET session_label = ?, start_month = ?, end_month = ?, duration = ? WHERE id = ? AND organisation_id = ?");
        
        if ($update_stmt) {
            $update_stmt->bind_param("ssssii", $session_label, $start_month, $end_month, $duration, $id, $organisation_id);
            
            if ($update_stmt->execute()) {
                $success = "Session updated successfully!";
                // Update our local array so the form shows the new data
                $session_data['start_month'] = $start_month;
                $session_data['end_month'] = $end_month;
                $session_data['session_label'] = $session_label;
                $session_data['duration'] = $duration;
            } else {
                $error = "Failed to update session: " . $update_stmt->error;
            }
            $update_stmt->close();
        } else {
            $error = "Database error: " . $conn->error;
        }
    }
}

// Find the index of the currently saved start month to pre-select the dropdown
$current_month_index = array_search($session_data['start_month'], $months);
if ($current_month_index === false) $current_month_index = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Session - Rotary Membership</title>
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
            --edit-color: #3b82f6;
        }
        
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: radial-gradient(circle at top right, rgba(45, 143, 133, 0.12), transparent 24%), radial-gradient(circle at top left, rgba(27, 108, 168, 0.12), transparent 18%), var(--bg-color); min-height: 100vh; color: var(--text-dark); overflow-x: hidden; }
        
        /* Sidebar Styles */
        .sidebar { position: fixed; left: 0; top: 0; width: var(--sidebar-width); height: 100vh; background: var(--primary-gradient); padding: 20px; z-index: 250; overflow-y: auto; box-shadow: 2px 0 15px rgba(0, 0, 0, 0.1); transition: transform 0.3s ease; }
        .sidebar-logo { display: flex; align-items: center; gap: 12px; padding: 10px 0 30px; border-bottom: 1px solid rgba(255, 255, 255, 0.15); margin-bottom: 25px; }
        .sidebar-logo .logo-icon { width: 45px; height: 45px; background: rgba(255, 255, 255, 0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; color: var(--white); }
        .sidebar-logo .logo-text { font-size: 20px; font-weight: 700; color: var(--white); letter-spacing: 0.5px; }
        .sidebar-menu { list-style: none; }
        .sidebar-menu li { margin-bottom: 8px; }
        .sidebar-menu a { display: flex; align-items: center; gap: 14px; padding: 14px 16px; color: rgba(255, 255, 255, 0.8); text-decoration: none; border-radius: 10px; transition: all 0.3s ease; font-size: 15px; font-weight: 500; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: rgba(255, 255, 255, 0.2); color: var(--white); transform: translateX(5px); }
        .sidebar-menu a i { width: 20px; text-align: center; font-size: 18px; }
        
        /* Main Content Area */
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; }
        
        /* Header Styles */
        .header { position: fixed; top: 0; left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); height: var(--header-height); background: rgba(255, 255, 255, 0.94); padding: 0 30px; display: flex; align-items: center; justify-content: space-between; box-shadow: var(--card-shadow); border-bottom: 1px solid rgba(148, 163, 184, 0.18); backdrop-filter: blur(12px); z-index: 200; }
        .header-left { display: flex; align-items: center; gap: 20px; }
        .menu-toggle { display: none; background: none; border: none; font-size: 22px; color: var(--text-dark); cursor: pointer; }
        .search-box { position: relative; width: 300px; }
        .search-box input { width: 100%; padding: 12px 15px 12px 45px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 14px; outline: none; transition: all 0.3s ease; background: #f7fafc; }
        .search-box input:focus { border-color: var(--primary-color); background: var(--white); box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        .search-box i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-light); }
        .header-right { display: flex; align-items: center; gap: 15px; }
        
        /* Profile Dropdown */
        .profile-section { position: relative; }
        .profile-btn { display: flex; align-items: center; gap: 12px; padding: 6px 12px 6px 6px; background: #f7fafc; border: none; border-radius: 12px; cursor: pointer; transition: all 0.3s ease; }
        .profile-btn:hover { background: #edf2f7; }
        .profile-avatar { width: 38px; height: 38px; border-radius: 10px; background: var(--primary-gradient); display: flex; align-items: center; justify-content: center; color: var(--white); font-size: 16px; font-weight: 600; }
        .profile-dropdown { position: absolute; top: 120%; right: 0; width: 220px; background: var(--white); border-radius: 12px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15); padding: 10px; opacity: 0; visibility: hidden; transform: translateY(-10px); transition: all 0.3s ease; z-index: 1001; }
        .profile-section:hover .profile-dropdown { opacity: 1; visibility: visible; transform: translateY(0); }
        .dropdown-item { display: flex; align-items: center; gap: 12px; padding: 12px; color: var(--text-dark); text-decoration: none; border-radius: 8px; transition: all 0.2s ease; font-size: 14px; }
        .dropdown-item:hover { background: #f7fafc; color: var(--primary-color); }
        .dropdown-item i { width: 18px; color: var(--text-light); }

        /* Dashboard Content */
        .dashboard-content { padding: 102px 30px 32px; max-width: 900px; margin: 0 auto; }
        
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
        .form-group input[type="text"], .form-group input[type="number"], .form-group select { width: 100%; padding: 12px 15px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 14px; font-family: inherit; outline: none; transition: all 0.3s ease; background: #f7fafc; color: var(--text-dark); }
        .form-group input:focus:not([readonly]), .form-group select:focus { border-color: var(--primary-color); background: var(--white); box-shadow: 0 0 0 3px rgba(27, 108, 168, 0.1); }
        .form-group input[readonly] { background: #edf2f7; cursor: not-allowed; color: var(--text-light); font-weight: 600; border-color: #e2e8f0; }
        
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
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .header { left: 0; width: 100%; padding: 0 16px; }
            .menu-toggle { display: block; }
            .search-box { display: none; }
            .dashboard-content { padding: 90px 15px 20px; }
            .form-actions { flex-direction: column; }
            .btn { justify-content: center; }
        }
    </style>
</head>
<body>

<?php 
$active_page = 'sessions';
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
                <h1><i class="fas fa-edit"></i> Edit Session</h1>
                <p>Update the timeframe for this membership session.</p>
            </div>
            
            <form action="" method="POST" id="sessionForm">
                <div class="form-group">
                    <label for="start_month"><i class="fas fa-calendar-alt"></i> Start Month *</label>
                    <select name="start_month" id="start_month" required>
                        <?php foreach($months as $index => $month): ?>
                            <option value="<?php echo $index; ?>" <?php echo ($index === $current_month_index) ? 'selected' : ''; ?>>
                                <?php echo $month; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="duration"><i class="fas fa-hourglass-half"></i> Duration (Months) *</label>
                    <input type="number" name="duration" id="duration" min="1" max="12" value="<?php echo htmlspecialchars($session_data['duration']); ?>" required placeholder="e.g. 1, 3, 6">
                </div>

                <div class="form-group">
                    <label for="end_month_display"><i class="fas fa-calendar-check"></i> End Month</label>
                    <input type="text" id="end_month_display" readonly>
                    <input type="hidden" name="end_month" id="end_month_hidden">
                </div>

                <div class="form-group">
                    <label for="session_label"><i class="fas fa-tag"></i> Session Label</label>
                    <input type="text" name="session_label" id="session_label" readonly style="color: var(--edit-color);">
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Session
                    </button>
                    <a href="sessions.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Sessions
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

        // Dynamic session calculation logic
        const months = <?php echo json_encode($months); ?>;
        const startSelect = document.getElementById('start_month');
        const durationInput = document.getElementById('duration');
        const endDisplay = document.getElementById('end_month_display');
        const endHidden = document.getElementById('end_month_hidden');
        const sessionLabel = document.getElementById('session_label');

        function calculateSession() {
            const startIdx = parseInt(startSelect.value);
            const duration = parseInt(durationInput.value) || 0;
            
            if (duration > 0) {
                let endIdx = (startIdx + duration - 1) % 12;
                const startMonth = months[startIdx];
                const endMonth = months[endIdx];
                
                endDisplay.value = endMonth;
                endHidden.value = endMonth;
                sessionLabel.value = `${startMonth} - ${endMonth}`;
            } else {
                endDisplay.value = "";
                endHidden.value = "";
                sessionLabel.value = "";
            }
        }

        // Attach event listeners
        startSelect.addEventListener('change', calculateSession);
        durationInput.addEventListener('input', calculateSession);
        
        // Trigger calculation immediately on load so the readonly fields populate
        calculateSession();
    </script>
</body>
</html>
