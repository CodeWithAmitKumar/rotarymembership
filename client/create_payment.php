<?php
require_once 'config.php';
require_once 'functions.php';

if (!isset($_SESSION['organisation_id'])) {
    header("Location: index.php");
    exit();
}

$page_title = 'Create Payment';
$organisation_id = (int) $_SESSION['organisation_id'];

// 1. Get the member ID from the URL. If missing, redirect back to members list.
$passed_member_id = isset($_GET['member_id']) ? (int)$_GET['member_id'] : 0;

if ($passed_member_id === 0) {
    header("Location: members.php");
    exit();
}

// 2. Fetch ONLY the specific member
$member_stmt = $conn->prepare("SELECT id, first_name, last_name, member_id as rotary_id FROM members WHERE id = ? AND organisation_id = ?");
$member_stmt->bind_param("ii", $passed_member_id, $organisation_id);
$member_stmt->execute();
$member_result = $member_stmt->get_result();

if ($member_result->num_rows === 0) {
    header("Location: members.php");
    exit();
}
$member = $member_result->fetch_assoc();
$member_stmt->close();

// 3. Fetch Sessions (Safe while loop)
$sessions_stmt = $conn->prepare("SELECT id, session_label FROM sessions WHERE organisation_id = ? ORDER BY id DESC");
$sessions_stmt->bind_param("i", $organisation_id);
$sessions_stmt->execute();
$sessions_res = $sessions_stmt->get_result();
$sessions = [];
if ($sessions_res) {
    while ($row = $sessions_res->fetch_assoc()) {
        $sessions[] = $row;
    }
}
$sessions_stmt->close();

// 4. Fetch Payment Heads (Safe while loop)
$heads_stmt = $conn->prepare("SELECT id, head_name, head_amount FROM payment_heads WHERE organisation_id = ? ORDER BY head_name ASC");
$heads_stmt->bind_param("i", $organisation_id);
$heads_stmt->execute();
$heads_res = $heads_stmt->get_result();
$payment_heads = [];
if ($heads_res) {
    while ($row = $heads_res->fetch_assoc()) {
        $payment_heads[] = $row;
    }
}
$heads_stmt->close();

// 5. Generate Dynamic Years
$current_year = date('Y');
$financial_years = [];
for ($i = $current_year - 1; $i >= 2000; $i--) {
    $financial_years[$i] = $i . '-' . ($i + 1);
}

// 6. Generate Receipt Number and Date
$payment_date = date('Y-m-d');
$padded_member_id = str_pad($member['id'], 4, '0', STR_PAD_LEFT);
$receipt_no = 'MR-' . date('Y') . '-' . $padded_member_id;

// 7. Fetch ALL active members for the "Reference By" dropdown 
$all_members_stmt = $conn->prepare("SELECT id, first_name, last_name, member_id as rotary_id FROM members WHERE organisation_id = ? AND is_active = 1 ORDER BY first_name ASC");
$all_members_stmt->bind_param("i", $organisation_id);
$all_members_stmt->execute();
$all_members_res = $all_members_stmt->get_result();
$all_members = [];
if ($all_members_res) {
    while ($row = $all_members_res->fetch_assoc()) {
        $all_members[] = $row;
    }
}
$all_members_stmt->close();

// 8. Fetch Organization Details (for QR Code)
$org_stmt = $conn->prepare("SELECT qr_code_path FROM organisations WHERE organisation_id = ?");
$org_stmt->bind_param("i", $organisation_id);
$org_stmt->execute();
$org_res = $org_stmt->get_result();
$org_data = $org_res->fetch_assoc();
$org_stmt->close();

