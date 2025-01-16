<?php
session_start();
include 'dbConnection.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['AdminID'])) {
    header("Location: loginAdm.php");
    exit;
}

// Fetch driver ID from the request
$driverID = $_GET['driverID'] ?? null;

if ($driverID) {
    // Fetch driver data
    $stmt = $connMe->prepare("
        SELECT u.FullName, u.EmailAddress, u.PhoneNo, u.Gender, u.BirthDate, 
               u.EmailSecCode, u.ProfilePicture, u.UserType,
               u.SecQues1, u.SecQues2,
               d.Username, d.Password, d.LicenseNo, d.LicenseExpDate, d.EHailingLicense,
               d.StickerExpDate, d.Availability, d.CompletedRide, d.UserID
        FROM USER u
        JOIN DRIVER d ON u.UserID = d.UserID
        WHERE d.DriverID = ?
    ");
    $stmt->bind_param("s", $driverID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Driver not found");
    }

    $driverData = $result->fetch_assoc();
} else {
    throw new Exception("Driver ID not provided");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Driver</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/fontawesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/solid.min.css">
    <style>
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

        .input-wrapper {
            width: 60%;
        }

        .field-value {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            font-family: Arial, sans-serif;
            box-sizing: border-box;
        }

        .password-field {
            position: relative;
            width: 100%;
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
        }

        .button:hover {
            background-color: #388e3c;
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
            opacity: 0.8;
        }

        .error:not(:empty) {
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 0.8;
                transform: translateY(0);
            }
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
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <a href="viewDri.php" class="nav-item">
            <i class="fas fa-arrow-left"></i>
            Back
        </a>
        <div class="nav-center">Edit Driver Details</div>
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
                <img src="<?php echo $driverData['ProfilePicture'] ? 'data:image/jpeg;base64,'.base64_encode($driverData['ProfilePicture']) : 'https://img.freepik.com/premium-vector/green-circle-with-white-person-inside-icon_1076610-14570.jpg'; ?>" 
                     alt="Profile Picture" class="profile-pic">
            </div>
            <div class="profile-name">
                <?php echo ucwords(strtolower($driverData['FullName'])); ?>
            </div>
        </div>

        <!-- Account Information Section -->
        <form id="editAccountForm" class="profile-section">
            <div class="section-header">
                <h2 class="section-title">Account Information</h2>
            </div>

            <div class="profile-field">
                <span class="field-label">User ID</span>
                <div class="input-wrapper">
                    <input type="text" class="field-value" value="<?php echo $driverData['UserID']; ?>" readonly>
                </div>
            </div>

            <div class="profile-field">
                <span class="field-label">Driver ID</span>
                <div class="input-wrapper">
                    <input type="text" class="field-value" value="<?php echo $driverID; ?>" readonly>
                </div>
            </div>

            <div class="profile-field">
                <span class="field-label">Username</span>
                <div class="input-wrapper">
                    <input type="text" class="field-value" id="username" name="username" 
                           value="<?php echo htmlspecialchars($driverData['Username']); ?>" 
                           maxlength="20" autocomplete="off" required>
                    <span id="usernameError" class="error"></span>
                </div>
            </div>

            <!-- Continue Account Information Section -->
            <div class="profile-field">
                <span class="field-label">Current Password</span>
                <div class="input-wrapper">
                    <div class="password-field">
                        <input type="password" class="field-value" id="currentPassword" 
                               value="<?php echo htmlspecialchars($driverData['Password']); ?>" readonly>
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
                               maxlength="16" autocomplete="off">
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
                           value="<?php echo strtolower($driverData['EmailAddress']); ?>"
                           autocomplete="off" required>
                    <span id="emailError" class="error"></span>
                </div>
            </div>

            <div class="profile-field">
                <span class="field-label">Current Security Code</span>
                <div class="input-wrapper">
                    <div class="password-field">
                        <input type="password" class="field-value" id="currentSecurityCode" 
                               value="<?php echo htmlspecialchars($driverData['EmailSecCode']); ?>" readonly>
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

            <div class="profile-field">
                <span class="field-label">Security Question 1:</span>
                <label class="question-text">What is your favourite food?</label>
            </div>
            <div class="profile-field">
                <span class="field-label">Current Answer 1:</span>
                <div class="input-wrapper">
                    <div class="password-field">
                        <input type="password" class="field-value" id="currentSecQues1" 
                               value="<?php echo htmlspecialchars($driverData['SecQues1']); ?>" readonly>
                        <button type="button" class="toggle-password" onclick="togglePassword('currentSecQues1')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="profile-field">
                <span class="field-label">New Answer 1:</span>
                <div class="input-wrapper">
                    <div class="password-field">
                        <input type="password" class="field-value" id="secQues1" name="secQues1" 
                               placeholder="Enter new answer (leave blank to keep current)"
                               maxLength="30">
                        <button type="button" class="toggle-password" onclick="togglePassword('secQues1')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="profile-field">
                <span class="field-label">Security Question 2:</span>
                <label class="question-text">What first city did you visited on your vacation?</label>
            </div>
            <div class="profile-field">
                <span class="field-label">Current Answer 2:</span>
                <div class="input-wrapper">
                    <div class="password-field">
                        <input type="password" class="field-value" id="currentSecQues2" 
                               value="<?php echo htmlspecialchars($driverData['SecQues2']); ?>" readonly>
                        <button type="button" class="toggle-password" onclick="togglePassword('currentSecQues2')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="profile-field">
                <span class="field-label">New Answer 2:</span>
                <div class="input-wrapper">
                    <div class="password-field">
                        <input type="password" class="field-value" id="secQues2" name="secQues2" 
                               placeholder="Enter new answer (leave blank to keep current)"
                               maxLength="50">
                        <button type="button" class="toggle-password" onclick="togglePassword('secQues2')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="section-button">
                <button type="submit" class="button save-btn">Save Changes</button>
            </div>
        </form>

        <!-- Personal Information Section -->
        <form id="editPersonalForm" class="profile-section">
            <div class="section-header">
                <h2 class="section-title">Personal Information</h2>
            </div>

            <div class="profile-field">
                <span class="field-label">Full Name</span>
                <div class="input-wrapper">
                    <input type="text" class="field-value" id="fullName" name="fullName" 
                           value="<?php echo ucwords(strtolower($driverData['FullName'])); ?>" 
                           required autocomplete="off">
                    <span id="fullNameError" class="error"></span>
                </div>
            </div>

            <div class="profile-field">
                <span class="field-label">License Number</span>
                <div class="input-wrapper">
                    <input type="text" class="field-value" id="licenseNo" name="licenseNo" 
                           value="<?php echo $driverData['LicenseNo']; ?>" 
                           maxlength="8" required autocomplete="off">
                    <span id="licenseError" class="error"></span>
                </div>
            </div>

            <div class="profile-field">
                <span class="field-label">License Expiry Date</span>
                <div class="input-wrapper">
                    <input type="date" class="field-value" id="licenseExpDate" name="licenseExpDate" 
                           value="<?php echo date('Y-m-d', strtotime($driverData['LicenseExpDate'])); ?>"
                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                           max="<?php echo date('Y-m-d', strtotime('+10 years')); ?>" required>
                    <span id="licenseExpDateError" class="error"></span>
                </div>
            </div>

            <div class="profile-field">
                <span class="field-label">Phone Number</span>
                <div class="input-wrapper">
                    <input type="text" class="field-value" id="phoneNo" name="phoneNo" 
                           value="<?php echo htmlspecialchars($driverData['PhoneNo']); ?>" 
                           maxLength="12" placeholder="Format: 012-3456789" 
                           required autocomplete="off">
                    <span id="phoneError" class="error"></span>
                </div>
            </div>

            <div class="profile-field">
                <span class="field-label">Gender</span>
                <div class="input-wrapper">
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="gender" value="M" 
                                   <?php echo $driverData['Gender'] === 'M' ? 'checked' : ''; ?>> Male
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="gender" value="F" 
                                   <?php echo $driverData['Gender'] === 'F' ? 'checked' : ''; ?>> Female
                        </label>
                    </div>
                </div>
            </div>

            <div class="profile-field">
                <span class="field-label">Birthday Date</span>
                <div class="input-wrapper">
                    <input type="date" class="field-value" id="birthDate" name="birthDate" 
                           value="<?php echo date('Y-m-d', strtotime($driverData['BirthDate'])); ?>"
                           max="<?php echo date('Y-m-d'); ?>"
                           min="<?php echo date('Y-m-d', strtotime('-125 years')); ?>" required>
                </div>
            </div>

            <div class="profile-field">
                <span class="field-label">User Type</span>
                <div class="input-wrapper">
                    <input type="text" class="field-value" value="<?php echo ucwords(strtolower($driverData['UserType'])); ?>" readonly>
                </div>
            </div>

            <div class="section-button">
                <button type="submit" class="button save-btn">Save Changes</button>
            </div>
        </form>

        <!-- Service Information Section -->
        <form id="editServiceForm" class="profile-section">
            <div class="section-header">
                <h2 class="section-title">Service Information</h2>
            </div>

            <div class="profile-field">
                <span class="field-label">E-Hailing License</span>
                <div class="input-wrapper">
                    <input type="text" class="field-value" 
                           value="<?php echo htmlspecialchars($driverData['EHailingLicense']); ?>" readonly>
                </div>
            </div>

            <div class="profile-field">
                <span class="field-label">Availability</span>
                <div class="input-wrapper">
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="availability" value="AVAILABLE" 
                                   <?php echo $driverData['Availability'] === 'AVAILABLE' ? 'checked' : ''; ?>> Available
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="availability" value="NOT AVAILABLE" 
                                   <?php echo $driverData['Availability'] === 'NOT AVAILABLE' ? 'checked' : ''; ?>> Not Available
                        </label>
                    </div>
                    <span id="availabilityError" class="error"></span>
                </div>
            </div>

            <div class="profile-field">
                <span class="field-label">Completed Rides</span>
                <div class="input-wrapper">
                    <input type="text" class="field-value" 
                           value="<?php echo $driverData['CompletedRide']; ?>" readonly>
                </div>
            </div>

            <div class="profile-field">
                <span class="field-label">Sticker Expiry Date</span>
                <div class="input-wrapper">
                    <input type="date" class="field-value" id="stickerExpDate" name="stickerExpDate" 
                           value="<?php echo date('Y-m-d', strtotime($driverData['StickerExpDate'])); ?>"
                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                           max="<?php echo date('Y-m-d', strtotime('+5 years')); ?>" required>
                    <span id="stickerExpDateError" class="error"></span>
                </div>
            </div>

            <div class="section-button">
                <button type="submit" class="button save-btn">Save Changes</button>
            </div>
        </form>
    </div>

    <script>
        // Store original data for comparison
        const originalData = {
            username: '<?php echo htmlspecialchars($driverData['Username']); ?>',
            email: '<?php echo strtolower($driverData['EmailAddress']); ?>',
            phoneNo: '<?php echo htmlspecialchars($driverData['PhoneNo']); ?>',
            licenseNo: '<?php echo $driverData['LicenseNo']; ?>'
        };

        // Password visibility toggle
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

        // Form submission handlers
        document.getElementById('editAccountForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            await saveAccountChanges();
        });

        document.getElementById('editPersonalForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            await savePersonalChanges();
        });

        document.getElementById('editServiceForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            await saveServiceChanges();
        });

        // Save functions
        async function saveAccountChanges() {
            // Reset error messages
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
                            userType: 'DRIVER'
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
                    document.getElementById('emailError').textContent = 'Invalid email format. Eg: abcd@gmail.com';
                    hasErrors = true;
                } else {
                    try {
                        const response = await fetch('validateEmail.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ 
                                email: email,
                                userType: 'DRIVER',
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
                driverId: '<?php echo $driverID; ?>',
                userId: '<?php echo $driverData['UserID']; ?>',
                username: username,
                password: password,
                email: email,
                securityCode: securityCode,
                secQues1: document.getElementById('secQues1').value.trim(),
                secQues2: document.getElementById('secQues2').value.trim()
            };

            try {
                const response = await fetch('editAccountDri.php', {
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
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while updating account information');
            }
        }

        async function savePersonalChanges() {
            // Reset all error messages
            document.getElementById('fullNameError').textContent = '';
            document.getElementById('phoneError').textContent = '';
            document.getElementById('licenseError').textContent = '';
            document.getElementById('licenseExpDateError').textContent = '';

            let hasErrors = false;

            // Validate full name
            const fullName = document.getElementById('fullName').value.trim();
            if (fullName.length === 0) {
                document.getElementById('fullNameError').textContent = 'Full name cannot be empty';
                hasErrors = true;
            }

            // Validate phone number
            const phoneNo = document.getElementById('phoneNo').value.trim();
            if (phoneNo !== originalData.phoneNo) {
                if (!/^01\d-\d{7,8}$/.test(phoneNo)) {
                    document.getElementById('phoneError').textContent = 'Invalid format. Example: 012-3456789';
                    hasErrors = true;
                } else {
                    try {
                        const response = await fetch('validatePhoneNo.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ 
                                phoneNo: phoneNo,
                                userType: 'DRIVER',
                                currentPhoneNo: originalData.phoneNo
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
            }

            // Validate license number
            const licenseNo = document.getElementById('licenseNo').value.trim();
            if (licenseNo !== originalData.licenseNo) {
                if (!/^\d{8}$/.test(licenseNo)) {
                    document.getElementById('licenseError').textContent = 'License number must be exactly 8 digits';
                    hasErrors = true;
                }
            }

            if (hasErrors) {
                return;
            }

            // If no errors, proceed with save
            const formData = {
                updateType: 'personal',
                driverId: '<?php echo $driverID; ?>',
                userId: '<?php echo $driverData['UserID']; ?>',
                fullName: fullName.toUpperCase(),
                phoneNo: phoneNo,
                gender: document.querySelector('input[name="gender"]:checked').value,
                birthDate: document.getElementById('birthDate').value,
                licenseNo: licenseNo,
                licenseExpDate: document.getElementById('licenseExpDate').value,
                stickerExpDate: document.getElementById('stickerExpDate').value
            };

            try {
                const response = await fetch('editAccountDri.php', {
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

        async function saveServiceChanges() {
            // Reset error messages
            document.getElementById('availabilityError').textContent = '';
            document.getElementById('stickerExpDateError').textContent = '';

            let hasErrors = false;

            // Validate availability
            if (!document.querySelector('input[name="availability"]:checked')) {
                document.getElementById('availabilityError').textContent = 'Please select availability';
                hasErrors = true;
            }

            // Validate sticker expiry date
            const stickerExpDate = document.getElementById('stickerExpDate').value;
            if (!stickerExpDate) {
                document.getElementById('stickerExpDateError').textContent = 'Sticker expiry date is required';
                hasErrors = true;
            }

            if (hasErrors) {
                return;
            }

            const formData = {
                updateType: 'service',
                driverId: '<?php echo $driverID; ?>',
                userId: '<?php echo $driverData['UserID']; ?>',
                availability: document.querySelector('input[name="availability"]:checked').value,
                stickerExpDate: stickerExpDate
            };

            try {
                const response = await fetch('editAccountDri.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
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
        }

        // Add after the form submission handlers

        // Username validation on blur
        document.getElementById('username').addEventListener('blur', async () => {
            const username = document.getElementById('username').value.trim();
            const usernameError = document.getElementById('usernameError');
            
            if (username === originalData.username) {
                usernameError.textContent = '';
                return;
            }

            try {
                const response = await fetch('validateUsername.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        username: username,
                        userType: 'DRIVER'
                    })
                });
                const data = await response.json();
                if (data.status === 'exists') {
                    usernameError.textContent = 'Username already exists';
                } else {
                    usernameError.textContent = '';
                }
            } catch (error) {
                console.error('Username validation error:', error);
            }
        });

        // Email validation on blur
        document.getElementById('email').addEventListener('blur', async () => {
            const email = document.getElementById('email').value.trim();
            const emailError = document.getElementById('emailError');
            
            if (email === originalData.email) {
                emailError.textContent = '';
                return;
            }

            if (!/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(email)) {
                emailError.textContent = 'Invalid email format. Eg: abcd@gmail.com';
                return;
            }

            try {
                const response = await fetch('validateEmail.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        email: email,
                        userType: 'DRIVER',
                        currentEmail: originalData.email
                    })
                });
                const data = await response.json();
                if (data.status === 'exists') {
                    emailError.textContent = 'Email already exists';
                } else {
                    emailError.textContent = '';
                }
            } catch (error) {
                console.error('Email validation error:', error);
            }
        });

        // Password validation on blur
        document.getElementById('password').addEventListener('blur', () => {
            const password = document.getElementById('password').value;
            const passwordError = document.getElementById('passwordError');
            
            if (!password) {
                passwordError.textContent = '';
                return;
            }

            if (password.length < 8 || password.length > 16) {
                passwordError.textContent = 'Password must be between 8 and 16 characters';
            } else {
                passwordError.textContent = '';
            }
        });

        // Security code validation on blur
        document.getElementById('securityCode').addEventListener('blur', () => {
            const securityCode = document.getElementById('securityCode').value;
            const securityCodeError = document.getElementById('securityCodeError');
            
            if (!securityCode) {
                securityCodeError.textContent = '';
                return;
            }

            let secErrors = [];
            if (securityCode.length < 4 || securityCode.length > 8) {
                secErrors.push('Length must be between 4-8 characters');
            }
            if (!/[a-z]/.test(securityCode)) secErrors.push('Must contain lowercase letter');
            if (!/[A-Z]/.test(securityCode)) secErrors.push('Must contain uppercase letter');
            if (!/[0-9]/.test(securityCode)) secErrors.push('Must contain number');
            if (!/[!@#$%^&*]/.test(securityCode)) secErrors.push('Must contain special character (!@#$%^&*)');
            
            securityCodeError.textContent = secErrors.join(', ');
        });

        // Phone number validation on blur
        document.getElementById('phoneNo').addEventListener('blur', async () => {
            const phoneNo = document.getElementById('phoneNo').value.trim();
            const phoneError = document.getElementById('phoneError');
            
            if (phoneNo === originalData.phoneNo) {
                phoneError.textContent = '';
                return;
            }

            if (!/^01\d-\d{7,8}$/.test(phoneNo)) {
                phoneError.textContent = 'Invalid format. Example: 012-3456789';
                return;
            }

            try {
                const response = await fetch('validatePhoneNo.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        phoneNo: phoneNo,
                        userType: 'DRIVER',
                        currentPhoneNo: originalData.phoneNo
                    })
                });
                const data = await response.json();
                if (data.status === 'exists') {
                    phoneError.textContent = 'Phone number already exists';
                } else {
                    phoneError.textContent = '';
                }
            } catch (error) {
                console.error('Phone validation error:', error);
            }
        });

        // License number validation on blur
        document.getElementById('licenseNo').addEventListener('blur', () => {
            const licenseNo = document.getElementById('licenseNo').value.trim();
            const licenseError = document.getElementById('licenseError');
            
            if (licenseNo === originalData.licenseNo) {
                licenseError.textContent = '';
                return;
            }

            if (!/^\d{8}$/.test(licenseNo)) {
                licenseError.textContent = 'License number must be exactly 8 digits';
            } else {
                licenseError.textContent = '';
            }
        });
    </script>
</body>
</html> 