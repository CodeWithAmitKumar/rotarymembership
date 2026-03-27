<?php
require_once 'header.php';

$page_title = 'Add Session';
$success = '';
$error = '';
$organisation_id = (int) $_SESSION['organisation_id'];

$months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

// Process the form when submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_month_index = (int) $_POST['start_month'];
    $duration = (int) $_POST['duration'];
    $end_month = trim($_POST['end_month']);
    $session_label = trim($_POST['session_label']);
    
    // Convert the start month index back to the text name safely
    $start_month = $months[$start_month_index] ?? '';

    if (empty($start_month) || empty($end_month) || empty($session_label) || $duration < 1) {
        $error = "All fields are required. Please try again.";
    } else {
        // Prepare the insert statement
        $stmt = $conn->prepare("INSERT INTO sessions (organisation_id, session_label, start_month, end_month, duration) VALUES (?, ?, ?, ?, ?)");
        
        if ($stmt) {
            $stmt->bind_param("isssi", $organisation_id, $session_label, $start_month, $end_month, $duration);
            
            if ($stmt->execute()) {
                $success = "Session added successfully!";
            } else {
                $error = "Failed to add session: " . $stmt->error;
            }
            $stmt->close();
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
    <title>Add Session - Rotary Membership</title>
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
        .form-group input[type="text"], .form-group input[type="number"], .form-group select { width: 100%; padding: 12px 15px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 14px; font-family: inherit; outline: none; transition: all 0.3s ease; background: #f7fafc; color: var(--text-dark); }
        .form-group input:focus:not([readonly]), .form-group select:focus { border-color: var(--primary-color); background: var(--white); box-shadow: 0 0 0 3px rgba(27, 108, 168, 0.1); }
        .form-group input[readonly] { background: #edf2f7; cursor: not-allowed; color: var(--text-light); font-weight: 600; border-color: #e2e8f0; }
        
        /* Form Actions & Buttons */
        .form-actions { display: flex; gap: 15px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e2e8f0; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 25px; border-radius: 10px; font-size: 14px; font-weight: 600; text-decoration: none; cursor: pointer; border: none; transition: all 0.3s ease; }
        .btn-primary { background: var(--primary-color); color: var(--white); }
        .btn-primary:hover { background: #0f4c81; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(27, 108, 168, 0.3); }
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
                <h1><i class="fas fa-plus-circle"></i> Add New Session</h1>
                <p>Define the timeframe for this membership session automatically.</p>
            </div>
            
            <form action="" method="POST" id="sessionForm">
                <div class="form-group">
                    <label for="start_month"><i class="fas fa-calendar-alt"></i> Start Month *</label>
                    <select name="start_month" id="start_month" required>
                        <?php foreach($months as $index => $month): ?>
                            <option value="<?php echo $index; ?>"><?php echo $month; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="duration"><i class="fas fa-hourglass-half"></i> Duration (Months) *</label>
                    <input type="number" name="duration" id="duration" min="1" max="12" value="1" required placeholder="e.g. 1, 3, 6">
                </div>

                <div class="form-group">
                    <label for="end_month_display"><i class="fas fa-calendar-check"></i> End Month</label>
                    <input type="text" id="end_month_display" readonly>
                    <input type="hidden" name="end_month" id="end_month_hidden">
                </div>

                <div class="form-group">
                    <label for="session_label"><i class="fas fa-tag"></i> Session Label</label>
                    <input type="text" name="session_label" id="session_label" readonly style="color: var(--primary-color);">
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Session
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

        startSelect.addEventListener('change', calculateSession);
        durationInput.addEventListener('input', calculateSession);
        calculateSession();
    </script>
</body>
</html>
