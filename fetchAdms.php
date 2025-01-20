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
    $adminID = isset($data['adminID']) ? $data['adminID'] : '';
    $username = isset($data['username']) ? $data['username'] : '';
    $department = isset($data['department']) ? $data['department'] : '';
    
    //Convert gender value in input field to M or F
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
}

$results = [];
$currentAdminID = $_SESSION['AdminID']; // Get current admin's ID

try {
    if ($criteria === 'adminID' && !empty($adminID)) {
        $query = "
            SELECT a.AdminID, a.UserID, a.Username, a.Department, a.Position,
                   u.FullName, u.ProfilePicture, u.Status, a.Position,
                   u.EmailAddress, u.PhoneNo, u.BirthDate, u.Gender
            FROM ADMIN a
            INNER JOIN USER u ON a.UserID = u.UserID
            WHERE a.AdminID = ? AND a.AdminID != ?
            ORDER BY a.AdminID ASC
        ";
        
        $stmt = $connMe->prepare($query);
        $stmt->bind_param("ss", $adminID, $currentAdminID);
        
    } else if ($criteria === 'username' && !empty($username)) {
        $query = "
            SELECT a.AdminID, a.UserID, a.Username, a.Department, a.Position,
                   u.FullName, u.ProfilePicture, u.Status, a.Position,
                   u.EmailAddress, u.PhoneNo, u.BirthDate, u.Gender
            FROM ADMIN a
            INNER JOIN USER u ON a.UserID = u.UserID
            WHERE UPPER(a.Username) LIKE UPPER(CONCAT('%', ?, '%'))
                AND a.AdminID != ?
            ORDER BY a.Username ASC
        ";
        
        $stmt = $connMe->prepare($query);
        $stmt->bind_param("ss", $username, $currentAdminID);
        
    } else if ($criteria === 'department' && !empty($department)) {
        $query = "
            SELECT a.AdminID, a.UserID, a.Username, a.Department, a.Position,
                   u.FullName, u.ProfilePicture, u.Status, a.Position,
                   u.EmailAddress, u.PhoneNo, u.BirthDate, u.Gender
            FROM ADMIN a
            INNER JOIN USER u ON a.UserID = u.UserID
            WHERE a.Department = ? AND a.AdminID != ?
            ORDER BY a.AdminID ASC
        ";
        
        $stmt = $connMe->prepare($query);
        $stmt->bind_param("ss", $department, $currentAdminID);
        
    } else if ($criteria === 'gender' && !empty($gender)) {
        $query = "
            SELECT a.AdminID, a.UserID, a.Username, a.Department, a.Position,
                   u.FullName, u.ProfilePicture, u.Status, a.Position,
                   u.EmailAddress, u.PhoneNo, u.BirthDate, u.Gender
            FROM ADMIN a
            INNER JOIN USER u ON a.UserID = u.UserID
            WHERE u.Gender = ? AND a.AdminID != ?
            ORDER BY a.AdminID ASC
        ";
        
        $stmt = $connMe->prepare($query);
        $stmt->bind_param("ss", $gender, $currentAdminID);
        
    } else if ($criteria === 'fullName' && !empty($fullName)) {
        $query = "
            SELECT a.AdminID, a.UserID, a.Username, a.Department, a.Position,
                   u.FullName, u.ProfilePicture, u.Status, a.Position,
                   u.EmailAddress, u.PhoneNo, u.BirthDate, u.Gender
            FROM ADMIN a
            INNER JOIN USER u ON a.UserID = u.UserID
            WHERE u.FullName LIKE CONCAT('%', ?, '%')
                AND a.AdminID != ?
            ORDER BY u.FullName ASC
        ";
        
        $stmt = $connMe->prepare($query);
        $stmt->bind_param("ss", $fullName, $currentAdminID);
        
    } else if ($criteria === 'status' && !empty($data['status'])) {
        $query = "
            SELECT a.AdminID, a.UserID, a.Username, a.Department, a.Position,
                   u.FullName, u.ProfilePicture, u.Status, a.Position,
                   u.EmailAddress, u.PhoneNo, u.BirthDate, u.Gender
            FROM ADMIN a
            INNER JOIN USER u ON a.UserID = u.UserID
            WHERE u.Status = ? AND a.AdminID != ?
            ORDER BY a.AdminID ASC
        ";
        
        $stmt = $connMe->prepare($query);
        $stmt->bind_param("ss", $data['status'], $currentAdminID);
        
    } else if ($criteria === 'all') {
        $query = "
            SELECT a.AdminID, a.UserID, a.Username, a.Department, a.Position,
                   u.FullName, u.ProfilePicture, u.Status, a.Position,
                   u.EmailAddress, u.PhoneNo, u.BirthDate, u.Gender
            FROM ADMIN a
            INNER JOIN USER u ON a.UserID = u.UserID
            WHERE a.AdminID != ?
            ORDER BY a.AdminID ASC
        ";
        
        $stmt = $connMe->prepare($query);
        $stmt->bind_param("s", $currentAdminID);
    }

    if (isset($stmt)) {
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