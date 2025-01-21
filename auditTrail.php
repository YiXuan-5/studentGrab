<?php

function logAuditTrail($tableName, $recordId, $action, $userId, $oldData = null, $newData = null) {
    global $connMe;
    
    try {
        // Validate action
        $validActions = ['INSERT', 'UPDATE', 'DELETE'];
        if (!in_array(strtoupper($action), $validActions)) {
            throw new Exception("Invalid action type: $action");
        }

        // Prepare the data
        $oldDataJson = null;
        $newDataJson = null;
        if ($oldData !== null) {
            $oldDataJson = json_encode($oldData);
        }
        if ($newData !== null) {
            $newDataJson = json_encode($newData);
        }

        // Check for JSON encoding errors
        if (($oldData !== null && $oldDataJson === false) || 
            ($newData !== null && $newDataJson === false)) {
            throw new Exception("JSON encoding failed");
        }

        // Prepare and execute the statement
        $stmt = $connMe->prepare("
            INSERT INTO AUDIT_TRAIL (TableName, RecordID, Action, UserID, OldData, NewData)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $connMe->error);
        }

        // Convert action to uppercase before binding
        $actionUpper = strtoupper($action);

        $stmt->bind_param(
            "ssssss",
            $tableName,
            $recordId,
            $actionUpper,
            $userId,
            $oldDataJson,
            $newDataJson
        );

        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $stmt->close();
        return true;

    } catch (Exception $e) {
        // Log the error (you might want to use a proper logging system)
        error_log("Audit Trail Error: " . $e->getMessage());
        return false;
    }
}


// Example usage:
/*
// For INSERT
logAuditTrail(
    "USER",
    $newUserId,
    "INSERT",
    $adminId,
    null,
    [
        "UserID" => $newUserId,
        "FullName" => $fullName,
        "Email" => $email
    ]
);

// For UPDATE
logAuditTrail(
    $conn,
    "USER",
    $userId,
    "UPDATE",
    $adminId,
    [
        "FullName" => $oldFullName,
        "Email" => $oldEmail
    ],
    [
        "FullName" => $newFullName,
        "Email" => $newEmail
    ]
);

// For DELETE
logAuditTrail(
    $conn,
    "USER",
    $userId,
    "DELETE",
    $adminId,
    [
        "UserID" => $userId,
        "FullName" => $fullName,
        "Email" => $email
    ],
    null
);
*/
?> 