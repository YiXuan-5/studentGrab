<?php
session_start();
include 'dbConnection.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['AdminID'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in as admin']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Validate input data
if (!isset($data['driverId']) || !isset($data['userId'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required data']);
    exit;
}

$driverId = $data['driverId'];
$userId = $data['userId'];

try {
    $connMe->begin_transaction();

    // First, check if user has other roles (ADMIN or PASSENGER)
    $stmt = $connMe->prepare("
        SELECT 
            UserType,
            (SELECT COUNT(*) FROM ADMIN WHERE UserID = ?) as isAdmin,
            (SELECT COUNT(*) FROM PASSENGER WHERE UserID = ?) as isPassenger
        FROM USER 
        WHERE UserID = ?
    ");
    
    $stmt->bind_param("sss", $userId, $userId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $userInfo = $result->fetch_assoc();

    // Delete from VEHICLE table first
    $stmt = $connMe->prepare("DELETE FROM VEHICLE WHERE DriverID = ?");
    $stmt->bind_param("s", $driverId);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to delete vehicle records");
    }

    // Then delete from DRIVER table
    $stmt = $connMe->prepare("DELETE FROM DRIVER WHERE DriverID = ? AND UserID = ?");
    $stmt->bind_param("ss", $driverId, $userId);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to delete driver record");
    }

    // If user has other roles, just update UserType
    if ($userInfo['isAdmin'] > 0 || $userInfo['isPassenger'] > 0) {
        // Remove 'DRIVER' from UserType
        $currentUserType = $userInfo['UserType'];
        $newUserType = trim(str_replace('DRIVER', '', $currentUserType));
        
        $stmt = $connMe->prepare("UPDATE USER SET UserType = ? WHERE UserID = ?");
        $stmt->bind_param("ss", $newUserType, $userId);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update user type");
        }
    } else {
        // If no other roles, delete from USER table
        $stmt = $connMe->prepare("DELETE FROM USER WHERE UserID = ?");
        $stmt->bind_param("s", $userId);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete user record");
        }
    }

    $connMe->commit();
    
    echo json_encode([
        'status' => 'success',
        'message' => $userInfo['isAdmin'] > 0 || $userInfo['isPassenger'] > 0 ? 
            'Driver account removed. Other roles maintained.' : 
            'Account completely deleted.'
    ]);

} catch (Exception $e) {
    $connMe->rollback();
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

$connMe->close();
?> 