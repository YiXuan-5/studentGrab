<?php
session_start();
include 'dbConnection.php';

// Check if user is logged in as admin
if (!isset($_SESSION['AdminID'])) {
    echo json_encode(['error' => 'Not authorized']);
    exit;
}

// Get and decode the JSON data
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

if (!$data) {
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

try {
    $query = "SELECT a.LogID, a.UserID, u.FullName, a.UserTypeForLog, a.Action, a.Status, 
                    a.IPAddress, a.DeviceInfo, a.TimeStamp 
             FROM AUDIT_LOG a
             LEFT JOIN USER u ON a.UserID = u.UserID 
             WHERE 1=1";
    $params = [];
    $types = "";

    // Add conditions based on search criteria
    switch ($data['criteria']) {
        case 'fullName':
            if (!empty($data['fullName'])) {
                $query .= " AND UPPER(u.FullName) LIKE UPPER(?)";
                $params[] = "%" . $data['fullName'] . "%";
                $types .= "s";
            }
            break;

        case 'userID':
            if (!empty($data['userID'])) {
                $query .= " AND a.UserID = ?";
                $params[] = $data['userID'];
                $types .= "s";
            }
            break;

        case 'userType':
            if (!empty($data['userType'])) {
                $query .= " AND a.UserTypeForLog = ?";
                $params[] = $data['userType'];
                $types .= "s";
            }
            break;

        case 'action':
            if (!empty($data['action'])) {
                $query .= " AND a.Action = ?";
                $params[] = $data['action'];
                $types .= "s";
            }
            break;

        case 'status':
            if (!empty($data['status'])) {
                $query .= " AND a.Status = ?";
                $params[] = $data['status'];
                $types .= "s";
            }
            break;

        case 'all':
            // No additional conditions needed
            break;

        default:
            throw new Exception("Invalid search criteria");
    }

    // Add ORDER BY clause to sort by UserID by default
    $query .= " ORDER BY a.LogID ASC";

    $stmt = $connMe->prepare($query);

    // Bind parameters if there are any
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        throw new Exception("Error executing query: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $logs = [];

    while ($row = $result->fetch_assoc()) {
        // Format timestamp for consistent display
        $row['TimeStamp'] = date('Y-m-d H:i:s', strtotime($row['TimeStamp']));
        $logs[] = $row;
    }

    echo json_encode($logs);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

$connMe->close();
?> 