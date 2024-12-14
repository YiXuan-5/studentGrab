<?php
session_start();
include 'dbConnection.php';

// Get the JSON data from the request
$data = json_decode(file_get_contents('php://input'), true);

// Prepare the SQL query based on the criteria
$criteria = $data['criteria'];
$passengerID = $data['psgrID'];
$results = [];

try {
    if ($criteria === 'psgrID' && !empty($passengerID)) {
        $stmt = $connMe->prepare("SELECT * FROM PASSENGER WHERE PsgrID = ?");
        $stmt->bind_param("s", $passengerID);
    }
    // Add more conditions for other criteria as needed

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $results[] = $row; // Collect results
    }

    echo json_encode($results); // Return results as JSON
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?> 