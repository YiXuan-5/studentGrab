<?php
session_start();
include 'dbConnection.php';
include 'auditLog.php';

// Log the logout activity if user was logged in
if (isset($_SESSION['UserID'])) {
    
    // Log the logout
    logUserActivity($_SESSION['UserID'], 'PASSENGER', 'LOGOUT', 'SUCCESS');
}

// Clear all session variables
session_unset();

// Destroy the session
session_destroy();

header("Location: mainPagePsgrDri.html");
exit();
?> 