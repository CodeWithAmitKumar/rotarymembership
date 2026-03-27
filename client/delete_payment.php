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

    $conn->begin_transaction();

    try {
        $details_stmt = $conn->prepare("DELETE FROM payment_details WHERE payment_id = ? AND organisation_id = ?");
        $details_stmt->bind_param("ii", $id, $organisation_id);
        $details_stmt->execute();
        $details_stmt->close();

        // Secure delete query ensuring the organization only deletes its own payments
        $stmt = $conn->prepare("DELETE FROM payments WHERE id = ? AND organisation_id = ?");

        if ($stmt) {
            $stmt->bind_param("ii", $id, $organisation_id);

            if (!$stmt->execute()) {
                throw new Exception('Unable to delete payment.');
            }

            $deleted_rows = $stmt->affected_rows;
            $stmt->close();

            if ($deleted_rows < 1) {
                throw new Exception('Payment not found.');
            }

            $conn->commit();
            header("Location: all_payments.php?msg=deleted");
            exit();
        }

        throw new Exception('Unable to prepare payment delete.');
    } catch (Throwable $e) {
        $conn->rollback();
    }
}

// If something went wrong, redirect back with error message
header("Location: all_payments.php?msg=error");
exit();
?>
