<?php
session_start();
include 'dbConnection.php';
include 'auditTrail.php';

header('Content-Type: application/json');

if (!isset($_SESSION['UserID']) || !isset($_SESSION['DriverID'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

try {
    $connMe->begin_transaction();

    // Check which type of update this is
    if (isset($data['updateType'])) {
        if ($data['updateType'] === 'account') {
            // Check if email exists (if changed) excluding current user himself
            if ($data['email']) {
                $stmt = $connMe->prepare("SELECT UserID FROM USER WHERE UPPER(EmailAddress) = UPPER(?) AND UserID != ?");
                $stmt->bind_param("ss", $data['email'], $_SESSION['UserID']);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    throw new Exception("Email already exists");
                }
            }

            // Get current user data for audit trail
            $stmt = $connMe->prepare("SELECT EmailAddress, EmailSecCode, SecQues1, SecQues2 FROM USER WHERE UserID = ?");
            $stmt->bind_param("s", $_SESSION['UserID']);
            $stmt->execute();
            $oldUserData = $stmt->get_result()->fetch_assoc();

            $stmt = $connMe->prepare("SELECT Username, Password FROM DRIVER WHERE DriverID = ?");
            $stmt->bind_param("s", $_SESSION['DriverID']);
            $stmt->execute();
            $oldDriverData = $stmt->get_result()->fetch_assoc();

            // Track changes for audit trail
            $oldData = [];
            $newData = [];

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
                        logAuditTrail("USER", $_SESSION['UserID'], "UPDATE", $_SESSION['UserID'], $oldData, $newData);
                    }
                }
            }

            // Update DRIVER table if username or password changed
            if ($data['username'] || $data['password']) {
                $driverOldData = [];
                $driverNewData = [];
                $updates = [];
                $params = [];
                $types = "";

                if ($data['username']) {
                    if ($data['username'] !== $oldDriverData['Username']) {
                        $driverOldData['Username'] = $oldDriverData['Username'];
                        $driverNewData['Username'] = $data['username'];
                    }
                    $updates[] = "Username = ?";
                    $params[] = $data['username'];
                    $types .= "s";
                }

                if ($data['password']) {
                    $driverOldData['Password'] = $oldDriverData['Password'];
                    $driverNewData['Password'] = $data['password'];
                    $updates[] = "Password = ?";
                    $params[] = $data['password'];
                    $types .= "s";
                }

                if (!empty($updates)) {
                    $query = "UPDATE DRIVER SET " . implode(", ", $updates) . " WHERE DriverID = ? AND UserID = ?";
                    $params[] = $_SESSION['DriverID'];
                    $params[] = $_SESSION['UserID'];
                    $types .= "ss";

                    $stmt = $connMe->prepare($query);
                    $stmt->bind_param($types, ...$params);
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to update driver information");
                    }

                    // Log DRIVER table changes if any
                    if (!empty($driverOldData)) {
                        logAuditTrail("DRIVER", $_SESSION['DriverID'], "UPDATE", $_SESSION['UserID'], $driverOldData, $driverNewData);
                    }
                }
            }
        } else if ($data['updateType'] === 'personal') {
            // Get current data for audit trail
            $stmt = $connMe->prepare("SELECT FullName, PhoneNo, Gender, BirthDate, MatricNo FROM USER WHERE UserID = ?");
            $stmt->bind_param("s", $_SESSION['UserID']);
            $stmt->execute();
            $oldUserData = $stmt->get_result()->fetch_assoc();

            $stmt = $connMe->prepare("SELECT LicenseNo, LicenseExpDate FROM DRIVER WHERE DriverID = ?");
            $stmt->bind_param("s", $_SESSION['DriverID']);
            $stmt->execute();
            $oldDriverData = $stmt->get_result()->fetch_assoc();

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
            if (strtoupper($data['matricNo']) !== $oldUserData['MatricNo']) {
                $oldData['MatricNo'] = $oldUserData['MatricNo'];
                $newData['MatricNo'] = strtoupper($data['matricNo']);
            }

            // Track driver-specific changes
            $driverOldData = [];
            $driverNewData = [];
            if ($data['licenseNo'] !== $oldDriverData['LicenseNo']) {
                $driverOldData['LicenseNo'] = $oldDriverData['LicenseNo'];
                $driverNewData['LicenseNo'] = $data['licenseNo'];
            }
            if ($data['licenseExpDate'] !== $oldDriverData['LicenseExpDate']) {
                $driverOldData['LicenseExpDate'] = $oldDriverData['LicenseExpDate'];
                $driverNewData['LicenseExpDate'] = $data['licenseExpDate'];
            }

            // Update USER and DRIVER tables
            $stmt = $connMe->prepare("
                UPDATE USER u
                JOIN DRIVER d ON u.UserID = d.UserID
                SET u.FullName = ?,
                    u.PhoneNo = ?,
                    u.Gender = ?,
                    u.BirthDate = ?,
                    u.MatricNo = UPPER(?),
                    d.LicenseNo = ?,
                    d.LicenseExpDate = ?
                WHERE u.UserID = ? AND d.DriverID = ?
            ");
            
            // Convert matric number to uppercase before binding
            $matricNo = strtoupper($data['matricNo']);
            
            $stmt->bind_param("sssssssss", 
                $data['fullName'],
                $data['phoneNo'],
                $data['gender'],
                $data['birthDate'],
                $matricNo,
                $data['licenseNo'],
                $data['licenseExpDate'],
                $_SESSION['UserID'],
                $_SESSION['DriverID']
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update user information");
            }

            // Log changes to USER table if any
            if (!empty($oldData)) {
                logAuditTrail("USER", $_SESSION['UserID'], "UPDATE", $_SESSION['UserID'], $oldData, $newData);
            }

            // Log changes to DRIVER table if any
            if (!empty($driverOldData)) {
                logAuditTrail("DRIVER", $_SESSION['DriverID'], "UPDATE", $_SESSION['UserID'], $driverOldData, $driverNewData);
            }
        } else if ($data['updateType'] === 'service') {
            // Get current availability for audit trail
            $stmt = $connMe->prepare("SELECT Availability FROM DRIVER WHERE DriverID = ?");
            $stmt->bind_param("s", $_SESSION['DriverID']);
            $stmt->execute();
            $oldData = $stmt->get_result()->fetch_assoc();

            if ($oldData['Availability'] !== $data['availability']) {
                // Update driver availability
                $stmt = $connMe->prepare("UPDATE DRIVER SET Availability = ? WHERE DriverID = ? AND UserID = ?");
                $stmt->bind_param("sss",
                    $data['availability'],
                    $_SESSION['DriverID'],
                    $_SESSION['UserID']
                );
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update availability");
                }

                // Log the change
                logAuditTrail(
                    "DRIVER",
                    $_SESSION['DriverID'],
                    "UPDATE",
                    $_SESSION['UserID'],
                    ['Availability' => $oldData['Availability']],
                    ['Availability' => $data['availability']]
                );
            }
        } else if ($data['updateType'] === 'vehicle') {
            // If setting to active, first deactivate all other vehicles
            if (strtoupper($data['status']) === 'ACTIVE') {
                $stmt = $connMe->prepare("UPDATE VEHICLE SET VehicleStatus = 'INACTIVE' WHERE DriverID = ?");
                $stmt->bind_param("s", $_SESSION['DriverID']);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update other vehicles' status");
                }
            }

            // Update vehicle information
            $stmt = $connMe->prepare("UPDATE VEHICLE SET 
                VehicleStatus = ?, 
                Model = UPPER(?), 
                PlateNo = UPPER(?), 
                Color = UPPER(?), 
                AvailableSeat = ?, 
                YearManufacture = ? 
                WHERE VhcID = ? AND DriverID = ?");
            
            $stmt->bind_param("ssssiiss",
                strtoupper($data['status']),
                $data['model'],
                $data['plateNo'],
                $data['color'],
                $data['availableSeat'],
                $data['yearManufacture'],
                $data['vhcId'],
                $_SESSION['DriverID']
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update vehicle information");
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