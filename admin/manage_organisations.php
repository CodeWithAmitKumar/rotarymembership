<?php
require_once '../config.php';
require_once '../functions.php';
requireAdminLogin();
$page_title = 'Manage Organisations';

// Handle delete request
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM organisations WHERE organisation_id = ?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        header("Location: manage_organisations.php?deleted=success");
        exit();
    } else {
        $error = "Failed to delete organisation";
    }
    $stmt->close();
}

// Get all organisations
$organisations = mysqli_query($conn, "SELECT * FROM organisations ORDER BY organisation_id DESC");
$sl_no = 1;
?>

<?php include 'header.php'; ?>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Organisation deleted successfully!
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h1><i class="fas fa-building"></i> Manage Organisations</h1>
                <a href="add_organisation.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Organisation
                </a>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Sl No</th>
                        <th>Organisation Name</th>
                        <th>Contact No</th>
                        <th>Email</th>
                        <th>WhatsApp No</th>
                        <th>Address</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($org = mysqli_fetch_assoc($organisations)): ?>
                        <tr>
                            <td><?php echo $sl_no++; ?></td>
                            <td><strong><?php echo htmlspecialchars($org['organisation_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($org['contact_no']); ?></td>
                            <td><?php echo htmlspecialchars($org['email']); ?></td>
                            <td><?php echo htmlspecialchars($org['whatsapp_no']); ?></td>
                            <td><?php echo htmlspecialchars($org['address']); ?></td>
                            <td>
                                <div class="action-btns">
                                    <a href="edit_organisation.php?organisation_id=<?php echo $org['organisation_id']; ?>" class="btn btn-primary" style="padding: 8px 15px; font-size: 14px;">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="manage_organisations.php?delete_id=<?php echo $org['organisation_id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this organisation?');">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    
                    <?php if (mysqli_num_rows($organisations) == 0): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 30px;">
                                No organisations found. <a href="add_organisation.php">Add one now</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

