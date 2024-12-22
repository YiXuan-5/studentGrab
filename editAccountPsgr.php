<?php
session_start();
include 'dbConnection.php';

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

            // Remove trailing comma and add WHERE clause
            $updateUserSQL = rtrim($updateUserSQL, ", ") . " WHERE UserID = ?";
            $params[] = $userID;
            $types .= "s";

            if (count($params) > 1) { // Only if there are fields to update
                $stmt = $connMe->prepare($updateUserSQL);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
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
            }
            break;

        case 'personal':
            // Update USER table
            $stmt = $connMe->prepare("
                UPDATE USER 
                SET FullName = ?, PhoneNo = ?, Gender = ?, BirthDate = ?
                WHERE UserID = ?
            ");
            $stmt->bind_param("sssss", 
                $data['fullName'],
                $data['phoneNo'],
                $data['gender'],
                $data['birthDate'],
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
            break;

        case 'preferences':
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
?> 