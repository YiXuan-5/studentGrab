<?php
include 'dbConnection.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$username = $data['username'];
$userType = $data['userType'];
$currentUsername = $data['currentUsername'] ?? '';

try {
    // Check if username exists, excluding the current username
    $stmt = $connMe->prepare("SELECT Username FROM " . $userType . " WHERE Username = ? AND Username != ?");
    $stmt->bind_param("ss", $username, $currentUsername);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['status' => 'exists']);
    } else {
        echo json_encode(['status' => 'available']);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$connMe->close();
?>
