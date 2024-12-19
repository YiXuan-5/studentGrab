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
error_log("Raw input data: " . $rawData); // Log raw input

$data = json_decode($rawData, true);
error_log("Decoded data: " . print_r($data, true)); // Log decoded data

// Validate input data
if (!$data || !isset($data['criteria'])) {
    echo json_encode(['error' => 'Invalid input data']);
    exit;
}

// Prepare the SQL query based on the criteria
$criteria = $data['criteria'];

//When loading all passengers, it still trying to access array keys that might not be set in the request data.
//So use if statement to check if the key is set before accessing it.
// Only set these variables if not loading all records
if ($criteria !== 'all') {
    $passengerID = isset($data['psgrID']) ? $data['psgrID'] : '';
    $role = isset($data['role']) ? strtoupper($data['role']) : '';
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
    $favPickUpLoc = isset($data['favPickUpLoc']) ? strtoupper($data['favPickUpLoc']) : '';
    $favDropOffLoc = isset($data['favDropOffLoc']) ? strtoupper($data['favDropOffLoc']) : '';
}

$results = [];

try {
    if ($criteria === 'psgrID' && !empty($passengerID)) {
        $query = "
            SELECT p.PsgrID, p.UserID, p.Username, 
                   u.FullName, u.ProfilePicture
            FROM PASSENGER p
            INNER JOIN USER u ON p.UserID = u.UserID
            WHERE p.PsgrID = ?
        ";
        error_log("SQL Query: " . $query); // Log the SQL query
        
        $stmt = $connMe->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $connMe->error);
        }
        
        $stmt->bind_param("s", $passengerID);
        error_log("Searching for PsgrID: " . $passengerID); // Log the passenger ID
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            // Convert ProfilePicture to base64 if it's a blob
            if (isset($row['ProfilePicture'])) {
                $row['ProfilePicture'] = base64_encode($row['ProfilePicture']);
            }
            $results[] = $row;
        }
        
        error_log("Query results: " . print_r($results, true)); // Log the results

        if (empty($results)) {
            echo json_encode(['message' => 'No results found']);
            exit;
        }
    }else if ($criteria === 'role' && !empty($role)) {
        $query = "
            SELECT p.PsgrID, p.UserID, p.Username, 
                   u.FullName, u.ProfilePicture
            FROM PASSENGER p
            INNER JOIN USER u ON p.UserID = u.UserID
            WHERE p.Role = ?
            ORDER BY p.PsgrID ASC
            ";
        error_log("SQL Query: " . $query); // Log the SQL query

        $stmt = $connMe->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $connMe->error);
        }
        
        $stmt->bind_param("s", $role);
        error_log("Searching for Role: " . $role); // Log the role
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            // Convert ProfilePicture to base64 if it's a blob
            if (isset($row['ProfilePicture'])) {
                $row['ProfilePicture'] = base64_encode($row['ProfilePicture']);
            }
            $results[] = $row;
        }
        
        error_log("Query results: " . print_r($results, true)); // Log the results

        if (empty($results)) {
            echo json_encode(['message' => 'No results found']);
            exit;
        }
    }else if ($criteria === 'username' && !empty($username)) {
        $query = "
            SELECT p.PsgrID, p.UserID, p.Username, 
                    u.FullName, u.ProfilePicture
            FROM PASSENGER p
            INNER JOIN USER u ON p.UserID = u.UserID
            WHERE UPPER(p.Username) LIKE UPPER(CONCAT('%', ?, '%'))
            ORDER BY p.Username ASC
            ";
        error_log("SQL Query: " . $query); // Log the SQL query

        $stmt = $connMe->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $connMe->error);
        }
        
        $stmt->bind_param("s", $username);
        error_log("Searching for Username: " . $username); // Log the username
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            // Convert ProfilePicture to base64 if it's a blob
            if (isset($row['ProfilePicture'])) {
                $row['ProfilePicture'] = base64_encode($row['ProfilePicture']);
            }
            $results[] = $row;
        }
        
        error_log("Query results: " . print_r($results, true)); // Log the results

        if (empty($results)) {
            echo json_encode(['message' => 'No results found']);
            exit;
        }
    }else if ($criteria === 'gender' && !empty($gender)) {
        $query = "
            SELECT p.PsgrID, p.UserID, p.Username, 
                   u.FullName, u.ProfilePicture
            FROM PASSENGER p
            INNER JOIN USER u ON p.UserID = u.UserID
            WHERE u.Gender = ?
            ORDER BY p.PsgrID ASC
            ";
        error_log("SQL Query: " . $query); // Log the SQL query

        $stmt = $connMe->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $connMe->error);
        }
        
        $stmt->bind_param("s", $gender);
        error_log("Searching for Gender: " . $gender); // Log the gender
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            // Convert ProfilePicture to base64 if it's a blob
            if (isset($row['ProfilePicture'])) {
                $row['ProfilePicture'] = base64_encode($row['ProfilePicture']);
            }
            $results[] = $row;
        }
        
        error_log("Query results: " . print_r($results, true)); // Log the results

        if (empty($results)) {
            echo json_encode(['message' => 'No results found']);
            exit;
        }
    }else if ($criteria === 'fullName' && !empty($fullName)) {
        $query = "
            SELECT p.PsgrID, p.UserID, p.Username, 
                    u.FullName, u.ProfilePicture
            FROM PASSENGER p
            INNER JOIN USER u ON p.UserID = u.UserID
            WHERE u.FullName LIKE CONCAT('%', ?, '%')
            ORDER BY u.FullName ASC
            ";
        error_log("SQL Query: " . $query); // Log the SQL query

        $stmt = $connMe->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $connMe->error);
        }
        
        $stmt->bind_param("s", $fullName);
        error_log("Searching for Full Name: " . $fullName); // Log the fullname
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            // Convert ProfilePicture to base64 if it's a blob
            if (isset($row['ProfilePicture'])) {
                $row['ProfilePicture'] = base64_encode($row['ProfilePicture']);
            }
            $results[] = $row;
        }
        
        error_log("Query results: " . print_r($results, true)); // Log the results

        if (empty($results)) {
            echo json_encode(['message' => 'No results found']);
            exit;
        }
    }else if ($criteria === 'pickupLocation' && !empty($favPickUpLoc)) {
        $query = "
            SELECT p.PsgrID, p.UserID, p.Username, 
                    u.FullName, u.ProfilePicture
            FROM PASSENGER p
            INNER JOIN USER u ON p.UserID = u.UserID
            WHERE p.FavPickUpLoc LIKE CONCAT('%', ?, '%')
            ORDER BY p.PsgrID ASC
            ";
        error_log("SQL Query: " . $query); // Log the SQL query

        $stmt = $connMe->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $connMe->error);
        }
        
        $stmt->bind_param("s", $favPickUpLoc);
        error_log("Searching for Favourite Pick Up Location: " . $favPickUpLoc); // Log the favourite pickup location
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            // Convert ProfilePicture to base64 if it's a blob
            if (isset($row['ProfilePicture'])) {
                $row['ProfilePicture'] = base64_encode($row['ProfilePicture']);
            }
            $results[] = $row;
        }
        
        error_log("Query results: " . print_r($results, true)); // Log the results

        if (empty($results)) {
            echo json_encode(['message' => 'No results found']);
            exit;
        }
    }else if ($criteria === 'dropoffLocation' && !empty($favDropOffLoc)) {
        $query = "
            SELECT p.PsgrID, p.UserID, p.Username, 
                    u.FullName, u.ProfilePicture
            FROM PASSENGER p
            INNER JOIN USER u ON p.UserID = u.UserID
            WHERE p.FavDropOffLoc LIKE CONCAT('%', ?, '%')
            ORDER BY p.PsgrID ASC
            ";
        error_log("SQL Query: " . $query); // Log the SQL query

        $stmt = $connMe->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $connMe->error);
        }
        
        $stmt->bind_param("s", $favDropOffLoc);
        error_log("Searching for Favourite Drop Off Location: " . $favDropOffLoc); // Log the favourite drop off location
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            // Convert ProfilePicture to base64 if it's a blob
            if (isset($row['ProfilePicture'])) {
                $row['ProfilePicture'] = base64_encode($row['ProfilePicture']);
            }
            $results[] = $row;
        }
        
        error_log("Query results: " . print_r($results, true)); // Log the results

        if (empty($results)) {
            echo json_encode(['message' => 'No results found']);
            exit;
        }
    } else if ($criteria === 'all') {
        $query = "
            SELECT p.PsgrID, p.UserID, p.Username, 
                   u.FullName, u.ProfilePicture
            FROM PASSENGER p
            INNER JOIN USER u ON p.UserID = u.UserID
            ORDER BY p.PsgrID ASC
        ";
        error_log("SQL Query for all passengers: " . $query);

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
        
        error_log("Query results for all passengers: " . print_r($results, true));

        if (empty($results)) {
            echo json_encode(['message' => 'No passengers found']);
            exit;
        }
    }
    
    echo json_encode($results);
    
} catch (Exception $e) {
    error_log("Error occurred: " . $e->getMessage()); // Log any errors
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
}
?> 