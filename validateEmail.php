<?php
include 'dbConnection.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'];
$userType = $data['userType'];
$currentEmail = $data['currentEmail'] ?? '';

try {
    // Check if email exists in USER table, excluding the current email
    $stmt = $connMe->prepare("SELECT EmailAddress FROM USER WHERE EmailAddress = UPPER(?) AND EmailAddress != UPPER(?)");
    $stmt->bind_param("ss", $email, $currentEmail);
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