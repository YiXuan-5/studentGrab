<?php
session_start();
include 'dbConnection.php';
include 'auditTrail.php';

header('Content-Type: application/json');

if (!isset($_SESSION['UserID']) || !isset($_SESSION['AdminID'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$inputData = file_get_contents('php://input');
error_log("Received data: " . $inputData);
$data = json_decode($inputData, true);
error_log("Decoded data: " . print_r($data, true));

try {
    $connMe->begin_transaction();

    // Check if this is an account update or personal info update
    if (isset($data['updateType'])) {
        if ($data['updateType'] === 'personal') {
            // Get current data for audit trail
            $stmt = $connMe->prepare("SELECT FullName, PhoneNo, Gender, BirthDate FROM USER WHERE UserID = ?");
            $stmt->bind_param("s", $_SESSION['UserID']);
            $stmt->execute();
            $oldUserData = $stmt->get_result()->fetch_assoc();
            
            // Track changes
            $oldData = [];
            $newData = [];
            
            // Compare and track changes
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

            // Update USER table for personal information
            $stmt = $connMe->prepare("UPDATE USER SET FullName = UPPER(?), PhoneNo = ?, Gender = ?, BirthDate = ? WHERE UserID = ?");
            $stmt->bind_param("sssss", 
                $data['fullName'],
                $data['phoneNo'],
                $data['gender'],
                $data['birthDate'],
                $_SESSION['UserID']
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update user information");
            }

            // Log changes to USER table if any
            if (!empty($oldData)) {
                logAuditTrail("USER", $_SESSION['UserID'], "UPDATE", $_SESSION['AdminID'], $oldData, $newData);
            }

        } else if ($data['updateType'] === 'account') {
            // Get current user data for audit trail
            $stmt = $connMe->prepare("SELECT EmailAddress, EmailSecCode, SecQues1, SecQues2 FROM USER WHERE UserID = ?");
            $stmt->bind_param("s", $_SESSION['UserID']);
            $stmt->execute();
            $oldUserData = $stmt->get_result()->fetch_assoc();

            $stmt = $connMe->prepare("SELECT Username, Password FROM ADMIN WHERE AdminID = ?");
            $stmt->bind_param("s", $_SESSION['AdminID']);
            $stmt->execute();
            $oldAdminData = $stmt->get_result()->fetch_assoc();

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

            // Initialize arrays for updates
            $updates = [];
            $params = [];
            $types = "";

            // Add email if provided
            if ($data['email']) {
                if (strtoupper($data['email']) !== strtoupper($oldUserData['EmailAddress'])) {
                    $oldData['EmailAddress'] = $oldUserData['EmailAddress'];
                    $newData['EmailAddress'] = strtoupper($data['email']);
                }
                $updates[] = "EmailAddress = UPPER(?)";
                $params[] = $data['email'];
                $types .= "s";
            }

            // Add security code if provided
            if ($data['securityCode']) {
                $oldData['EmailSecCode'] = $oldUserData['EmailSecCode'];
                $newData['EmailSecCode'] = $data['securityCode'];
                $updates[] = "EmailSecCode = ?";
                $params[] = $data['securityCode'];
                $types .= "s";
            }

            // Always update security questions if provided
            if (isset($data['secQues1'])) {
                $oldData['SecQues1'] = $oldUserData['SecQues1'];
                $newData['SecQues1'] = $data['secQues1'];
                $updates[] = "SecQues1 = ?";
                $params[] = $data['secQues1'];
                $types .= "s";
            }

            if (isset($data['secQues2'])) {
                $oldData['SecQues2'] = $oldUserData['SecQues2'];
                $newData['SecQues2'] = $data['secQues2'];
                $updates[] = "SecQues2 = ?";
                $params[] = $data['secQues2'];
                $types .= "s";
            }

            // Update USER table if there are any updates
            if (!empty($updates)) {
                $query = "UPDATE USER SET " . implode(", ", $updates) . " WHERE UserID = ?";
                $params[] = $_SESSION['UserID'];
                $types .= "s";

                $stmt = $connMe->prepare($query);
                if (!$stmt) {
                    throw new Exception("Error preparing statement: " . $connMe->error);
                }

                $stmt->bind_param($types, ...$params);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update user information: " . $stmt->error);
                }

                // Log USER table changes if any
                if (!empty($oldData)) {
                    logAuditTrail("USER", $_SESSION['UserID'], "UPDATE", $_SESSION['AdminID'], $oldData, $newData);
                }
            }

            // Update ADMIN table if username or password changed
            if ($data['username'] || $data['password']) {
                $adminOldData = [];
                $adminNewData = [];
                $updates = [];
                $params = [];
                $types = "";

                if ($data['username']) {
                    if ($data['username'] !== $oldAdminData['Username']) {
                        $adminOldData['Username'] = $oldAdminData['Username'];
                        $adminNewData['Username'] = $data['username'];
                    }
                    $updates[] = "Username = ?";
                    $params[] = $data['username'];
                    $types .= "s";
                }

                if ($data['password']) {
                    $adminOldData['Password'] = $oldAdminData['Password'];
                    $adminNewData['Password'] = $data['password'];
                    $updates[] = "Password = ?";
                    $params[] = $data['password'];
                    $types .= "s";
                }

                if (!empty($updates)) {
                    $query = "UPDATE ADMIN SET " . implode(", ", $updates) . " WHERE AdminID = ? AND UserID = ?";
                    $params[] = $_SESSION['AdminID'];
                    $params[] = $_SESSION['UserID'];
                    $types .= "ss";

                    $stmt = $connMe->prepare($query);
                    $stmt->bind_param($types, ...$params);
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to update admin information");
                    }

                    // Log ADMIN table changes if any
                    if (!empty($adminOldData)) {
                        logAuditTrail("ADMIN", $_SESSION['AdminID'], "UPDATE", $_SESSION['AdminID'], $adminOldData, $adminNewData);
                    }
                }
            }
        } else if ($data['updateType'] === 'job') {
            // Get current job data for audit trail
            $stmt = $connMe->prepare("SELECT Department, Position FROM ADMIN WHERE AdminID = ?");
            $stmt->bind_param("s", $_SESSION['AdminID']);
            $stmt->execute();
            $oldData = $stmt->get_result()->fetch_assoc();

            // Track changes
            $changedData = [];
            $newData = [];

            if ($data['department'] !== $oldData['Department']) {
                $changedData['Department'] = $oldData['Department'];
                $newData['Department'] = $data['department'];
            }
            if ($data['position'] !== $oldData['Position']) {
                $changedData['Position'] = $oldData['Position'];
                $newData['Position'] = $data['position'];
            }

            // Update ADMIN table with new job information
            $stmt = $connMe->prepare("UPDATE ADMIN SET Department = ?, Position = ? WHERE AdminID = ? AND UserID = ?");
            $stmt->bind_param("ssss",
                $data['department'],
                $data['position'],
                $_SESSION['AdminID'],
                $_SESSION['UserID']
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update job");
            }

            // Log changes if any job information was updated
            if (!empty($changedData)) {
                logAuditTrail("ADMIN", $_SESSION['AdminID'], "UPDATE", $_SESSION['AdminID'], $changedData, $newData);
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