<?php
session_start();
include 'dbConnection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['UserID']) || !isset($_SESSION['AdminID'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

try {
    $connMe->begin_transaction();

    // First, check if user has other roles (PASSENGER or DRIVER)
    $stmt = $connMe->prepare("
        SELECT 
            UserType,
            (SELECT COUNT(*) FROM PASSENGER WHERE UserID = ?) as isPsgr,
            (SELECT COUNT(*) FROM DRIVER WHERE UserID = ?) as isDriver
        FROM USER 
        WHERE UserID = ?
    ");
    
    $stmt->bind_param("sss", $_SESSION['UserID'], $_SESSION['UserID'], $_SESSION['UserID']);
    $stmt->execute();
    $result = $stmt->get_result();
    $userInfo = $result->fetch_assoc();

    // Delete from ADMIN table first
    $stmt = $connMe->prepare("DELETE FROM ADMIN WHERE AdminID = ? AND UserID = ?");
    $stmt->bind_param("ss", $_SESSION['AdminID'], $_SESSION['UserID']);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to delete admin record");
    }

    // If user has other roles, just update UserType
    if ($userInfo['isPsgr'] > 0 || $userInfo['isDriver'] > 0) {
        // Remove 'ADMIN' from UserType
        $currentUserType = $userInfo['UserType'];
        $newUserType = trim(str_replace('ADMIN', '', $currentUserType));
        
        $stmt = $connMe->prepare("UPDATE USER SET UserType = ? WHERE UserID = ?");
        $stmt->bind_param("ss", $newUserType, $_SESSION['UserID']);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update user type");
        }
    } else {
        // If no other roles, delete from USER table
        $stmt = $connMe->prepare("DELETE FROM USER WHERE UserID = ?");
        $stmt->bind_param("s", $_SESSION['UserID']);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete user record");
        }
    }

    $connMe->commit();
    session_destroy();
    
    echo json_encode([
        'status' => 'success',
        'message' => $userInfo['isPsgr'] > 0 || $userInfo['isDriver'] > 0 ? 
            'Admin account removed. Other roles maintained.' : 
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