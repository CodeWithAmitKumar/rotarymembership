<?php
require_once '../config.php';
require_once '../functions.php';
requireAdminLogin();
$page_title = 'Edit Organisation';

$success = '';
$error = '';

// Get organisation ID
$org_id = isset($_GET['organisation_id']) ? intval($_GET['organisation_id']) : 0;

if ($org_id <= 0) {
    header("Location: manage_organisations.php");
    exit();
}

// Get organisation details
$stmt = $conn->prepare("SELECT * FROM organisations WHERE organisation_id = ?");
$stmt->bind_param("i", $org_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: manage_organisations.php");
    exit();
}

$organisation = $result->fetch_assoc();
$stmt->close();

// Handle update request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $organisation_name = clean_input($_POST['organisation_name']);
    $contact_no = clean_input($_POST['contact_no']);
    $email = clean_input($_POST['email']);
    $whatsapp_no = clean_input($_POST['whatsapp_no']);
    $address = clean_input($_POST['address']);
    
    // Check if email already exists (excluding current organisation)
    $check_email = $conn->prepare("SELECT organisation_id FROM organisations WHERE email = ? AND organisation_id != ?");
    $check_email->bind_param("si", $email, $org_id);
    $check_email->execute();
    
    if ($check_email->get_result()->num_rows > 0) {
        $error = "Email already exists!";
    } else {
        // Update organisation
        $stmt = $conn->prepare("UPDATE organisations SET organisation_name = ?, contact_no = ?, email = ?, whatsapp_no = ?, address = ? WHERE organisation_id = ?");
        $stmt->bind_param("sssssi", $organisation_name, $contact_no, $email, $whatsapp_no, $address, $org_id);
        
        if ($stmt->execute()) {
            $success = "Organisation updated successfully!";
            // Refresh data
            $organisation['organisation_name'] = $organisation_name;
            $organisation['contact_no'] = $contact_no;
            $organisation['email'] = $email;
            $organisation['whatsapp_no'] = $whatsapp_no;
            $organisation['address'] = $address;
        } else {
            $error = "Failed to update organisation!";
        }
        $stmt->close();
    }
    $check_email->close();
}
?>

<?php include 'header.php'; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h1><i class="fas fa-edit"></i> Edit Organisation</h1>
                    <p>Update the organisation details below</p>
                </div>
                
                <form method="POST">
                    <div class="form-group">
                        <label><i class="fas fa-building"></i> Organisation Name *</label>
                        <input type="text" name="organisation_name" required value="<?php echo htmlspecialchars($organisation['organisation_name']); ?>" placeholder="Enter organisation name">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email *</label>
                        <input type="email" name="email" required value="<?php echo htmlspecialchars($organisation['email']); ?>" placeholder="Enter email address">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Contact No *</label>
                        <input type="text" name="contact_no" required value="<?php echo htmlspecialchars($organisation['contact_no']); ?>" placeholder="Enter contact number">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fab fa-whatsapp"></i> WhatsApp No</label>
                        <input type="text" name="whatsapp_no" value="<?php echo htmlspecialchars($organisation['whatsapp_no']); ?>" placeholder="Enter WhatsApp number">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-map-marker-alt"></i> Address</label>
                        <textarea name="address" placeholder="Enter address"><?php echo htmlspecialchars($organisation['address']); ?></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Organisation
                        </button>
                        <a href="manage_organisations.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </form>
            </div>

