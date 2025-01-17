<?php
function logUserActivity($userID, $userType, $action, $status) {
    global $connMe;
    
    // Get client IP address
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    
    // Get device info
    $deviceInfo = $_SERVER['HTTP_USER_AGENT'];
    
    // Convert user type to match the CHECK constraint
    $userType = strtoupper($userType);
    $action = strtoupper($action);
    $status = strtoupper($status);
    
    // Prepare the SQL statement
    $stmt = $connMe->prepare("INSERT INTO AUDIT_LOG (UserID, UserTypeForLog, Action, Status, IPAddress, DeviceInfo) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $userID, $userType, $action, $status, $ipAddress, $deviceInfo);
    
    // Execute the statement
    $stmt->execute();
    $stmt->close();
}
?> 