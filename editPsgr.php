<?php
session_start();
include 'dbConnection.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['AdminID'])) {
    header("Location: loginAdm.php");
    exit;
}

// Fetch passenger ID from the request
$passengerID = $_GET['passengerID'] ?? null;

if ($passengerID) {
    // Fetch passenger data
    $stmt = $connMe->prepare("
        SELECT u.FullName, u.EmailAddress, u.PhoneNo, u.Gender, u.BirthDate, u.EmailSecCode, u.ProfilePicture, u.UserType,
               p.Username, p.Password, p.FavPickUpLoc, p.FavDropOffLoc, p.Role, p.UserID
        FROM USER u
        JOIN PASSENGER p ON u.UserID = p.UserID
        WHERE p.PsgrID = ?
    ");
    $stmt->bind_param("s", $passengerID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Passenger not found");
    }

    $passengerData = $result->fetch_assoc();
} else {
    throw new Exception("Passenger ID not provided");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Passenger</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/fontawesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/solid.min.css">
    <style>
        /* Copy all styles from profilePsgr.php and modify as needed */
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            padding-top: 60px;
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

        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 40px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .profile-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 0 40px;
        }

        .profile-pic-container {
            position: relative;
            width: 200px;
            height: 200px;
            margin: 0 auto;
            border-radius: 50%;
            border: 3px solid #4caf50;
            overflow: hidden;
        }

        .profile-pic {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-name {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
        }

        .profile-section {
            margin-bottom: 20px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 40px;
            padding-bottom: 10px;
        }

        .section-title {
            font-size: 20px;
            font-weight: bold;
            color: #4caf50;
            margin-left: 0;
        }

        .profile-field {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            margin: 0 40px;
        }

        .field-label {
            font-weight: bold;
            color: #666;
            width: 200px;
            flex-shrink: 0;
        }

        .field-value {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            font-family: Arial, sans-serif;
        }

        .password-field {
            position: relative;
            width: 100%;
        }

        .password-field input {
            width: 100%;
            padding: 8px;
            padding-right: 35px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            font-family: Arial, sans-serif;
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
            color: #4caf50;
            z-index: 1;
        }

        .toggle-password:hover {
            color: #388e3c;
        }

        .button {
            background-color: #4caf50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        .button:hover {
            background-color: #388e3c;
        }

        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .cancel-btn {
            background-color: white;
            color: #ff4444;
            border: 2px solid #ff4444;
        }

        .cancel-btn:hover {
            background-color: #ffebeb;
        }

        .save-btn {
            background-color: #4caf50;
            color: white;
            border: none;
        }

        .save-btn:hover {
            background-color: #388e3c;
        }

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
            cursor: pointer;
        }

        .section-button {
            display: flex;
            justify-content: center;
            margin: 30px 40px;
            margin-bottom: 20px;
        }

        .error {
            display: block;
            color: red;
            font-size: 14px;
            margin-top: 5px;
            margin-left: 0;
        }

        /* Move the cancel button to bottom center */
        .page-bottom {
            display: flex;
            justify-content: center;
            margin-top: 40px;
            padding-bottom: 20px;
        }

        /* Add more styles as needed */

        /* Update the CSS for input wrappers and fields */
        .input-wrapper {
            width: 60%;  /* This controls the width of all input containers */
        }

        .field-value {
            width: 100%;  /* Make input take full width of its wrapper */
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            font-family: Arial, sans-serif;
            box-sizing: border-box;
        }

        .password-field {
            position: relative;
            width: 100%;  /* Make password field take full width of its wrapper */
        }

        .action-buttons {
            margin-top: 20px;
        }

        .delete-btn {
            background-color: white;
            color: #ff4444;
            border: 2px solid #ff4444;
            padding: 8px 15px;
        }

        .delete-btn:hover {
            background-color: #ffebeb;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <a href="viewPsgr.php" class="nav-item">
            <i class="fas fa-arrow-left"></i>
            Back
        </a>
        <div class="nav-center">
            <span>Edit Passenger Details</span>
        </div>
        <div class="nav-items right">
            <a href="profileAdm.php" class="nav-item">
                <i class="fas fa-user"></i>
                Profile
            </a>
        </div>
    </nav>

    <div class="container">
        <!-- Profile Header with Picture -->
        <div class="profile-header">
            <div class="profile-pic-container">
                <img src="<?php echo $passengerData['ProfilePicture'] ? 'data:image/jpeg;base64,'.base64_encode($passengerData['ProfilePicture']) : 'https://img.freepik.com/premium-vector/green-circle-with-white-person-inside-icon_1076610-14570.jpg'; ?>" 
                     alt="Profile Picture" class="profile-pic">
            </div>
            <div class="profile-name">
                <?php echo ucwords(strtolower($passengerData['FullName'])); ?>
            </div>
        </div>

        <!-- Account Information Section -->
        <div class="profile-section">
            <div class="section-header">
                <div class="section-title">Account Information</div>
            </div>
            <form id="editAccountForm">
                <div class="profile-field">
                    <span class="field-label">User ID</span>
                    <div class="input-wrapper">
                        <input type="text" class="field-value" value="<?php echo htmlspecialchars($passengerData['UserID']); ?>" readonly>
                    </div>
                </div>
                <div class="profile-field">
                    <span class="field-label">Passenger ID</span>
                    <div class="input-wrapper">
                        <input type="text" class="field-value" value="<?php echo htmlspecialchars($passengerID); ?>" readonly>
                    </div>
                </div>
                <div class="profile-field">
                    <span class="field-label">Username</span>
                    <div class="input-wrapper">
                        <input type="text" class="field-value" id="username" name="username" 
                               value="<?php echo htmlspecialchars($passengerData['Username']); ?>" 
                               maxlength="20" autocomplete="off" required>
                        <span id="usernameError" class="error"></span>
                    </div>
                </div>
                <div class="profile-field">
                    <span class="field-label">Current Password</span>
                    <div class="input-wrapper">
                        <div class="password-field">
                            <input type="password" class="field-value" id="currentPassword" 
                                   value="<?php echo htmlspecialchars($passengerData['Password']); ?>" readonly>
                            <button type="button" class="toggle-password" onclick="togglePassword('currentPassword')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="profile-field">
                    <span class="field-label">New Password</span>
                    <div class="input-wrapper">
                        <div class="password-field">
                            <input type="password" class="field-value" id="password" name="password" 
                                   placeholder="Enter new password (leave blank to keep current)"
                                   autocomplete="off" maxlength=16>
                            <button type="button" class="toggle-password" onclick="togglePassword('password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <span id="passwordError" class="error"></span>
                    </div>
                </div>
                <div class="profile-field">
                    <span class="field-label">Email</span>
                    <div class="input-wrapper">
                        <input type="email" class="field-value" id="email" name="email" 
                               value="<?php echo strtolower($passengerData['EmailAddress']); ?>"
                               autocomplete="off" required>
                        <span id="emailError" class="error"></span>
                    </div>
                </div>
                <div class="profile-field">
                    <span class="field-label">Current Security Code</span>
                    <div class="input-wrapper">
                        <div class="password-field">
                            <input type="password" class="field-value" id="currentSecurityCode" 
                                   value="<?php echo htmlspecialchars($passengerData['EmailSecCode']); ?>" readonly>
                            <button type="button" class="toggle-password" onclick="togglePassword('currentSecurityCode')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="profile-field">
                    <span class="field-label">New Security Code</span>
                    <div class="input-wrapper">
                        <div class="password-field">
                            <input type="password" class="field-value" id="securityCode" name="securityCode" 
                                   placeholder="Enter new security code (leave blank to keep current)" 
                                   maxlength="8" autocomplete="off">
                            <button type="button" class="toggle-password" onclick="togglePassword('securityCode')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <span id="securityCodeError" class="error"></span>
                    </div>
                </div>
                <div class="section-button">
                    <button type="submit" class="button save-btn">Save Changes</button>
                </div>
            </form>
        </div>

        <!-- Personal Information Section -->
        <div class="profile-section">
            <div class="section-header">
                <div class="section-title">Personal Information</div>
            </div>
            <form id="editPersonalForm">
                <div class="profile-field">
                    <span class="field-label">Full Name</span>
                    <div class="input-wrapper">
                        <input type="text" class="field-value" id="fullName" name="fullName" 
                               value="<?php echo ucwords(strtolower($passengerData['FullName'])); ?>" 
                               required autocomplete="off" required>
                        <span id="fullNameError" class="error"></span>
                    </div>
                </div>
                <div class="profile-field">
                    <span class="field-label">Phone Number</span>
                    <div class="input-wrapper">
                        <input type="text" class="field-value" id="phoneNo" name="phoneNo" 
                               value="<?php echo htmlspecialchars($passengerData['PhoneNo']); ?>" 
                               maxLength="12" placeholder="Format: 012-3456789" 
                               required autocomplete="off" required>
                        <span id="phoneError" class="error"></span>
                    </div>
                </div>
                <div class="profile-field">
                    <span class="field-label">Gender</span>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="gender" value="M" 
                                   <?php echo $passengerData['Gender'] === 'M' ? 'checked' : ''; ?>>
                            Male
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="gender" value="F" 
                                   <?php echo $passengerData['Gender'] === 'F' ? 'checked' : ''; ?>>
                            Female
                        </label>
                    </div>
                </div>
                <div class="profile-field">
                    <span class="field-label">Birthday Date</span>
                    <div class="input-wrapper">
                        <input type="date" class="field-value" id="birthDate" name="birthDate" 
                               value="<?php echo date('Y-m-d', strtotime($passengerData['BirthDate'])); ?>"
                               max="<?php echo date('Y-m-d'); ?>"
                               min="<?php echo date('Y-m-d', strtotime('-125 years')); ?>" required>
                    </div>
                </div>
                <div class="profile-field">
                    <span class="field-label">Role</span>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="role" value="Student" 
                                   <?php echo strtolower($passengerData['Role']) === 'student' ? 'checked' : ''; ?>>
                            Student
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="role" value="Staff" 
                                   <?php echo strtolower($passengerData['Role']) === 'staff' ? 'checked' : ''; ?>>
                            Staff
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="role" value="Visitor" 
                                   <?php echo strtolower($passengerData['Role']) === 'visitor' ? 'checked' : ''; ?>>
                            Visitor
                        </label>
                    </div>
                </div>
                <div class="profile-field">
                    <span class="field-label">User Type</span>
                    <div class="input-wrapper">
                        <input type="text" class="field-value" value="<?php echo ucwords(strtolower($passengerData['UserType'])); ?>" readonly>
                    </div>
                </div>
                <div class="section-button">
                    <button type="submit" class="button save-btn">Save Changes</button>
                </div>
            </form>
        </div>

        <!-- Preferences Section -->
        <div class="profile-section">
            <div class="section-header">
                <div class="section-title">Preferences</div>
            </div>
            <form id="editPreferencesForm">
                <div class="profile-field">
                    <span class="field-label">Favorite Pickup Location</span>
                    <div class="input-wrapper">
                        <input type="text" class="field-value" id="favPickUpLoc" name="favPickUpLoc" 
                               value="<?php echo ucwords(strtolower($passengerData['FavPickUpLoc'])); ?>" 
                               required autocomplete="off" required>
                    </div>
                </div>
                <div class="profile-field">
                    <span class="field-label">Favorite Drop-off Location</span>
                    <div class="input-wrapper">
                        <input type="text" class="field-value" id="favDropOffLoc" name="favDropOffLoc" 
                               value="<?php echo ucwords(strtolower($passengerData['FavDropOffLoc'])); ?>" 
                               required autocomplete="off" required>
                    </div>
                </div>
                <div class="section-button">
                    <button type="submit" class="button save-btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Store original values for comparison
        const originalData = {
            username: '<?php echo htmlspecialchars($passengerData['Username']); ?>',
            email: '<?php echo strtolower($passengerData['EmailAddress']); ?>',
            phoneNo: '<?php echo htmlspecialchars($passengerData['PhoneNo']); ?>'
        };

        // Add validation event listeners
        document.getElementById('username').addEventListener('blur', validateUsername);
        document.getElementById('email').addEventListener('blur', validateEmail);
        document.getElementById('phoneNo').addEventListener('blur', validatePhoneNo);
        document.getElementById('securityCode').addEventListener('input', validateSecurityCode);

        // Username validation
        async function validateUsername() {
            const username = document.getElementById('username').value.trim();
            document.getElementById('usernameError').textContent = '';
            
            if (username === originalData.username) {
                return true;
            }

            if (username.length === 0) {
                document.getElementById('usernameError').textContent = 'Username cannot be empty';
                return false;
            }

            if (username.length > 20) {
                document.getElementById('usernameError').textContent = 'Username must not exceed 20 characters';
                return false;
            }

            try {
                const response = await fetch('validateUsername.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ 
                        username: username,
                        userType: 'PASSENGER'
                    })
                });
                const data = await response.json();
                
                if (data.status === 'exists') {
                    document.getElementById('usernameError').textContent = 'Username already exists';
                    return false;
                } else {
                    document.getElementById('usernameError').textContent = '';
                    return true;
                }
            } catch (error) {
                console.error('Error:', error);
                return false;
            }
        }

        // Email validation
        async function validateEmail() {
            const email = document.getElementById('email').value.trim();
            const emailError = document.getElementById('emailError');
            
            if (email === originalData.email) {
                emailError.textContent = '';
                return true;
            }

            if (email.length === 0) {
                emailError.textContent = 'Email cannot be empty';
                return false;
            }

            if (!/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(email)) {
                emailError.textContent = 'Invalid email format. Eg: abcd@gmail.com';
                return false;
            }

            try {
                const response = await fetch('validateEmail.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ 
                        email: email,
                        userType: 'PASSENGER',
                        currentEmail: originalData.email
                    })
                });
                const data = await response.json();
                
                if (data.status === 'exists') {
                    emailError.textContent = 'Email already exists';
                    return false;
                } else {
                    emailError.textContent = '';
                    return true;
                }
            } catch (error) {
                console.error('Error:', error);
                return false;
            }
        }

        // Phone number validation
        async function validatePhoneNo() {
            const phoneNo = document.getElementById('phoneNo').value.trim();
            const phoneError = document.getElementById('phoneError');
            
            if (phoneNo === originalData.phoneNo) {
                phoneError.textContent = '';
                return true;
            }

            if (phoneNo.length === 0) {
                phoneError.textContent = 'Phone number cannot be empty';
                return false;
            }

            if (!/^01\d-\d{7,8}$/.test(phoneNo)) {
                phoneError.textContent = 'Invalid phone number format. Example: 012-3456789';
                return false;
            }

            try {
                const response = await fetch('validatePhoneNo.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ 
                        phoneNo: phoneNo,
                        userType: 'PASSENGER'
                    })
                });
                const data = await response.json();
                
                if (data.status === 'exists') {
                    phoneError.textContent = 'Phone number already exists';
                    return false;
                } else {
                    phoneError.textContent = '';
                    return true;
                }
            } catch (error) {
                console.error('Error:', error);
                return false;
            }
        }

        // Security code validation
        function validateSecurityCode() {
            const secCode = document.getElementById('securityCode').value;
            const securityCodeError = document.getElementById('securityCodeError');
            
            if (!secCode) {
                securityCodeError.textContent = '';
                return true; // Empty is OK as it means no change
            }

            let errorMessage = [];
            if (secCode.length < 4 || secCode.length > 8) {
                errorMessage.push('Security code must be between 4-8 characters');
            }
            if (!/[a-z]/.test(secCode)) {
                errorMessage.push('Must contain lowercase letter');
            }
            if (!/[A-Z]/.test(secCode)) {
                errorMessage.push('Must contain uppercase letter');
            }
            if (!/[0-9]/.test(secCode)) {
                errorMessage.push('Must contain number');
            }
            if (!/[!@#$%^&*]/.test(secCode)) {
                errorMessage.push('Must contain special character (!@#$%^&*)');
            }
            
            securityCodeError.textContent = errorMessage.join(', ');
            return errorMessage.length === 0;
        }

        // Save all changes
        async function saveAllChanges() {
            // Validate all fields first
            const isUsernameValid = await validateUsername();
            const isEmailValid = await validateEmail();
            const isPhoneValid = await validatePhoneNo();
            const isSecurityCodeValid = validateSecurityCode();

            if (!isUsernameValid || !isEmailValid || !isPhoneValid || !isSecurityCodeValid) {
                alert('Please fix all errors before saving');
                return;
            }

            // Gather all form data
            const formData = {
                psgrId: '<?php echo $passengerID; ?>',
                userId: '<?php echo $passengerData['UserID']; ?>',
                username: document.getElementById('username').value.trim(),
                email: document.getElementById('email').value.trim(),
                securityCode: document.getElementById('securityCode').value,
                fullName: document.getElementById('fullName').value.trim().toUpperCase(),
                phoneNo: document.getElementById('phoneNo').value.trim(),
                gender: document.querySelector('input[name="gender"]:checked').value,
                birthDate: document.getElementById('birthDate').value,
                role: document.querySelector('input[name="role"]:checked').value.toUpperCase(),
                favPickUpLoc: document.getElementById('favPickUpLoc').value.trim().toUpperCase(),
                favDropOffLoc: document.getElementById('favDropOffLoc').value.trim().toUpperCase()
            };

            try {
                const response = await fetch('updateAccountPsgr.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        ...formData,
                        updateType: 'all'
                    })
                });

                const data = await response.json();
                if (data.status === 'success') {
                    alert('Passenger information updated successfully');
                    window.location.href = 'viewPsgr.php';
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while updating passenger information');
            }
        }

        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = event.currentTarget.querySelector('i');
            
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

        async function saveAccountChanges() {
            try {
                // Reset all error messages first
                document.getElementById('usernameError').textContent = '';
                document.getElementById('emailError').textContent = '';
                document.getElementById('passwordError').textContent = '';
                document.getElementById('securityCodeError').textContent = '';

                let hasErrors = false;
                
                // Username validation
                const username = document.getElementById('username').value.trim();
                if (username !== originalData.username) {
                    try {
                        const response = await fetch('validateUsername.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ 
                                username: username,
                                userType: 'PASSENGER'
                            })
                        });
                        const data = await response.json();
                        if (data.status === 'exists') {
                            document.getElementById('usernameError').textContent = 'Username already exists';
                            hasErrors = true;
                        }
                    } catch (error) {
                        console.error('Username validation error:', error);
                    }
                }

                // Email validation
                const email = document.getElementById('email').value.trim();
                if (email !== originalData.email) {
                    if (!/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(email)) {
                        document.getElementById('emailError').textContent = 'Invalid email format';
                        hasErrors = true;
                    } else {
                        try {
                            const response = await fetch('validateEmail.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ 
                                    email: email,
                                    userType: 'PASSENGER',
                                    currentEmail: originalData.email
                                })
                            });
                            const data = await response.json();
                            if (data.status === 'exists') {
                                document.getElementById('emailError').textContent = 'Email already exists';
                                hasErrors = true;
                            }
                        } catch (error) {
                            console.error('Email validation error:', error);
                        }
                    }
                }

                // Password validation (if provided)
                const password = document.getElementById('password').value;
                if (password) {
                    if (password.length < 8 || password.length > 16) {
                        document.getElementById('passwordError').textContent = 'Password must be between 8 and 16 characters';
                        hasErrors = true;
                    }
                }

                // Security code validation (if provided)
                const securityCode = document.getElementById('securityCode').value;
                if (securityCode) {
                    let secErrors = [];
                    if (securityCode.length < 4 || securityCode.length > 8) {
                        secErrors.push('Length must be between 4-8 characters');
                    }
                    if (!/[a-z]/.test(securityCode)) secErrors.push('Must contain lowercase letter');
                    if (!/[A-Z]/.test(securityCode)) secErrors.push('Must contain uppercase letter');
                    if (!/[0-9]/.test(securityCode)) secErrors.push('Must contain number');
                    if (!/[!@#$%^&*]/.test(securityCode)) secErrors.push('Must contain special character (!@#$%^&*)');
                    
                    if (secErrors.length > 0) {
                        document.getElementById('securityCodeError').textContent = secErrors.join(', ');
                        hasErrors = true;
                    }
                }

                if (hasErrors) {
                    return;
                }

                // If no errors, proceed with save
                const formData = {
                    updateType: 'account',
                    psgrId: '<?php echo $passengerID; ?>',
                    userId: '<?php echo $passengerData['UserID']; ?>',
                    username: username,
                    password: password,
                    email: email,
                    securityCode: securityCode
                };

                const response = await fetch('editAccountPsgr.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();
                if (data.status === 'success') {
                    alert('Account information updated successfully');
                    location.reload();
                } else {
                    // Show error message from server if any
                    const errorField = data.field + 'Error';
                    if (document.getElementById(errorField)) {
                        document.getElementById(errorField).textContent = data.message;
                    } else {
                        alert(data.message || 'An error occurred');
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while updating account information');
            }
        }

        async function savePersonalChanges() {
            // Reset error messages
            document.getElementById('fullNameError').textContent = '';
            document.getElementById('phoneError').textContent = '';

            let hasErrors = false;

            // Validate full name
            const fullName = document.getElementById('fullName').value.trim();
            if (fullName.length === 0) {
                document.getElementById('fullNameError').textContent = 'Full name cannot be empty';
                hasErrors = true;
            }

            // Validate phone number
            const phoneNo = document.getElementById('phoneNo').value.trim();
            if (phoneNo.length === 0) {
                document.getElementById('phoneError').textContent = 'Phone number cannot be empty';
                hasErrors = true;
            } else if (!/^01\d-\d{7,8}$/.test(phoneNo)) {
                document.getElementById('phoneError').textContent = 'Invalid format. Example: 012-3456789';
                hasErrors = true;
            } else if (phoneNo !== originalData.phoneNo) {
                // Add check for existing phone number
                try {
                    const response = await fetch('validatePhoneNo.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ 
                            phoneNo: phoneNo,
                            userType: 'PASSENGER'
                        })
                    });
                    const data = await response.json();
                    if (data.status === 'exists') {
                        document.getElementById('phoneError').textContent = 'Phone number already exists';
                        hasErrors = true;
                    }
                } catch (error) {
                    console.error('Phone validation error:', error);
                }
            }

            if (hasErrors) {
                return;
            }

            // Continue with the save if no errors
            const formData = {
                updateType: 'personal',
                psgrId: '<?php echo $passengerID; ?>',
                userId: '<?php echo $passengerData['UserID']; ?>',
                fullName: document.getElementById('fullName').value.trim().toUpperCase(),
                phoneNo: document.getElementById('phoneNo').value.trim(),
                gender: document.querySelector('input[name="gender"]:checked').value,
                birthDate: document.getElementById('birthDate').value,
                role: document.querySelector('input[name="role"]:checked').value.toUpperCase()
            };

            try {
                const response = await fetch('editAccountPsgr.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
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
        }

        async function savePreferencesChanges() {
            const favPickUpLoc = document.getElementById('favPickUpLoc').value.trim();
            const favDropOffLoc = document.getElementById('favDropOffLoc').value.trim();

            if (!favPickUpLoc || !favDropOffLoc) {
                alert('Both pickup and drop-off locations are required');
                return;
            }

            // Continue with the save if no errors
            const formData = {
                updateType: 'preferences',
                psgrId: '<?php echo $passengerID; ?>',
                userId: '<?php echo $passengerData['UserID']; ?>',
                favPickUpLoc: favPickUpLoc.toUpperCase(),
                favDropOffLoc: favDropOffLoc.toUpperCase()
            };

            try {
                const response = await fetch('editAccountPsgr.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();
                if (data.status === 'success') {
                    alert('Preferences updated successfully');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while updating preferences');
            }
        }

        document.getElementById('editAccountForm').addEventListener('submit', async function(e) {
            e.preventDefault(); // Prevent default form submission
            await saveAccountChanges();
        });

        document.getElementById('editPersonalForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            await savePersonalChanges();
        });

        document.getElementById('editPreferencesForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            await savePreferencesChanges();
        });
    </script>
</body>
</html> 