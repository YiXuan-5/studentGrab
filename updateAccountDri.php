<?php
session_start();
include 'dbConnection.php';

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

                //!empty($updates) is used to check if there are any updates to be made
                //implode(", ", $updates) is used to concatenate all updates with commas    
                if (!empty($updates)) {
                    $query = "UPDATE USER SET " . implode(", ", $updates) . " WHERE UserID = ?";
                    $params[] = $_SESSION['UserID'];
                    $types .= "s";

                    $stmt = $connMe->prepare($query);
                    $stmt->bind_param($types, ...$params);
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to update user information");
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
                    $params[] = $_SESSION['DriverID'];
                    $params[] = $_SESSION['UserID'];
                    $types .= "ss";

                    $stmt = $connMe->prepare($query);
                    $stmt->bind_param($types, ...$params);
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to update driver information");
                    }
                }
            }
        } else if ($data['updateType'] === 'personal') {
            // Update USER table
            $stmt = $connMe->prepare("
                UPDATE USER u
                JOIN DRIVER d ON u.UserID = d.UserID
                SET u.FullName = ?,
                    u.PhoneNo = ?,
                    u.Gender = ?,
                    u.BirthDate = ?,
                    d.LicenseNo = ?,
                    d.LicenseExpDate = ?
                WHERE u.UserID = ? AND d.DriverID = ?
            ");
            
            $stmt->bind_param("ssssssss", 
                $data['fullName'],
                $data['phoneNo'],
                $data['gender'],
                $data['birthDate'],
                $data['licenseNo'],
                $data['licenseExpDate'],
                $_SESSION['UserID'],
                $_SESSION['DriverID']
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update user information");
            }
        } else if ($data['updateType'] === 'service') {
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