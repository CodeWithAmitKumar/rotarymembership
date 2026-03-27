<?php
require_once '../config.php';
require_once '../functions.php';
requireAdminLogin();
$page_title = 'Admin Dashboard';

// Get statistics
$total_organisations = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM organisations"))['count'];

// Get recent organisations
$recent_organisations = mysqli_query($conn, "SELECT * FROM organisations ORDER BY organisation_id DESC LIMIT 5");
?>

<?php include 'header.php'; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-building"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_organisations; ?></h3>
                    <p>Total Organisations</p>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h1><i class="fas fa-history"></i> Recent Organisations</h1>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Organisation Name</th>
                        <th>Contact No</th>
                        <th>Email</th>
                        <th>WhatsApp No</th>
                        <th>Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($org = mysqli_fetch_assoc($recent_organisations)): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($org['organisation_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($org['contact_no']); ?></td>
                            <td><?php echo htmlspecialchars($org['email']); ?></td>
                            <td><?php echo htmlspecialchars($org['whatsapp_no']); ?></td>
                            <td><?php echo htmlspecialchars($org['address']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                    
                    <?php if (mysqli_num_rows($recent_organisations) == 0): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 30px;">
                                No organisations found. <a href="add_organisation.php">Add one now</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

