<?php
// Sanitize input data
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Check if user is logged in as admin
function requireAdminLogin() {
    if (!isset($_SESSION['admin_id'])) {
        header("Location: login.php");
        exit();
    }
}
