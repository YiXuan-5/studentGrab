<?php
include 'dbConnection.php';
include 'auditTrail.php';

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

// Function to get DriverID based on UserID
function getDriverIDByUserID($conn, $userId) {
    $stmt = $conn->prepare("SELECT DriverID FROM DRIVER WHERE UserID = ? ORDER BY DriverID DESC LIMIT 1");
    $stmt->bind_param("s", $userId);
    
    if (!$stmt->execute()) {
        throw new Exception("Error fetching DriverID: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception("No driver found with UserID: " . $userId);
    }
    
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['DriverID'];
}

// Function to get VehicleID based on DriverID
function getVehicleIDByDriverID($conn, $driverId) {
    $stmt = $conn->prepare("SELECT VhcID FROM VEHICLE WHERE DriverID = ? ORDER BY VhcID DESC LIMIT 1");
    $stmt->bind_param("s", $driverId);
    
    if (!$stmt->execute()) {
        throw new Exception("Error fetching VehicleID: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception("No vehicle found with DriverID: " . $driverId);
    }
    
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['VhcID'];
}

// Function to get EHailing License based on DriverID
function getEHailingLicenseByDriverID($conn, $driverId) {
    $stmt = $conn->prepare("SELECT EHailingLicense FROM DRIVER WHERE DriverID = ?");
    $stmt->bind_param("s", $driverId);
    
    if (!$stmt->execute()) {
        throw new Exception("Error fetching EHailing License: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception("No EHailing License found for DriverID: " . $driverId);
    }
    
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['EHailingLicense'];
}

// Start transaction
$connMe->begin_transaction();

try {
    $formData = $data['formData'];
    $isExistingUser = $data['isExistingUser'];
    
    // Convert gender to single character (M/F)
    $gender = strtoupper($formData['gender']) === 'MALE' ? 'M' : 'F';
    
    if (!$isExistingUser) {
        // Insert into USER table first
        $userStmt = $connMe->prepare("INSERT INTO USER (FullName, EmailAddress, EmailSecCode, PhoneNo, UserType, BirthDate, Gender, SecQues1, SecQues2, MatricNo) VALUES (?, UPPER(?), ?, ?, UPPER(?), ?, ?, ?, ?, UPPER(?))");
        
        // Create variables first
        $fullName = strtoupper($formData['fullName']);
        $email = $formData['emailChecked'];
        $emailSecCode = $formData['emailSecCode'];
        $phoneNo = $formData['phoneChecked'];  // Changed from phoneNo to phoneChecked
        $userType = $formData['userType'];
        $birthDate = $formData['birthDate'];
        $secQues1 = $formData['secQues1'];
        $secQues2 = $formData['secQues2'];
        $matricNo = $formData['matricNoDisplay'];
        
        $userStmt->bind_param("ssssssssss", 
            $fullName,
            $email,
            $emailSecCode,
            $phoneNo,
            $userType,
            $birthDate,
            $gender,
            $secQues1,
            $secQues2,
            $matricNo
        );
        
        if (!$userStmt->execute()) {
            throw new Exception("Error inserting into USER table: " . $userStmt->error);
        }
        
        $userStmt->close();
        
        // Get the UserID that was generated
        $userID = getUserIDByEmail($connMe, $formData['emailChecked']);
        
    } else {
        // Get existing USER ID
        $userID = getUserIDByEmail($connMe, $formData['emailChecked']);

        // Get current user data before update
        $stmt = $connMe->prepare("SELECT UserType FROM USER WHERE UserID = ?");
        $stmt->bind_param("s", $userID);
        $stmt->execute();
        $oldUserData = $stmt->get_result()->fetch_assoc();

        // Update UserType to include DRIVER role
        $updateTypeStmt = $connMe->prepare("UPDATE USER SET UserType = CONCAT(UserType, ' DRIVER') 
                                          WHERE UserID = ? AND UserType NOT LIKE '%DRIVER%'");
        
        if (!$updateTypeStmt) {
            throw new Exception("Error preparing update statement: " . $connMe->error);
        }
        
        $updateTypeStmt->bind_param("s", $userID);
        
        if (!$updateTypeStmt->execute()) {
            throw new Exception("Error updating UserType: " . $updateTypeStmt->error);
        }
        
        $updateTypeStmt->close();
    }

    // Verify that we have a valid UserID before proceeding
    if (!$userID) {
        throw new Exception("Invalid UserID");
    }

    // Insert into DRIVER table
    $driverStmt = $connMe->prepare("INSERT INTO DRIVER (UserID, Username, Password, LicenseNo, LicenseExpDate, StickerExpDate) VALUES (?, ?, ?, ?, ?, ?)");
    
    if (!$driverStmt) {
        throw new Exception("Error preparing DRIVER statement: " . $connMe->error);
    }
    
    // Create variables for driver data
    $username = $formData['username'];
    $password = $formData['password'];
    $licenseNo = $formData['licenseNo'];
    $licenseExpDate = $formData['licenseExpDate'];
    $stickerExpDate = $formData['stickerExpDate'];
    
    $driverStmt->bind_param("ssssss",
        $userID,
        $username,
        $password,
        $licenseNo,
        $licenseExpDate,
        $stickerExpDate
    );
    
    if (!$driverStmt->execute()) {
        throw new Exception("Error inserting into DRIVER table: " . $driverStmt->error);
    }
    
    $driverStmt->close();

    // Get the DriverID after successful insertion
    $driverID = getDriverIDByUserID($connMe, $userID);

    // Now log the new user creation with DriverID (for new users)
    if (!$isExistingUser) {
        $newUserData = [
            'FullName' => strtoupper($formData['fullName']),
            'EmailAddress' => strtoupper($formData['emailChecked']),
            'EmailSecCode' => $formData['emailSecCode'],
            'PhoneNo' => $formData['phoneChecked'],
            'UserType' => strtoupper($formData['userType']),
            'BirthDate' => $formData['birthDate'],
            'Gender' => $gender,
            'SecQues1' => $formData['secQues1'],
            'SecQues2' => $formData['secQues2'],
            'MatricNo' => strtoupper($formData['matricNoDisplay'])
        ];
        logAuditTrail("USER", $userID, "INSERT", $driverID, null, $newUserData);
    }
    
    // For existing users, update the UserType audit trail with DriverID
    if ($isExistingUser && !str_contains($oldUserData['UserType'], 'DRIVER')) {
        logAuditTrail(
            "USER",
            $userID,
            "UPDATE",
            $driverID,
            ['UserType' => $oldUserData['UserType']],
            ['UserType' => $oldUserData['UserType'] . ' DRIVER']
        );
    }

    // Log the new driver creation
    $newDriverData = [
        'UserID' => $userID,
        'Username' => $formData['username'],
        'Password' => $formData['password'],
        'LicenseNo' => $formData['licenseNo'],
        'LicenseExpDate' => $formData['licenseExpDate'],
        'StickerExpDate' => $formData['stickerExpDate']
    ];
    logAuditTrail("DRIVER", $driverID, "INSERT", $driverID, null, $newDriverData);

    // Insert into VEHICLE table
    $vehicleStmt = $connMe->prepare("INSERT INTO VEHICLE (DriverID, Model, PlateNo, Color, AvailableSeat, YearManufacture) VALUES (?, UPPER(?), UPPER(?), UPPER(?), ?, ?)");
    
    if (!$vehicleStmt) {
        throw new Exception("Error preparing VEHICLE statement: " . $connMe->error);
    }
    
    $vehicleStmt->bind_param("ssssii",
        $driverID,
        $formData['model'],
        $formData['plateNo'],
        $formData['color'],
        $formData['availableSeat'],
        $formData['yearManufacture']
    );
    
    if (!$vehicleStmt->execute()) {
        throw new Exception("Error inserting into VEHICLE table: " . $vehicleStmt->error);
    }
    
    $vehicleStmt->close();

    // Get the VehicleID and EHailing License after successful insertion
    $vehicleID = getVehicleIDByDriverID($connMe, $driverID);
    $ehailingLicense = getEHailingLicenseByDriverID($connMe, $driverID);

    // Log the new vehicle creation
    $newVehicleData = [
        'DriverID' => $driverID,
        'Model' => strtoupper($formData['model']),
        'PlateNo' => strtoupper($formData['plateNo']),
        'Color' => strtoupper($formData['color']),
        'AvailableSeat' => $formData['availableSeat'],
        'YearManufacture' => $formData['yearManufacture'],
        'VehicleStatus' => 'ACTIVE'
    ];
    logAuditTrail("VEHICLE", $vehicleID, "INSERT", $driverID, null, $newVehicleData);

    // If we got here, everything worked, so commit the transaction
    $connMe->commit();
    
    echo json_encode([
        'status' => 'success',
        'userId' => $userID,
        'driverId' => $driverID,
        'vehicleId' => $vehicleID,
        'ehailingLicense' => $ehailingLicense
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
