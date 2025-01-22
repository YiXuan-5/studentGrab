<?php
session_start();
include 'dbConnection.php';

// Check if user is logged in as admin
if (!isset($_SESSION['AdminID'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not authorized']);
    exit;
}

// Get and decode the JSON data
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

if (!$data) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

try {
    $query = "SELECT t.TrailID, t.TableName, t.RecordID, t.Action, t.UserID, 
                    CASE 
                        WHEN t.UserID LIKE 'A%' THEN (
                            SELECT u.FullName 
                            FROM USER u 
                            INNER JOIN ADMIN a ON a.AdminID = t.UserID 
                            WHERE u.UserID = a.UserID
                        )
                        WHEN t.UserID LIKE 'D%' THEN (
                            SELECT u.FullName 
                            FROM USER u 
                            INNER JOIN DRIVER d ON d.DriverID = t.UserID 
                            WHERE u.UserID = d.UserID
                        )
                        WHEN t.UserID LIKE 'P%' THEN (
                            SELECT u.FullName 
                            FROM USER u 
                            INNER JOIN PASSENGER p ON p.PsgrID = t.UserID 
                            WHERE u.UserID = p.UserID
                        )
                        ELSE NULL
                    END as FullName,
                    t.OldData, t.NewData, t.TimeStamp 
             FROM AUDIT_TRAIL t
             WHERE 1=1";
    $params = [];
    $types = "";

    // Add conditions based on search criteria
    switch ($data['criteria']) {
        case 'trailID':
            if (!empty($data['trailID'])) {
                $query .= " AND t.TrailID = ?";
                $params[] = $data['trailID'];
                $types .= "i";
            }
            break;

        case 'tableName':
            if (!empty($data['tableName'])) {
                $query .= " AND t.TableName = ?";
                $params[] = $data['tableName'];
                $types .= "s";
            }
            break;

        case 'action':
            if (!empty($data['action'])) {
                $query .= " AND t.Action = ?";
                $params[] = $data['action'];
                $types .= "s";
            }
            break;

        case 'userID':
            if (!empty($data['userID'])) {
                $query .= " AND t.UserID = ?";
                $params[] = $data['userID'];
                $types .= "s";
            }
            break;

        case 'all':
            // No additional conditions needed
            break;

        default:
            header('Content-Type: application/json');
            echo json_encode(['error' => "Invalid search criteria"]);
            exit;
    }

    // Add ORDER BY clause to sort by TrailID by default
    $query .= " ORDER BY t.TrailID ASC";

    $stmt = $connMe->prepare($query);

    // Bind parameters if there are any
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        throw new Exception("Error executing query: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $trails = [];

    while ($row = $result->fetch_assoc()) {
        // Format timestamp for consistent display
        $row['TimeStamp'] = date('Y-m-d H:i:s', strtotime($row['TimeStamp']));
        
        // Convert JSON strings to formatted strings for display
        $row['OldData'] = ($row['OldData'] !== null && $row['OldData'] !== '') 
            ? json_encode(json_decode($row['OldData']), JSON_PRETTY_PRINT) 
            : '-';
        $row['NewData'] = ($row['NewData'] !== null && $row['NewData'] !== '') 
            ? json_encode(json_decode($row['NewData']), JSON_PRETTY_PRINT) 
            : '-';
        
        $trails[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($trails);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}

$connMe->close();
?> 