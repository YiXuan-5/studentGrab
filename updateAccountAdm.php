<?php
session_start();
include 'dbConnection.php';

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

        } else if ($data['updateType'] === 'account') {
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
                $updates[] = "EmailAddress = UPPER(?)";
                $params[] = $data['email'];
                $types .= "s";
            }

            // Add security code if provided
            if ($data['securityCode']) {
                $updates[] = "EmailSecCode = ?";
                $params[] = $data['securityCode'];
                $types .= "s";
            }

            // Always update security questions if provided
            if (isset($data['secQues1'])) {
                $updates[] = "SecQues1 = ?";
                $params[] = $data['secQues1'];
                $types .= "s";
            }

            if (isset($data['secQues2'])) {
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
            }

            // Update ADMIN table if username or password changed
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
                    $query = "UPDATE ADMIN SET " . implode(", ", $updates) . " WHERE AdminID = ? AND UserID = ?";
                    $params[] = $_SESSION['AdminID'];
                    $params[] = $_SESSION['UserID'];
                    $types .= "ss";

                    $stmt = $connMe->prepare($query);
                    $stmt->bind_param($types, ...$params);
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to update admin information");
                    }
                }
            }
        } else if ($data['updateType'] === 'job') {
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