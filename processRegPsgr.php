<?php
include 'dbConnection.php';

header('Content-Type: application/json');

// Get and decode the JSON data
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

// Function to get UserID based on email
function getUserIDByEmail($conn, $email) {
    $stmt = $conn->prepare("SELECT UserID FROM USER WHERE UPPER(EmailAddress) = UPPER(?) ORDER BY UserID DESC LIMIT 1");
    $stmt->bind_param("s", $email);
    
    if (!$stmt->execute()) {
        throw new Exception("Error fetching UserID: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception("No user found with email: " . $email);
    }
    
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['UserID'];
}

// Function to get PsgrID based on UserID
function getPsgrIDByUserID($conn, $userId) {
    $stmt = $conn->prepare("SELECT PsgrID FROM PASSENGER WHERE UserID = ? ORDER BY PsgrID DESC LIMIT 1");
    $stmt->bind_param("s", $userId);
    
    if (!$stmt->execute()) {
        throw new Exception("Error fetching PsgrID: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception("No passenger found with UserID: " . $userId);
    }
    
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['PsgrID'];
}

// Start transaction
$connMe->begin_transaction();

try {
    // Get the form data from the JSON  (user input)
    $formData = $data['formData'];
    $isExistingUser = $data['isExistingUser'];
    
    // Convert gender to single character (M/F)
    $gender = strtoupper($formData['gender']) === 'MALE' ? 'M' : 'F';
    
    if (!$isExistingUser) {
        // Insert into USER table first
        $userStmt = $connMe->prepare("INSERT INTO USER (FullName, EmailAddress, EmailSecCode, PhoneNo, UserType, BirthDate, Gender, SecQues1, SecQues2, MatricNo) VALUES (?, UPPER(?), ?, ?, UPPER(?), ?, ?, ?, ?, ?)");
        
        // Create variables first
        $fullName = strtoupper($formData['fullName']);
        $emailChecked = $formData['emailChecked'];
        $emailSecCode = $formData['emailSecCode'];
        $phoneNo = $formData['phoneChecked'];
        $userType = $formData['userType'];
        $birthDate = $formData['birthDate'];
        $secQues1 = $formData['secQues1'];
        $secQues2 = $formData['secQues2'];
        $matricNoDisplay = !empty($formData['matricNoDisplay']) ? strtoupper($formData['matricNoDisplay']) : null;

        $userStmt->bind_param("ssssssssss", 
            $fullName,
            $emailChecked,
            $emailSecCode,
            $phoneNo,
            $userType,
            $birthDate,
            $gender,
            $secQues1,
            $secQues2,
            $matricNoDisplay
        );
        
        if (!$userStmt->execute()) {
            throw new Exception("Error inserting into USER table: " . $userStmt->error);
        }
        
        $userStmt->close();
        
        // Get the UserID that was generated by the trigger
        $userId = getUserIDByEmail($connMe, $formData['emailChecked']);
        
    } else {
        // Get existing USER ID
        $userId = getUserIDByEmail($connMe, $formData['emailChecked']);

        // Update UserType to include PASSENGER role
        $updateTypeStmt = $connMe->prepare("UPDATE USER SET UserType = CONCAT(UserType, ' PASSENGER') 
                                          WHERE UserID = ? AND UserType NOT LIKE '%PASSENGER%'");
        
        if (!$updateTypeStmt) {
            throw new Exception("Error preparing update statement: " . $connMe->error);
        }
        
        $updateTypeStmt->bind_param("s", $userId);
        
        if (!$updateTypeStmt->execute()) {
            throw new Exception("Error updating UserType: " . $updateTypeStmt->error);
        }
        
        $updateTypeStmt->close();
    }

    // Verify that we have a valid UserID before proceeding
    if (!$userId) {
        throw new Exception("Invalid UserID");
    }

    // Insert into PASSENGER table
    $psgrStmt = $connMe->prepare("INSERT INTO PASSENGER (UserID, Username, Password, FavPickUpLoc, FavDropOffLoc, Role) VALUES (?, ?, ?, UPPER(?), UPPER(?), UPPER(?))");
    
    if (!$psgrStmt) {
        throw new Exception("Error preparing PASSENGER statement: " . $connMe->error);
    }
    
    $psgrStmt->bind_param("ssssss",
        $userId,
        $formData['username'],
        $formData['password'],
        $formData['favPickUpLoc'],
        $formData['favDropOffLoc'],
        $formData['role']
    );
    
    if (!$psgrStmt->execute()) {
        throw new Exception("Error inserting into PASSENGER table: " . $psgrStmt->error);
    }
    
    $psgrStmt->close();

    // Get the PsgrID after successful insertion
    $psgrId = getPsgrIDByUserID($connMe, $userId);

    // If we got here, everything worked, so commit the transaction
    $connMe->commit();
    
    echo json_encode([
        'status' => 'success',
        'userId' => $userId,
        'psgrId' => $psgrId
    ]);

} catch (Exception $e) {
    // Something went wrong, rollback the transaction
    $connMe->rollback();
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

$connMe->close();
?>