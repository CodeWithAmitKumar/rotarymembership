<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['organisation_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

if (isset($_POST['id']) && isset($_POST['status'])) {
    $id = (int) $_POST['id'];
    $status = (int) $_POST['status'];
    $organisation_id = (int) $_SESSION['organisation_id'];

    // Update the database
    $stmt = $conn->prepare("UPDATE members SET is_active = ? WHERE id = ? AND organisation_id = ?");
    
    if ($stmt) {
        $stmt->bind_param("iii", $status, $id, $organisation_id);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database update failed']);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database prepare statement failed']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
}
?>