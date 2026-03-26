<?php
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in
if (!isset($_SESSION['organisation_id'])) {
    header("Location: index.php");
    exit();
}

// Redirect if accessed directly without submitting the previous form
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['member_id']) || empty($_POST['payment_heads'])) {
    header("Location: create_payment.php");
    exit();
}

$page_title = 'Checkout';
$organisation_id = (int) $_SESSION['organisation_id'];
$member_id = (int) $_POST['member_id'];
$session_id = (int) $_POST['session_id'];
$selected_heads = $_POST['payment_heads']; // Array of head IDs

// 1. Re-calculate the total securely on the backend to prevent tampering
$total_amount = 0;
$heads_summary = [];

if (is_array($selected_heads) && count($selected_heads) > 0) {
    $placeholders = implode(',', array_fill(0, count($selected_heads), '?'));
    
    $query = "SELECT id, head_name, head_amount FROM payment_heads WHERE id IN ($placeholders) AND organisation_id = ?";
    $stmt = $conn->prepare($query);
    
    $bind_params = $selected_heads;
    $bind_params[] = $organisation_id;
    $types = str_repeat('i', count($selected_heads)) . 'i';
    $stmt->bind_param($types, ...$bind_params);
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $total_amount += $row['head_amount'];
        $heads_summary[] = $row; // Store for receipt generation
    }
    $stmt->close();
}

// 2. Fetch Member Details for display
$mem_stmt = $conn->prepare("SELECT first_name, last_name, member_id FROM members WHERE id = ? AND organisation_id = ?");
$mem_stmt->bind_param("ii", $member_id, $organisation_id);
$mem_stmt->execute();
$member_info = $mem_stmt->get_result()->fetch_assoc();
$mem_stmt->close();

// 3. Fetch Organisation's Custom QR Code
$qr_stmt = $conn->prepare("SELECT qr_code_path FROM organisations WHERE organisation_id = ?");
$qr_stmt->bind_param("i", $organisation_id);
$qr_stmt->execute();
$qr_data = $qr_stmt->get_result()->fetch_assoc();
$qr_code_image = $qr_data['qr_code_path'] ?? null;
$qr_stmt->close();

