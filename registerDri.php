<?php
include 'dbConnection.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];

    // Check if the user already exists
    $stmt = $connMe->prepare("SELECT UserID FROM USER WHERE EmailAddress = UPPER(?)");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $userID = $row['UserID'];

        // Check if user is already registered as a driver
        $stmt = $connMe->prepare("SELECT DriverID FROM DRIVER WHERE UserID = ?");
        $stmt->bind_param("s", $userID);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            // User is already a driver
            $response['status'] = 'exists_driver';
        } else {
            // Send user data as part of the response
            $response['status'] = 'exists_user';

            // Store common attributes
            $stmt = $connMe->prepare("SELECT FullName, EmailAddress, PhoneNo, UserType, BirthDate, Gender, EmailSecCode, SecQues1, SecQues2 FROM USER WHERE UserID = ?");
            $stmt->bind_param("s", $userID);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            $response['userData'] = [
                'fullName' => $row['FullName'],
                'emailChecked' => $row['EmailAddress'],
                'phoneNo' => $row['PhoneNo'],
                'userType' => $row['UserType'],
                'birthDate' => $row['BirthDate'],
                'gender' => $row['Gender'],
                'emailSecCode' => $row['EmailSecCode'],
                'secQues1' => $row['SecQues1'],
                'secQues2' => $row['SecQues2']
            ];
        }
    } else {
        // User does not exist
        $response['status'] = 'exists_none';
    }

    $stmt->close();
    $connMe->close();

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Driver</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            font-size: 16px;
            background-color: rgba(0, 0, 0, 0.3);
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
        }
        .container {
            position: relative;
            max-width: unset;
            border: 2px solid #8bc34a;
            border-radius: 10px;
            padding: 30px;
            background-color: #ffffff;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.7);
            display: flex; /* Make container flexbox for vertical alignment */
            flex-direction: column; /* Stack elements vertically */
            align-items: center;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .close-icon {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 30px;
            color: #4caf50;
            text-decoration: none;
            font-weight: bold;
        }
        .close-icon:hover {
            color: #388e3c;
        }
        h1 {
            font-size: 40px;
            margin-bottom: 40px;
            font-family: "Palatino", serif;
            color: #333;
            /* Wrap long text content */
            flex-wrap: wrap;
            /* Center heading within container */
            align-self: center;
        }
        /* Define the divider class */
        .divider {
            border: none;
            height: 1px;
            background-color: grey;
            width: 100%;
            margin-top: 45px;
        }
        h3  {
             font-size: 20px;
        }
        form {
            display: flex; /* Make form flexbox for vertical alignment */
            flex-direction: column; /* Stack elements vertically */
            gap: 0;
            /* Center form within container */
            width: 100%; /* Stretch form to full width */
        }
        label {
            width: 300px; /* Set fixed width for labels */
            text-align: left; /* Align text left within label */
            font-size: 16px;
            /* Add space before label text */
            margin-bottom: 5px;
            margin-right: 10px; /* Add margin between label and input */
        }
        input {
            padding: 6px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            /* Align input at the right of the label */
            flex: 1; /* Allow remaining space for the input */
            margin-bottom: 0; /* Add margin to the bottom of the input */
        }
        /* Additional styles for radio buttons (if needed) */
        .radio-group {
            display: flex; /* Make radio group flexbox */
            align-items: center; /* Align radio buttons vertically */
            margin-bottom: 10px; /* Add margin to create space below the radio buttons */
            width: 290px;
        }
        .radio-label {
            margin-right: 10px; /* Add spacing between radio label and button */
        }
        .form-group {
            display: flex;
            align-items: center;
            margin-bottom: 10px; /* Add margin to create space between form groups */
            width: 100%; /* Make each form group take up the full width of the container */
        }
        .button {
            padding: 10px 20px;
            font-size: 18px;
            color: white;
            background-color: #4caf50;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            width: 125px;
            margin: 0 auto; /* This centers the button horizontally */
            margin-top: 30px;
            display: block; /* Ensure the button is a block-level element */
        }
        .button:hover {
            background-color: #388e3c;
            transform: scale(1.05); /* Slightly enlarges the button on hover */
        }
        .error {
            color: red;
            font-size: 16px;
            display: block; /* Ensures the error appears below the input */
            margin-top: 0; /* Adds spacing between the input and the error */
            margin-bottom: 10px;
        }
        .info-box {
            background-color: #f4f4f9;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .question-text {
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="loginDri.php" class="close-icon">✖</a>
        <h1>Registration Driver</h1>

        <!-- Email Check Form -->
        <form id="emailForm" method="POST">
            <div class="form-group" id="emailStep" style="display: none;">
                <label for="email">Email Address:</label>
                <input type="text" id="email" name="email" required placeholder="Your active email" autocomplete="off">
            </div>
            <span id="emailError" class="error"></span>

            <button type="submit" class="button" id="checkButton">Check</button>

            <div id="securityCodeSection" style="display: none;">
                <label for="securityCode">Enter Security Code:</label>
                <input type="password" id="securityCode" name="securityCode" maxLength="8" autocomplete="off">
                <button type="button" id="validateSecCodeButton">Validate Security Code</button>
            </div>
            <span id="securityCodeError" style="color: red;"></span>
        </form>

        <!-- Main Registration Form -->
        <form id="registerForm" method="POST">
            <div id="dividerSection" style="display: none;">
                <hr class="divider">
            </div>

            <!-- Basic Information Section -->
            <div id="userFields" style="display: none;">
                <h3>Basic Information</h3>
                <div class="form-group">
                    <label for="fullName">Full Name:</label>
                    <input type="text" id="fullName" name="fullName" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="emailChecked">Email Address:</label>
                    <!--readonly input attribute can allow the value to be passed when click submit button, compared to "disabled"-->
                    <input type="text" id="emailChecked" name="emailChecked" readonly> 
                </div>
                <div class="form-group">
                    <label for="emailSecCode">Email's Security Code:</label>
                    <input type="password" id="emailSecCode" name="emailSecCode" maxLength=8 required autocomplete="off">
                </div>
                <!--Seperate from div part to avoid the error message occur side-by-side-->
                <span id="emailSecCodeError" class="error"></span> <!-- Error message will be displayed here -->
                <div class="form-group">
                    <label for="phoneNo">Phone Number:</label>
                    <input type="text" id="phoneNo" name="phoneNo" maxLength=12 required autocomplete="off">
                </div>
                <!--Seperate from div part to avoid the error message occur side-by-side-->
                <span id="phoneError" class="error"></span> <!-- Error message will be displayed here -->

                <div class="form-group">
                    <label for="userType">Type of User:</label>
                    <!--readonly input attribute can allow the value to be passed when click submit button, compared to "disabled"-->
                    <input type="text" id="userType" name="userType" value="Driver" readonly> 
                </div>

                <div class="form-group">
                    <label for="birthDate">Birthday Date:</label>
                    <input type="date" id="birthDate" name="birthDate" 
                          min="<?php echo date('Y-m-d', strtotime('-60 years')); ?>"
                          max="<?php echo date('Y-m-d', strtotime('-19 years')); ?>"
                           required>
                    <span id="birthDateError" class="error"></span>
                </div>
                <div class="form-group">
                    <label for="gender">Gender:</label>
                    <div class="radio-group">
                        <label class="radio-label">
                            <!-- since the browser will treat all radio buttons with the same name as a single input-->
                            <input type="radio" id="genderMale" name="gender" value="male"> Male
                        </label>
                        <label class="radio-label">
                            <input type="radio" id="genderFemale" name="gender" value="female"> Female
                        </label>
                    </div>
                </div>

                <!-- Security Question -->
                <div class="form-group">
                    <label>Security Question 1:</label>
                    <label class="question-text">What is your favourite food?</label>
                </div>
                <div class="form-group">
                    <label for="secQues1">Your Answer:</label>
                    <input type="text" id="secQues1" name="secQues1" maxLength="30" required autocomplete="off">
                </div>

                <div class="form-group">
                    <label>Security Question 2:</label>
                    <label class="question-text">What first city did you visited on your vacation?</label>
                </div>
                <div class="form-group">
                    <label for="secQues2">Your Answer:</label>
                    <input type="text" id="secQues2" name="secQues2" maxLength="50" required autocomplete="off">
                </div>
            </div>

            <!-- Driver-Specific Information -->
            <div id="driverFields" style="display: none;">
                <h3>Driver Information</h3>
                <div class="form-group">
                    <label for="licenseNo">License Number:</label>
                    <input type="text" id="licenseNo" name="licenseNo" maxLength="8" required autocomplete="off">
                </div>
                <span id="licenseError" class="error"></span>

                <div class="form-group">
                    <label for="licenseExpDate">License Expiry Date:</label>
                    <input type="date" id="licenseExpDate" name="licenseExpDate" required>
                </div>

                <div class="form-group">
                    <label for="stickerExpDate">UTeM Peer Rides Sticker Expiry Date:</label>
                    <input type="date" id="stickerExpDate" name="stickerExpDate" required>
                </div>

                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" maxLength="20" required autocomplete="off">
                </div>
                <span id="usernameError" class="error"></span>

                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" maxLength="16" required autocomplete="off">
                </div>
                <span id="pwdError" class="error"></span>
            </div>

            <!-- Vehicle Information -->
            <div id="vehicleFields" style="display: none;">
                <h3>Vehicle Information</h3>
                <div class="form-group">
                    <label for="model">Car Model:</label>
                    <input type="text" id="model" name="model" required autocomplete="off">
                </div>

                <div class="form-group">
                    <label for="plateNo">Plate Number:</label>
                    <input type="text" id="plateNo" name="plateNo" maxLength="10" required autocomplete="off">
                </div>
                <span id="plateError" class="error"></span>

                <div class="form-group">
                    <label for="color">Car Color:</label>
                    <input type="text" id="color" name="color" required autocomplete="off">
                </div>

                <div class="form-group">
                    <label for="availableSeat">Available Seats:</label>
                    <input type="number" id="availableSeat" name="availableSeat" min="1" max="7" value="1" required>
                </div>

                <div class="form-group">
                    <label for="yearManufacture">Year Manufactured:</label>
                    <!-- select is used to create a dropdown list -->
                    <select id="yearManufacture" name="yearManufacture" required>
                        <!-- Will be populated by JavaScript -->
                    </select>
                </div>
            </div>

            <button type="submit" class="button" id="submitButton">Register</button>
        </form>
    </div>

    <script>
        // Add these variable declarations
        const emailStep = document.getElementById("emailStep");
        const checkButton = document.getElementById("checkButton");
        const emailForm = document.getElementById("emailForm");
        const emailInput = document.getElementById("email");
        const emailError = document.getElementById("emailError");

        const registerForm = document.getElementById("registerForm");
        const userFields = document.getElementById("userFields");
        const phoneInput = document.getElementById("phoneNo");
        const phoneError = document.getElementById("phoneError");
        const driverFields = document.getElementById("driverFields");
        const vehicleFields = document.getElementById("vehicleFields");
        const passwordInput = document.getElementById("password");
        const pwdError = document.getElementById("pwdError");
        const emailSecCodeInput = document.getElementById("emailSecCode");
        const emailSecCodeError = document.getElementById("emailSecCodeError");
        const submitButton = document.getElementById("submitButton");

        const securityCodeSection = document.getElementById("securityCodeSection");
        const securityCodeInput = document.getElementById("securityCode");
        const securityCodeError = document.getElementById("securityCodeError");
        const validateSecCodeButton = document.getElementById("validateSecCodeButton");

        // Initialize form elements
        document.addEventListener("DOMContentLoaded", () => {
            emailStep.style.display = "block";
            dividerSection.style.display = "none";
            userFields.style.display = "none";
            driverFields.style.display = "none";
            vehicleFields.style.display = "none";
            checkButton.style.display = "block";
            submitButton.style.display = "none";

            // Set up year manufactured dropdown
            const yearSelect = document.getElementById('yearManufacture');
            const currentYear = new Date().getFullYear();
            const minYear = currentYear - 15;
            
            // Populate the dropdown list with years from current year to minYear
            for (let year = currentYear; year >= minYear; year--) {
                const option = document.createElement('option');
                option.value = year;
                option.textContent = year;
                yearSelect.appendChild(option);
            }

            // Set up license and sticker expiry date constraints
            const today = new Date();
            const tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1);
            
            // Set up license expiry date max 15 years
            const maxLicenseDate = new Date(today);
            maxLicenseDate.setFullYear(today.getFullYear() + 10);
            
            // Set up sticker expiry date max 5 years
            const maxStickerDate = new Date(today);
            maxStickerDate.setFullYear(today.getFullYear() + 5);
            
            document.getElementById('licenseExpDate').min = formatDate(tomorrow);
            document.getElementById('licenseExpDate').max = formatDate(maxLicenseDate);
            
            document.getElementById('stickerExpDate').min = formatDate(tomorrow);
            document.getElementById('stickerExpDate').max = formatDate(maxStickerDate);
        });

        // Email validation and form handling
        //Check format email
        emailInput.addEventListener("input", () => {
            // Clear any previous error messages
            emailError.textContent = ""; 
            emailInput.setCustomValidity("");

            const emailValue = emailInput.value.trim();
            const isValid = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(emailValue);

            if (!isValid) {
                emailError.textContent = "Enter email with correct format (Eg: abc@gmail.com).";
                emailInput.setCustomValidity("");
                checkButton.disabled = true; // Disable the button for invalid email
            } else {
                emailError.textContent = "";
                emailInput.setCustomValidity(""); // Clear any error messages
                checkButton.disabled = false; // Enable the check button if valid
                fillReadonlyValue();
            }
        });

        //Email checking
        emailForm.addEventListener("submit", (e) => {
            e.preventDefault();

            emailError.style.color = "";

            // Collect form data
            const emailFormData = new FormData(emailForm);

            // Send FormData to backend
            fetch("registerDri.php", {
                method: "POST",
                body: emailFormData,
            })
            .then((response) => response.json())
            .then((data) => {
                if (data.status === "exists_driver") {
                    emailError.style.color = "red"; 
                    emailError.textContent = "You are already registered as a driver.";

                    //Do not display any textfield
                    dividerSection.style.display = "none";
                    userFields.style.display = "none";
                    driverFields.style.display = "none";
                    vehicleFields.style.display = "none";
                    submitButton.style.display = "none";
                    
                } else if (data.status === "exists_user") {
                    // Check age before proceeding
                    const birthDate = new Date(data.userData.birthDate);
                    const age = validateAge(birthDate.toISOString().split('T')[0]);
                    
                    if (age < 19) {
                        emailError.style.color = "red";
                        emailError.textContent = "You must be at least 19 years old to register as a driver.";
                        setTimeout(() => {
                            window.location.href = 'mainPage.html';
                        }, 3000); // Redirect after 3 seconds
                        return;
                    }

                    emailError.style.color = "green";
                    dividerSection.style.display = "none";
                    emailError.textContent = "Email exists. Welcome!\nPlease enter your email's security code.";
                    emailError.style.whiteSpace = "pre-wrap";
                    userFields.style.display = "none";
                    driverFields.style.display = "none";
                    vehicleFields.style.display = "none";
                    submitButton.style.display = "none";
                    submitButton.disabled = false;
                    checkButton.style.display = "none";
                    emailStep.style.display = "none"; 

                    // Show the security code input field
                    securityCodeSection.style.display = "block";

                    // Prefill the form with user data
                    const registerForm = document.getElementById('registerForm');
                    const userData = data.userData;

                    if (userData) {
                        for (const key in userData) {
                            const inputElement = registerForm.querySelector(`#${key}`);
                            if (inputElement) {
                                inputElement.value = userData[key];
                            }
                        }
                    }

                } else if (data.status === "exists_none") {
                    emailError.style.color = "green";
                    dividerSection.style.display = "block"; 
                    emailError.textContent = "Email does not exist. Welcome, new user!\nKindly fill in all user information required as driver.";
                    emailError.style.whiteSpace = "pre-wrap";
                    userFields.style.display = "block";
                    driverFields.style.display = "block";
                    vehicleFields.style.display = "block";
                    submitButton.style.display = "block";
                    checkButton.style.display = "none";
                    emailStep.style.display = "none"; 
                }
            })
            .catch((err) => {
                console.error("Error:", err);
                emailError.textContent = "Unable to verify email. Please try again.";
            });
        });

        // Validate the security code when the button is clicked
        validateSecCodeButton.addEventListener("click", () => {
            const emailValue = emailInput.value.trim().toUpperCase();
            const securityCodeValue = securityCodeInput.value.trim();

            // Reset error message
            securityCodeError.textContent = "";

            if (securityCodeValue === "") {
                securityCodeError.textContent = "Security code cannot be empty.";
                return;
            }

            // Send data to the backend for validation
            fetch("validateSecCode.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    email: emailValue,
                    securityCode: securityCodeValue,
                }),
            })
            .then((response) => response.json())
            .then((data) => {
                if (data.status === "valid") {
                    securityCodeError.style.color = "green";
                    securityCodeError.textContent = "Security code is valid!\nKindly complete your specific driver information below.";
                    securityCodeError.style.whiteSpace = "pre-wrap"; 
                    dividerSection.style.display = "block";
                    securityCodeSection.style.display = "none";
                    driverFields.style.display = "block";
                    vehicleFields.style.display = "block";
                    submitButton.style.display = "block";
                } else {
                    securityCodeError.style.color = "red";
                    securityCodeError.textContent = "Invalid security code. Please try again.";
                    driverFields.style.display = "none";
                    vehicleFields.style.display = "none";
                    submitButton.style.display = "none";
                }
            })
            .catch((err) => {
                console.error("Error:", err);
                securityCodeError.textContent = "An error occurred. Please try again.";
            });
        });

        // Since will keep checking the input whenever user react to the input field
        emailSecCodeInput.addEventListener("input", validateForm);

        phoneInput.addEventListener("input", validateForm);
        // Phone number validation
        phoneInput.addEventListener('blur', async (e) => {
                const phoneNo = e.target.value.trim();
                
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

        passwordInput.addEventListener("input", validateForm);

        // To ensure one of the input field is not correct, submit button cannot be clicked
        function validateForm() {
            const phoneValue = phoneInput.value.trim();
            const pwdValue = passwordInput.value.trim();
            const emailSecCodeValue = emailSecCodeInput.value.trim();
            const plateNo = document.getElementById('plateNo').value.trim();

            // Reset error messages
            phoneError.textContent = "";
            pwdError.textContent = "";
            emailSecCodeError.textContent = "";
            document.getElementById('plateError').textContent = "";

            let isValid = true;

            //Validate Security Code
            if(!/^(?=(.*[a-z]))(?=(.*[A-Z]))(?=(.*[0-9]))(?=(.*[!@#$%^&*]))[a-zA-Z0-9!@#$%^&*]{4,8}$/.test(emailSecCodeValue)){
                emailSecCodeError.textContent = "Must contains lowercase, uppercase, number, special character(!@#$%^&*).";
                isValid = false;
            }

            // Validate phone number
            if (!/^[0-9]{3}-[0-9]{7,8}$/.test(phoneValue)) {
                phoneError.textContent = "Enter phone number (Eg:012-34567890).";
                isValid = false;
            }

            // Validate password
            if (pwdValue.length < 8 || pwdValue.length > 16) {
                pwdError.textContent = "Minimum length of password must be 8 characters.";
                isValid = false;
            }

            // Validate plate number
            if (!/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{1,10}$/.test(plateNo)) {
                document.getElementById('plateError').textContent = "Plate number must contain both letters and numbers, no spaces";
                isValid = false;
            }
            
            // Enable or disable the submit button based on overall form validity
            submitButton.disabled = !isValid;
        }

        //Set the email address value based on user enter before fill in all information
        function fillReadonlyValue() {
            // Get the value from the "email" input
            const emailValue = document.getElementById('email').value;
            
            // Set the value of the "emailChecked" input
            document.getElementById('emailChecked').value = emailValue;
        }

        // License number validation
        document.getElementById('licenseNo').addEventListener('input', async (e) => {
            const licenseNo = e.target.value.trim();
            const licenseError = document.getElementById('licenseError');

            // \d is used to match any digit
            if (!/^\d{8}$/.test(licenseNo)) {
                licenseError.textContent = "License number must be exactly 8 numeric digits";
                return;
            }

            try {
                const response = await fetch('validateLicenseNo.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ licenseNo: licenseNo })
                });
                const data = await response.json();
                
                if (data.status === 'exists') {
                    licenseError.textContent = "License number already registered";
                } else {
                    licenseError.textContent = "";
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });

        // Username validation
        document.getElementById('username').addEventListener('blur', async () => {
            const username = document.getElementById('username').value.trim();
            const userType = "DRIVER";
            const usernameError = document.getElementById('usernameError');

            if (username === "") {
                usernameError.textContent = "Username cannot be empty";
                return;
            }

            try {
                const response = await fetch('validateUsername.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ username: username, userType: userType })
                });
                const data = await response.json();
                
                if (data.status === 'exists') {
                    usernameError.textContent = "Username already exists. Please use another username.";
                } else {
                    usernameError.textContent = "";
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });

        // Password validation
        document.getElementById('password').addEventListener('input', () => {
            const password = document.getElementById('password').value;
            const pwdError = document.getElementById('pwdError');

            if (password.length < 8 || password.length > 16) {
                pwdError.textContent = "Minimum length of password must be 8 characters.";
            } else {
                pwdError.textContent = "";
            }
        });

        // Add this function to check age
        function validateAge(birthDate) {
            const today = new Date();
            const birth = new Date(birthDate);
            let age = today.getFullYear() - birth.getFullYear();
            const monthDiff = today.getMonth() - birth.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
                age--;
            }
            
            return age;
        }

        // Update the form submission handler
        registerForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const birthDate = document.getElementById('birthDate').value;
            const age = validateAge(birthDate);
            
            if (age < 19) {
                alert('You must be at least 19 years old to register as a driver.');
                return;
            }
            
            const formData = new FormData(registerForm);
            let confirmationMessage = "Please confirm the details below:\n\n";

            for (const [key, value] of formData.entries()) {
                // Special handling for security questions
                if (key === 'secQues1') {
                    confirmationMessage += `SECURITY QUESTION 1: ${value}\n\n`;
                    continue;
                }
                if (key === 'secQues2') {
                    confirmationMessage += `SECURITY QUESTION 2: ${value}\n\n`;
                    continue;
                }
                
                const label = registerForm.querySelector(`label[for="${key}"]`);
                const labelText = label ? label.textContent : key;
                const upperCaseLabel = labelText.toUpperCase();
                let inputValue = value;

                if (upperCaseLabel === "TYPE OF USER:") {
                    if (emailError.textContent.includes("Email exists")) {
                        // For existing users, concatenate with current value
                        inputValue = inputValue.concat(" ", "DRIVER");
                    } else {
                        // For new users, just set as DRIVER
                    inputValue = "DRIVER";
                    }
                }

                if (upperCaseLabel !== "PASSWORD:" && 
                    upperCaseLabel !== "USERNAME:" && 
                    upperCaseLabel !== "EMAIL'S SECURITY CODE:") {
                    inputValue = inputValue.toUpperCase();
                }

                confirmationMessage += `${upperCaseLabel} ${inputValue}\n\n`;
            }

            if (window.confirm(confirmationMessage)) {
                const registrationData = {
                    formData: Object.fromEntries(formData),
                    isExistingUser: emailError.textContent.includes("Email exists")
                };

                fetch("processRegDri.php", {
                    method: "POST",
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(registrationData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const message = `Registration successful!\n\n` +
                                      `Your User ID: ${data.userId}\n` +
                                      `Your Driver ID: ${data.driverId}\n` +
                                      `Your EHailing License: ${data.ehailingLicense}\n` +
                                      `Your Vehicle ID: ${data.vehicleId}\n\n` +
                                      `Click OK to proceed to login page.`;
                        
                        alert(message);
                        location.href = "loginDri.php";
                    } else {
                        alert(`Error: ${data.message}`);
                    }
                })
                .catch(err => {
                    console.error("Error:", err);
                    alert("An unexpected error occurred. Please try again.");
                });
            } else {
                alert("Registration canceled.");
            }
        });

        // Helper function to format date
        function formatDate(date) {
            let year = date.getFullYear();
            let month = (date.getMonth() + 1).toString().padStart(2, '0');
            let day = date.getDate().toString().padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        // Add this after the license number validation
        document.getElementById('plateNo').addEventListener('input', (e) => {
            validateForm();
        });
    </script>
</body>
</html>
