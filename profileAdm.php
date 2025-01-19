<?php
session_start();
include 'dbConnection.php';

// Check if user is logged in
if (!isset($_SESSION['AdminID']) || !isset($_SESSION['UserID'])) {

    header("Location: loginAdm.php");
    exit;
}

// Fetch user data
$adminID = $_SESSION['AdminID'];
$userID = $_SESSION['UserID'];

try {
    // Get user and admin information
    $stmt = $connMe->prepare("
        SELECT u.FullName, u.EmailAddress, u.PhoneNo, u.Gender, u.BirthDate, u.EmailSecCode, u.ProfilePicture,
               u.SecQues1, u.SecQues2, u.Status,
               a.Username, a.Password, a.Department, a.Position
        FROM USER u
        JOIN ADMIN a ON u.UserID = a.UserID
        WHERE a.AdminID = ? AND u.UserID = ?
    ");
    
    $stmt->bind_param("ss", $adminID, $userID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("User not found");
    }
    
    $userData = $result->fetch_assoc();
    
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
    <title>Administrator Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/fontawesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/solid.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            padding-top: 60px; /* Space for fixed navbar */
        }

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
        }

        .nav-brand img {
            height: 50px; /* Adjust based on your logo size */
            width: auto;
            pointer-events: none; /* Makes the image non-clickable */
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

        .nav-items {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-items.right {
            margin-left: auto;
        }

        .nav-item {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: background-color 0.3s;
        }

        .nav-item:hover {
            background-color: #388e3c;
        }

        .nav-item.active {
            background-color: #388e3c;
        }

        .nav-item i {
            font-size: 18px;
        }

        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

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
            object-position: center;
        }

        .profile-name {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .edit-icon {
            margin-left: 10px;
            color: #4caf50;
            cursor: pointer;
            font-size: 18px;
        }

        .profile-section {
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #4caf50;
        }

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

        .change-btn {
            background-color: #4caf50;
            color: white;
            border: none;
            padding: 5px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        .change-btn:hover {
            background-color: #388e3c;
        }

        .logout-btn {
            background-color: #ff4444;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 20px;
            width: 100%;
            font-size: 16px;
        }

        .logout-btn:hover {
            background-color: #cc0000;
        }

        .id-info {
            color: #666;
            margin: 5px 0;
            font-size: 16px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .edit-btn {
            background-color: #4caf50;
            color: white;
            border: none;
            padding: 5px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .edit-btn:hover {
            background-color: #388e3c;
        }

        .edit-btn i {
            font-size: 16px;
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

        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            align-items: stretch; /* Ensure buttons stretch to same height */
        }

        .delete-btn, .logout-btn {
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 50%;
            transition: all 0.3s ease;
            display: inline-block; /* Change from flex to inline-block */
            text-align: center; /* Center the text */
            margin: 0; /* Remove any default margins */
            box-sizing: border-box; /* Include padding in width calculation */
        }

        .delete-btn {
            background-color: white;
            color: #ff4444;
            border: 2px solid #ff4444;
        }

        .delete-btn:hover {
            background-color: #ffebeb;
        }

        .logout-btn {
            background-color: #ff4444;
            color: white;
            border: none;
        }

        .logout-btn:hover {
            background-color: #cc0000;
        }

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
            padding: 20px 0;
        }

        .modal-content {
            position: relative;
            background-color: white;
            margin: 20px auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
        }

        .modal h2 {
            font-size: 24px;
            margin-bottom: 20px;
            font-family: Arial, sans-serif;
            color: #333;
        }

        input {
            font-size: 16px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .readonly {
            background-color: #f5f5f5;
            cursor: not-allowed;
        }

        .error {
            color: red;
            font-size: 16px;
            margin-top: 5px;
        }

        .password-field {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-field input {
            padding-right: 35px;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            background: none;
            border: none;
            cursor: pointer;
            color: #4caf50;
        }

        .toggle-password:hover {
            color: #388e3c;
        }

        .modal .button-group {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .modal .button {
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 50%;
            transition: all 0.3s ease;
            font-family: Arial, sans-serif;
        }

        .modal .save-btn {
            background-color: #4caf50;
            color: white;
            border: none;
        }

        .modal .save-btn:hover {
            background-color: #388e3c;
        }

        .modal .cancel-btn {
            background-color: white;
            color: #ff4444;
            border: 2px solid #ff4444;
        }

        .modal .cancel-btn:hover {
            background-color: #ffebeb;
        }

        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 5px;
        }

        .radio-label {
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            font-family: Arial, sans-serif;
        }

        .radio-label input[type="radio"] {
            width: auto;
            cursor: pointer;
        }

        /* Update input and radio label styles */
        .form-group input,
        .form-group select,
        .radio-label,
        .modal h2,
        .button {
            font-family: Arial, sans-serif;  /* Match the profile page font */
        }

        /* Update placeholder text font */
        .form-group input::placeholder {
            font-family: Arial, sans-serif;
        }

        /* Ensure consistent font in Firefox */
        .form-group input:-moz-placeholder {
            font-family: Arial, sans-serif;
        }

        /* Ensure consistent font in Chrome */
        .form-group input::-webkit-input-placeholder {
            font-family: Arial, sans-serif;
        }

        /* Ensure consistent font in IE */
        .form-group input:-ms-input-placeholder {
            font-family: Arial, sans-serif;
        }

        .field-value[readonly] {
            border: none;
            background: none;
            color: #333;
            font-family: Arial, sans-serif;
            padding: 0;
            width: 50%;
            text-align: right;
        }

        .field-value[readonly]:focus {
            outline: none;
        }

        .change-pic-btn {
            position: absolute;
            bottom: 0;
            right: 0;
            transform: translate(25%, 25%);
            background-color: #4caf50;
            color: white;
            border: 2px solid white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s;
            z-index: 100;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .change-pic-btn:hover {
            background-color: #388e3c;
        }

        .change-pic-btn {
            border: 2px solid white;
        }

        .cropper-container {
            max-width: 100%;
            height: 400px;
            margin: 20px 0;
        }

        #cropperImage {
            max-width: 100%;
            display: block;
        }

        /* Override Cropper.js styles for circular crop */
        .cropper-view-box,
        .cropper-face {
            border-radius: 50%;
        }

        /* Optional: Add a subtle shadow to make it stand out more */
        .change-pic-btn {
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .question-text {
            color: #666;
            font-style: italic;
            margin-left: 10px;
            font-weight: normal;
        }
    </style>
</head>
<body>
    <!-- Top Navigation Bar -->
    <nav class="navbar">
        <!-- Left side - Home -->
        <div class="nav-items">
            <a href="homePageAdm.php" class="nav-item">
                <i class="fas fa-home"></i>
                Home
            </a>
        </div>

        <!-- Center section with logo and text -->
        <div class="nav-center">
            <img src="Image/logo" alt="UTeM Peer Ride Logo" class="center-logo">
            <span>UTeM Peer Ride - Administrator Portal</span>
        </div>

        <!-- Right Side Items -->
        <div class="nav-items right">
            <a href="profileAdm.php" class="nav-item active">
                <i class="fas fa-user"></i>
                Profile
            </a>
        </div>
    </nav>

    <!-- Profile Section -->
    <div class="container">
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
                <span class="field-label">Admin ID</span>
                <span class="field-value"><?php echo htmlspecialchars($_SESSION['AdminID']); ?></span>
            </div>
            <div class="profile-field">
                <span class="field-label">Status</span>
                <span class="field-value"><?php echo ucwords(strtolower($userData['Status'])); ?></span>
            </div>
            <div class="profile-field">
                <span class="field-label">Username</span>
                <span class="field-value"><?php echo htmlspecialchars($userData['Username']); ?></span>
            </div>
            <div class="profile-field">
                <span class="field-label">Password</span>
                <span class="field-value">********</span>
            </div>
            <div class="profile-field">
                <span class="field-label">Email</span>
                <span class="field-value"><?php echo strtolower($userData['EmailAddress']); ?></span>
            </div>
            <div class="profile-field">
                <span class="field-label">Security Code</span>
                <span class="field-value"><?php echo str_repeat('*', 8); ?></span>
            </div>
            <div class="profile-field">
                <span class="field-label">Security Question 1</span>
                <span class="field-value">What is your favourite food?</span>
            </div>
            <div class="profile-field">
                <span class="field-label">Answer 1</span>
                <span class="field-value"><?php echo str_repeat('*', strlen($userData['SecQues1'])); ?></span>
            </div>
            <div class="profile-field">
                <span class="field-label">Security Question 2</span>
                <span class="field-value">What first city did you visited on your vacation?</span>
            </div>
            <div class="profile-field">
                <span class="field-label">Answer 2</span>
                <span class="field-value"><?php echo str_repeat('*', strlen($userData['SecQues2'])); ?></span>
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
                <!-- ucwords(): Convert the first character of each word to uppercase -->
                <span class="field-value"><?php echo ucwords(strtolower($userData['FullName'])); ?></span>
            </div>
            <div class="profile-field">
                <span class="field-label">Phone Number</span>
                <!-- htmlspecialchars(): Convert special characters in a string to HTML entities -->
                <span class="field-value"><?php echo htmlspecialchars($userData['PhoneNo']); ?></span>
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

        <!-- Job Information Section -->
        <div class="profile-section">
            <div class="section-header">
                <div class="section-title">Job Information</div>
                <button class="edit-btn" onclick="editSection('job')">
                    <i class="fas fa-edit"></i> Edit
                </button>
            </div>
            <div class="profile-field">
                <span class="field-label">Department</span>
                <input type="text" class="field-value" 
                       value="<?php echo ($userData['Department']); ?>" 
                       readonly>
            </div>
            <div class="profile-field">
                <span class="field-label">Position</span>
                <input type="text" class="field-value" 
                       value="<?php echo ucwords(strtolower($userData['Position'])); ?>" 
                       readonly>
            </div>
        </div>

        <div class="button-group">
            <button class="delete-btn" onclick="deleteAccount()">Delete Account</button>
            <button class="logout-btn" onclick="logout()">Log Out</button>
        </div>
    </div>

    <div id="editAccountModal" class="modal" style="display: none;">
        <div class="modal-content">
            <h2>Edit Account Information</h2>
            <form id="editAccountForm">
                <div class="form-group">
                    <label>User ID:</label>
                    <input type="text" value="<?php echo $_SESSION['UserID']; ?>" readonly class="readonly">
                </div>

                <div class="form-group">
                    <label>Admin ID:</label>
                    <input type="text" value="<?php echo $_SESSION['AdminID']; ?>" readonly class="readonly">
                </div>

                <div class="form-group">
                    <label>Status:</label>
                    <input type="text" value="<?php echo ucwords(strtolower($userData['Status'])); ?>" readonly class="readonly">
                </div>

                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($userData['Username']); ?>" maxlength="20">
                    <span id="usernameError" class="error"></span>
                </div>

                <div class="form-group">
                    <label>Current Password:</label>
                    <div class="password-field">
                        <input type="password" id="currentPassword" value="<?php echo htmlspecialchars($userData['Password']); ?>" readonly class="readonly">
                        <button type="button" class="toggle-password" onclick="togglePassword('currentPassword')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label>New Password:</label>
                    <div class="password-field">
                        <input type="password" id="password" name="password" placeholder="Enter new password (leave blank to keep current)">
                        <button type="button" class="toggle-password" onclick="togglePassword('password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <span id="passwordError" class="error"></span>
                </div>

                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo strtolower($userData['EmailAddress']); ?>" autocomplete="off">
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

                <div class="form-group">
                    <label>Security Question 1:</label>
                    <label class="question-text">What is your favourite food?</label>
                </div>
                <div class="form-group">
                    <label>Current Answer 1:</label>
                    <div class="password-field">
                        <input type="password" id="currentSecQues1" 
                               value="<?php echo htmlspecialchars($userData['SecQues1']); ?>" 
                               readonly class="readonly">
                        <button type="button" class="toggle-password" onclick="togglePassword('currentSecQues1')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label>New Answer 1:</label>
                    <div class="password-field">
                        <input type="password" id="secQues1" name="secQues1" 
                               placeholder="Enter new answer (leave blank to keep current)"
                               maxLength="30">
                        <button type="button" class="toggle-password" onclick="togglePassword('secQues1')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label>Security Question 2:</label>
                    <label class="question-text">What first city did you visited on your vacation?</label>
                </div>
                <div class="form-group">
                    <label>Current Answer 2:</label>
                    <div class="password-field">
                        <input type="password" id="currentSecQues2" 
                               value="<?php echo htmlspecialchars($userData['SecQues2']); ?>" 
                               readonly class="readonly">
                        <button type="button" class="toggle-password" onclick="togglePassword('currentSecQues2')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label>New Answer 2:</label>
                    <div class="password-field">
                        <input type="password" id="secQues2" name="secQues2" 
                               placeholder="Enter new answer (leave blank to keep current)"
                               maxLength="50">
                        <button type="button" class="toggle-password" onclick="togglePassword('secQues2')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="button-group">
                    <button type="button" class="button cancel-btn" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="button save-btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editPersonalModal" class="modal" style="display: none;">
        <div class="modal-content">
            <h2>Edit Personal Information</h2>
            <form id="editPersonalForm">
                <div class="form-group">
                    <label>Full Name:</label>
                    <input type="text" id="fullName" name="fullName" 
                           value="<?php echo ucwords(strtolower($userData['FullName'])); ?>" autocomplete="off">
                    <span id="fullNameError" class="error"></span>
                </div>

                <div class="form-group">
                    <label>Phone Number:</label>
                    <input type="text" id="phoneNo" name="phoneNo" 
                           value="<?php echo htmlspecialchars($userData['PhoneNo']); ?>"
                           maxLength="12"
                           placeholder="Format: 012-3456789" autocomplete="off">
                    <span id="phoneError" class="error"></span>
                </div>

                <div class="form-group">
                    <label>Gender:</label>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="gender" value="M" 
                                   <?php echo $userData['Gender'] === 'M' ? 'checked' : ''; ?>>
                            Male
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="gender" value="F" 
                                   <?php echo $userData['Gender'] === 'F' ? 'checked' : ''; ?>>
                            Female
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Birthday Date:</label>
                    <input type="date" id="birthDate" name="birthDate" 
                           value="<?php echo date('Y-m-d', strtotime($userData['BirthDate'])); ?>"
                           max="<?php echo date('Y-m-d'); ?>"
                           min="<?php echo date('Y-m-d', strtotime('-125 years')); ?>" required>
                    <span id="birthDateError" class="error"></span>
                </div>

                <div class="button-group">
                    <button type="button" class="button cancel-btn" onclick="closePersonalModal()">Cancel</button>
                    <button type="submit" class="button save-btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editJobModal" class="modal" style="display: none;">
        <div class="modal-content">
            <h2>Edit Job Information</h2>
            <form id="editJobForm">
                <div class="form-group">
                    <label>Department:</label>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="department" value="HEPA" 
                                   <?php echo $userData['Department'] === 'HEPA' ? 'checked' : ''; ?>>
                            HEPA
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="department" value="SPKU" 
                                   <?php echo $userData['Department'] === 'SPKU' ? 'checked' : ''; ?>>
                            SPKU
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Position:</label>
                    <input type="text" id="position" name="position" autocomplete="off"
                           value="<?php echo ucwords(strtolower($userData['Position'])); ?>">
                    <span id="positionError" class="error"></span>
                </div>

                <div class="button-group">
                    <button type="button" class="button cancel-btn" onclick="closeJobModal()">Cancel</button>
                    <button type="submit" class="button save-btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div id="cropperModal" class="modal" style="display: none;">
        <div class="modal-content">
            <h2>Adjust Profile Picture</h2>
            <div class="cropper-container">
                <img id="cropperImage" src="" alt="Image to crop">
            </div>
            <div class="button-group">
                <button type="button" class="button cancel-btn" onclick="closeCropperModal()">Cancel</button>
                <button type="button" class="button save-btn" onclick="saveCroppedImage()">Save</button>
            </div>
        </div>
    </div>

    <script>
        function editSection(section) {
            if (section === 'account') {
                document.getElementById('editAccountModal').style.display = 'block';
            } else if (section === 'personal') {
                document.getElementById('editPersonalModal').style.display = 'block';
            } else if (section === 'job') {
                document.getElementById('editJobModal').style.display = 'block';
            }
        }

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
            
            // Reset gender radio button
            const gender = '<?php echo $userData['Gender']; ?>';
            const genderRadio = document.querySelector(`input[name="gender"][value="${gender}"]`);
            if (genderRadio) genderRadio.checked = true;
            
            // Clear any error messages
            document.getElementById('fullNameError').textContent = '';
            document.getElementById('phoneError').textContent = '';
            
            // Hide modal
            document.getElementById('editPersonalModal').style.display = 'none';
        }

        function closeJobModal() {
            // Reset form values to original values
            document.getElementById('position').value = '<?php echo ucwords(strtolower($userData['Position'])); ?>';
            
            // Reset department radio button
            const department = '<?php echo $userData['Department']; ?>';
            const departmentRadio = document.querySelector(`input[name="department"][value="${department}"]`);
            if (departmentRadio) departmentRadio.checked = true;
            
            // Clear any error messages
            document.getElementById('positionError').textContent = '';
            
            // Hide modal
            document.getElementById('editJobModal').style.display = 'none';
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

            // Get security question values
            const secQues1 = document.getElementById('secQues1').value.trim();
            const secQues2 = document.getElementById('secQues2').value.trim();

            // Validate security questions
            if (!secQues1 || !secQues2) {
                alert('Security question answers cannot be empty');
                return;
            }

            try {
                const response = await fetch('updateAccountAdm.php', {
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
                        secQues1: secQues1,
                        secQues2: secQues2,
                        userId: '<?php echo $_SESSION['UserID']; ?>',
                        adminId: '<?php echo $_SESSION['AdminID']; ?>'
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
                        userType: 'ADMIN',
                        currentUsername: currentUsername // Add current username to request
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
                        userType: 'ADMIN',
                        currentEmail: currentEmail // Add current email to request
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

        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logoutAdm.php';
            }
        }

        function deleteAccount() {
            if (confirm('Are you sure you want to delete your admin account? This will remove your admin privileges.')) {
                fetch('deleteAccountAdm.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        userId: '<?php echo $_SESSION['UserID']; ?>',
                        adminId: '<?php echo $_SESSION['AdminID']; ?>'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert(data.message);
                        window.location.href = 'mainPagePsgrDri.html';
                    } else {
                        alert('Failed to delete account: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the account');
                });
            }
        }

        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = event.currentTarget.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                // Change the icon to show the password
                icon.classList.remove('fa-eye');
                // Change the icon to hide the password
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Personal Information form validation and submission
        document.getElementById('editPersonalForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const fullName = document.getElementById('fullName').value.trim();
            
            // Basic validation
            if (!fullName) {
                document.getElementById('fullNameError').textContent = 'Full name cannot be empty';
                return;
            }

            // Validate phone number
            const phoneNo = document.getElementById('phoneNo').value.trim();
            if (!/^01\d-\d{7,8}$/.test(phoneNo)) {
                document.getElementById('phoneError').textContent = 'Invalid phone number format. Example: 012-3456789';
                return;
            }

            try {
                const response = await fetch('updateAccountAdm.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        updateType: 'personal',
                        fullName: fullName.toUpperCase(),
                        phoneNo: phoneNo,
                        gender: document.querySelector('input[name="gender"]:checked').value,
                        birthDate: document.getElementById('birthDate').value,
                        userId: '<?php echo $_SESSION['UserID']; ?>',
                        adminId: '<?php echo $_SESSION['AdminID']; ?>'
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

        // Add input event listener for validation
        document.getElementById('fullName').addEventListener('input', (e) => {
            const value = e.target.value.trim();
            if (!value) {
                document.getElementById('fullNameError').textContent = 'Full name cannot be empty';
            } else {
                document.getElementById('fullNameError').textContent = '';
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
                        userType: 'ADMIN',
                        currentPhoneNo: currentPhoneNo // Add current phone number to request
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

        // Add preferences form submission handler
        document.getElementById('editJobForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const position = document.getElementById('position').value.trim();
            
            // Basic validation
            if (!position) {
                document.getElementById('positionError').textContent = 'Position cannot be empty';
                return;
            }

            try {
                const response = await fetch('updateAccountAdm.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        updateType: 'job',
                        department: document.querySelector('input[name="department"]:checked').value.toUpperCase(), // Convert role to uppercase
                        position: position.toUpperCase(),
                        userId: '<?php echo $_SESSION['UserID']; ?>',
                        adminId: '<?php echo $_SESSION['AdminID']; ?>'
                    })
                });

                const data = await response.json();
                if (data.status === 'success') {
                    alert('Job information updated successfully');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while updating job information');
            }
        });

        // Add input event listeners for validation
        document.getElementById('position').addEventListener('input', (e) => {
            const value = e.target.value.trim();
            if (!value) {
                document.getElementById('positionError').textContent = 'Position cannot be empty';
            } else {
                document.getElementById('positionError').textContent = '';
            }
        });

        let cropper = null;

        function uploadProfilePic(input) {
            if (input.files && input.files[0]) {
                const maxSize = 5 * 1024 * 1024; // 5MB
                if (input.files[0].size > maxSize) {
                    alert('File size must be less than 5MB');
                    return;
                }

                // Check file type
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(input.files[0].type)) {
                    alert('Only JPG, PNG and GIF files are allowed');
                    return;
                }

                // Show cropper modal
                const reader = new FileReader();
                reader.onload = function(e) {
                    const cropperImage = document.getElementById('cropperImage');
                    cropperImage.src = e.target.result;
                    
                    // Initialize cropper
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
                        background: false
                    });

                    document.getElementById('cropperModal').style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function closeCropperModal() {
            document.getElementById('cropperModal').style.display = 'none';
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
        }

        function saveCroppedImage() {
            if (!cropper) return;

            const canvas = cropper.getCroppedCanvas({
                width: 400,
                height: 400
            });

            canvas.toBlob(function(blob) {
                const formData = new FormData();
                formData.append('profilePic', blob, 'profile.jpg');
                formData.append('userId', '<?php echo $_SESSION['UserID']; ?>');

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
                    alert('An error occurred while uploading the profile picture');
                });

                closeCropperModal();
            }, 'image/jpeg', 0.9);
        }

        // Close cropper modal when clicking outside
        window.onclick = function(event) {
            const cropperModal = document.getElementById('cropperModal');
            if (event.target === cropperModal) {
                closeCropperModal();
            }
        }
    </script>
</body>
</html> 