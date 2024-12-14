<?php
session_start();
include 'dbConnection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['UserID']) || !isset($_SESSION['DriverID'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$userID = $_SESSION['UserID'];
$driverID = $_SESSION['DriverID'];

try {
    $connMe->begin_transaction();

    // First, check if user has other roles
    $stmt = $connMe->prepare("SELECT UserType FROM USER WHERE UserID = ?");
    $stmt->bind_param("s", $userID);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
    $userTypes = explode(" ", $userData['UserType']);

    // Delete all vehicles associated with this driver
    $stmt = $connMe->prepare("DELETE FROM VEHICLE WHERE DriverID = ?");
    $stmt->bind_param("s", $driverID);
    if (!$stmt->execute()) {
        throw new Exception("Failed to delete vehicles");
    }

    // Delete from DRIVER table
    $stmt = $connMe->prepare("DELETE FROM DRIVER WHERE DriverID = ? AND UserID = ?");
    $stmt->bind_param("ss", $driverID, $userID);
    if (!$stmt->execute()) {
        throw new Exception("Failed to delete driver record");
    }

    if (count($userTypes) > 1) {
        // User has other roles, just remove DRIVER from UserType
        $userTypes = array_filter($userTypes, function($type) {
            return $type !== 'DRIVER';
        });
        $newUserType = implode(" ", $userTypes);

        // Update USER table to remove DRIVER from UserType
        $stmt = $connMe->prepare("UPDATE USER SET UserType = ? WHERE UserID = ?");
        $stmt->bind_param("ss", $newUserType, $userID);
        if (!$stmt->execute()) {
            throw new Exception("Failed to update user type");
        }
    } else {
        // User only has DRIVER role, delete the user record
        $stmt = $connMe->prepare("DELETE FROM USER WHERE UserID = ?");
        $stmt->bind_param("s", $userID);
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete user record");
        }
    }

    $connMe->commit();
    
    // Clear session
    session_destroy();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Account deleted successfully',
        'hasOtherRoles' => count($userTypes) > 1
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