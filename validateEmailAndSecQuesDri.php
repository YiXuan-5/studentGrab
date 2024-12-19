<?php
include 'dbConnection.php';

header('Content-Type: application/json');

// Get and decode the JSON data
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

try {
    // Check if email and security answers match
    $stmt = $connMe->prepare("SELECT u.UserID 
                             FROM USER u 
                             JOIN DRIVER d ON u.UserID = d.UserID 
                             WHERE UPPER(u.EmailAddress) = UPPER(?) 
                             AND u.SecQues1 = ? 
                             AND u.SecQues2 = ?");
    
    if (!$stmt) {
        throw new Exception("Error preparing statement: " . $connMe->error);
    }
    
    $stmt->bind_param("sss", 
        $data['email'],
        $data['secQues1'],
        $data['secQues2']
    );
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['status' => 'valid']);
    } else {
        echo json_encode([
            'status' => 'invalid',
            'message' => 'Invalid email or security answers'
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