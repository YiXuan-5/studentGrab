<?php
include 'dbConnection.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$matricNoDisplay = $data['matricNoDisplay'];
$currentUserId = $data['currentUserId'] ?? null;

$stmt = $connMe->prepare("SELECT UserID FROM USER WHERE MatricNo = ? AND UserID != ?");
$stmt->bind_param("ss", $matricNoDisplay, $currentUserId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['status' => 'exists']);
} else {
    echo json_encode(['status' => 'not_exists']);
}

$stmt->close();
$connMe->close();
?> 