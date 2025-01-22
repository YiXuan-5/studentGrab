<?php
session_start();
include 'dbConnection.php';
include 'auditTrail.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['AdminID'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

// Get the JSON data from the request
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit;
}

try {
    // Start transaction
    $connMe->begin_transaction();

    $updateType = $data['updateType'];
    $psgrID = $data['psgrId'];
    $userID = $data['userId'];

    switch ($updateType) {
        case 'account':
            // Get old data before update
            $stmt = $connMe->prepare("
                SELECT u.EmailAddress, u.EmailSecCode, u.SecQues1, u.SecQues2, u.Status,
                       p.Username, p.Password
                FROM USER u
                JOIN PASSENGER p ON u.UserID = p.UserID
                WHERE p.PsgrID = ? AND u.UserID = ?
            ");
            $stmt->bind_param("ss", $data['psgrId'], $data['userId']);
            $stmt->execute();
            $oldData = $stmt->get_result()->fetch_assoc();

            // Update USER table
            $updateUserSQL = "UPDATE USER SET ";
            $params = [];
            $types = "";

            if (!empty($data['email'])) {
                $updateUserSQL .= "EmailAddress = ?, ";
                $params[] = strtoupper($data['email']);
                $types .= "s";
            }

            if (!empty($data['securityCode'])) {
                $updateUserSQL .= "EmailSecCode = ?, ";
                $params[] = $data['securityCode'];
                $types .= "s";
            }

            if (!empty($data['secQues1'])) {
                $updateUserSQL .= "SecQues1 = ?, ";
                $params[] = $data['secQues1'];
                $types .= "s";
            }

            if (!empty($data['secQues2'])) {
                $updateUserSQL .= "SecQues2 = ?, ";
                $params[] = $data['secQues2'];
                $types .= "s";
            }

            if (!empty($data['status'])) {
                $updateUserSQL .= "Status = ?, ";
                $params[] = $data['status'];
                $types .= "s";
            }

            // Remove trailing comma and add WHERE clause
            $updateUserSQL = rtrim($updateUserSQL, ", ") . " WHERE UserID = ?";
            $params[] = $userID;
            $types .= "s";

            if (count($params) > 1) { // Only if there are fields to update
                $stmt = $connMe->prepare($updateUserSQL);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();

                // Log audit trail for USER table update
                $oldUserData = [];
                $newUserData = [];

                // Only include fields that actually changed
                // Convert email to uppercase before comparison
                $upperEmail = isset($data['email']) ? strtoupper($data['email']) : '';
                if (!empty($upperEmail) && $upperEmail !== $oldData['EmailAddress']) {
                    $oldUserData["EmailAddress"] = $oldData['EmailAddress'];
                    $newUserData["EmailAddress"] = $upperEmail;
                }
                if (isset($data['securityCode']) && !empty($data['securityCode']) && 
                    $data['securityCode'] !== $oldData['EmailSecCode']) {
                    $oldUserData["EmailSecCode"] = $oldData['EmailSecCode'];
                    $newUserData["EmailSecCode"] = $data['securityCode'];
                }
                if (isset($data['secQues1']) && !empty($data['secQues1']) && 
                    $data['secQues1'] !== $oldData['SecQues1']) {
                    $oldUserData["SecQues1"] = $oldData['SecQues1'];
                    $newUserData["SecQues1"] = $data['secQues1'];
                }
                if (isset($data['secQues2']) && !empty($data['secQues2']) && 
                    $data['secQues2'] !== $oldData['SecQues2']) {
                    $oldUserData["SecQues2"] = $oldData['SecQues2'];
                    $newUserData["SecQues2"] = $data['secQues2'];
                }
                if (isset($data['status']) && !empty($data['status']) && 
                    $data['status'] !== $oldData['Status']) {
                    $oldUserData["Status"] = $oldData['Status'];
                    $newUserData["Status"] = $data['status'];
                }
                
                // Only log if there are actual changes
                if (!empty($newUserData)) {
                    logAuditTrail(
                        "USER",
                        $data['userId'],
                        "UPDATE",
                        $_SESSION['AdminID'],
                        $oldUserData,
                        $newUserData
                    );
                }
            }

            // Update PASSENGER table
            $updatePsgrSQL = "UPDATE PASSENGER SET ";
            $params = [];
            $types = "";

            if (!empty($data['username'])) {
                $updatePsgrSQL .= "Username = ?, ";
                $params[] = $data['username'];
                $types .= "s";
            }

            if (!empty($data['password'])) {
                $updatePsgrSQL .= "Password = ?, ";
                $params[] = $data['password'];
                $types .= "s";
            }

            // Remove trailing comma and add WHERE clause
            $updatePsgrSQL = rtrim($updatePsgrSQL, ", ") . " WHERE PsgrID = ?";
            $params[] = $psgrID;
            $types .= "s";

            if (count($params) > 1) { // Only if there are fields to update
                $stmt = $connMe->prepare($updatePsgrSQL);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();

                // Log audit trail for passenger account update
                $oldPsgrData = [];
                $newPsgrData = [];

                if (isset($data['username']) && !empty($data['username']) && 
                    $data['username'] !== $oldData['Username']) {
                    $oldPsgrData["Username"] = $oldData['Username'];
                    $newPsgrData["Username"] = $data['username'];
                }
                if (isset($data['password']) && !empty($data['password']) && 
                    $data['password'] !== $oldData['Password']) {
                    $oldPsgrData["Password"] = $oldData['Password'];
                    $newPsgrData["Password"] = $data['password'];
                }
                
                if (!empty($newPsgrData)) {
                    logAuditTrail(
                        "PASSENGER",
                        $data['psgrId'],
                        "UPDATE",
                        $_SESSION['AdminID'],
                        $oldPsgrData,
                        $newPsgrData
                    );
                }
            }
            break;

        case 'personal':
            // Get old data before update
            $stmt = $connMe->prepare("
                SELECT u.FullName, u.PhoneNo, u.Gender, u.BirthDate, u.MatricNo,
                       p.Role
                FROM USER u
                JOIN PASSENGER p ON u.UserID = p.UserID
                WHERE p.PsgrID = ? AND u.UserID = ?
            ");
            $stmt->bind_param("ss", $data['psgrId'], $data['userId']);
            $stmt->execute();
            $oldData = $stmt->get_result()->fetch_assoc();

            // Update USER table
            $stmt = $connMe->prepare("
                UPDATE USER 
                SET FullName = ?, PhoneNo = ?, Gender = ?, BirthDate = ?, 
                   MatricNo = CASE WHEN ? IS NULL THEN NULL ELSE UPPER(?) END
                WHERE UserID = ?
            ");
            $stmt->bind_param("sssssss", 
                $data['fullName'],
                $data['phoneNo'],
                $data['gender'],
                $data['birthDate'],
                $data['matricNo'],
                $data['matricNo'],
                $userID
            );
            $stmt->execute();

            // Update PASSENGER table
            $stmt = $connMe->prepare("
                UPDATE PASSENGER 
                SET Role = ?
                WHERE PsgrID = ?
            ");
            $stmt->bind_param("ss", 
                $data['role'],
                $psgrID
            );
            $stmt->execute();

            // Log audit trail for personal info update
            $oldUserData = [];
            $newUserData = [];

            if (isset($data['fullName']) && !empty($data['fullName']) && 
                strtoupper($data['fullName']) !== $oldData['FullName']) {
                $oldUserData["FullName"] = $oldData['FullName'];
                $newUserData["FullName"] = strtoupper($data['fullName']);
            }
            if (isset($data['phoneNo']) && !empty($data['phoneNo']) && 
                $data['phoneNo'] !== $oldData['PhoneNo']) {
                $oldUserData["PhoneNo"] = $oldData['PhoneNo'];
                $newUserData["PhoneNo"] = $data['phoneNo'];
            }
            if (isset($data['gender']) && !empty($data['gender']) && 
                $data['gender'] !== $oldData['Gender']) {
                $oldUserData["Gender"] = $oldData['Gender'];
                $newUserData["Gender"] = $data['gender'];
            }
            if (isset($data['birthDate']) && !empty($data['birthDate']) && 
                $data['birthDate'] !== $oldData['BirthDate']) {
                $oldUserData["BirthDate"] = $oldData['BirthDate'];
                $newUserData["BirthDate"] = $data['birthDate'];
            }
            if (isset($data['matricNo']) && $data['matricNo'] !== $oldData['MatricNo']) {
                $oldUserData["MatricNo"] = $oldData['MatricNo'];
                $newUserData["MatricNo"] = $data['matricNo'];
            }
            
            if (!empty($newUserData)) {
                logAuditTrail(
                    "USER",
                    $data['userId'],
                    "UPDATE",
                    $_SESSION['AdminID'],
                    $oldUserData,
                    $newUserData
                );
            }

            // Log audit trail for passenger role update
            if (isset($data['role']) && !empty($data['role']) && $data['role'] !== $oldData['Role']) {
                logAuditTrail(
                    "PASSENGER",
                    $data['psgrId'],
                    "UPDATE",
                    $_SESSION['AdminID'],
                    ["Role" => $oldData['Role']],
                    ["Role" => $data['role']]
                );
            }
            break;

        case 'preferences':
            // Get old data before update
            $stmt = $connMe->prepare("
                SELECT FavPickUpLoc, FavDropOffLoc
                FROM PASSENGER
                WHERE PsgrID = ?
            ");
            $stmt->bind_param("s", $data['psgrId']);
            $stmt->execute();
            $oldData = $stmt->get_result()->fetch_assoc();

            $stmt = $connMe->prepare("
                UPDATE PASSENGER 
                SET FavPickUpLoc = ?, FavDropOffLoc = ?
                WHERE PsgrID = ?
            ");
            $stmt->bind_param("sss", 
                $data['favPickUpLoc'],
                $data['favDropOffLoc'],
                $psgrID
            );
            $stmt->execute();

            // Log audit trail for preferences update
            $oldPsgrData = [];
            $newPsgrData = [];

            if (isset($data['favPickUpLoc']) && !empty($data['favPickUpLoc']) && 
                strtoupper($data['favPickUpLoc']) !== $oldData['FavPickUpLoc']) {
                $oldPsgrData["FavPickUpLoc"] = $oldData['FavPickUpLoc'];
                $newPsgrData["FavPickUpLoc"] = strtoupper($data['favPickUpLoc']);
            }
            if (isset($data['favDropOffLoc']) && !empty($data['favDropOffLoc']) && 
                strtoupper($data['favDropOffLoc']) !== $oldData['FavDropOffLoc']) {
                $oldPsgrData["FavDropOffLoc"] = $oldData['FavDropOffLoc'];
                $newPsgrData["FavDropOffLoc"] = strtoupper($data['favDropOffLoc']);
            }
            
            if (!empty($newPsgrData)) {
                logAuditTrail(
                    "PASSENGER",
                    $data['psgrId'],
                    "UPDATE",
                    $_SESSION['AdminID'],
                    $oldPsgrData,
                    $newPsgrData
                );
            }
            break;

        default:
            throw new Exception("Invalid update type");
    }

    // Commit transaction
    $connMe->commit();
    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    // Rollback transaction on error
    $connMe->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$connMe->close();
?> 