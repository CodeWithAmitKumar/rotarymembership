<?php
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in
if (!isset($_SESSION['organisation_id'])) {
    header("Location: index.php");
    exit();
}

$page_title = 'Payment Checkout';
$organisation_id = (int) $_SESSION['organisation_id'];

$already_paid = false;
$existing_receipt_no = '';
$payment_success = false;
$save_error = '';

$member_name = '';
$rotary_id = '';
$session_label = '';
$receipt_items = [];
$reference_name = '';

function fetchReceiptItems(mysqli $conn, array $selected_heads, int $organisation_id): array {
    if (empty($selected_heads)) {
        return [];
    }

    $selected_heads = array_values(array_filter(array_map('intval', $selected_heads), function ($id) {
        return $id > 0;
    }));

    if (empty($selected_heads)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($selected_heads), '?'));
    $types = 'i' . str_repeat('i', count($selected_heads));

    $heads_stmt = $conn->prepare("SELECT id, head_name, head_amount FROM payment_heads WHERE organisation_id = ? AND id IN ($placeholders)");
    $params = array_merge([$organisation_id], $selected_heads);
    $heads_stmt->bind_param($types, ...$params);
    $heads_stmt->execute();
    $heads_res = $heads_stmt->get_result();

    $items = [];
    while ($row = $heads_res->fetch_assoc()) {
        $items[] = [
            'id' => (int) $row['id'],
            'head_name' => $row['head_name'],
            'head_amount' => (float) $row['head_amount']
        ];
    }

    $heads_stmt->close();
    return $items;
}

function fetchStoredReceiptItems(mysqli $conn, int $payment_id, int $organisation_id): array {
    $items_stmt = $conn->prepare("SELECT head_name, head_amount FROM payment_details WHERE payment_id = ? AND organisation_id = ? ORDER BY id ASC");
    $items_stmt->bind_param("ii", $payment_id, $organisation_id);
    $items_stmt->execute();
    $items_res = $items_stmt->get_result();

    $items = [];
    while ($row = $items_res->fetch_assoc()) {
        $items[] = [
            'head_name' => $row['head_name'],
            'head_amount' => (float) $row['head_amount']
        ];
    }

    $items_stmt->close();
    return $items;
}

function fetchReferenceName(mysqli $conn, string $reference_by, int $organisation_id): string {
    $reference_by = trim($reference_by);
    if ($reference_by === '') {
        return '';
    }

    if (ctype_digit($reference_by)) {
        $ref_member_id = (int) $reference_by;
        $ref_stmt = $conn->prepare("SELECT first_name, last_name, member_id FROM members WHERE id = ? AND organisation_id = ?");
        $ref_stmt->bind_param("ii", $ref_member_id, $organisation_id);
        $ref_stmt->execute();
        $ref_res = $ref_stmt->get_result();
        $reference_name = '';

        if ($ref_row = $ref_res->fetch_assoc()) {
            $reference_name = trim($ref_row['first_name'] . ' ' . $ref_row['last_name']);
            if (!empty($ref_row['member_id'])) {
                $reference_name .= ' (' . $ref_row['member_id'] . ')';
            }
        }

        $ref_stmt->close();
        return $reference_name;
    }

    return $reference_by;
}

