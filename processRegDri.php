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
        $userStmt = $connMe->prepare("INSERT INTO USER (FullName, EmailAddress, EmailSecCode, PhoneNo, UserType, BirthDate, Gender) VALUES (?, UPPER(?), ?, ?, UPPER(?), ?, ?)");
        
        $userStmt->bind_param("sssssss", 
            strtoupper($formData['fullName']),
            $formData['emailChecked'],
            $formData['emailSecCode'],
            $formData['phoneNo'],
            $formData['userType'],
            $formData['birthDate'],
            $gender
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
    
    $driverStmt->bind_param("ssssss",
        $userID,
        $formData['username'],
        $formData['password'],
        $formData['licenseNo'],
        $formData['licenseExpDate'],
        $formData['stickerExpDate']
    );
    
    if (!$driverStmt->execute()) {
        throw new Exception("Error inserting into DRIVER table: " . $driverStmt->error);
    }
    
    $driverStmt->close();

    // Get the DriverID after successful insertion
    $driverID = getDriverIDByUserID($connMe, $userID);

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
