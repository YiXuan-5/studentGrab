<?php
include 'dbConnection.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Get the POST data
$data = json_decode(file_get_contents("php://input"), true);
$licenseNo = $data['licenseNo'] ?? '';

if (empty($licenseNo)) {
    echo json_encode(["status" => "error", "message" => "License number is required"]);
    exit;
}

// Check if license number is exactly 8 digits
if (!preg_match('/^\d{8}$/', $licenseNo)) {
    echo json_encode(["status" => "error", "message" => "License number must be exactly 8 digits"]);
    exit;
}

// Query to check if the license number exists
$stmt = $connMe->prepare("SELECT DriverID FROM DRIVER WHERE LicenseNo = ?");
$stmt->bind_param("s", $licenseNo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // License number exists
    echo json_encode(["status" => "exists"]);
} else {
    // License number is available
    echo json_encode(["status" => "available"]);
}

$stmt->close();
$connMe->close();
?> 