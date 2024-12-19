<?php
session_start();
include 'dbConnection.php';

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

            // Update USER table if email, security code, or security questions changed
            if ($data['email'] || $data['securityCode'] || $data['secQues1'] || $data['secQues2']) {
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

                if ($data['secQues1']) {
                    $updates[] = "SecQues1 = ?";
                    $params[] = $data['secQues1'];
                    $types .= "s";
                }

                if ($data['secQues2']) {
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
                }
            }

            // Update PASSENGER table if username or password changed
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
                    $query = "UPDATE PASSENGER SET " . implode(", ", $updates) . " WHERE PsgrID = ? AND UserID = ?";
                    $params[] = $_SESSION['PsgrID'];
                    $params[] = $_SESSION['UserID'];
                    $types .= "ss";

                    $stmt = $connMe->prepare($query);
                    $stmt->bind_param($types, ...$params);
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to update passenger information");
                    }
                }
            }
        } else if ($data['updateType'] === 'preferences') {
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