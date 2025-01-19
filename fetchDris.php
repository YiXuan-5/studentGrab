<?php
session_start();
include 'dbConnection.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set header to return JSON
header('Content-Type: application/json');

// Get the JSON data from the request
$rawData = file_get_contents('php://input');
error_log("Raw input data: " . $rawData);

$data = json_decode($rawData, true);
error_log("Decoded data: " . print_r($data, true));

// Validate input data
if (!$data || !isset($data['criteria'])) {
    echo json_encode(['error' => 'Invalid input data']);
    exit;
}

// Prepare the SQL query based on the criteria
$criteria = $data['criteria'];

// Only set these variables if not loading all records
if ($criteria !== 'all') {
    $driverID = isset($data['driverID']) ? $data['driverID'] : '';
    $username = isset($data['username']) ? $data['username'] : '';
    
    //Convert gender value in input field to M or F to ease in searching database
    if (isset($data['gender'])) {
        if ($data['gender'] === 'Male') {
            $gender = 'M';
        } else if ($data['gender'] === 'Female') {
            $gender = 'F';
        } else {
            $gender = '';
        }
    } else {
        $gender = '';
    }
    
    $fullName = isset($data['fullName']) ? strtoupper($data['fullName']) : '';
    $availability = isset($data['availability']) ? $data['availability'] : '';
}

$results = [];

try {
    if ($criteria === 'driverID' && !empty($driverID)) {
        $query = "
            SELECT d.DriverID, d.UserID, d.Username, d.StickerExpDate,
                   u.FullName, u.ProfilePicture
            FROM DRIVER d
            INNER JOIN USER u ON d.UserID = u.UserID
            WHERE d.DriverID = ?
            ORDER BY d.DriverID ASC
        ";
        
        $stmt = $connMe->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $connMe->error);
        }
        
        $stmt->bind_param("s", $driverID);
        error_log("Searching for DriverID: " . $driverID);
        
    } else if ($criteria === 'username' && !empty($username)) {
        $query = "
            SELECT d.DriverID, d.UserID, d.Username, d.StickerExpDate,
                   u.FullName, u.ProfilePicture
            FROM DRIVER d
            INNER JOIN USER u ON d.UserID = u.UserID
            WHERE UPPER(d.Username) LIKE UPPER(CONCAT('%', ?, '%'))
            ORDER BY d.Username ASC
        ";
        
        $stmt = $connMe->prepare($query);
        $stmt->bind_param("s", $username);
        error_log("Searching for Username: " . $username);
        
    } else if ($criteria === 'gender' && !empty($gender)) {
        $query = "
            SELECT d.DriverID, d.UserID, d.Username, d.StickerExpDate,
                   u.FullName, u.ProfilePicture
            FROM DRIVER d
            INNER JOIN USER u ON d.UserID = u.UserID
            WHERE u.Gender = ?
            ORDER BY d.DriverID ASC
        ";
        
        $stmt = $connMe->prepare($query);
        $stmt->bind_param("s", $gender);
        error_log("Searching for Gender: " . $gender);
        
    } else if ($criteria === 'fullName' && !empty($fullName)) {
        $query = "
            SELECT d.DriverID, d.UserID, d.Username, d.StickerExpDate,
                   u.FullName, u.ProfilePicture
            FROM DRIVER d
            INNER JOIN USER u ON d.UserID = u.UserID
            WHERE u.FullName LIKE CONCAT('%', ?, '%')
            ORDER BY u.FullName ASC
        ";
        
        $stmt = $connMe->prepare($query);
        $stmt->bind_param("s", $fullName);
        error_log("Searching for Full Name: " . $fullName);
        
    } else if ($criteria === 'stickerExpDate') {
        $query = "
            SELECT d.DriverID, d.UserID, d.Username, d.StickerExpDate,
                   u.FullName, u.ProfilePicture
            FROM DRIVER d
            INNER JOIN USER u ON d.UserID = u.UserID
            ORDER BY d.StickerExpDate ASC
        ";
        
        $stmt = $connMe->prepare($query);
        error_log("Searching by Sticker Expiry Date");
        
    } else if ($criteria === 'availability' && !empty($availability)) {
        $availabilityValue = ($availability === 'Available') ? 'AVAILABLE' : 'NOT AVAILABLE';
        $query = "
            SELECT d.DriverID, d.UserID, d.Username, d.StickerExpDate,
                   u.FullName, u.ProfilePicture
            FROM DRIVER d
            INNER JOIN USER u ON d.UserID = u.UserID
            WHERE d.Availability = ?
            ORDER BY d.DriverID ASC
        ";
        
        $stmt = $connMe->prepare($query);
        $stmt->bind_param("s", $availabilityValue);
        error_log("Searching for Availability: " . $availabilityValue);
    } else if ($criteria === 'all') {
        $query = "
            SELECT d.DriverID, d.UserID, d.Username, d.StickerExpDate,
                   u.FullName, u.ProfilePicture
            FROM DRIVER d
            INNER JOIN USER u ON d.UserID = u.UserID
            ORDER BY d.DriverID ASC
        ";
        error_log("SQL Query for all drivers: " . $query);

        $stmt = $connMe->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $connMe->error);
        }

        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            if (isset($row['ProfilePicture'])) {
                $row['ProfilePicture'] = base64_encode($row['ProfilePicture']);
            }
            $results[] = $row;
        }
        
        error_log("Query results for all drivers: " . print_r($results, true));

        if (empty($results)) {
            echo json_encode(['message' => 'No drivers found']);
            exit;
        }
    } else if ($criteria === 'stickerExpiry') {
        $query = "
            SELECT d.DriverID, d.UserID, d.Username, 
                   u.FullName, u.ProfilePicture, d.StickerExpDate
            FROM DRIVER d
            INNER JOIN USER u ON d.UserID = u.UserID
            WHERE d.StickerExpDate BETWEEN ? AND ?
            ORDER BY d.DriverID ASC
        ";
        $stmt = $connMe->prepare($query);
        $stmt->bind_param("ss", $data['startDate'], $data['endDate']);
    } else if ($criteria === 'matricNo' && !empty($data['matricNo'])) {
        $query = "
            SELECT d.DriverID, d.UserID, d.Username, d.StickerExpDate,
                   u.FullName, u.ProfilePicture
            FROM DRIVER d
            INNER JOIN USER u ON d.UserID = u.UserID
            WHERE u.MatricNo LIKE UPPER(CONCAT('%', ?, '%'))
            ORDER BY u.MatricNo ASC
        ";
        
        $stmt = $connMe->prepare($query);
        $stmt->bind_param("s", $data['matricNo']);
        error_log("Searching for Matric No: " . $data['matricNo']);
        
    } else if ($criteria === 'status' && !empty($data['status'])) {
        $query = "
            SELECT d.DriverID, d.UserID, d.Username, d.StickerExpDate,
                   u.FullName, u.ProfilePicture
            FROM DRIVER d
            INNER JOIN USER u ON d.UserID = u.UserID
            WHERE u.Status = ?
            ORDER BY d.DriverID ASC
        ";
        
        $stmt = $connMe->prepare($query);
        $stmt->bind_param("s", $data['status']);
        error_log("Searching for Status: " . $data['status']);
    }

    if (isset($stmt) && $criteria !== 'all') {
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            if (isset($row['ProfilePicture'])) {
                $row['ProfilePicture'] = base64_encode($row['ProfilePicture']);
            }
            $results[] = $row;
        }
    }
    
    echo json_encode($results);
    
} catch (Exception $e) {
    error_log("Error occurred: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
}
?> 