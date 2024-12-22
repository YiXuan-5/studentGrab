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

try {
    $connMe->begin_transaction();

    if (isset($data['updateType'])) {
        if ($data['updateType'] === 'account') {
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

                if (!empty($updates)) {
                    $query = "UPDATE USER SET " . implode(", ", $updates) . " WHERE UserID = ?";
                    $params[] = $data['userId'];
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
                    $params[] = $data['driverId'];
                    $params[] = $data['userId'];
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
                    d.LicenseExpDate = ?,
                    d.StickerExpDate = ?
                WHERE u.UserID = ? AND d.DriverID = ?
            ");
            
            $stmt->bind_param("sssssssss", 
                $data['fullName'],
                $data['phoneNo'],
                $data['gender'],
                $data['birthDate'],
                $data['licenseNo'],
                $data['licenseExpDate'],
                $data['stickerExpDate'],
                $data['userId'],
                $data['driverId']
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update user information");
            }
        } else if ($data['updateType'] === 'service') {
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