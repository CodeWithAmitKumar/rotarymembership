<?php
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in
if (!isset($_SESSION['organisation_id'])) {
    header("Location: index.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $organisation_id = (int) $_SESSION['organisation_id'];

    // Delete query ensuring the organization only deletes its own members
    $stmt = $conn->prepare("DELETE FROM members WHERE id = ? AND organisation_id = ?");
    
    if ($stmt) {
        $stmt->bind_param("ii", $id, $organisation_id);
        
        if ($stmt->execute()) {
            // Redirect back with success message
            header("Location: members.php?msg=deleted");
            exit();
        }
        $stmt->close();
    }
}

// If something went wrong, redirect back with error message
header("Location: members.php?msg=error");
exit();
?>