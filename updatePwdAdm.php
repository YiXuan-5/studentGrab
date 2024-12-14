<?php
include 'dbConnection.php';

header('Content-Type: application/json');

// Get and decode the JSON data
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

try {
    // Prepare the update statement
    $stmt = $connMe->prepare("UPDATE ADMIN a 
                             JOIN USER u ON a.UserID = u.UserID 
                             SET a.Password = ? 
                             WHERE UPPER(u.EmailAddress) = UPPER(?)");
    
    if (!$stmt) {
        throw new Exception("Error preparing statement: " . $connMe->error);
    }
    
    $stmt->bind_param("ss", 
        $data['newPassword'],
        $data['email']
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Error updating password: " . $stmt->error);
    }
    
    if ($stmt->affected_rows === 0) {
        throw new Exception("No admin found with this email address");
    }
    
    $stmt->close();
    $connMe->close();
    
    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?> 