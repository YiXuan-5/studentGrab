<?php
session_start();
include 'dbConnection.php';
include 'auditTrail.php';

header('Content-Type: application/json');

if (!isset($_SESSION['UserID']) || !isset($_SESSION['PsgrID'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

try {
    $connMe->begin_transaction();

    // Check if this is an account update or personal info update
    if (isset($data['updateType'])) {
        if ($data['updateType'] === 'personal') {
            // Get current data for audit trail
            $stmt = $connMe->prepare("SELECT FullName, PhoneNo, Gender, BirthDate, MatricNo FROM USER WHERE UserID = ?");
            $stmt->bind_param("s", $_SESSION['UserID']);
            $stmt->execute();
            $oldUserData = $stmt->get_result()->fetch_assoc();

            $stmt = $connMe->prepare("SELECT Role FROM PASSENGER WHERE PsgrID = ?");
            $stmt->bind_param("s", $_SESSION['PsgrID']);
            $stmt->execute();
            $oldPassengerData = $stmt->get_result()->fetch_assoc();

            // Track changes
            $oldData = [];
            $newData = [];

            // Compare and track USER table changes
            if ($data['fullName'] !== $oldUserData['FullName']) {
                $oldData['FullName'] = $oldUserData['FullName'];
                $newData['FullName'] = $data['fullName'];
            }
            if ($data['phoneNo'] !== $oldUserData['PhoneNo']) {
                $oldData['PhoneNo'] = $oldUserData['PhoneNo'];
                $newData['PhoneNo'] = $data['phoneNo'];
            }
            if ($data['gender'] !== $oldUserData['Gender']) {
                $oldData['Gender'] = $oldUserData['Gender'];
                $newData['Gender'] = $data['gender'];
            }
            if ($data['birthDate'] !== $oldUserData['BirthDate']) {
                $oldData['BirthDate'] = $oldUserData['BirthDate'];
                $newData['BirthDate'] = $data['birthDate'];
            }
            if (($data['matricNo'] ?? null) !== $oldUserData['MatricNo']) {
                $oldData['MatricNo'] = $oldUserData['MatricNo'];
                $newData['MatricNo'] = $data['matricNo'];
            }

            // Update USER table for personal information
            $stmt = $connMe->prepare("UPDATE USER SET FullName = UPPER(?), PhoneNo = ?, Gender = ?, BirthDate = ?, 
                                    MatricNo = CASE WHEN ? IS NULL THEN NULL ELSE UPPER(?) END
                                    WHERE UserID = ?");
            $stmt->bind_param("sssssss", 
                $data['fullName'],
                $data['phoneNo'],
                $data['gender'],
                $data['birthDate'],
                $data['matricNo'],
                $data['matricNo'],
                $_SESSION['UserID']
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update user information");
            }

            // Log changes to USER table if any
            if (!empty($oldData)) {
                logAuditTrail("USER", $_SESSION['UserID'], "UPDATE", $_SESSION['PsgrID'], $oldData, $newData);
            }

            // Track PASSENGER table changes
            $passengerOldData = [];
            $passengerNewData = [];
            if (strtoupper($data['role']) !== $oldPassengerData['Role']) {
                $passengerOldData['Role'] = $oldPassengerData['Role'];
                $passengerNewData['Role'] = strtoupper($data['role']);
            }

            // Update PASSENGER table for role
            $stmt = $connMe->prepare("UPDATE PASSENGER SET Role = UPPER(?) WHERE PsgrID = ? AND UserID = ?");
            $stmt->bind_param("sss",
                $data['role'],
                $_SESSION['PsgrID'],
                $_SESSION['UserID']
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update passenger information");
            }

            // Log changes to PASSENGER table if any
            if (!empty($passengerOldData)) {
                logAuditTrail("PASSENGER", $_SESSION['PsgrID'], "UPDATE", $_SESSION['PsgrID'], $passengerOldData, $passengerNewData);
            }
        } else if ($data['updateType'] === 'account') {
            // Get current user data for audit trail
            $stmt = $connMe->prepare("SELECT EmailAddress, EmailSecCode, SecQues1, SecQues2 FROM USER WHERE UserID = ?");
            $stmt->bind_param("s", $_SESSION['UserID']);
            $stmt->execute();
            $oldUserData = $stmt->get_result()->fetch_assoc();

            $stmt = $connMe->prepare("SELECT Username, Password FROM PASSENGER WHERE PsgrID = ?");
            $stmt->bind_param("s", $_SESSION['PsgrID']);
            $stmt->execute();
            $oldPassengerData = $stmt->get_result()->fetch_assoc();

            // Track changes for audit trail
            $oldData = [];
            $newData = [];

            // Check if email exists (if changed)
            if ($data['email']) {
                $stmt = $connMe->prepare("SELECT UserID FROM USER WHERE UPPER(EmailAddress) = UPPER(?) AND UserID != ?");
                $stmt->bind_param("ss", $data['email'], $_SESSION['UserID']);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    throw new Exception("Email already exists");
                }
            }

            // Update USER table if email, security code, or security questions changed
            if ($data['email'] || $data['securityCode'] || $data['secQues1'] || $data['secQues2']) {
                $updates = [];
                $params = [];
                $types = "";

                if ($data['email']) {
                    if (strtoupper($data['email']) !== strtoupper($oldUserData['EmailAddress'])) {
                        $oldData['EmailAddress'] = $oldUserData['EmailAddress'];
                        $newData['EmailAddress'] = strtoupper($data['email']);
                    }
                    $updates[] = "EmailAddress = UPPER(?)";
                    $params[] = $data['email'];
                    $types .= "s";
                }

                if ($data['securityCode']) {
                    $oldData['EmailSecCode'] = $oldUserData['EmailSecCode'];
                    $newData['EmailSecCode'] = $data['securityCode'];
                    $updates[] = "EmailSecCode = ?";
                    $params[] = $data['securityCode'];
                    $types .= "s";
                }

                if ($data['secQues1']) {
                    $oldData['SecQues1'] = $oldUserData['SecQues1'];
                    $newData['SecQues1'] = $data['secQues1'];
                    $updates[] = "SecQues1 = ?";
                    $params[] = $data['secQues1'];
                    $types .= "s";
                }

                if ($data['secQues2']) {
                    $oldData['SecQues2'] = $oldUserData['SecQues2'];
                    $newData['SecQues2'] = $data['secQues2'];
                    $updates[] = "SecQues2 = ?";
                    $params[] = $data['secQues2'];
                    $types .= "s";
                }

                if (!empty($updates)) {
                    $query = "UPDATE USER SET " . implode(", ", $updates) . " WHERE UserID = ?";
                    $params[] = $_SESSION['UserID'];
                    $types .= "s";

                    $stmt = $connMe->prepare($query);
                    $stmt->bind_param($types, ...$params);
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to update user information");
                    }

                    // Log USER table changes if any
                    if (!empty($oldData)) {
                        logAuditTrail("USER", $_SESSION['UserID'], "UPDATE", $_SESSION['PsgrID'], $oldData, $newData);
                    }
                }
            }

            // Update PASSENGER table if username or password changed
            if ($data['username'] || $data['password']) {
                $passengerOldData = [];
                $passengerNewData = [];
                $updates = [];
                $params = [];
                $types = "";

                if ($data['username']) {
                    if ($data['username'] !== $oldPassengerData['Username']) {
                        $passengerOldData['Username'] = $oldPassengerData['Username'];
                        $passengerNewData['Username'] = $data['username'];
                    }
                    $updates[] = "Username = ?";
                    $params[] = $data['username'];
                    $types .= "s";
                }

                if ($data['password']) {
                    $passengerOldData['Password'] = $oldPassengerData['Password'];
                    $passengerNewData['Password'] = $data['password'];
                    $updates[] = "Password = ?";
                    $params[] = $data['password'];
                    $types .= "s";
                }

                if (!empty($updates)) {
                    $query = "UPDATE PASSENGER SET " . implode(", ", $updates) . " WHERE PsgrID = ? AND UserID = ?";
                    $params[] = $_SESSION['PsgrID'];
                    $params[] = $_SESSION['UserID'];
                    $types .= "ss";

                    $stmt = $connMe->prepare($query);
                    $stmt->bind_param($types, ...$params);
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to update passenger information");
                    }

                    // Log PASSENGER table changes if any
                    if (!empty($passengerOldData)) {
                        logAuditTrail("PASSENGER", $_SESSION['PsgrID'], "UPDATE", $_SESSION['PsgrID'], $passengerOldData, $passengerNewData);
                    }
                }
            }
        } else if ($data['updateType'] === 'preferences') {
            // Get current preferences for audit trail
            $stmt = $connMe->prepare("SELECT FavPickUpLoc, FavDropOffLoc FROM PASSENGER WHERE PsgrID = ?");
            $stmt->bind_param("s", $_SESSION['PsgrID']);
            $stmt->execute();
            $oldData = $stmt->get_result()->fetch_assoc();

            // Track changes
            $changedData = [];
            $newData = [];
            
            if ($data['favPickUpLoc'] !== $oldData['FavPickUpLoc']) {
                $changedData['FavPickUpLoc'] = $oldData['FavPickUpLoc'];
                $newData['FavPickUpLoc'] = $data['favPickUpLoc'];
            }
            if ($data['favDropOffLoc'] !== $oldData['FavDropOffLoc']) {
                $changedData['FavDropOffLoc'] = $oldData['FavDropOffLoc'];
                $newData['FavDropOffLoc'] = $data['favDropOffLoc'];
            }

            // Update PASSENGER table with new preferences
            $stmt = $connMe->prepare("UPDATE PASSENGER SET FavPickUpLoc = ?, FavDropOffLoc = ? WHERE PsgrID = ? AND UserID = ?");
            $stmt->bind_param("ssss",
                $data['favPickUpLoc'],
                $data['favDropOffLoc'],
                $_SESSION['PsgrID'],
                $_SESSION['UserID']
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update preferences");
            }

            // Log changes if any preferences were updated
            if (!empty($changedData)) {
                logAuditTrail("PASSENGER", $_SESSION['PsgrID'], "UPDATE", $_SESSION['PsgrID'], $changedData, $newData);
            }
        }
    } else {
        throw new Exception("Update type not specified");
    }

    $connMe->commit();
    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    $connMe->rollback();
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

$connMe->close();
?> 