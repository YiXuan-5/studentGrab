<?php
include 'dbConnection.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$phoneNo = $data['phoneNo'];
$userType = $data['userType'];
$currentPhoneNo = $data['currentPhoneNo'] ?? '';

try {
    // Check if phone number exists, excluding the current phone number
    $stmt = $connMe->prepare("SELECT PhoneNo FROM USER WHERE PhoneNo = ? AND PhoneNo != ?");
    $stmt->bind_param("ss", $phoneNo, $currentPhoneNo);
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