// --- PROCESS FINAL SUBMISSION ---
if (isset($_POST['finalize_payment'])) {
    $payment_mode = $_POST['payment_mode']; // 'Online' or 'Offline'
    $utr_no = ($payment_mode === 'Online') ? trim($_POST['utr_no']) : null;
    
    $json_heads = json_encode($heads_summary);
    
    $insert = $conn->prepare("INSERT INTO payments (organisation_id, member_id, session_id, payment_heads_json, total_amount, payment_mode, utr_receipt_no) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $insert->bind_param("iiisdss", $organisation_id, $member_id, $session_id, $json_heads, $total_amount, $payment_mode, $utr_no);
    
    if ($insert->execute()) {
        $payment_id = $insert->insert_id;
        // Redirect to the success page
        header("Location: payment_success.php?id=" . $payment_id);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Rotary Membership</title>
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
        
        /* Main Content Area & Header */
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; }
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
        
        /* Checkout Specific Styles */
        .dashboard-content { padding: 102px 30px 32px; max-width: 900px; margin: 0 auto; }
        .checkout-container { max-width: 800px; margin: 0 auto; }
        .card { background: var(--white); border-radius: 16px; box-shadow: var(--card-shadow); padding: 40px; overflow: hidden; }
        
        .summary-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px; margin-bottom: 30px; }
        .summary-box h2 { font-size: 18px; margin-bottom: 15px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; color: var(--text-dark); }
        .summary-item { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 15px; color: var(--text-dark); }
        .summary-total { display: flex; justify-content: space-between; margin-top: 15px; padding-top: 15px; border-top: 2px dashed #cbd5e0; font-size: 20px; font-weight: 800; color: var(--primary-color); }
        
        .payment-methods { display: flex; gap: 20px; margin-bottom: 30px; }
        .method-card { flex: 1; border: 2px solid #e2e8f0; border-radius: 12px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.2s; font-weight: 600; color: var(--text-dark); }
        .method-card:hover { border-color: var(--primary-color); }
        .method-card.active { border-color: var(--primary-color); background: rgba(27,108,168,0.05); }
        .method-card i { font-size: 24px; display: block; margin-bottom: 10px; color: var(--primary-color); }
        
        /* Online Payment Section */
        #online-details { display: none; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 30px; text-align: center; margin-bottom: 30px; }
        .qr-placeholder { margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; }
        
        .form-group input { width: 100%; max-width: 400px; padding: 12px 15px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 15px; outline: none; margin: 0 auto; display: block; text-align: center; font-family: inherit;}
        .form-group input:focus { border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(27,108,168,0.1); }
        
        .btn-submit { width: 100%; background: var(--success-color); color: var(--white); border: none; padding: 16px; border-radius: 10px; font-size: 18px; font-weight: 700; cursor: pointer; transition: 0.3s; font-family: inherit; display: inline-flex; align-items: center; justify-content: center; gap: 8px;}
        .btn-submit:hover { background: #059669; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(16,185,129,0.3); }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .header { left: 0; width: 100%; padding: 0 16px; }
            .menu-toggle { display: block; }
            .search-box { display: none; }
            .dashboard-content { padding: 90px 15px 20px; }
            .card { padding: 25px 20px; }
            .payment-methods { flex-direction: column; gap: 15px; }
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
        <div class="checkout-container">
            <div class="card">
                <h1 style="margin-bottom: 25px; text-align: center;"><i class="fas fa-lock" style="color: var(--primary-color); margin-right: 10px;"></i> Complete Payment</h1>
                
                <div class="summary-box">
                    <h2>Payment for: <?php echo htmlspecialchars($member_info['first_name'] . ' ' . $member_info['last_name']); ?> (<?php echo htmlspecialchars($member_info['member_id']); ?>)</h2>
                    <?php foreach($heads_summary as $h): ?>
                        <div class="summary-item">
                            <span><?php echo htmlspecialchars($h['head_name']); ?></span>
                            <span>₹ <?php echo number_format($h['head_amount'], 2); ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="summary-total">
                        <span>Total Amount to Pay:</span>
                        <span>₹ <?php echo number_format($total_amount, 2); ?></span>
                    </div>
                </div>

                <form action="" method="POST" id="checkoutForm">
                    <input type="hidden" name="member_id" value="<?php echo $member_id; ?>">
                    <input type="hidden" name="session_id" value="<?php echo $session_id; ?>">
                    <?php foreach($selected_heads as $hid): ?>
                        <input type="hidden" name="payment_heads[]" value="<?php echo htmlspecialchars($hid); ?>">
                    <?php endforeach; ?>
                    
                    <input type="hidden" name="payment_mode" id="payment_mode_input" value="Offline">
                    <input type="hidden" name="finalize_payment" value="1">

                    <h3 style="margin-bottom: 15px; text-align: center; color: var(--text-dark);">Select Payment Method</h3>
                    <div class="payment-methods">
                        <div class="method-card" id="card-offline" onclick="selectMethod('Offline')">
                            <i class="fas fa-money-bill-wave"></i> Cash / Cheque (Offline)
                        </div>
                        <div class="method-card" id="card-online" onclick="selectMethod('Online')">
                            <i class="fas fa-qrcode"></i> Scan QR / UPI (Online)
                        </div>
                    </div>

                    <div id="online-details">
                        <p style="margin-bottom: 15px; font-weight: 600; color: var(--text-dark);">Scan to Pay: <span style="color: var(--primary-color);">₹ <?php echo number_format($total_amount, 2); ?></span></p>
                        
                        <div class="qr-placeholder">
                            <?php if ($qr_code_image && file_exists($qr_code_image)): ?>
                                <img src="<?php echo htmlspecialchars($qr_code_image); ?>" alt="Scan to Pay" style="max-width: 100%; max-height: 250px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                            <?php else: ?>
                                <div style="background: #f1f5f9; border: 2px dashed #cbd5e0; padding: 40px; border-radius: 10px; width: 100%; max-width: 250px;">
                                    <i class="fas fa-qrcode" style="font-size: 60px; color: #94a3b8; margin-bottom: 10px;"></i>
                                    <p style="font-size: 13px; color: var(--text-light);">No QR Code configured in settings.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group" style="margin-top: 20px;">
                            <input type="text" name="utr_no" id="utr_no" placeholder="Enter 12-digit UTR / Reference No.">
                        </div>
                    </div>

                    <button type="submit" class="btn-submit" id="finalBtn">
                        <i class="fas fa-check-circle"></i> Record Offline Payment
                    </button>
                </form>
            </div>
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

    // Payment Method Selection Logic
    selectMethod('Offline'); // Set default on load

    function selectMethod(method) {
        document.getElementById('payment_mode_input').value = method;
        
        if (method === 'Online') {
            document.getElementById('card-online').classList.add('active');
            document.getElementById('card-offline').classList.remove('active');
            document.getElementById('online-details').style.display = 'block';
            document.getElementById('utr_no').required = true;
            document.getElementById('finalBtn').innerHTML = '<i class="fas fa-check-circle"></i> Verify & Generate Receipt';
        } else {
            document.getElementById('card-offline').classList.add('active');
            document.getElementById('card-online').classList.remove('active');
            document.getElementById('online-details').style.display = 'none';
            document.getElementById('utr_no').required = false;
            document.getElementById('finalBtn').innerHTML = '<i class="fas fa-check-circle"></i> Record Offline Payment';
        }
    }
</script>
</body>
</html>