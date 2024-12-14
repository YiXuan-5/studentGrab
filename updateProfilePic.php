<?php
session_start();
include 'dbConnection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['UserID'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}
//Update profile picture
try {
    // Check if the file is uploaded
    if (!isset($_FILES['profilePic'])) {
        throw new Exception('No file uploaded');
    }

    $file = $_FILES['profilePic'];
    
    // Validate file
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Invalid file type. Only JPG, PNG and GIF are allowed');
    }

    if ($file['size'] > 5 * 1024 * 1024) { // 5MB
        throw new Exception('File size must be less than 5MB');
    }

    // Read file content
    $imageData = file_get_contents($file['tmp_name']);

    // Update database
    $stmt = $connMe->prepare("UPDATE USER SET ProfilePicture = ? WHERE UserID = ?");
    $stmt->bind_param("ss", $imageData, $_SESSION['UserID']);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update profile picture");
    }

    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

$connMe->close();
?> 