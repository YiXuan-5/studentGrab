<?php
session_start();
include 'dbConnection.php';

// Check if user is logged in
if (!isset($_SESSION['DriverID']) || !isset($_SESSION['UserID'])) {
    header("Location: loginDri.php");
    exit;
}

// Fetch user data
$driverID = $_SESSION['DriverID'];
$userID = $_SESSION['UserID'];

try {
    // Get user and driver information
    $stmt = $connMe->prepare("
        SELECT u.FullName, u.EmailAddress, u.PhoneNo, u.Gender, u.BirthDate, u.EmailSecCode, u.ProfilePicture,
               d.Username, d.Password, d.LicenseNo, d.LicenseExpDate, d.StickerExpDate, d.EHailingLicense, 
               d.Availability, d.CompletedRide
        FROM USER u
        JOIN DRIVER d ON u.UserID = d.UserID
        WHERE d.DriverID = ? AND u.UserID = ?
    ");
    
    $stmt->bind_param("ss", $driverID, $userID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("User not found");
    }
    
    $userData = $result->fetch_assoc();

    // Get active vehicle information
    $stmt = $connMe->prepare("
        SELECT VhcID, Model, PlateNo, Color, AvailableSeat, YearManufacture, VehicleStatus, VhcPicture
        FROM VEHICLE 
        WHERE DriverID = ? AND VehicleStatus = 'ACTIVE'
        LIMIT 1
    ");
    
    $stmt->bind_param("s", $driverID);
    $stmt->execute();
    $activeVehicle = $stmt->get_result()->fetch_assoc();

    // Get all other vehicles
    $stmt = $connMe->prepare("
        SELECT VhcID, Model, PlateNo, Color, AvailableSeat, YearManufacture, VehicleStatus, VhcPicture
        FROM VEHICLE 
        WHERE DriverID = ? AND VehicleStatus = 'INACTIVE'
        ORDER BY VhcID DESC
    ");
    
    $stmt->bind_param("s", $driverID);
    $stmt->execute();
    $otherVehicles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/fontawesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/solid.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            padding-top: 60px;
        }

        /* Navigation styles */
        .navbar {
            background-color: #4caf50;
            padding: 10px 20px;
            position: fixed;
            width: 100%;
            top: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            box-sizing: border-box;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: white;
            gap: 5px;
        }

        .nav-brand:hover {
            color: white;
        }

        .nav-brand i {
            font-size: 20px;
        }

        .nav-center {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            align-items: center;
            gap: 15px;
            color: white;
            font-size: 20px;
            font-weight: bold;
            white-space: nowrap;
        }

        .center-logo {
            height: 40px;
            width: auto;
            pointer-events: none;
        }

        .nav-items {
            display: flex;
            align-items: center;
        }

        .nav-item {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .nav-item:hover, .nav-item.active {
            background-color: #388e3c;
        }

        /* Container styles */
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        /* Profile section styles */
        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .profile-pic-container {
            position: relative;
            width: 200px;
            height: 200px;
            margin: 0 auto;
            border-radius: 50%;
            border: 3px solid #4caf50;
        }

        .profile-pic-wrapper {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            overflow: hidden;
        }

        .profile-pic {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .change-pic-btn {
            position: absolute;
            bottom: 0;
            right: 0;
            transform: translate(25%, 25%);
            background-color: #4caf50;
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .change-pic-btn:hover {
            background-color: #388e3c;
        }

        .profile-name {
            font-size: 24px;
            font-weight: bold;
            margin: 20px 0 10px;
        }

        /* Section styles */
        .profile-section {
            margin-bottom: 20px;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 20px;
            font-weight: bold;
            color: #4caf50;
        }

        /* Field styles */
        .profile-field {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        .field-label {
            font-weight: bold;
            color: #666;
        }

        .field-value {
            color: #333;
        }

        /* Vehicle section styles */
        .vehicle-container {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 10px;
            position: relative;
        }

        .vehicle-image-container {
            width: 300px;
            height: 300px;
            position: relative;
            border: 2px solid #4caf50;
            border-radius: 0;
            overflow: hidden;
            flex-shrink: 0;
            margin-top: 60px;
        }

        .vehicle-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .vehicle-info {
            flex: 1;
            min-width: 300px;
            margin-top: 60px;
        }

        .vehicle-actions {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
            z-index: 5;
        }

        .add-vehicle-btn {
            background-color: #4caf50;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-family: Arial, sans-serif;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .add-vehicle-btn i {
            font-size: 16px;
        }

        .show-more-btn {
            width: 100%;
            padding: 12px;
            margin-top: 20px;
            background-color: #f5f5f5;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-family: Arial, sans-serif;
            font-size: 16px;
            color: #333;
            transition: background-color 0.3s;
        }

        .show-more-btn:hover {
            background-color: #e0e0e0;
        }

        /* Button styles */
        .edit-btn, .delete-btn {
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 5px;
            font-family: Arial, sans-serif;
        }

        .edit-btn {
            background-color: #4caf50;
            color: white;
            border: none;
        }

        .edit-btn:hover {
            background-color: #388e3c;
        }

        .delete-btn {
            background-color: white;
            color: #ff4444;
            border: 2px solid #ff4444;
        }

        .delete-btn:hover {
            background-color: #ffebeb;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            overflow-y: auto;
        }

        .modal-content {
            background-color: white;
            margin: 20px auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            position: relative;
            box-sizing: border-box;
        }

        .modal h2 {
            margin-bottom: 20px;
            color: #333;
            font-size: 24px;
        }
        input {
            font-size: 16px;
        }
        /* Form styles */
        .form-group {
            margin-bottom: 15px;
            width: 100%;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            font-family: Arial, sans-serif;
            box-sizing: border-box;
        }

        .readonly {
            background-color: #f5f5f5;
            cursor: not-allowed;
        }

        /* Radio button styles */
        .radio-group {
            display: flex;
            gap: 20px;
        }

        .radio-label {
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
        }

        .radio-label input[type="radio"] {
            width: auto;
        }

        /* Error message styles */
        .error {
            color: red;
            font-size: 16px;
            margin-top: 5px;
        }

        /* Cropper styles */
        .cropper-container {
            max-width: 100%;
            height: 400px;
            margin: 20px 0;
        }

        #cropperImage, #vehicleCropperImage {
            max-width: 100%;
            display: block;
        }

        /* Action buttons */
        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .button {
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 50%;
            font-family: Arial, sans-serif;
        }

        .save-btn {
            background-color: #4caf50;
            color: white;
            border: none;
        }

        .save-btn:hover {
            background-color: #388e3c;
        }

        .cancel-btn {
            background-color: white;
            color: #ff4444;
            border: 2px solid #ff4444;
        }

        .cancel-btn:hover {
            background-color: #ffebeb;
        }

        /* Update the button styles */
        .delete-account {
            background-color: white;
            color: #ff4444;
            border: 2px solid #ff4444;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 50%;
            transition: background-color 0.3s;
        }

        .delete-account:hover {
            background-color: #ffebeb;
        }

        .log-out {
            background-color: #ff4444;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 50%;
            transition: background-color 0.3s;
        }

        .log-out:hover {
            background-color: #cc0000;
        }

        /* Update vehicle action button styles */
        .vehicle-actions .edit-btn, 
        .vehicle-actions .delete-btn {
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 5px;
            font-family: Arial, sans-serif;
            min-width: 100px;
            justify-content: center;
        }

        .vehicle-actions .edit-btn i,
        .vehicle-actions .delete-btn i {
            margin-right: 5px;
        }

        /* Update vehicle image container styles */
        .vehicle-image-container {
            width: 300px;
            height: 300px;
            position: relative;
            border: 2px solid #4caf50;
            border-radius: 0;
            overflow: hidden;
            flex-shrink: 0;
            margin-top: 0;
        }

        /* Update camera button styles for vehicle */
        .vehicle-image-container .change-pic-btn {
            position: absolute;
            bottom: 10px;
            right: 10px;
            transform: none;
            background-color: #4caf50;
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 10;
        }

        .vehicle-image-container .change-pic-btn:hover {
            background-color: #388e3c;
        }

        /* Password field styles */
        .password-field {
            position: relative;
            width: 100%;
            box-sizing: border-box;
        }

        .password-field input {
            width: 100%;
            padding-right: 40px;
            box-sizing: border-box;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #4caf50; /* Green color */
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .toggle-password:hover {
            color: #388e3c; /* Darker green on hover */
        }

        .toggle-password i {
            font-size: 16px;
        }

        /* Add these styles to make cropper circular */
        .cropper-view-box,
        .cropper-face {
            border-radius: 50%;
        }

        .cropper-view-box {
            box-shadow: 0 0 0 1px #39f;
            outline: 0;
        }

        .cropper-face {
            background-color: inherit !important;
        }

        /* Update cropper container styles */
        .cropper-container {
            max-width: 100%;
            height: 400px;
            margin: 20px 0;
        }

        #cropperImage {
            max-width: 100%;
            display: block;
        }

        /* For profile picture cropper - keep circular */
        #cropperModal .cropper-view-box,
        #cropperModal .cropper-face {
            border-radius: 50%;
        }

        /* For vehicle picture cropper - keep square */
        #vehicleCropperModal .cropper-view-box,
        #vehicleCropperModal .cropper-face {
            border-radius: 0;
        }

        .cropper-view-box {
            box-shadow: 0 0 0 1px #39f;
            outline: 0;
        }

        .cropper-face {
            background-color: inherit !important;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <div class="navbar">
        <!-- Left side - Home -->
        <div class="nav-items">
            <a href="homePageDri.php" class="nav-item">
                <i class="fas fa-home"></i>
                Home
            </a>
        </div>
        <div class="nav-center">
            <img src="Image/logo" alt="Logo" class="center-logo">
            UTeM Peer Ride - Driver Portal
        </div>
        <div class="nav-items right">
            <a href="profileDri.php" class="nav-item active">
                <i class="fas fa-user"></i> Profile
            </a>
        </div>
    </div>

    <div class="container">
        <!-- Profile Header with Picture -->
        <div class="profile-header">
            <div class="profile-pic-container">
                <div class="profile-pic-wrapper">
                    <img src="<?php echo $userData['ProfilePicture'] ? 'data:image/jpeg;base64,'.base64_encode($userData['ProfilePicture']) : 'https://img.freepik.com/premium-vector/green-circle-with-white-person-inside-icon_1076610-14570.jpg'; ?>" 
                         alt="Profile Picture" class="profile-pic">
                </div>
                <button class="change-pic-btn" onclick="document.getElementById('profilePicInput').click()">
                    <i class="fas fa-camera"></i>
                </button>
                <input type="file" id="profilePicInput" style="display: none;" accept="image/*" onchange="uploadProfilePic(this)">
            </div>
            <div class="profile-name">
                <?php echo ucwords(strtolower($userData['FullName'])); ?>
            </div>
        </div>

        <!-- Account Information Section -->
        <div class="profile-section">
            <div class="section-header">
                <div class="section-title">Account Information</div>
                <button class="edit-btn" onclick="editSection('account')">
                    <i class="fas fa-edit"></i> Edit
                </button>
            </div>
            <div class="profile-field">
                <span class="field-label">User ID</span>
                <span class="field-value"><?php echo htmlspecialchars($_SESSION['UserID']); ?></span>
            </div>
            <div class="profile-field">
                <span class="field-label">Driver ID</span>
                <span class="field-value"><?php echo htmlspecialchars($_SESSION['DriverID']); ?></span>
            </div>
            <div class="profile-field">
                <span class="field-label">Username</span>
                <span class="field-value"><?php echo htmlspecialchars($userData['Username']); ?></span>
            </div>
            <div class="profile-field">
                <span class="field-label">Password</span>
                <span class="field-value"><?php echo str_repeat('*', 8); ?></span>
            </div>
            <div class="profile-field">
                <span class="field-label">Email</span>
                <span class="field-value"><?php echo strtolower($userData['EmailAddress']); ?></span>
            </div>
            <div class="profile-field">
                <span class="field-label">Security Code</span>
                <span class="field-value"><?php echo str_repeat('*', 8); ?></span>
            </div>
        </div>

        <!-- Personal Information Section -->
        <div class="profile-section">
            <div class="section-header">
                <div class="section-title">Personal Information</div>
                <button class="edit-btn" onclick="editSection('personal')">
                    <i class="fas fa-edit"></i> Edit
                </button>
            </div>
            <div class="profile-field">
                <span class="field-label">Full Name</span>
                <span class="field-value"><?php echo ucwords(strtolower($userData['FullName'])); ?></span>
            </div>
            <div class="profile-field">
                <span class="field-label">License Number</span>
                <span class="field-value"><?php echo $userData['LicenseNo']; ?></span>
            </div>
            <div class="profile-field">
                <span class="field-label">License Expiry Date</span>
                <span class="field-value"><?php echo date('Y/m/d', strtotime($userData['LicenseExpDate'])); ?></span>
            </div>
            <div class="profile-field">
                <span class="field-label">Phone Number</span>
                <span class="field-value"><?php echo $userData['PhoneNo']; ?></span>
            </div>
            <div class="profile-field">
                <span class="field-label">Gender</span>
                <span class="field-value"><?php echo $userData['Gender'] === 'M' ? 'Male' : 'Female'; ?></span>
            </div>
            <div class="profile-field">
                <span class="field-label">Birthday Date</span>
                <span class="field-value"><?php echo date('Y/m/d', strtotime($userData['BirthDate'])); ?></span>
            </div>
        </div>

        <!-- Service Information Section -->
        <div class="profile-section">
            <div class="section-header">
                <div class="section-title">Service Information</div>
                <button class="edit-btn" onclick="editSection('service')">
                    <i class="fas fa-edit"></i> Edit
                </button>
            </div>
            <div class="profile-field">
                <span class="field-label">E-Hailing License</span>
                <span class="field-value"><?php echo $userData['EHailingLicense']; ?></span>
            </div>
            <div class="profile-field">
                <span class="field-label">Availability</span>
                <span class="field-value"><?php echo ucwords(strtolower($userData['Availability'])); ?></span>
            </div>
            <div class="profile-field">
                <span class="field-label">Completed Rides</span>
                <span class="field-value"><?php echo $userData['CompletedRide']; ?></span>
            </div>
            <div class="profile-field">
                <span class="field-label">Sticker Expiry Date</span>
                <span class="field-value"><?php echo date('Y/m/d', strtotime($userData['StickerExpDate'])); ?></span>
            </div>
        </div>

        <!-- Vehicle Section -->
        <div class="profile-section">
            <div class="section-header">
                <div class="section-title">Vehicle Information</div>
                <button class="add-vehicle-btn" onclick="showAddVehicleModal()">
                    <i class="fas fa-plus"></i> Add Vehicle
                </button>
            </div>

            <?php if ($activeVehicle): ?>
            <!-- Active Vehicle -->
            <div class="vehicle-container">
                <div class="vehicle-info">
                    <div class="vehicle-actions">
                        <button class="edit-btn" onclick="editVehicle('<?php echo $activeVehicle['VhcID']; ?>')">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="delete-btn" onclick="deleteVehicle('<?php echo $activeVehicle['VhcID']; ?>')">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                    <div class="profile-field">
                        <span class="field-label">Vehicle ID</span>
                        <span class="field-value"><?php echo $activeVehicle['VhcID']; ?></span>
                    </div>
                    <div class="profile-field">
                        <span class="field-label">Status</span>
                        <span class="field-value"><?php echo ucwords(strtolower($activeVehicle['VehicleStatus'])); ?></span>
                    </div>
                    <div class="profile-field">
                        <span class="field-label">Model</span>
                        <span class="field-value"><?php echo ucwords(strtolower($activeVehicle['Model'])); ?></span>
                    </div>
                    <div class="profile-field">
                        <span class="field-label">Plate Number</span>
                        <span class="field-value"><?php echo $activeVehicle['PlateNo']; ?></span>
                    </div>
                    <div class="profile-field">
                        <span class="field-label">Color</span>
                        <span class="field-value"><?php echo ucwords(strtolower($activeVehicle['Color'])); ?></span>
                    </div>
                    <div class="profile-field">
                        <span class="field-label">Available Seats</span>
                        <span class="field-value"><?php echo $activeVehicle['AvailableSeat']; ?></span>
                    </div>
                    <div class="profile-field">
                        <span class="field-label">Year Manufactured</span>
                        <span class="field-value"><?php echo $activeVehicle['YearManufacture']; ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($otherVehicles)): ?>
            <button class="show-more-btn" onclick="toggleOtherVehicles()">
                Show More Vehicles
            </button>
            <div id="otherVehicles" style="display: none;">
                <?php foreach ($otherVehicles as $vehicle): ?>
                <div class="vehicle-container">
                    <div class="vehicle-info">
                        <div class="vehicle-actions">
                            <button class="edit-btn" onclick="editVehicle('<?php echo $vehicle['VhcID']; ?>')">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="delete-btn" onclick="deleteVehicle('<?php echo $vehicle['VhcID']; ?>')">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                        <div class="profile-field">
                            <span class="field-label">Vehicle ID</span>
                            <span class="field-value"><?php echo $vehicle['VhcID']; ?></span>
                        </div>
                        <div class="profile-field">
                            <span class="field-label">Status</span>
                            <span class="field-value"><?php echo ucwords(strtolower($vehicle['VehicleStatus'])); ?></span>
                        </div>
                        <div class="profile-field">
                            <span class="field-label">Model</span>
                            <span class="field-value"><?php echo ucwords(strtolower($vehicle['Model'])); ?></span>
                        </div>
                        <div class="profile-field">
                            <span class="field-label">Plate Number</span>
                            <span class="field-value"><?php echo $vehicle['PlateNo']; ?></span>
                        </div>
                        <div class="profile-field">
                            <span class="field-label">Color</span>
                            <span class="field-value"><?php echo ucwords(strtolower($vehicle['Color'])); ?></span>
                        </div>
                        <div class="profile-field">
                            <span class="field-label">Available Seats</span>
                            <span class="field-value"><?php echo $vehicle['AvailableSeat']; ?></span>
                        </div>
                        <div class="profile-field">
                            <span class="field-label">Year Manufactured</span>
                            <span class="field-value"><?php echo $vehicle['YearManufacture']; ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Action Buttons -->
        <div class="button-group">
            <button onclick="deleteAccount()" class="delete-account">Delete Account</button>
            <button onclick="logout()" class="log-out">Log Out</button>
        </div>
    </div>

    <!-- Modals -->
    <!-- Account Information Modal -->
    <div id="editAccountModal" class="modal" style="display: none;">
        <div class="modal-content">
            <h2>Edit Account Information</h2>
            <form id="editAccountForm">
                <div class="form-group">
                    <label>User ID:</label>
                    <input type="text" value="<?php echo $_SESSION['UserID']; ?>" readonly class="readonly">
                </div>

                <div class="form-group">
                    <label>Driver ID:</label>
                    <input type="text" value="<?php echo $_SESSION['DriverID']; ?>" readonly class="readonly">
                </div>

                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" id="username" name="username" 
                           value="<?php echo htmlspecialchars($userData['Username']); ?>" 
                           maxlength="20" required>
                    <span id="usernameError" class="error"></span>
                </div>

                <div class="form-group">
                    <label>Current Password:</label>
                    <div class="password-field">
                        <input type="password" id="currentPassword" value="<?php echo htmlspecialchars($userData['Password']); ?>" readonly class="readonly" maxLength=16>
                        <button type="button" class="toggle-password" onclick="togglePassword('currentPassword')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label>New Password:</label>
                    <div class="password-field">
                        <input type="password" id="password" name="password" placeholder="Enter new password (leave blank to keep current)" maxLength=16>
                        <button type="button" class="toggle-password" onclick="togglePassword('password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <span id="passwordError" class="error"></span>
                </div>

                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo strtolower($userData['EmailAddress']); ?>" required autocomplete="off">
                    <span id="emailError" class="error"></span>
                </div>

                <div class="form-group">
                    <label>Current Security Code:</label>
                    <div class="password-field">
                        <input type="password" id="currentSecurityCode" 
                               value="<?php echo htmlspecialchars($userData['EmailSecCode']); ?>" 
                               readonly class="readonly">
                        <button type="button" class="toggle-password" onclick="togglePassword('currentSecurityCode')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label>New Security Code:</label>
                    <div class="password-field">
                        <input type="password" id="securityCode" name="securityCode" maxlength="8" placeholder="Enter new security code (leave blank to keep current)">
                        <button type="button" class="toggle-password" onclick="togglePassword('securityCode')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <span id="securityCodeError" class="error"></span>
                </div>

                <div class="button-group">
                    <button type="button" class="button cancel-btn" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="button save-btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Personal Information Modal -->
    <div id="editPersonalModal" class="modal">
        <div class="modal-content">
            <h2>Edit Personal Information</h2>
            <form id="editPersonalForm">
                <div class="form-group">
                    <label for="fullName">Full Name:</label>
                    <input type="text" id="fullName" name="fullName" 
                           value="<?php echo ucwords(strtolower($userData['FullName'])); ?>" autocomplete="off">
                           <span id="fullNameError" class="error"></span>

                </div>

                <div class="form-group">
                    <label for="licenseNo">License Number:</label>
                    <input type="text" id="licenseNo" name="licenseNo" maxLength="8" 
                           value="<?php echo $userData['LicenseNo']; ?>" required autocomplete="off">
                    <span id="licenseError" class="error"></span>
                </div>

                <div class="form-group">
                    <label for="licenseExpDate">License Expiry Date:</label>
                    <input type="date" id="licenseExpDate" name="licenseExpDate" 
                           value="<?php echo date('Y-m-d', strtotime($userData['LicenseExpDate'])); ?>" 
                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                           max="<?php echo date('Y-m-d', strtotime('+10 years')); ?>"
                           required>
                </div>

                <div class="form-group">
                    <label for="phoneNo">Phone Number:</label>
                    <input type="text" id="phoneNo" name="phoneNo" maxLength="12" 
                           value="<?php echo $userData['PhoneNo']; ?>" required autocomplete="off">
                    <span id="phoneError" class="error"></span>
                </div>

                <div class="form-group">
                    <label>Gender:</label>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="gender" value="Male"> Male
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="gender" value="Female"> Female
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="birthDate">Birthday Date:</label>
                    <input type="date" id="birthDate" name="birthDate" 
                           value="<?php echo date('Y-m-d', strtotime($userData['BirthDate'])); ?>" 
                           min="<?php echo date('Y-m-d', strtotime('-60 years')); ?>"
                           max="<?php echo date('Y-m-d', strtotime('-19 years')); ?>"
                           required>
                </div>

                <div class="button-group">
                    <button type="button" class="button cancel-btn" onclick="closePersonalModal()">Cancel</button>
                    <button type="submit" class="button save-btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Service Information Modal -->
    <div id="editServiceModal" class="modal">
        <div class="modal-content">
            <h2>Edit Service Information</h2>
            <form id="editServiceForm">
                <div class="form-group">
                    <label for="ehailingLicense">E-Hailing License:</label>
                    <input type="text" id="ehailingLicense" name="ehailingLicense" 
                           value="<?php echo $userData['EHailingLicense']; ?>" readonly class="readonly">
                </div>

                <div class="form-group">
                    <label>Availability:</label>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="availability" value="AVAILABLE"
                                   <?php echo $userData['Availability'] === 'AVAILABLE' ? 'checked' : ''; ?>> Available
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="availability" value="NOT AVAILABLE"
                                   <?php echo $userData['Availability'] === 'NOT AVAILABLE' ? 'checked' : ''; ?>> Not Available
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="completedRide">Completed Rides:</label>
                    <input type="text" id="completedRide" name="completedRide" 
                           value="<?php echo $userData['CompletedRide']; ?>" readonly class="readonly">
                </div>

                <div class="form-group">
                    <label for="stickerExpDate">Sticker Expiry Date:</label>
                    <input type="date" id="stickerExpDate" name="stickerExpDate" 
                           value="<?php echo date('Y-m-d', strtotime($userData['StickerExpDate'])); ?>" readonly class="readonly">
                </div>

                <div class="button-group">
                    <button type="button" class="button cancel-btn" onclick="closeModal('editServiceModal')">Cancel</button>
                    <button type="submit" class="button save-btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Vehicle Edit Modal -->
    <div id="editVehicleModal" class="modal">
        <div class="modal-content">
            <h2>Edit Vehicle Information</h2>
            <form id="editVehicleForm">
                <input type="hidden" id="editVhcId" name="vhcId">
                
                <div class="form-group">
                    <label>Vehicle Status:</label>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="status" value="Active"> Active
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="status" value="Inactive"> Inactive
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="editModel">Car Model:</label>
                    <input type="text" id="editModel" name="model" required autocomplete="off">
                </div>

                <div class="form-group">
                    <label for="editPlateNo">Plate Number:</label>
                    <input type="text" id="editPlateNo" name="plateNo" maxLength="10" required autocomplete="off">
                    <span id="editPlateError" class="error"></span>
                </div>

                <div class="form-group">
                    <label for="editColor">Car Color:</label>
                    <input type="text" id="editColor" name="color" required autocomplete="off">
                </div>

                <div class="form-group">
                    <label for="editAvailableSeat">Available Seats:</label>
                    <input type="number" id="editAvailableSeat" name="availableSeat" 
                           min="1" max="7" value="1" required>
                </div>

                <div class="form-group">
                    <label for="editYearManufacture">Year Manufactured:</label>
                    <select id="editYearManufacture" name="yearManufacture" class="year-select" required>
                    </select>
                </div>

                <div class="button-group">
                    <button type="button" class="button cancel-btn" onclick="closeEditVehicleModal()">Cancel</button>
                    <button type="submit" class="button save-btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Vehicle Modal -->
    <div id="addVehicleModal" class="modal">
        <div class="modal-content">
            <h2>Add New Vehicle</h2>
            <form id="addVehicleForm">
                <div class="form-group">
                    <label for="newModel">Car Model:</label>
                    <input type="text" id="newModel" name="model" required autocomplete="off">
                </div>

                <div class="form-group">
                    <label for="newPlateNo">Plate Number:</label>
                    <input type="text" id="newPlateNo" name="plateNo" maxLength="10" required autocomplete="off">
                    <span id="newPlateError" class="error"></span>
                </div>

                <div class="form-group">
                    <label for="newColor">Car Color:</label>
                    <input type="text" id="newColor" name="color" required autocomplete="off">
                </div>

                <div class="form-group">
                    <label for="newAvailableSeat">Available Seats:</label>
                    <input type="number" id="newAvailableSeat" name="availableSeat" 
                           min="1" max="7" value="1" required>
                </div>

                <div class="form-group">
                    <label for="newYearManufacture">Year Manufactured:</label>
                    <select id="newYearManufacture" name="yearManufacture" class="year-select" required>
                    </select>
                </div>

                <div class="button-group">
                    <button type="button" class="button cancel-btn" onclick="closeAddVehicleModal()">Cancel</button>
                    <button type="submit" class="button save-btn">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Profile Picture Cropper Modal -->
    <div id="cropperModal" class="modal">
        <div class="modal-content">
            <h2>Adjust Profile Picture</h2>
            <div class="cropper-container">
                <img id="cropperImage" src="" alt="Image to crop">
            </div>
            <div class="button-group">
                <button type="button" class="button cancel-btn" onclick="closeCropperModal()">Cancel</button>
                <button type="button" class="button save-btn" onclick="saveProfilePic()">Save</button>
            </div>
        </div>
    </div>

    <script>
        // Variable declarations
        let cropper = null;
        let vehicleCropper = null;

        // Profile picture handling
        function uploadProfilePic(input) {
            if (input.files && input.files[0]) {
                const maxSize = 5 * 1024 * 1024; // 5MB
                if (input.files[0].size > maxSize) {
                    alert('File size must be less than 5MB');
                    return;
                }

                // Check file type
                const allowedTypes = ['image/jpeg', 'image/png','image/gif'];
                if (!allowedTypes.includes(input.files[0].type)){
                    alert('Only JPG, PNG and GIF files are allowed');
                    return;
                }

                // Show cropper modal
                const reader = new FileReader();
                reader.onload = function(e) {
                    const cropperImage = document.getElementById('cropperImage');
                    cropperImage.src = e.target.result;
                    
                    if (cropper) {
                        cropper.destroy();
                    }
                    cropper = new Cropper(cropperImage, {
                        aspectRatio: 1,
                        viewMode: 1,
                        dragMode: 'move',
                        autoCropArea: 1,
                        cropBoxResizable: false,
                        cropBoxMovable: false,
                        guides: false,
                        center: false,
                        highlight: false,
                        background: false,
                        rotatable: true,
                        scalable: true,
                        zoomable: true
                    });

                    document.getElementById('cropperModal').style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Vehicle picture handling
        function uploadVehiclePic(input, vhcId) {
            if (input.files && input.files[0]) {
                const maxSize = 5 * 1024 * 1024;
                if (input.files[0].size > maxSize) {
                    alert('File size must be less than 5MB');
                    return;
                }

                const allowedTypes = ['image/jpeg', 'image/png','image/gif'];
                if (!allowedTypes.includes(input.files[0].type)){
                    alert('Only JPG, PNG and GIF files are allowed');
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    const vehicleCropperImage = document.getElementById('vehicleCropperImage');
                    vehicleCropperImage.src = e.target.result;
                   
                    if (vehicleCropper) {
                        vehicleCropper.destroy();
                    }
                    vehicleCropper = new Cropper(vehicleCropperImage, {
                        aspectRatio: 1,
                        viewMode: 1,
                        dragMode: 'move',
                        autoCropArea: 1,
                        cropBoxResizable: false,
                        cropBoxMovable: false,
                        guides: false,
                        center: false,
                        highlight: false,
                        background: false,
                        rotatable: true,
                        scalable: true,
                        zoomable: true
                    });

                    // Store vhcId for use when saving
                    document.getElementById('currentVhcId').value = vhcId;
                    document.getElementById('vehicleCropperModal').style.display ='block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Save cropped profile picture
        function saveProfilePic() {
            if (!cropper) return;

            cropper.getCroppedCanvas({
                width: 400,
                height: 400
            }).toBlob(function(blob) {
                const formData = new FormData();
                formData.append('profilePic', blob, 'profile.jpg');

                fetch('updateProfilePic.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('Profile picture updated successfully');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating profile picture');
                });

                closeCropperModal();
            }, 'image/jpeg', 0.9);
        }

        // Save cropped vehicle picture
        function saveVehiclePic() {
            if (!vehicleCropper) return;

            const vhcId = document.getElementById('currentVhcId').value;

            vehicleCropper.getCroppedCanvas({
                width: 400,
                height: 400
            }).toBlob(function(blob) {
                const formData = new FormData();
                formData.append('vehiclePic', blob, 'vehicle.jpg');
                formData.append('vhcId', vhcId);

                fetch('updateVhcPicture.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('Vehicle picture updated successfully');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating vehicle picture');
                });

                closeVehicleCropperModal();
            }, 'image/jpeg', 0.9);
        }

        // Vehicle management functions
        function addVehicle(formData) {
            fetch('vehicleDri.php', {
               method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    operation: 'add',
                    ...formData
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('Vehicle added successfully');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding vehicle');
            });
        }

        function updateVehicle(vhcId, formData) {
            fetch('vehicleDri.php', {
               method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    operation: 'update',
                    vhcId: vhcId,
                    ...formData
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('Vehicle updated successfully');
                    location.reload();
                } else{
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating vehicle');
            });
        }

        function deleteVehicle(vhcId) {
            if (confirm('Are you sure you want to delete this vehicle?')){
                fetch('vehicleDri.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        operation: 'delete',
                        vhcId: vhcId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('Vehicle deleted successfully');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting vehicle');
                });
            }
        }

        // Form validation functions
        function validatePlateNo(plateNo) {
            return /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{1,10}$/.test(plateNo);
        }

        function validateLicenseNo(licenseNo) {
            return /^\d{8}$/.test(licenseNo);
        }

        // Modal handling functions
        function editSection(section) {
            const modal = document.getElementById(`edit${section.charAt(0).toUpperCase() + section.slice(1)}Modal`);
            
            if (section === 'personal') {
                // Set date values in Y-m-d format for HTML date inputs
                document.getElementById('birthDate').value = '<?php echo date('Y-m-d', strtotime($userData['BirthDate'])); ?>';
                document.getElementById('licenseExpDate').value = '<?php echo date('Y-m-d', strtotime($userData['LicenseExpDate'])); ?>';
                
                // Set other fields
                document.getElementById('fullName').value = '<?php echo ucwords(strtolower($userData['FullName'])); ?>';
                document.getElementById('phoneNo').value = '<?php echo $userData['PhoneNo']; ?>';
                document.getElementById('licenseNo').value = '<?php echo $userData['LicenseNo']; ?>';
                
                // Set gender radio button
                const gender = '<?php echo $userData['Gender']; ?>';
                const genderRadio = document.querySelector(`input[name="gender"][value="${gender === 'M' ? 'Male' : 'Female'}"]`);
                if (genderRadio) genderRadio.checked = true;
            } 
            else if (section === 'service') {
                // Set date value in Y-m-d format
                document.getElementById('stickerExpDate').value = '<?php echo date('Y-m-d', strtotime($userData['StickerExpDate'])); ?>';
                
                // Set availability radio button
                const availability = '<?php echo $userData['Availability']; ?>';
                const availabilityRadio = document.querySelector(`input[name="availability"][value="${availability === 'AVAILABLE' ? 'Available' : 'Not Available'}"]`);
                if (availabilityRadio) availabilityRadio.checked = true;
                
                document.getElementById('ehailingLicense').value = '<?php echo $userData['EHailingLicense']; ?>';
            }
            
            modal.style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function closeCropperModal() {
            document.getElementById('cropperModal').style.display = 'none';
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
        }

        function closeVehicleCropperModal() {
            document.getElementById('vehicleCropperModal').style.display ='none';
            if (vehicleCropper) {
                vehicleCropper.destroy();
                vehicleCropper = null;
            }
        }

        // Other utility functions
        function toggleOtherVehicles() {
            const otherVehicles = document.getElementById('otherVehicles');
            otherVehicles.style.display = otherVehicles.style.display === 'none' ? 'block' : 'none';
        }

        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logoutDri.php';
            }
        }

        function deleteAccount() {
            if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
                fetch('deleteAccountDri.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        userId: '<?php echo $_SESSION['UserID']; ?>',
                       driverId: '<?php echo $_SESSION['DriverID']; ?>'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert(data.message);
                        window.location.href = 'loginDri.php';
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting account');
                });
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Set up year manufactured dropdown for vehicle forms
            const yearSelects = document.querySelectorAll('.year-select');
            const currentYear = new Date().getFullYear();
            const minYear = currentYear - 15;
            
            yearSelects.forEach(select => {
                // Clear existing options
                select.innerHTML = '';
                
                // Add default/placeholder option
                const defaultOption = document.createElement('option');
                defaultOption.value = '';
                defaultOption.textContent = 'Select Year';
                defaultOption.disabled = true;
                defaultOption.selected = true;
                select.appendChild(defaultOption);
                
                // Add year options from current year to minYear
                for (let year = currentYear; year >= minYear; year--) {
                    const option = document.createElement('option');
                    option.value = year;
                    option.textContent = year;
                    select.appendChild(option);
                }
            });

            // Set initial values for edit form if vehicle exists
            if (typeof activeVehicle !== 'undefined' && activeVehicle) {
                const yearSelect = document.getElementById('editYearManufacture');
                if (yearSelect) {
                    yearSelect.value = activeVehicle.YearManufacture;
                }
            }

            // Account form validation
            document.getElementById('editAccountForm').addEventListener('submit', async (e) => {
                e.preventDefault();

                // Check for any validation errors
                const errors = document.querySelectorAll('.error');
                for (let error of errors) {
                    if (error.textContent) {
                        alert('Please fix all errors before submitting');
                        return;
                    }
                }

                try {
                    const response = await fetch('updateAccountDri.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            updateType: 'account',
                            username: document.getElementById('username').value.trim(),
                            password: document.getElementById('password').value,
                            email: document.getElementById('email').value.trim(),
                            securityCode: document.getElementById('securityCode').value,
                            userId: '<?php echo $_SESSION['UserID']; ?>',
                            driverId: '<?php echo $_SESSION['DriverID']; ?>'
                        })
                    });

                    const data = await response.json();
                    if (data.status === 'success') {
                        alert('Account information updated successfully');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('An error occurred while updating account information');
                }
            });

            // Add validation event listeners
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');
            const emailInput = document.getElementById('email');
            const securityCodeInput = document.getElementById('securityCode');

            // Username validation
            usernameInput.addEventListener('blur', async () => {
                const username = usernameInput.value.trim();
                const currentUsername = '<?php echo $userData['Username']; ?>';
                const usernameError = document.getElementById('usernameError');
                
                if (username === currentUsername) {
                    usernameError.textContent = '';
                    return;
                }

                if (username.length === 0) {
                    usernameError.textContent = 'Username cannot be empty';
                    return;
                }

                if (username.length > 20) {
                    usernameError.textContent = 'Username must not exceed 20 characters';
                    return;
                }

                try {
                    const response = await fetch('validateUsername.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ 
                            username: username,
                            userType: 'DRIVER'
                        })
                    });
                    const data = await response.json();
                    
                    if (data.status === 'exists') {
                        usernameError.textContent = 'Username already exists. Please choose another username.';
                    } else {
                        usernameError.textContent = ''; // Clear error message if username is valid
                    }
                } catch (error) {
                    console.error('Error:', error);
                }
            });

            // Password validation
            passwordInput.addEventListener('input', () => {
                const password = passwordInput.value;
                if (password) {
                    if (password.length < 8 || password.length > 16) {
                        document.getElementById('passwordError').textContent = 'Password must be between 8 and 16 characters';
                    } else {
                        document.getElementById('passwordError').textContent = '';
                    }
                } else {
                    document.getElementById('passwordError').textContent = '';
                }
            });

            // Email validation
            emailInput.addEventListener('blur', async () => {
                const email = emailInput.value.trim();
                const currentEmail = '<?php echo $userData['EmailAddress']; ?>';
                const emailError = document.getElementById('emailError');
                
                if (email === currentEmail) {
                    emailError.textContent = '';
                    return;
                }

                if (email.length === 0) {
                    emailError.textContent = 'Email cannot be empty';
                    return;
                }

                if (!/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(email)) {
                    emailError.textContent = 'Invalid email format. Example: example@domain.com';
                    return;
                }

                try {
                    const response = await fetch('validateEmail.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ 
                            email: email,
                            userType: 'DRIVER',
                            currentEmail: currentEmail // Add current email to the request
                        })
                    });
                    const data = await response.json();
                    
                    if (data.status === 'exists') {
                        emailError.textContent = 'Email already exists. Please use another email address.';
                    } else {
                        emailError.textContent = ''; // Clear error message if email is valid
                    }
                } catch (error) {
                    console.error('Error:', error);
                }
            });

            // Security code validation
            securityCodeInput.addEventListener('input', () => {
                const secCode = securityCodeInput.value;
                if (secCode) {
                    let errorMessage = [];
                    if (secCode.length < 4 || secCode.length > 8) {
                        errorMessage.push('Length must be between 4-8 characters');
                    }
                    if (!/[a-z]/.test(secCode)) {
                        errorMessage.push('Must contain at least one lowercase letter');
                    }
                    if (!/[A-Z]/.test(secCode)) {
                        errorMessage.push('Must contain at least one uppercase letter');
                    }
                    if (!/[0-9]/.test(secCode)) {
                        errorMessage.push('Must contain at least one number');
                    }
                    if (!/[!@#$%^&*]/.test(secCode)) {
                        errorMessage.push('Must contain at least one special character (!@#$%^&*)');
                    }
                    
                    document.getElementById('securityCodeError').textContent = errorMessage.join('; ');
                    if (errorMessage.length === 0) {
                        document.getElementById('securityCodeError').textContent = '';
                    }
                } else {
                    document.getElementById('securityCodeError').textContent = '';
                }
            });

            // Close modal when clicking outside
            window.onclick = function(event) {
                const modal = document.getElementById('editAccountModal');
                if (event.target === modal) {
                    closeEditModal();
                }
            }

            // Personal information form validation
            document.getElementById('editPersonalForm').addEventListener('submit', async (e) => {
                e.preventDefault();

                const fullName = document.getElementById('fullName').value.trim();
                if (!fullName) {
                    document.getElementById('fullNameError').textContent = 'Full name cannot be empty';
                    return;
                }

                // Check for any validation errors
                const errors = document.querySelectorAll('.error');
                for (let error of errors) {
                    if (error.textContent) {
                        alert('Please fix all errors before submitting');
                        return;
                    }
                }

                try {
                    const response = await fetch('updateAccountDri.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            updateType: 'personal',
                            fullName: fullName,
                            licenseNo: document.getElementById('licenseNo').value.trim(),
                            licenseExpDate: document.getElementById('licenseExpDate').value,
                            phoneNo: document.getElementById('phoneNo').value.trim(),
                            gender: document.querySelector('input[name="gender"]:checked').value === 'Male' ? 'M' : 'F',
                            birthDate: document.getElementById('birthDate').value,
                            userId: '<?php echo $_SESSION['UserID']; ?>',
                            driverId: '<?php echo $_SESSION['DriverID']; ?>'
                        })
                    });

                    const data = await response.json();
                    if (data.status === 'success') {
                        alert('Personal information updated successfully');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('An error occurred while updating personal information');
                }
            });

            // Service information form validation
            document.getElementById('editServiceForm').addEventListener('submit', async (e) => {
                e.preventDefault();

                try {
                    const response = await fetch('updateAccountDri.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            updateType: 'service',
                            availability: document.querySelector('input[name="availability"]:checked').value,
                            userId: '<?php echo $_SESSION['UserID']; ?>',
                            driverId: '<?php echo $_SESSION['DriverID']; ?>'
                        })
                    });

                    const data = await response.json();
                    if (data.status === 'success') {
                        alert('Service information updated successfully');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('An error occurred while updating service information');
                }
            });

            // Add this with other validation listeners
            const licenseNoInput = document.getElementById('licenseNo');

            // License number validation
            licenseNoInput.addEventListener('blur', async () => {
                const licenseNo = licenseNoInput.value.trim();
                const currentLicenseNo = '<?php echo $userData['LicenseNo']; ?>';
                const licenseError = document.getElementById('licenseError');
                
                if (licenseNo === currentLicenseNo) {
                    licenseError.textContent = '';
                    return;
                }

                if (licenseNo.length === 0) {
                    licenseError.textContent = 'License number cannot be empty';
                    return;
                }

                if (!/^\d{8}$/.test(licenseNo)) {
                    licenseError.textContent = 'License number must be exactly 8 digits';
                    return;
                }

                try {
                    const response = await fetch('validateLicenseNo.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ 
                            licenseNo: licenseNo,
                            userType: 'DRIVER'
                        })
                    });
                    const data = await response.json();
                    
                    if (data.status === 'exists') {
                        licenseError.textContent = 'License number already exists. Please check your input.';
                    } else {
                        licenseError.textContent = ''; // Clear error message if license number is valid
                    }
                } catch (error) {
                    console.error('Error:', error);
                }
            });

            // Phone number validation
            document.getElementById('phoneNo').addEventListener('blur', async (e) => {
                const phoneNo = e.target.value.trim();
                const currentPhoneNo = '<?php echo $userData['PhoneNo']; ?>';
                const phoneError = document.getElementById('phoneError');
                
                if (phoneNo === currentPhoneNo) {
                    phoneError.textContent = '';
                    return;
                }

                if (phoneNo.length === 0) {
                    phoneError.textContent = 'Phone number cannot be empty';
                    return;
                }

                if (!/^01\d-\d{7,8}$/.test(phoneNo)) {
                    phoneError.textContent = 'Invalid phone number format. Example: 012-3456789';
                    return;
                }

                try {
                    const response = await fetch('validatePhoneNo.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ 
                            phoneNo: phoneNo,
                            userType: 'DRIVER'
                        })
                    });
                    const data = await response.json();
                    
                    if (data.status === 'exists') {
                        phoneError.textContent = 'Phone number already exists. Please use another phone number.';
                    } else {
                        phoneError.textContent = ''; // Clear error message if phone number is valid
                    }
                } catch (error) {
                    console.error('Error:', error);
                }
            });

            // Add form submission handlers for vehicle forms
            document.getElementById('addVehicleForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(e.target);
                const data = Object.fromEntries(formData);
                
                try {
                    const response = await fetch('vehicleDri.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            operation: 'add',
                            ...data
                        })
                    });
                    
                    const result = await response.json();
                    if (result.status === 'success') {
                        alert('Vehicle added successfully');
                        location.reload();
                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('An error occurred while adding vehicle');
                }
            });

            document.getElementById('editVehicleForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(e.target);
                const data = Object.fromEntries(formData);
                const vhcId = document.getElementById('editVhcId').value;
                
                try {
                    const response = await fetch('vehicleDri.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            operation: 'update',
                            vhcId: vhcId,
                            ...data
                        })
                    });
                    
                    const result = await response.json();
                    if (result.status === 'success') {
                        alert('Vehicle updated successfully');
                        location.reload();
                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('An error occurred while updating vehicle');
                }
            });
        });

        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Validation functions
        function validateUsername(username) {
            if (!username) {
                return 'Username is required';
            }
            if (!/^[A-Za-z\d]{3,20}$/.test(username)) {
                return 'Username must be 3-20 characters and contain only letters and numbers';
            }
            return '';
        }

        function validatePassword(password) {
            if (password && !/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,16}$/.test(password)) {
                return 'Password must be 8-16 characters and contain both letters and numbers';
            }
            return '';
        }

        function validateEmail(email) {
            if (!email) {
                return 'Email is required';
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                return 'Please enter a valid email address';
            }
            return '';
        }

        function validateSecurityCode(code) {
            if (code && !/^\d{8}$/.test(code)) {
                return 'Security code must be exactly 8 digits';
            }
            return '';
        }

        function validatePhoneNo(phoneNo) {
            return /^01\d-\d{7,8}$/.test(phoneNo);
        }

        function validateLicenseNo(licenseNo) {
            return /^\d{8}$/.test(licenseNo);
        }

        // Function to convert date format from d/m/Y to Y-m-d for input type="date"
        function formatDateForInput(dateStr) {
            const parts = dateStr.split('/');
            return `${parts[2]}-${parts[1]}-${parts[0]}`;
        }

        function editVehicle(vhcId) {
            // Find the vehicle data
            const activeVehicle = <?php echo json_encode($activeVehicle); ?>;
            const otherVehicles = <?php echo json_encode($otherVehicles); ?>;
            const allVehicles = [activeVehicle, ...otherVehicles].filter(v => v);
            const selectedVehicle = allVehicles.find(v => v.VhcID === vhcId);

            if (selectedVehicle) {
                // Set form values
                document.getElementById('editVhcId').value = selectedVehicle.VhcID;
                document.getElementById('editModel').value = capitalizeWords(selectedVehicle.Model);
                document.getElementById('editPlateNo').value = selectedVehicle.PlateNo;
                document.getElementById('editColor').value = capitalizeWords(selectedVehicle.Color);
                document.getElementById('editAvailableSeat').value = selectedVehicle.AvailableSeat;
                document.getElementById('editYearManufacture').value = selectedVehicle.YearManufacture;

                // Set status radio button
                const statusRadio = document.querySelector(`input[name="status"][value="${selectedVehicle.VehicleStatus === 'ACTIVE' ? 'Active' : 'Inactive'}"]`);
                if (statusRadio) statusRadio.checked = true;

                // Show modal
                document.getElementById('editVehicleModal').style.display = 'block';
            }
        }

        function showAddVehicleModal() {
            document.getElementById('addVehicleModal').style.display = 'block';
        }

        // Add this function to capitalize first letter of each word
        function capitalizeWords(str) {
            return str.toLowerCase().split(' ')
                .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                .join(' ');
        }

        // Add plate number validation
        const plateNoInputs = document.querySelectorAll('input[name="plateNo"]');
        plateNoInputs.forEach(input => {
            input.addEventListener('input', () => {
                const plateNo = input.value.trim();
                const errorElement = input.nextElementSibling;
                
                if (!/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{1,10}$/.test(plateNo)) {
                    errorElement.textContent = 'Plate number must contain both letters and numbers, no spaces';
                } else {
                    errorElement.textContent = '';
                }
            });
        });

        // Add full name validation listener
        const fullNameInput = document.getElementById('fullName');

        fullNameInput.addEventListener('input', () => {
            const fullName = fullNameInput.value.trim();
            if (fullName.length === 0) {
                document.getElementById('fullNameError').textContent = 'Full name cannot be empty';
            } else {
                document.getElementById('fullNameError').textContent = '';
            }
        });

        // Update the close modal functions
        function closeEditModal() {
            // Reset form values to original values
            document.getElementById('username').value = '<?php echo htmlspecialchars($userData['Username']); ?>';
            document.getElementById('password').value = '';
            document.getElementById('email').value = '<?php echo strtolower($userData['EmailAddress']); ?>';
            document.getElementById('securityCode').value = '';
            
            // Clear any error messages
            document.getElementById('usernameError').textContent = '';
            document.getElementById('passwordError').textContent = '';
            document.getElementById('emailError').textContent = '';
            document.getElementById('securityCodeError').textContent = '';
            
            // Hide modal
            document.getElementById('editAccountModal').style.display = 'none';
        }

        function closePersonalModal() {
            // Reset form values to original values
            document.getElementById('fullName').value = '<?php echo ucwords(strtolower($userData['FullName'])); ?>';
            document.getElementById('phoneNo').value = '<?php echo htmlspecialchars($userData['PhoneNo']); ?>';
            document.getElementById('birthDate').value = '<?php echo date('Y-m-d', strtotime($userData['BirthDate'])); ?>';
            document.getElementById('licenseNo').value = '<?php echo $userData['LicenseNo']; ?>';
            document.getElementById('licenseExpDate').value = '<?php echo date('Y-m-d', strtotime($userData['LicenseExpDate'])); ?>';
            
            // Reset gender radio button
            const gender = '<?php echo $userData['Gender']; ?>';
            const genderRadio = document.querySelector(`input[name="gender"][value="${gender}"]`);
            if (genderRadio) genderRadio.checked = true;
            
            // Clear any error messages
            document.getElementById('fullNameError').textContent = '';
            document.getElementById('phoneError').textContent = '';
            document.getElementById('licenseError').textContent = '';
            
            // Hide modal
            document.getElementById('editPersonalModal').style.display = 'none';
        }

        // Add these functions to handle vehicle modal closing
        function closeAddVehicleModal() {
            // Reset form values to empty
            document.getElementById('addModel').value = '';
            document.getElementById('addPlateNo').value = '';
            document.getElementById('addColor').value = '';
            document.getElementById('addAvailableSeat').value = '';
            document.getElementById('addYearManufacture').value = '';
            
            // Clear any error messages
            const errorElements = document.getElementById('addVehicleForm').querySelectorAll('.error');
            errorElements.forEach(element => element.textContent = '');
            
            // Hide modal
            document.getElementById('addVehicleModal').style.display = 'none';
        }

        function closeEditVehicleModal() {
            const vhcId = document.getElementById('editVhcId').value;
            // Find the vehicle data
            const activeVehicle = <?php echo json_encode($activeVehicle); ?>;
            const otherVehicles = <?php echo json_encode($otherVehicles); ?>;
            const allVehicles = [activeVehicle, ...otherVehicles].filter(v => v);
            const selectedVehicle = allVehicles.find(v => v.VhcID === vhcId);

            if (selectedVehicle) {
                // Reset form values to original values
                document.getElementById('editModel').value = capitalizeWords(selectedVehicle.Model);
                document.getElementById('editPlateNo').value = selectedVehicle.PlateNo;
                document.getElementById('editColor').value = capitalizeWords(selectedVehicle.Color);
                document.getElementById('editAvailableSeat').value = selectedVehicle.AvailableSeat;
                document.getElementById('editYearManufacture').value = selectedVehicle.YearManufacture;

                // Reset status radio button
                const statusRadio = document.querySelector(`input[name="status"][value="${selectedVehicle.VehicleStatus === 'ACTIVE' ? 'Active' : 'Inactive'}"]`);
                if (statusRadio) statusRadio.checked = true;
            }
            
            // Clear any error messages
            const errorElements = document.getElementById('editVehicleForm').querySelectorAll('.error');
            errorElements.forEach(element => element.textContent = '');
            
            // Hide modal
            document.getElementById('editVehicleModal').style.display = 'none';
        }

        // Update the showAddVehicleModal function
        function showAddVehicleModal() {
            // Reset form before showing
            document.getElementById('addVehicleForm').reset();
            // Clear any error messages
            const errorElements = document.getElementById('addVehicleForm').querySelectorAll('.error');
            errorElements.forEach(element => element.textContent = '');
            // Show modal
            document.getElementById('addVehicleModal').style.display = 'block';
        }

        // Update the editVehicle function
        function editVehicle(vhcId) {
            // Find the vehicle data
            const activeVehicle = <?php echo json_encode($activeVehicle); ?>;
            const otherVehicles = <?php echo json_encode($otherVehicles); ?>;
            const allVehicles = [activeVehicle, ...otherVehicles].filter(v => v);
            const selectedVehicle = allVehicles.find(v => v.VhcID === vhcId);

            if (selectedVehicle) {
                // Set form values
                document.getElementById('editVhcId').value = selectedVehicle.VhcID;
                document.getElementById('editModel').value = capitalizeWords(selectedVehicle.Model);
                document.getElementById('editPlateNo').value = selectedVehicle.PlateNo;
                document.getElementById('editColor').value = capitalizeWords(selectedVehicle.Color);
                document.getElementById('editAvailableSeat').value = selectedVehicle.AvailableSeat;
                document.getElementById('editYearManufacture').value = selectedVehicle.YearManufacture;

                // Set status radio button
                const statusRadio = document.querySelector(`input[name="status"][value="${selectedVehicle.VehicleStatus === 'ACTIVE' ? 'Active' : 'Inactive'}"]`);
                if (statusRadio) statusRadio.checked = true;

                // Clear any error messages
                const errorElements = document.getElementById('editVehicleForm').querySelectorAll('.error');
                errorElements.forEach(element => element.textContent = '');

                // Show modal
                document.getElementById('editVehicleModal').style.display = 'block';
            }
        }
    </script>
</body>
</html>