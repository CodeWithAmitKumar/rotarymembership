<?php
require_once 'header.php';

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $organisation_id = (int) $_SESSION['organisation_id'];

    // Delete query ensuring the organization only deletes its own payment heads
    $stmt = $conn->prepare("DELETE FROM payment_heads WHERE id = ? AND organisation_id = ?");
    
    if ($stmt) {
        $stmt->bind_param("ii", $id, $organisation_id);
        
        if ($stmt->execute()) {
            // Redirect back with success message
            header("Location: payment_heads.php?msg=deleted");
            exit();
        }
        $stmt->close();
    }
}

// If something went wrong, redirect back with error message
header("Location: payment_heads.php?msg=error");
exit();
?>