// Set up the dynamic QR code URL (with a fallback if empty)
$qr_image_url = !empty($org_data['qr_code_path']) ? app_url($org_data['qr_code_path']) : 'https://via.placeholder.com/150?text=No+QR+Found';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Payment - Rotary Membership</title>
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
            --border-color: #e2e8f0;
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
        .card { background: var(--white); border-radius: 16px; box-shadow: var(--card-shadow); overflow: hidden; margin-bottom: 25px; border: 1px solid var(--border-color); }
        .card-header { padding: 25px 30px; border-bottom: 1px solid var(--border-color); background: #f8fafc; display: flex; justify-content: space-between; align-items: center; }
        .card-header h1 { font-size: 22px; font-weight: 700; color: var(--text-dark); margin-bottom: 5px; }
        .card-header p { color: var(--text-light); font-size: 14px; }
        .back-btn { display: inline-flex; align-items: center; gap: 8px; color: var(--text-light); text-decoration: none; font-size: 14px; font-weight: 600; transition: color 0.2s; }
        .back-btn:hover { color: var(--primary-color); }
        
        /* Form Sections */
        form { padding: 0; }
        .form-section { padding: 30px; border-bottom: 1px dashed var(--border-color); }
        .form-section:last-child { border-bottom: none; }
        
        .section-title { font-size: 16px; font-weight: 700; color: var(--text-dark); margin-bottom: 20px; display: flex; align-items: center; gap: 12px; }
        .step-badge { width: 28px; height: 28px; background: var(--primary-color); color: var(--white); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: bold; box-shadow: 0 4px 10px rgba(27, 108, 168, 0.2); }

        /* Form Inputs */
        .form-row { display: flex; gap: 20px; }
        .form-group { flex: 1; margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; color: var(--text-dark); }
        .form-group select, .form-group input[type="date"], .form-group input[type="text"] { width: 100%; padding: 12px 15px; border: 2px solid var(--border-color); border-radius: 10px; font-size: 14px; outline: none; background: #f7fafc; transition: all 0.3s; font-family: inherit; color: var(--text-dark); cursor: pointer; }
        .form-group select:focus, .form-group input[type="date"]:focus, .form-group input[type="text"]:focus { border-color: var(--primary-color); background: var(--white); box-shadow: 0 0 0 3px rgba(27,108,168,0.1); }
        
        /* Read Only Box */
        .readonly-box { padding: 12px 15px; background: #f1f5f9; border: 2px solid #cbd5e0; border-radius: 10px; font-weight: 600; color: #475569; display: flex; align-items: center; gap: 10px; }
        .readonly-box i { color: var(--success-color); font-size: 18px; }

        /* Payment Head Checkboxes */
        .heads-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; }
        .head-card { border: 2px solid var(--border-color); border-radius: 10px; padding: 15px; display: flex; align-items: center; gap: 12px; cursor: pointer; transition: all 0.2s; background: var(--white); box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .head-card:hover { border-color: #cbd5e0; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .head-card.selected { border-color: var(--primary-color); background: rgba(27, 108, 168, 0.04); }
        .head-card input[type="checkbox"] { width: 20px; height: 20px; cursor: pointer; accent-color: var(--primary-color); }
        .head-details { flex: 1; }
        .head-name { font-weight: 600; font-size: 14px; color: var(--text-dark); }
        .head-amount { font-weight: 700; font-size: 16px; color: var(--primary-color); margin-top: 4px; }

        /* Payment Method Radio Cards */
        .method-grid { display: flex; gap: 20px; margin-bottom: 25px; }
        .method-card { flex: 1; border: 2px solid var(--border-color); border-radius: 10px; padding: 20px; display: flex; align-items: center; gap: 15px; cursor: pointer; transition: all 0.2s; background: var(--white); }
        .method-card:hover { border-color: #cbd5e0; }
        .method-card.selected { border-color: var(--primary-color); background: rgba(27, 108, 168, 0.04); }
        .method-card input[type="radio"] { width: 20px; height: 20px; cursor: pointer; accent-color: var(--primary-color); }
        .method-icon { width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .online-icon { background: rgba(16, 185, 129, 0.1); color: var(--success-color); }
        .offline-icon { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .method-name { font-weight: 700; font-size: 16px; color: var(--text-dark); }
        .method-desc { font-size: 13px; color: var(--text-light); margin-top: 4px; }

        /* QR Code Container */
        .qr-container { display: flex; gap: 30px; align-items: center; background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid var(--border-color); }
        .qr-image-box { width: 150px; height: 150px; background: var(--white); border: 2px dashed #cbd5e0; border-radius: 10px; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .qr-image-box img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .qr-instructions { flex: 1; }
        
        /* Calculation Footer */
        .calc-footer { background: #f8fafc; padding: 25px 30px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); }
        .calc-total { font-size: 15px; color: var(--text-light); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;}
        .calc-total span { display: block; font-size: 32px; color: var(--text-dark); font-weight: 800; margin-top: 5px; text-transform: none; letter-spacing: 0; }
        .btn-pay { background: var(--success-color); color: var(--white); border: none; padding: 16px 36px; border-radius: 12px; font-size: 16px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 10px; transition: all 0.3s; font-family: inherit; box-shadow: 0 4px 15px rgba(16,185,129,0.2); }
        .btn-pay:hover { background: #059669; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(16,185,129,0.3); }
        .btn-pay:disabled { background: #94a3b8; cursor: not-allowed; transform: none; box-shadow: none; opacity: 0.7; }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .header { left: 0; width: 100%; padding: 0 16px; }
            .menu-toggle { display: block; }
            .dashboard-content { padding: 90px 15px 20px; }
            .form-row { flex-direction: column; gap: 0; }
            .method-grid { flex-direction: column; gap: 15px; }
            .qr-container { flex-direction: column; text-align: center; }
            .calc-footer { flex-direction: column; gap: 20px; text-align: center; }
            .btn-pay { width: 100%; justify-content: center; }
            .card-header { flex-direction: column; align-items: flex-start; gap: 15px; }
        }
        
        /* Animation for Method Toggles */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
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
        <div class="card">
            <div class="card-header">
                <div>
                    <h1><i class="fas fa-file-invoice-dollar" style="color: var(--primary-color);"></i> New Payment Entry</h1>
                    <p>Process a new payment record for the selected member.</p>
                </div>
                <a href="members.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Members</a>
            </div>
            
            <form action="checkout.php" method="POST" id="paymentForm">

                <div class="form-section">
                    <h3 class="section-title"><span class="step-badge">1</span> Receipt Details</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Receipt Number</label>
                            <div class="readonly-box" style="border-color: var(--border-color); background: #f7fafc; font-family: monospace; font-size: 16px;">
                                <i class="fas fa-receipt" style="color: var(--text-light);"></i>
                                <div><?php echo htmlspecialchars($receipt_no); ?></div>
                            </div>
                            <input type="hidden" name="receipt_no" value="<?php echo htmlspecialchars($receipt_no); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="payment_date">Payment Date *</label>
                            <input type="date" name="payment_date" id="payment_date" value="<?php echo $payment_date; ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3 class="section-title"><span class="step-badge">2</span> Member Information</h3>
                    <div class="form-group" style="max-width: 500px;">
                        <label>Selected Member</label>
                        <div class="readonly-box">
                            <i class="fas fa-check-circle"></i>
                            <div>
                                <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?> 
                                <span style="color: var(--text-light); font-weight: 500;">(ID: <?php echo htmlspecialchars($member['rotary_id']); ?>)</span>
                            </div>
                        </div>
                        <input type="hidden" name="member_id" value="<?php echo $member['id']; ?>">
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title"><span class="step-badge">3</span> Payment Period</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="payment_year">Select Year *</label>
                            <select name="payment_year" id="payment_year" required>
                                <option value="">-- Choose Year --</option>
                                <?php foreach($financial_years as $value => $label): ?>
                                    <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="session_id">Select Session *</label>
                            <select name="session_id" id="session_id" required>
                                <option value="">-- Choose Session --</option>
                                <?php foreach($sessions as $s): ?>
                                    <option value="<?php echo $s['id']; ?>">
                                        <?php echo htmlspecialchars($s['session_label']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title"><span class="step-badge">4</span> Applicable Fees *</h3>
                    <p style="font-size: 13px; color: var(--text-light); margin-bottom: 15px;">Select all the heads this payment covers.</p>
                    
                    <div class="heads-grid">
                        <?php foreach($payment_heads as $head): ?>
                            <label class="head-card">
                                <input type="checkbox" name="payment_heads[]" value="<?php echo $head['id']; ?>" class="head-checkbox" data-amount="<?php echo $head['head_amount']; ?>" onchange="calculateTotal(); toggleCardStyle(this);">
                                <div class="head-details">
                                    <div class="head-name"><?php echo htmlspecialchars($head['head_name']); ?></div>
                                    <div class="head-amount">₹ <?php echo number_format($head['head_amount'], 2); ?></div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title"><span class="step-badge">5</span> Payment Method *</h3>
                    
                    <div class="method-grid">
                        <label class="method-card" id="card-online">
                            <input type="radio" name="payment_method" value="online" onchange="togglePaymentMethod();">
                            <div class="method-icon online-icon"><i class="fas fa-mobile-alt"></i></div>
                            <div>
                                <div class="method-name">Online / UPI</div>
                                <div class="method-desc">Scan QR code and enter UTR</div>
                            </div>
                        </label>
                        
                        <label class="method-card" id="card-offline">
                            <input type="radio" name="payment_method" value="offline" onchange="togglePaymentMethod();">
                            <div class="method-icon offline-icon"><i class="fas fa-money-bill-wave"></i></div>
                            <div>
                                <div class="method-name">Offline / Cash</div>
                                <div class="method-desc">Cash collected by a member</div>
                            </div>
                        </label>
                    </div>

                    <div id="online_fields" style="display: none; animation: fadeIn 0.3s;">
                        <div class="qr-container">
                            <div class="qr-image-box">
                                <img src="<?php echo $qr_image_url; ?>" alt="Scan to Pay" onerror="this.src='https://via.placeholder.com/150?text=Your+QR+Here'">
                            </div>
                            <div class="qr-instructions">
                                <h4 style="margin-bottom: 10px; color: var(--text-dark);">Scan & Pay</h4>
                                <p style="font-size: 14px; color: var(--text-light); margin-bottom: 15px;">Scan the QR code using any UPI app. After payment, enter the 12-digit UTR/Transaction ID below.</p>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label for="utr_no">UTR / Transaction ID *</label>
                                    <input type="text" name="utr_receipt_no" id="utr_no" placeholder="e.g. 301234567890">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="offline_fields" style="display: none; animation: fadeIn 0.3s;">
                        <div class="form-group" style="max-width: 500px;">
                            <label for="reference_by">Cash Collected By (Reference) *</label>
                            <select name="reference_by" id="reference_by">
                                <option value="">-- Select Member --</option>
                                <?php foreach($all_members as $am): ?>
                                    <option value="<?php echo $am['id']; ?>">
                                        <?php echo htmlspecialchars($am['first_name'] . ' ' . $am['last_name'] . ' (' . $am['rotary_id'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                </div>

                <div class="calc-footer">
                    <div class="calc-total">
                        Total Payable Amount
                        <span>₹ <span id="display-total" style="display: inline;">0.00</span></span>
                        <input type="hidden" name="calculated_total" id="hidden-total" value="0">
                    </div>
                    <div style="text-align: right;">
                        <button type="submit" class="btn-pay" id="payBtn" disabled>
                            Proceed to Checkout <i class="fas fa-lock" style="font-size: 14px; margin-left: 4px;"></i>
                        </button>
                        <span id="method-warning" style="display:block; color: #ef4444; font-size: 12px; margin-top: 8px; font-weight: 500;">Select fees & method to proceed</span>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Sidebar Toggle
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

    // Calculate Total & Validate Form
    function calculateTotal() {
        let total = 0;
        let checkedCount = 0;
        const checkboxes = document.querySelectorAll('.head-checkbox:checked');
        
        checkboxes.forEach(cb => {
            total += parseFloat(cb.dataset.amount);
            checkedCount++;
        });
        
        document.getElementById('display-total').innerText = total.toFixed(2);
        document.getElementById('hidden-total').value = total.toFixed(2);
        
        checkFormValidity(checkedCount);
    }

    // Toggle Payment Method Visibility
    function togglePaymentMethod() {
        const methodRadios = document.querySelectorAll('input[name="payment_method"]');
        let selectedMethod = null;
        
        document.getElementById('card-online').classList.remove('selected');
        document.getElementById('card-offline').classList.remove('selected');

        methodRadios.forEach(radio => {
            if(radio.checked) {
                selectedMethod = radio.value;
                document.getElementById('card-' + selectedMethod).classList.add('selected');
            }
        });

        const onlineFields = document.getElementById('online_fields');
        const offlineFields = document.getElementById('offline_fields');
        const utrInput = document.getElementById('utr_no');
        const refInput = document.getElementById('reference_by');

        if (selectedMethod === 'online') {
            onlineFields.style.display = 'block';
            offlineFields.style.display = 'none';
            utrInput.setAttribute('required', 'required');
            refInput.removeAttribute('required');
            refInput.value = ''; 
        } else if (selectedMethod === 'offline') {
            onlineFields.style.display = 'none';
            offlineFields.style.display = 'block';
            utrInput.removeAttribute('required');
            utrInput.value = ''; 
            refInput.setAttribute('required', 'required');
        }

        const checkedCount = document.querySelectorAll('.head-checkbox:checked').length;
        checkFormValidity(checkedCount);
    }

    // Check if form is valid to proceed
    function checkFormValidity(checkedCount) {
        const payBtn = document.getElementById('payBtn');
        const warningTxt = document.getElementById('method-warning');
        const methodSelected = document.querySelector('input[name="payment_method"]:checked') !== null;

        if (checkedCount > 0 && methodSelected) {
            payBtn.disabled = false;
            warningTxt.style.display = 'none';
        } else {
            payBtn.disabled = true;
            warningTxt.style.display = 'block';
            if (checkedCount === 0) {
                warningTxt.innerText = "Please select at least one fee.";
            } else if (!methodSelected) {
                warningTxt.innerText = "Please select a payment method.";
            }
        }
    }

    // Toggle styling for selected fee cards
    function toggleCardStyle(checkbox) {
        if(checkbox.checked) {
            checkbox.closest('.head-card').classList.add('selected');
        } else {
            checkbox.closest('.head-card').classList.remove('selected');
        }
    }
</script>
</body>
</html>