// ==========================================
// MODE 1: NEW PAYMENT SUBMISSION (POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['member_id'])) {
    
    $member_id = (int)$_POST['member_id'];
    $receipt_no = $_POST['receipt_no'];
    $payment_date = $_POST['payment_date'];
    $payment_year = $_POST['payment_year'];
    $session_id = (int)$_POST['session_id'];
    $calculated_total = (float)$_POST['calculated_total'];
    $payment_method = strtolower(trim($_POST['payment_method'] ?? ''));
    // Handle conditional fields based on payment method
    $submitted_utr = trim($_POST['utr_receipt_no'] ?? ($_POST['utr_no'] ?? ''));
    $utr_no = ($payment_method === 'online' && $submitted_utr !== '') ? $submitted_utr : '';
    $reference_by = ($payment_method === 'offline' && !empty($_POST['reference_by'])) ? (string) ((int) $_POST['reference_by']) : '';
    $payment_mode = ucfirst($payment_method);
    $reference_name = ($payment_method === 'offline') ? fetchReferenceName($conn, $reference_by, $organisation_id) : '';

    $selected_heads = $_POST['payment_heads'] ?? [];
    $receipt_items = fetchReceiptItems($conn, $selected_heads, $organisation_id);

    // Duplicate Check
    $check_stmt = $conn->prepare("SELECT id, receipt_no FROM payments WHERE member_id = ? AND payment_year = ? AND session_id = ? AND organisation_id = ?");
    $check_stmt->bind_param("isii", $member_id, $payment_year, $session_id, $organisation_id);
    $check_stmt->execute();
    $check_res = $check_stmt->get_result();

    if ($check_res->num_rows > 0) {
        $already_paid = true;
        $existing_data = $check_res->fetch_assoc();
        $existing_receipt_no = $existing_data['receipt_no'];
    } 
    $check_stmt->close();

    // Save to Database
    if (!$already_paid) {
        $conn->begin_transaction();

        try {
            $insert_stmt = $conn->prepare("INSERT INTO payments (organisation_id, member_id, receipt_no, payment_date, payment_year, session_id, total_amount, payment_mode, payment_method, utr_receipt_no, reference_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("iissiidssss", $organisation_id, $member_id, $receipt_no, $payment_date, $payment_year, $session_id, $calculated_total, $payment_mode, $payment_method, $utr_no, $reference_by);

            if (!$insert_stmt->execute()) {
                throw new Exception('Unable to save payment: ' . $insert_stmt->error);
            }

            $payment_id = (int) $conn->insert_id;
            $insert_stmt->close();

            if (!empty($receipt_items)) {
                $detail_stmt = $conn->prepare("INSERT INTO payment_details (organisation_id, payment_id, payment_head_id, head_name, head_amount) VALUES (?, ?, ?, ?, ?)");

                foreach ($receipt_items as $item) {
                    $payment_head_id = (int) ($item['id'] ?? 0);
                    $head_name = $item['head_name'];
                    $head_amount = (float) $item['head_amount'];

                    $detail_stmt->bind_param("iiisd", $organisation_id, $payment_id, $payment_head_id, $head_name, $head_amount);

                    if (!$detail_stmt->execute()) {
                        throw new Exception('Unable to save payment details: ' . $detail_stmt->error);
                    }
                }

                $detail_stmt->close();
            }

            $conn->commit();
            $payment_success = true;
        } catch (Throwable $e) {
            $conn->rollback();
            $payment_success = false;
            $save_error = $e->getMessage();
        }
    }

    // Fetch Member Details
    $member_stmt = $conn->prepare("SELECT first_name, last_name, member_id as rotary_id FROM members WHERE id = ?");
    $member_stmt->bind_param("i", $member_id);
    $member_stmt->execute();
    $member = $member_stmt->get_result()->fetch_assoc();
    $member_name = $member['first_name'] . ' ' . $member['last_name'];
    $rotary_id = $member['rotary_id'];
    $member_stmt->close();

    // Fetch Session Label
    $session_label = "N/A";
    $sess_stmt = $conn->prepare("SELECT session_label FROM sessions WHERE id = ?");
    $sess_stmt->bind_param("i", $session_id);
    $sess_stmt->execute();
    $sess_res = $sess_stmt->get_result();
    if ($sess_res->num_rows > 0) {
        $session_label = $sess_res->fetch_assoc()['session_label'];
    }
    $sess_stmt->close();

}
// ==========================================
// MODE 2: VIEW EXISTING RECEIPT (GET REQUEST)
// ==========================================
elseif (isset($_GET['id']) && is_numeric($_GET['id'])) {
    
    $payment_id = (int)$_GET['id'];
    
    // Fetch everything based on the payment ID
    $view_stmt = $conn->prepare("SELECT p.*, m.id as member_db_id, m.first_name, m.last_name, m.member_id as rotary_id, s.session_label 
                                 FROM payments p 
                                 JOIN members m ON p.member_id = m.id 
                                 JOIN sessions s ON p.session_id = s.id 
                                 WHERE p.id = ? AND p.organisation_id = ?");
    $view_stmt->bind_param("ii", $payment_id, $organisation_id);
    $view_stmt->execute();
    $view_res = $view_stmt->get_result();
    
    if ($view_res->num_rows === 0) {
        header("Location: all_payments.php?msg=error"); // Redirect if payment not found
        exit();
    }
    
    $payment_data = $view_res->fetch_assoc();
    $view_stmt->close();

    // Map database values to HTML variables
    $member_id = $payment_data['member_db_id'];
    $member_name = $payment_data['first_name'] . ' ' . $payment_data['last_name'];
    $rotary_id = $payment_data['rotary_id'];
    $session_label = $payment_data['session_label'];
    
    $receipt_no = $payment_data['receipt_no'];
    $payment_date = $payment_data['payment_date'];
    $payment_year = $payment_data['payment_year'];
    $calculated_total = $payment_data['total_amount'];
    $payment_method = $payment_data['payment_method'];
    $utr_no = $payment_data['utr_receipt_no'];
    $reference_name = (strtolower($payment_method) === 'offline') ? fetchReferenceName($conn, (string) ($payment_data['reference_by'] ?? ''), $organisation_id) : '';
    
    $receipt_items = fetchStoredReceiptItems($conn, $payment_id, $organisation_id);

    if (empty($receipt_items)) {
        $receipt_items = [
            ['head_name' => 'Membership Dues / Fees', 'head_amount' => $calculated_total]
        ];
    }
}
// ==========================================
// MODE 3: INVALID ACCESS
// ==========================================
else {
    header("Location: members.php");
    exit();
}

// Fetch Organisation Details for the Receipt Header
$org_stmt = $conn->prepare("SELECT organisation_name, address, contact_no, email FROM organisations WHERE organisation_id = ?");
$org_stmt->bind_param("i", $organisation_id);
$org_stmt->execute();
$org_data = $org_stmt->get_result()->fetch_assoc();
$org_stmt->close();

$org_name = $org_data['organisation_name'] ?? 'Organization Name';
$org_address = $org_data['address'] ?? '';
$org_contact = $org_data['contact_no'] ?? '';
$org_email = $org_data['email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout & Receipt - Rotary Membership</title>
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
            --danger-color: #ef4444;
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
        .dashboard-content { padding: 100px 30px 40px; max-width: 850px; margin: 0 auto; }

        /* Status Banners */
        .status-banner { border-radius: 12px; padding: 20px; display: flex; align-items: center; gap: 15px; margin-bottom: 25px; animation: slideDown 0.4s ease; }
        .status-banner.success { background: rgba(16, 185, 129, 0.1); border: 2px solid var(--success-color); }
        .status-banner.error { background: rgba(239, 68, 68, 0.1); border: 2px solid var(--danger-color); }
        .status-icon { width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; color: white; }
        .success .status-icon { background: var(--success-color); }
        .error .status-icon { background: var(--danger-color); }
        .status-text h2 { font-size: 18px; font-weight: 700; margin-bottom: 4px; }
        .success .status-text h2 { color: #065f46; }
        .error .status-text h2 { color: #991b1b; }
        .status-text p { font-size: 14px; margin: 0; color: var(--text-dark); }

        /* Realistic Receipt Card */
        .receipt-card { background: #fff; border-radius: 16px; box-shadow: var(--card-shadow); padding: 40px; border: 1px solid var(--border-color); position: relative; }
        
        /* Organization Header inside Receipt */
        .receipt-org-header { text-align: center; border-bottom: 2px solid var(--primary-color); padding-bottom: 20px; margin-bottom: 25px; }
        .org-name { font-size: 28px; color: var(--primary-color); font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
        .org-details { color: var(--text-light); font-size: 13px; line-height: 1.6; }
        .receipt-title { display: inline-block; background: var(--primary-color); color: white; padding: 6px 20px; border-radius: 20px; font-size: 14px; font-weight: 700; letter-spacing: 1px; margin-top: 15px; text-transform: uppercase; }
        
        .receipt-meta { display: flex; justify-content: space-between; margin-bottom: 30px; background: #f8fafc; padding: 15px 20px; border-radius: 10px; border: 1px solid #e2e8f0; }
        .meta-group label { display: block; font-size: 12px; color: var(--text-light); text-transform: uppercase; font-weight: 600; margin-bottom: 4px; }
        .meta-group span { font-size: 16px; font-weight: 700; color: var(--text-dark); }
        .meta-group.right { text-align: right; }

        .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .detail-box { padding: 15px; border: 1px solid var(--border-color); border-radius: 10px; background: #fff; }
        .detail-box h4 { font-size: 12px; color: var(--text-light); text-transform: uppercase; margin-bottom: 8px; }
        .detail-box p { font-size: 16px; color: var(--text-dark); font-weight: 700; margin: 0; }
        .detail-box span { font-size: 14px; color: var(--text-light); font-weight: 500; display: block; margin-top: 4px;}

        /* Items Table */
        .receipt-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .receipt-table th { background: #f1f5f9; padding: 12px 15px; text-align: left; font-size: 13px; color: var(--text-light); text-transform: uppercase; border-bottom: 2px solid var(--border-color); }
        .receipt-table th.amount { text-align: right; }
        .receipt-table td { padding: 15px; border-bottom: 1px solid var(--border-color); font-size: 15px; color: var(--text-dark); font-weight: 600; }
        .receipt-table td.amount { text-align: right; color: var(--primary-color); }
        
        /* Totals Area */
        .receipt-total { display: flex; justify-content: flex-end; align-items: center; padding-top: 10px; }
        .total-box { background: rgba(27, 108, 168, 0.05); border: 2px solid var(--primary-color); padding: 15px 30px; border-radius: 12px; text-align: right; }
        .total-box span { display: block; font-size: 13px; color: var(--primary-color); font-weight: 700; text-transform: uppercase; margin-bottom: 5px; }
        .total-box strong { font-size: 28px; color: var(--text-dark); letter-spacing: -0.5px; }

        /* Signatures */
        .signatures { display: flex; justify-content: space-between; margin-top: 60px; padding-top: 20px; }
        .sig-line { width: 200px; text-align: center; }
        .sig-line div { border-bottom: 1px dashed var(--text-light); height: 40px; margin-bottom: 10px; }
        .sig-line p { font-size: 13px; color: var(--text-light); font-weight: 600; }

        /* Buttons */
        .action-buttons { display: flex; gap: 15px; margin-top: 30px; justify-content: center; }
        .btn { padding: 14px 28px; border-radius: 10px; font-size: 15px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; text-decoration: none; }
        .btn-print { background: var(--primary-color); color: white; border: none; box-shadow: 0 4px 15px rgba(27, 108, 168, 0.2); }
        .btn-print:hover { background: #0f4c81; transform: translateY(-2px); }
        .btn-secondary { background: white; color: var(--text-dark); border: 2px solid #cbd5e0; }
        .btn-secondary:hover { background: #f8fafc; border-color: var(--primary-color); color: var(--primary-color); }

        @keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }

        /* PRINT STYLES - Hides Sidebar and Buttons when Printing */
        @media print {
            body { background: white; }
            .sidebar, .header, .action-buttons, .status-banner { display: none !important; }
            .main-content { margin-left: 0; }
            .dashboard-content { padding: 0; margin: 0; max-width: 100%; }
            .receipt-card { box-shadow: none; border: none; padding: 0; }
            .total-box { background: white !important; border-color: #000; }
            * { color: #000 !important; }
        }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .header { left: 0; width: 100%; padding: 0 16px; }
            .main-content { margin-left: 0; }
            .menu-toggle { display: block; }
            .details-grid { grid-template-columns: 1fr; }
            .receipt-meta { flex-direction: column; gap: 15px; }
            .meta-group.right { text-align: left; }
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
        
        <?php if($already_paid): ?>
            <div class="status-banner error">
                <div class="status-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="status-text">
                    <h2>Payment Already Exists!</h2>
                    <p><strong><?php echo htmlspecialchars($member_name); ?></strong> has already paid for <strong>Year <?php echo htmlspecialchars($payment_year); ?></strong> (<?php echo htmlspecialchars($session_label); ?>). The existing receipt number is <strong><?php echo htmlspecialchars($existing_receipt_no); ?></strong>.</p>
                </div>
            </div>
            
            <div class="action-buttons" style="justify-content: flex-start;">
                <a href="create_payment.php?member_id=<?php echo $member_id; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Go Back
                </a>
            </div>

        <?php else: ?>
            <?php if($payment_success): ?>
            <div class="status-banner success">
                <div class="status-icon"><i class="fas fa-check"></i></div>
                <div class="status-text">
                    <h2>Payment Recorded Successfully!</h2>
                    <p>The payment has been saved to the database. The official receipt is generated below.</p>
                </div>
            </div>
            <?php elseif(!empty($save_error)): ?>
            <div class="status-banner error">
                <div class="status-icon"><i class="fas fa-times"></i></div>
                <div class="status-text">
                    <h2>Payment Could Not Be Saved</h2>
                    <p><?php echo htmlspecialchars($save_error); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <div class="receipt-card" id="printableArea">
                <div class="receipt-org-header">
                    <h1 class="org-name"><?php echo htmlspecialchars($org_name); ?></h1>
                    <p class="org-details">
                        <?php if($org_address) echo htmlspecialchars($org_address) . '<br>'; ?>
                        <?php if($org_contact) echo 'Ph: ' . htmlspecialchars($org_contact); ?>
                        <?php if($org_contact && $org_email) echo ' | '; ?>
                        <?php if($org_email) echo 'Email: ' . htmlspecialchars($org_email); ?>
                    </p>
                    <div class="receipt-title">Official Receipt</div>
                </div>

                <div class="receipt-meta">
                    <div class="meta-group">
                        <label>Receipt Number</label>
                        <span><?php echo htmlspecialchars($receipt_no); ?></span>
                    </div>
                    <div class="meta-group right">
                        <label>Payment Date</label>
                        <span><?php echo date('d M Y', strtotime($payment_date)); ?></span>
                    </div>
                </div>

                <div class="details-grid">
                    <div class="detail-box">
                        <h4>Received From</h4>
                        <p><?php echo htmlspecialchars($member_name); ?></p>
                        <span>Member ID: <?php echo htmlspecialchars($rotary_id); ?></span>
                    </div>
                    <div class="detail-box">
                        <h4>Payment Details</h4>
                        <p style="text-transform: capitalize;">
                            Method: <?php echo htmlspecialchars($payment_method); ?>
                            <?php if($payment_method == 'online'): ?>
                                <i class="fas fa-mobile-alt" style="color: var(--primary-color); margin-left: 5px;"></i>
                            <?php else: ?>
                                <i class="fas fa-money-bill-wave" style="color: #f59e0b; margin-left: 5px;"></i>
                            <?php endif; ?>
                        </p>
                        <?php if($payment_method == 'online' && !empty($utr_no)): ?>
    <span>UTR/Txn ID: <?php echo htmlspecialchars($utr_no); ?></span>
<?php elseif($payment_method == 'offline' && !empty($reference_name)): ?>
    <span>Collected By: <?php echo htmlspecialchars($reference_name); ?></span>
<?php endif; ?>
                    </div>
                </div>

                <p style="font-size: 14px; color: var(--text-dark); margin-bottom: 15px; font-weight: 500;">
                    Acknowledgment for Financial Year <strong><?php echo htmlspecialchars($payment_year); ?></strong> (<?php echo htmlspecialchars($session_label); ?>)
                </p>

                <table class="receipt-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Particulars / Fee Heads</th>
                            <th class="amount">Amount (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $count = 1;
                        if(!empty($receipt_items)) {
                            foreach($receipt_items as $item) {
                                echo "<tr>";
                                echo "<td>{$count}</td>";
                                echo "<td>" . htmlspecialchars($item['head_name']) . "</td>";
                                echo "<td class='amount'>" . number_format($item['head_amount'], 2) . "</td>";
                                echo "</tr>";
                                $count++;
                            }
                        } else {
                            echo "<tr><td colspan='3'>No items recorded.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>

                <div class="receipt-total">
                    <div class="total-box">
                        <span>Total Amount Paid</span>
                        <strong>₹ <?php echo number_format($calculated_total, 2); ?></strong>
                    </div>
                </div>

                <div class="signatures">
                    <div class="sig-line">
                        <div></div>
                        <p>Member Signature</p>
                    </div>
                    <div class="sig-line">
                        <div></div>
                        <p>Authorized Signatory</p>
                    </div>
                </div>
            </div>

            <div class="action-buttons">
                <button onclick="window.print()" class="btn btn-print">
                    <i class="fas fa-print"></i> Print Receipt
                </button>
                <a href="all_payments.php" class="btn btn-secondary">
                    <i class="fas fa-list"></i> Back to Payments
                </a>
            </div>
        <?php endif; ?>

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

    // Auto-print if 'action=download' is in the URL
    <?php if (isset($_GET['action']) && $_GET['action'] == 'download'): ?>
    window.onload = function() {
        setTimeout(function() {
            window.print();
        }, 500);
    };
    <?php endif; ?>
</script>
</body>
</html>
