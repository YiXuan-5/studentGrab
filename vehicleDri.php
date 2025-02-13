<?php
session_start();
include 'dbConnection.php';
include 'auditTrail.php';

header('Content-Type: application/json');

if (!isset($_SESSION['DriverID'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$driverID = $_SESSION['DriverID'];

// Add this function to check and update driver availability
function updateDriverAvailability($driverID, $connMe) {
    // Check if all vehicles are inactive
    $stmt = $connMe->prepare("SELECT COUNT(*) as activeCount FROM VEHICLE WHERE DriverID = ? AND VehicleStatus = 'ACTIVE'");
    $stmt->bind_param("s", $driverID);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    // If no active vehicles, set driver availability to NOT AVAILABLE
    if ($row['activeCount'] == 0) {
        $stmt = $connMe->prepare("UPDATE DRIVER SET Availability = 'NOT AVAILABLE' WHERE DriverID = ?");
        $stmt->bind_param("s", $driverID);
        $stmt->execute();
    }
}

try {
    $connMe->begin_transaction();

    if ($data['operation'] === 'add') {
        // First, set all existing vehicles to INACTIVE
        $stmt = $connMe->prepare("UPDATE VEHICLE SET VehicleStatus = 'INACTIVE' WHERE DriverID = ?");
        $stmt->bind_param("s", $driverID);
        if (!$stmt->execute()) {
            throw new Exception("Failed to update existing vehicles");
        }

        // Then add the new vehicle (no need to specify VehicleStatus since it's ACTIVE by default)
        $stmt = $connMe->prepare("INSERT INTO VEHICLE (DriverID, Model, PlateNo, Color, AvailableSeat, YearManufacture) VALUES (?, UPPER(?), UPPER(?), UPPER(?), ?, ?)");
        $stmt->bind_param("ssssii", 
            $driverID,
            $data['model'],
            $data['plateNo'],
            $data['color'],
            $data['availableSeat'],
            $data['yearManufacture']
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to add vehicle");
        }

        //Get the new vehicle ID after insert
        $stmt = $connMe->prepare("SELECT VhcID FROM VEHICLE WHERE DriverID = ? ORDER BY VhcID DESC LIMIT 1");
        $stmt->bind_param("s", $driverID);
        $stmt->execute();
        $result = $stmt->get_result();
        $newVehicle = $result->fetch_assoc();
        $newVhcId = $newVehicle['VhcID'];
        
        // Log the INSERT operation
        logAuditTrail(
            "VEHICLE",
            $newVhcId,
            "INSERT",
            $driverID,
            null,
            [
                "DriverID" => $driverID,
                "Model" => strtoupper($data['model']),
                "PlateNo" => strtoupper($data['plateNo']),
                "Color" => strtoupper($data['color']),
                "AvailableSeat" => $data['availableSeat'],
                "YearManufacture" => $data['yearManufacture'],
                "VehicleStatus" => "ACTIVE"
            ]
        );

    } else if ($data['operation'] === 'update') {
        // Get old vehicle data before update
        $stmt = $connMe->prepare("SELECT * FROM VEHICLE WHERE VhcID = ? AND DriverID = ?");
        $stmt->bind_param("ss", $data['vhcId'], $driverID);
        $stmt->execute();
        $oldVehicle = $stmt->get_result()->fetch_assoc();

        // If setting to active, first deactivate all other vehicles
        if (strtoupper($data['status']) === 'ACTIVE') {
            $stmt = $connMe->prepare("UPDATE VEHICLE SET VehicleStatus = 'INACTIVE' WHERE DriverID = ? AND VhcID != ?");
            $stmt->bind_param("ss", $driverID, $data['vhcId']);
            if (!$stmt->execute()) {
                throw new Exception("Failed to update other vehicles' status");
            }
        }

        // Update vehicle information
        $stmt = $connMe->prepare("UPDATE VEHICLE SET 
            VehicleStatus = ?, 
            Model = UPPER(?), 
            PlateNo = UPPER(?), 
            Color = UPPER(?), 
            AvailableSeat = ?, 
            YearManufacture = ? 
            WHERE VhcID = ? AND DriverID = ?");
        
        $status = strtoupper($data['status']);
        $stmt->bind_param("ssssiiss",
            $status,
            $data['model'],
            $data['plateNo'],
            $data['color'],
            $data['availableSeat'],
            $data['yearManufacture'],
            $data['vhcId'],
            $driverID
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update vehicle information");
        }

        // Track only changed fields
        $changedFields = [];
        $newFields = [];
        
        if ($oldVehicle['VehicleStatus'] !== $status) {
            $changedFields['VehicleStatus'] = $oldVehicle['VehicleStatus'];
            $newFields['VehicleStatus'] = $status;
        }
        if (strtoupper($oldVehicle['Model']) !== strtoupper($data['model'])) {
            $changedFields['Model'] = $oldVehicle['Model'];
            $newFields['Model'] = strtoupper($data['model']);
        }
        if (strtoupper($oldVehicle['PlateNo']) !== strtoupper($data['plateNo'])) {
            $changedFields['PlateNo'] = $oldVehicle['PlateNo'];
            $newFields['PlateNo'] = strtoupper($data['plateNo']);
        }
        if (strtoupper($oldVehicle['Color']) !== strtoupper($data['color'])) {
            $changedFields['Color'] = $oldVehicle['Color'];
            $newFields['Color'] = strtoupper($data['color']);
        }
        if ($oldVehicle['AvailableSeat'] != $data['availableSeat']) {
            $changedFields['AvailableSeat'] = $oldVehicle['AvailableSeat'];
            $newFields['AvailableSeat'] = $data['availableSeat'];
        }
        if ($oldVehicle['YearManufacture'] != $data['yearManufacture']) {
            $changedFields['YearManufacture'] = $oldVehicle['YearManufacture'];
            $newFields['YearManufacture'] = $data['yearManufacture'];
        }

        // Log the UPDATE operation only if fields changed
        if (!empty($changedFields)) {
            logAuditTrail(
                "VEHICLE",
                $data['vhcId'],
                "UPDATE",
                $driverID,
                $changedFields,
                $newFields
            );
        }

        updateDriverAvailability($driverID, $connMe);

    } else if ($data['operation'] === 'delete') {
        // Get vehicle data before deletion
        $stmt = $connMe->prepare("SELECT * FROM VEHICLE WHERE VhcID = ? AND DriverID = ?");
        $stmt->bind_param("ss", $data['vhcId'], $driverID);
        $stmt->execute();
        $deletedVehicle = $stmt->get_result()->fetch_assoc();

        $stmt = $connMe->prepare("DELETE FROM VEHICLE WHERE VhcID = ? AND DriverID = ?");
        $stmt->bind_param("ss", $data['vhcId'], $driverID);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete vehicle");
        }

        // Log the DELETE operation
        logAuditTrail(
            "VEHICLE",
            $data['vhcId'],
            "DELETE",
            $driverID,
            $deletedVehicle,
            null
        );

        updateDriverAvailability($driverID, $connMe);
    }

    $connMe->commit();
    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    $connMe->rollback();
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

$connMe->close();
?> 