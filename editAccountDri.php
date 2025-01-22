<?php
session_start();
include 'dbConnection.php';
include 'auditTrail.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['AdminID'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in as admin']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

try {
    $connMe->begin_transaction();

    if (isset($data['updateType'])) {
        if ($data['updateType'] === 'account') {
            // Get old data before update
            $stmt = $connMe->prepare("
                SELECT u.EmailAddress, u.EmailSecCode, u.SecQues1, u.SecQues2, u.Status,
                       d.Username, d.Password
                FROM USER u
                JOIN DRIVER d ON u.UserID = d.UserID
                WHERE d.DriverID = ? AND u.UserID = ?
            ");
            $stmt->bind_param("ss", $data['driverId'], $data['userId']);
            $stmt->execute();
            $oldData = $stmt->get_result()->fetch_assoc();

            // Check if email exists (if changed)
            if ($data['email']) {
                $stmt = $connMe->prepare("SELECT UserID FROM USER WHERE UPPER(EmailAddress) = UPPER(?) AND UserID != ?");
                $stmt->bind_param("ss", $data['email'], $data['userId']);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    throw new Exception("Email already exists");
                }
            }

            // Update USER table if email or security code changed
            if ($data['email'] || $data['securityCode']) {
                $updates = [];
                $params = [];
                $types = "";

                if ($data['email']) {
                    $updates[] = "EmailAddress = UPPER(?)";
                    $params[] = $data['email'];
                    $types .= "s";
                }

                if ($data['securityCode']) {
                    $updates[] = "EmailSecCode = ?";
                    $params[] = $data['securityCode'];
                    $types .= "s";
                }

                if (!empty($data['secQues1'])) {
                    $updates[] = "SecQues1 = ?";
                    $params[] = $data['secQues1'];
                    $types .= "s";
                }

                if (!empty($data['secQues2'])) {
                    $updates[] = "SecQues2 = ?";
                    $params[] = $data['secQues2'];
                    $types .= "s";
                }

                if (!empty($data['status'])) {
                    $updates[] = "Status = ?";
                    $params[] = $data['status'];
                    $types .= "s";
                }

                if (!empty($updates)) {
                    $query = "UPDATE USER SET " . implode(", ", $updates) . " WHERE UserID = ?";
                    $params[] = $data['userId'];
                    $types .= "s";

                    $stmt = $connMe->prepare($query);
                    $stmt->bind_param($types, ...$params);
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to update user information");
                    }

                    // Log audit trail for USER table update
                    $oldUserData = [];
                    $newUserData = [];
                    
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
            }

            // Update DRIVER table if username or password changed
            if ($data['username'] || $data['password']) {
                $updates = [];
                $params = [];
                $types = "";

                if ($data['username']) {
                    $updates[] = "Username = ?";
                    $params[] = $data['username'];
                    $types .= "s";
                }

                if ($data['password']) {
                    $updates[] = "Password = ?";
                    $params[] = $data['password'];
                    $types .= "s";
                }

                if (!empty($updates)) {
                    $query = "UPDATE DRIVER SET " . implode(", ", $updates) . " WHERE DriverID = ? AND UserID = ?";
                    $params[] = $data['driverId'];
                    $params[] = $data['userId'];
                    $types .= "ss";

                    $stmt = $connMe->prepare($query);
                    $stmt->bind_param($types, ...$params);
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to update driver information");
                    }

                    // Log audit trail for driver account update
                    $oldDriverData = [];
                    $newDriverData = [];
                    if (isset($data['username']) && !empty($data['username']) && 
                        $data['username'] !== $oldData['Username']) {
                        $oldDriverData["Username"] = $oldData['Username'];
                        $newDriverData["Username"] = $data['username'];
                    }
                    if (isset($data['password']) && !empty($data['password']) && 
                        $data['password'] !== $oldData['Password']) {
                        $oldDriverData["Password"] = $oldData['Password'];
                        $newDriverData["Password"] = $data['password'];
                    }
                    
                    if (!empty($newDriverData)) {
                        logAuditTrail(
                            "DRIVER",
                            $data['driverId'],
                            "UPDATE",
                            $_SESSION['AdminID'],
                            $oldDriverData,
                            $newDriverData
                        );
                    }
                }
            }
        } else if ($data['updateType'] === 'personal') {
            // Get old data before update
            $stmt = $connMe->prepare("
                SELECT u.FullName, u.PhoneNo, u.Gender, u.BirthDate, u.MatricNo,
                       d.LicenseNo, d.LicenseExpDate
                FROM USER u
                JOIN DRIVER d ON u.UserID = d.UserID
                WHERE d.DriverID = ? AND u.UserID = ?
            ");
            $stmt->bind_param("ss", $data['driverId'], $data['userId']);
            $stmt->execute();
            $oldData = $stmt->get_result()->fetch_assoc();

            // Check if matric number is empty
            if (empty($data['matricNo'])) {
                throw new Exception("Matric number is required for drivers");
            }

            // Update USER table
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
            
            $stmt->bind_param("sssssssss", 
                $data['fullName'],
                $data['phoneNo'],
                $data['gender'],
                $data['birthDate'],
                $data['matricNo'],
                $data['licenseNo'],
                $data['licenseExpDate'],
                $data['userId'],
                $data['driverId']
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update user information");
            }

            // Log audit trail for USER table update
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
            if (isset($data['matricNo']) && !empty($data['matricNo']) && 
                strtoupper($data['matricNo']) !== $oldData['MatricNo']) {
                $oldUserData["MatricNo"] = $oldData['MatricNo'];
                $newUserData["MatricNo"] = strtoupper($data['matricNo']);
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

            // Log audit trail for driver license info update
            $oldDriverData = [];
            $newDriverData = [];
            
            if (isset($data['licenseNo']) && !empty($data['licenseNo']) && 
                $data['licenseNo'] !== $oldData['LicenseNo']) {
                $oldDriverData["LicenseNo"] = $oldData['LicenseNo'];
                $newDriverData["LicenseNo"] = $data['licenseNo'];
            }
            if (isset($data['licenseExpDate']) && !empty($data['licenseExpDate']) && 
                $data['licenseExpDate'] !== $oldData['LicenseExpDate']) {
                $oldDriverData["LicenseExpDate"] = $oldData['LicenseExpDate'];
                $newDriverData["LicenseExpDate"] = $data['licenseExpDate'];
            }
            
            if (!empty($newDriverData)) {
                logAuditTrail(
                    "DRIVER",
                    $data['driverId'],
                    "UPDATE",
                    $_SESSION['AdminID'],
                    $oldDriverData,
                    $newDriverData
                );
            }

        } else if ($data['updateType'] === 'service') {
            // Get old data before update
            $stmt = $connMe->prepare("SELECT Availability, StickerExpDate FROM DRIVER WHERE DriverID = ?");
            $stmt->bind_param("s", $data['driverId']);
            $stmt->execute();
            $oldData = $stmt->get_result()->fetch_assoc();

            // Update driver availability and sticker expiry date
            $stmt = $connMe->prepare("UPDATE DRIVER SET Availability = ?, StickerExpDate = ? WHERE DriverID = ? AND UserID = ?");
            $stmt->bind_param("ssss",
                $data['availability'],
                $data['stickerExpDate'],
                $data['driverId'],
                $data['userId']
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update availability");
            }

            // Log audit trail for service info update
            $oldDriverData = [];
            $newDriverData = [];
            
            if (isset($data['availability']) && !empty($data['availability']) && 
                $data['availability'] !== $oldData['Availability']) {
                $oldDriverData["Availability"] = $oldData['Availability'];
                $newDriverData["Availability"] = $data['availability'];
            }
            if (isset($data['stickerExpDate']) && !empty($data['stickerExpDate']) && 
                $data['stickerExpDate'] !== $oldData['StickerExpDate']) {
                $oldDriverData["StickerExpDate"] = $oldData['StickerExpDate'];
                $newDriverData["StickerExpDate"] = $data['stickerExpDate'];
            }
            
            if (!empty($newDriverData)) {
                logAuditTrail(
                    "DRIVER",
                    $data['driverId'],
                    "UPDATE",
                    $_SESSION['AdminID'],
                    $oldDriverData,
                    $newDriverData
                );
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