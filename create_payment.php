<?php
require_once 'config.php';
require_once 'functions.php';

if (!isset($_SESSION['organisation_id'])) {
    header("Location: index.php");
    exit();
}

$page_title = 'Create Payment';
$organisation_id = (int) $_SESSION['organisation_id'];

// 1. Fetch Members
$members_stmt = $conn->prepare("SELECT id, first_name, last_name, member_id as rotary_id FROM members WHERE organisation_id = ? ORDER BY first_name ASC");
$members_stmt->bind_param("i", $organisation_id);
$members_stmt->execute();
$members = $members_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$members_stmt->close();

// 2. Fetch Sessions
$sessions_stmt = $conn->prepare("SELECT id, session_label FROM sessions WHERE organisation_id = ? ORDER BY id DESC");
$sessions_stmt->bind_param("i", $organisation_id);
$sessions_stmt->execute();
$sessions = $sessions_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$sessions_stmt->close();

// 3. Fetch Payment Heads
$heads_stmt = $conn->prepare("SELECT id, head_name, head_amount FROM payment_heads WHERE organisation_id = ? ORDER BY head_name ASC");
$heads_stmt->bind_param("i", $organisation_id);
$heads_stmt->execute();
$payment_heads = $heads_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$heads_stmt->close();
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
        
        /* FULL Header Styles Restored */
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
        .card { background: var(--white); border-radius: 16px; box-shadow: var(--card-shadow); overflow: hidden; margin-bottom: 25px; }
        .card-header { padding: 25px 30px; border-bottom: 1px solid #e2e8f0; }
        .card-header h1 { font-size: 22px; font-weight: 700; color: var(--text-dark); margin-bottom: 5px; }
        
        /* Form Styles */
        form { padding: 30px; }
        .form-row { display: flex; gap: 20px; }
        .form-row .form-group { flex: 1; }
        .form-group { margin-bottom: 22px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; }
        .form-group label i { margin-right: 6px; color: var(--primary-color); }
        .form-group select { width: 100%; padding: 12px 15px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 14px; outline: none; background: #f7fafc; transition: all 0.3s; font-family: inherit; color: var(--text-dark);}
        .form-group select:focus { border-color: var(--primary-color); background: var(--white); box-shadow: 0 0 0 3px rgba(27,108,168,0.1); }
        
        /* Payment Head Checkboxes */
        .heads-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; margin-top: 10px; }
        .head-card { border: 2px solid #e2e8f0; border-radius: 10px; padding: 15px; display: flex; align-items: center; gap: 12px; cursor: pointer; transition: all 0.2s; background: #f8fafc; }
        .head-card:hover { border-color: #cbd5e0; }
        .head-card.selected { border-color: var(--primary-color); background: rgba(27, 108, 168, 0.05); }
        .head-card input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; accent-color: var(--primary-color); }
        .head-details { flex: 1; }
        .head-name { font-weight: 600; font-size: 14px; color: var(--text-dark); }
        .head-amount { font-weight: 700; font-size: 15px; color: var(--primary-color); margin-top: 3px; }

        /* Calculation Footer */
        .calc-footer { background: #f8fafc; padding: 20px 30px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .calc-total { font-size: 16px; color: var(--text-light); font-weight: 600; }
        .calc-total span { font-size: 28px; color: var(--text-dark); font-weight: 800; margin-left: 10px; }
        .btn-pay { background: var(--success-color); color: var(--white); border: none; padding: 14px 30px; border-radius: 10px; font-size: 16px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 10px; transition: all 0.3s; font-family: inherit;}
        .btn-pay:hover { background: #059669; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(16,185,129,0.3); }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .header { left: 0; width: 100%; padding: 0 16px; }
            .menu-toggle { display: block; }
            .search-box { display: none; }
            .dashboard-content { padding: 90px 15px 20px; }
            .form-row { flex-direction: column; gap: 0; }
            .calc-footer { flex-direction: column; gap: 20px; text-align: center; }
            .btn-pay { width: 100%; justify-content: center; }
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
                <h1><i class="fas fa-file-invoice-dollar"></i> Create Payment</h1>
                <p>Select a member, session, and applicable fees.</p>
            </div>
            
            <form action="checkout.php" method="POST" id="paymentForm">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="member_id"><i class="fas fa-user"></i> Select Member *</label>
                        <select name="member_id" id="member_id" required>
                            <option value="">-- Choose Member --</option>
                            <?php foreach($members as $m): ?>
                                <option value="<?php echo $m['id']; ?>">
                                    <?php echo htmlspecialchars($m['first_name'] . ' ' . $m['last_name']) . " (" . htmlspecialchars($m['rotary_id']) . ")"; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="session_id"><i class="fas fa-calendar-alt"></i> Select Session *</label>
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

                <div class="form-group" style="margin-top: 15px;">
                    <label><i class="fas fa-tags"></i> Select Payment Heads *</label>
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

                <div class="calc-footer">
                    <div class="calc-total">
                        Total Amount: <br>
                        <span>₹ <span id="display-total">0.00</span></span>
                        <input type="hidden" name="calculated_total" id="hidden-total" value="0">
                    </div>
                    <button type="submit" class="btn-pay" id="payBtn" disabled>
                        Proceed to Checkout <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleSidebar() { document.querySelector('.sidebar').classList.toggle('active'); }
    
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

    // Real-time Total Calculation
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
        
        // Disable proceed button if no heads are selected
        const payBtn = document.getElementById('payBtn');
        payBtn.disabled = (checkedCount === 0);
        payBtn.style.opacity = (checkedCount === 0) ? '0.5' : '1';
    }

    // Toggle border styling for selected items
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