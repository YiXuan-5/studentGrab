<?php
include 'dbConnection.php';

header('Content-Type: application/json');

// Get and decode the JSON data
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

try {
    // Check if email and security code match
    $stmt = $connMe->prepare("SELECT u.UserID 
                             FROM USER u 
                             JOIN DRIVER d ON u.UserID = d.UserID 
                             WHERE UPPER(u.EmailAddress) = UPPER(?) 
                             AND u.EmailSecCode = ?");
    
    if (!$stmt) {
        throw new Exception("Error preparing statement: " . $connMe->error);
    }
    
    $stmt->bind_param("ss", 
        $data['email'],
        $data['securityCode']
    );
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['status' => 'valid']);
    } else {
        echo json_encode([
            'status' => 'invalid',
            'message' => 'Invalid email or security code'
        ]);
    }
    
    $stmt->close();
    $connMe->close();

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?> 