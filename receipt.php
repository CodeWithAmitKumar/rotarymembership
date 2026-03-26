<?php
require_once 'config.php';
require_once 'functions.php';

if (!isset($_SESSION['organisation_id']) || !isset($_GET['id'])) {
    die("Invalid Access");
}

$organisation_id = (int) $_SESSION['organisation_id'];
$payment_id = (int) $_GET['id'];

// Fetch Payment, Member, and Organisation Details
$query = "SELECT p.*, 
                 m.first_name, m.last_name, m.member_id as rotary_id, m.email, m.phone_no, m.address,
                 s.session_label,
                 o.organisation_name
          FROM payments p
          JOIN members m ON p.member_id = m.id
          JOIN sessions s ON p.session_id = s.id
          JOIN organisations o ON p.organisation_id = o.organisation_id
          WHERE p.id = ? AND p.organisation_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $payment_id, $organisation_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Receipt not found or unauthorized access.");
}

$data = $result->fetch_assoc();
$stmt->close();

$heads_purchased = json_decode($data['payment_heads_json'], true);
$receipt_no = "REC-" . str_pad($data['id'], 5, '0', STR_PAD_LEFT);
$auto_print = isset($_GET['action']) && $_GET['action'] === 'download';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt - <?php echo $receipt_no; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap');
        
        body { background: #f1f5f9; font-family: 'Inter', sans-serif; color: #1e293b; padding: 40px 20px; }
        .receipt-container { max-width: 800px; margin: 0 auto; background: #fff; padding: 50px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border-radius: 4px; border-top: 8px solid #1b6ca8; }
        
        /* Header */
        .r-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; padding-bottom: 30px; border-bottom: 1px solid #e2e8f0; }
        .org-details h1 { font-size: 24px; color: #0f4c81; margin-bottom: 5px; font-weight: 800; }
        .org-details p { font-size: 13px; color: #64748b; line-height: 1.5; }
        .receipt-title { text-align: right; }
        .receipt-title h2 { font-size: 28px; color: #1e293b; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 5px; }
        .receipt-title p { font-size: 14px; font-weight: 600; color: #1b6ca8; }

        /* Info Block */
        .info-block { display: flex; justify-content: space-between; margin-bottom: 40px; }
        .info-col h3 { font-size: 12px; text-transform: uppercase; color: #94a3b8; letter-spacing: 1px; margin-bottom: 10px; }
        .info-col p { font-size: 14px; line-height: 1.6; color: #334155; }
        .info-col strong { color: #0f4c81; }

        /* Table */
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { background: #f8fafc; padding: 12px 15px; text-align: left; font-size: 13px; text-transform: uppercase; color: #64748b; border-bottom: 2px solid #e2e8f0; }
        td { padding: 15px; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        .amt-col { text-align: right; font-weight: 600; }
        
        /* Totals */
        .totals { width: 350px; margin-left: auto; }
        .total-row { display: flex; justify-content: space-between; padding: 10px 15px; font-size: 14px; }
        .total-row.grand-total { background: #f8fafc; font-size: 18px; font-weight: 800; color: #0f4c81; border-top: 2px solid #e2e8f0; border-radius: 6px; padding: 15px; margin-top: 10px; }
        
        /* Footer */
        .r-footer { margin-top: 60px; text-align: center; color: #64748b; font-size: 13px; border-top: 1px solid #e2e8f0; padding-top: 20px; }
        .signature-box { margin-top: 50px; display: flex; justify-content: flex-end; }
        .signature-line { border-top: 1px solid #1e293b; width: 200px; text-align: center; padding-top: 10px; font-size: 14px; font-weight: 600; color: #1e293b; }

        /* Print Specific Styles */
        .no-print { text-align: center; margin-bottom: 30px; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; background: #1b6ca8; color: #fff; text-decoration: none; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; font-family: inherit;}
        .btn:hover { background: #0f4c81; }

        @media print {
            body { background: #fff; padding: 0; }
            .receipt-container { box-shadow: none; border-top: none; padding: 0; max-width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()" class="btn"><i class="fas fa-print"></i> Print / Save as PDF</button>
</div>

<div class="receipt-container">
    <div class="r-header">
        <div class="org-details">
            <h1><?php echo htmlspecialchars($data['organisation_name']); ?></h1>
            <p>Rotary Membership Management System<br>Official Payment Receipt</p>
        </div>
        <div class="receipt-title">
            <h2>RECEIPT</h2>
            <p><?php echo $receipt_no; ?></p>
        </div>
    </div>

    <div class="info-block">
        <div class="info-col">
            <h3>Billed To</h3>
            <p>
                <strong><?php echo htmlspecialchars($data['first_name'] . ' ' . $data['last_name']); ?></strong><br>
                Rotary ID: <?php echo htmlspecialchars($data['rotary_id']); ?><br>
                <?php echo htmlspecialchars($data['email'] ?? ''); ?><br>
                <?php echo htmlspecialchars($data['phone_no'] ?? ''); ?>
            </p>
        </div>
        <div class="info-col" style="text-align: right;">
            <h3>Payment Details</h3>
            <p>
                Date: <strong><?php echo date('d M Y, h:i A', strtotime($data['payment_date'])); ?></strong><br>
                Session: <strong><?php echo htmlspecialchars($data['session_label']); ?></strong><br>
                Payment Mode: <strong><?php echo htmlspecialchars($data['payment_mode']); ?></strong><br>
                <?php if ($data['payment_mode'] === 'Online' && !empty($data['utr_receipt_no'])): ?>
                    Ref/UTR: <strong><?php echo htmlspecialchars($data['utr_receipt_no']); ?></strong>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 70%;">Description / Payment Head</th>
                <th class="amt-col" style="width: 25%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; foreach ($heads_purchased as $head): ?>
            <tr>
                <td><?php echo $i++; ?></td>
                <td><?php echo htmlspecialchars($head['head_name']); ?></td>
                <td class="amt-col">₹ <?php echo number_format($head['head_amount'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals">
        <div class="total-row">
            <span>Subtotal</span>
            <span>₹ <?php echo number_format($data['total_amount'], 2); ?></span>
        </div>
        <div class="total-row grand-total">
            <span>Total Paid</span>
            <span>₹ <?php echo number_format($data['total_amount'], 2); ?></span>
        </div>
    </div>

    <div class="signature-box">
        <div class="signature-line">Authorized Signatory</div>
    </div>

    <div class="r-footer">
        <p>Thank you for your payment. This is a computer-generated receipt and requires no physical signature.</p>
    </div>
</div>

<?php if ($auto_print): ?>
<script>
    // Automatically open print dialog when clicking "Download PDF" from previous page
    window.onload = function() {
        window.print();
    };
</script>
<?php endif; ?>

</body>
</html>