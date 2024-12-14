<?php
header('Content-Type: application/json');

// Connect to the database
include 'dbConnection.php'; // Replace with your actual database connection file

// Get JSON input
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// Extract email and security code
$email = $data['email'] ?? '';
$securityCode = $data['securityCode'] ?? '';

$response = [];
//Check the email and security code are valid or not that entered in registration page
if (!empty($email) && !empty($securityCode)) {
    // Query to validate security code based on email
    $stmt = $connMe->prepare("SELECT * FROM USER WHERE EmailAddress = ? AND EmailSecCode = ?");
    $stmt->bind_param("ss", $email, $securityCode);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $response['status'] = 'valid';
    } else {
        $response['status'] = 'invalid';
    }

    $stmt->close();
} else {
    $response['status'] = 'error';
    $response['message'] = 'Invalid input.';
}

// Send response back to the client
echo json_encode($response);
$connMe->close();
?>
