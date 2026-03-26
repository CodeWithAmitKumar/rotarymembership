<?php
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in
if (!isset($_SESSION['organisation_id'])) {
    header("Location: index.php");
    exit();
}

$page_title = 'Payment Successful';
$organisation_id = (int) $_SESSION['organisation_id'];
$payment_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// If no payment ID is passed, redirect to all payments
if ($payment_id === 0) {
    header("Location: all_payments.php");
    exit();
}

// Fetch details for this specific payment only
$query = "SELECT p.id as payment_id, p.total_amount, p.payment_mode, p.payment_date, p.utr_receipt_no, 
                 m.first_name, m.last_name, m.member_id as rotary_id
          FROM payments p
          JOIN members m ON p.member_id = m.id
          WHERE p.id = ? AND p.organisation_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $payment_id, $organisation_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Payment not found or unauthorized
    header("Location: all_payments.php");
    exit();
}

$payment = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - Rotary Membership</title>
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
            --success-color: #10b981;
        }
        
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: radial-gradient(circle at top right, rgba(45, 143, 133, 0.12), transparent 24%), radial-gradient(circle at top left, rgba(27, 108, 168, 0.12), transparent 18%), var(--bg-color); min-height: 100vh; color: var(--text-dark); overflow-x: hidden; }
        
        /* Sidebar & Header Styles */
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

        /* Dashboard Content & Success Card Styles */
        .dashboard-content { padding: 120px 30px 32px; max-width: 700px; margin: 0 auto; display: flex; justify-content: center; }
        
        .success-card { background: var(--white); border-radius: 20px; box-shadow: var(--card-shadow); padding: 50px 40px; text-align: center; width: 100%; position: relative; overflow: hidden; animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1); }
        
        /* Top decorative line */
        .success-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 6px; background: var(--success-color); }

        /* Icon Animation */
        .icon-circle { width: 90px; height: 90px; background: rgba(16, 185, 129, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px; animation: popIn 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        .icon-circle i { font-size: 45px; color: var(--success-color); }

        .success-card h1 { font-size: 28px; color: var(--text-dark); margin-bottom: 10px; opacity: 0; animation: fadeIn 0.5s ease forwards 0.3s; }
        .success-card p { color: var(--text-light); font-size: 15px; margin-bottom: 35px; opacity: 0; animation: fadeIn 0.5s ease forwards 0.4s; }

        /* Receipt Details Box */
        .details-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px; text-align: left; margin-bottom: 35px; opacity: 0; animation: fadeIn 0.5s ease forwards 0.5s; }
        .detail-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14px; }
        .detail-row:last-child { margin-bottom: 0; padding-top: 12px; border-top: 1px dashed #cbd5e0; font-size: 18px; font-weight: 700; color: var(--primary-color); }
        .detail-label { color: var(--text-light); font-weight: 500; }
        .detail-value { color: var(--text-dark); font-weight: 600; text-align: right; }

        /* Action Buttons */
        .action-buttons { display: flex; gap: 15px; justify-content: center; opacity: 0; animation: fadeIn 0.5s ease forwards 0.6s; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 14px 25px; border-radius: 10px; font-size: 15px; font-weight: 600; text-decoration: none; cursor: pointer; border: none; transition: all 0.3s ease; flex: 1; }
        
        .btn-success { background: var(--success-color); color: var(--white); }
        .btn-success:hover { background: #059669; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3); }
        
        .btn-outline { background: transparent; color: var(--primary-color); border: 2px solid var(--primary-color); }
        .btn-outline:hover { background: rgba(27, 108, 168, 0.05); transform: translateY(-2px); }

        /* Animations */
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes popIn {
            0% { transform: scale(0); opacity: 0; }
            60% { transform: scale(1.1); opacity: 1; }
            100% { transform: scale(1); }
        }
        @keyframes fadeIn {
            to { opacity: 1; }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .header { left: 0; width: 100%; }
            .main-content { margin-left: 0; }
            .menu-toggle { display: block; }
            .action-buttons { flex-direction: column; }
        }
    </style>
</head>
<body>

<?php 
$active_page = 'payments'; 
include 'sidebar.php'; 
?>

<div class="main-content">
    <?php include 'header.php'; ?>

    <div class="dashboard-content">
        <div class="success-card">
            
            <div class="icon-circle">
                <i class="fas fa-check"></i>
            </div>
            
            <h1>Payment Successful!</h1>
            <p>The transaction has been recorded securely in the system.</p>

            <div class="details-box">
                <div class="detail-row">
                    <span class="detail-label">Receipt No:</span>
                    <span class="detail-value">REC-<?php echo str_pad($payment['payment_id'], 5, '0', STR_PAD_LEFT); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Member:</span>
                    <span class="detail-value">
                        <?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?><br>
                        <span style="font-size: 12px; color: #64748b;">ID: <?php echo htmlspecialchars($payment['rotary_id']); ?></span>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date:</span>
                    <span class="detail-value"><?php echo date('d M Y, h:i A', strtotime($payment['payment_date'])); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Payment Mode:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($payment['payment_mode']); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Total Amount:</span>
                    <span class="detail-value">₹ <?php echo number_format($payment['total_amount'], 2); ?></span>
                </div>
            </div>

            <div class="action-buttons">
                <a href="receipt.php?id=<?php echo $payment['payment_id']; ?>&action=download" target="_blank" class="btn btn-success">
                    <i class="fas fa-download"></i> Download Receipt
                </a>
                <a href="all_payments.php" class="btn btn-outline">
                    <i class="fas fa-list"></i> View All Payments
                </a>
            </div>

        </div>
    </div>
</div>

<script>
    function toggleSidebar() { document.querySelector('.sidebar').classList.toggle('active'); }
</script>
</body>
</html>