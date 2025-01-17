<?php
//session_start(): Start a new session or resume an existing session
session_start();
include 'dbConnection.php';
include 'auditLog.php';

// Log the logout activity if user was logged in
if (isset($_SESSION['UserID'])) {
    
    // Log the logout
    logUserActivity($_SESSION['UserID'], 'ADMIN', 'LOGOUT', 'SUCCESS');
}

// Clear all session variables
session_unset();

//session_destroy(): Destroys all data registered to a session
session_destroy();

header("Location: mainPageAdm.html");
exit();
?> 