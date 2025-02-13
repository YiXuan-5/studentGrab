<?php
include 'dbConnection.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    $response = []; // Initialize response array

    $emailUserID = null;
    $phoneUserID = null;

    // Check email
    if ($email) {
        $emailUpper = strtoupper($email); // Normalize email
        $stmtEmail = $connMe->prepare("SELECT UserID FROM USER WHERE EmailAddress = ?");
        $stmtEmail->bind_param("s", $emailUpper);
        $stmtEmail->execute();
        $resultEmail = $stmtEmail->get_result();

        //Find a user based on email
        if ($resultEmail->num_rows === 1) {
            $row = $resultEmail->fetch_assoc();
            $emailUserID = $row['UserID'];
        }
        $stmtEmail->close();
    }

    // Check phone
    if ($phone) {
        $stmtPhone = $connMe->prepare("SELECT UserID FROM USER WHERE PhoneNo = ?");
        $stmtPhone->bind_param("s", $phone);
        $stmtPhone->execute();
        $resultPhone = $stmtPhone->get_result();

        //Find user based on phone number
        if ($resultPhone->num_rows === 1) {
            $row = $resultPhone->fetch_assoc();
            $phoneUserID = $row['UserID'];
        }
        $stmtPhone->close();
    }

    // Handle ambiguity
    //If email and phone number find the user
    if ($emailUserID && $phoneUserID) {

        //If the email and phone number refers to same user
        if ($emailUserID === $phoneUserID) {
            $userID = $emailUserID;
        } else {
            // Conflict: email and phone point to different users
            $response['status'] = 'conflict';
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
    } elseif ($emailUserID) {
        $userID = $emailUserID; // Use the user found by email
    } elseif ($phoneUserID) {
        $userID = $phoneUserID; // Use the user found by phone
    } else {
        $response['status'] = 'exists_none';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    // At this point, $userID is guaranteed to be the correct user
    // Check if user is a driver
    $stmtDriver = $connMe->prepare("SELECT DriverID FROM DRIVER WHERE UserID = ?");
    $stmtDriver->bind_param("s", $userID);
    $stmtDriver->execute();
    $resultDriver = $stmtDriver->get_result();

    if ($resultDriver->num_rows === 1) {
        $response['status'] = 'exists_driver';
    } else {
        $response['status'] = 'exists_user';

        // Fetch user details
        $stmtUser = $connMe->prepare("SELECT FullName, EmailAddress, PhoneNo, UserType, BirthDate, Gender, EmailSecCode, SecQues1, SecQues2, MatricNo FROM USER WHERE UserID = ?");
        $stmtUser->bind_param("s", $userID);
        $stmtUser->execute();
        $resultUser = $stmtUser->get_result();
        $userDetails = $resultUser->fetch_assoc();

        $response['userData'] = [
            'fullName' => $userDetails['FullName'],
            'emailChecked' => $userDetails['EmailAddress'],
            'phoneChecked' => $userDetails['PhoneNo'],
            'userType' => $userDetails['UserType'],
            'birthDate' => $userDetails['BirthDate'],
            'gender' => $userDetails['Gender'],
            'emailSecCode' => $userDetails['EmailSecCode'],
            'secQues1' => $userDetails['SecQues1'],
            'secQues2' => $userDetails['SecQues2'],
            'matricNo' => $userDetails['MatricNo']
        ];
        $stmtUser->close();
    }
    $stmtDriver->close();
    $connMe->close();

    // Send the response as JSON
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

        <!-- Add Matric Number Check Form -->
        <form id="matricForm" method="POST">
            <div class="form-group">
                <label for="matricNo">Matric Number:</label>
                <input type="text" id="matricNo" name="matricNo" maxLength="10" 
                       placeholder="Enter your matric number" autocomplete="off" required>
            </div>
            <span id="matricError" class="error"></span>
            <button type="submit" class="button" id="checkMatricButton">Check</button>
        </form>

        <!-- Email and Phone Check Form - Initially Hidden -->
        <form id="emailNPhoneForm" method="POST" style="display: none;">
            <!-- Email Input -->
            <div class="form-group" id="emailStep" style="display: none;">
                <label for="email">Email Address:</label>
                <input type="text" id="email" name="email" required placeholder="Your active email" autocomplete="off">
            </div>
            <span id="emailError" class="error"></span>

            <!-- Phone Input -->
            <div class="form-group" id="phoneStep" style="display: none;">
                <label for="phone">Phone Number:</label>
                <input type="text" id="phone" name="phone" required maxLength=12 placeholder="Your active phone number" autocomplete="off">
            </div>
            <span id="phoneError" class="error"></span>
            <span id="checkError" class="error"></span>

            <!-- Check Button -->
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
                    <label for="matricNoDisplay">Matric Number:</label>
                    <input type="text" id="matricNoDisplay" name="matricNoDisplay" maxLength="10" 
                         required autocomplete="off">
                </div>
                <span id="matricNoDisplayError" class="error"></span>
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
                    <label for="phoneChecked">Phone Number:</label>
                    <input type="text" id="phoneChecked" name="phoneChecked" readonly>
                </div>

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
        const phoneStep = document.getElementById("phoneStep");
        const checkButton = document.getElementById("checkButton");
        const emailNPhoneForm = document.getElementById("emailNPhoneForm");
        const emailInput = document.getElementById("email");
        const emailError = document.getElementById("emailError");
        const checkError = document.getElementById("checkError");

        const registerForm = document.getElementById("registerForm");
        const userFields = document.getElementById("userFields");
        const phoneInput = document.getElementById("phone");

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

        const matricForm = document.getElementById("matricForm");
        //Matric no bfr email n phone form
        const matricNo = document.getElementById("matricNo");
        const matricError = document.getElementById("matricError");
        //Matric no in basic information field
        const matricNoDisplayError = document.getElementById("matricNoDisplayError");

        let validEmail = null;

        // Initialize form elements
        document.addEventListener("DOMContentLoaded", () => {
            emailStep.style.display = "block";
            phoneStep.style.display = "block";
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

        // Function to validate both email and phone number
        function validateInputs() {
            const emailValue = emailInput.value.trim();
            const phoneValue = phoneInput.value.trim();

            // Email validation
            const isEmailValid = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(emailValue);
            emailError.textContent = isEmailValid ? "" : "Enter email with correct format (Eg: abc@gmail.com).";

            // Phone validation
            const isPhoneValid = /^[0-9]{3}-[0-9]{7,8}$/.test(phoneValue);
            phoneError.textContent = isPhoneValid ? "" : "Enter phone number (Eg:012-34567890).";

            // Enable or disable the check button based on both validations
            checkButton.disabled = !(isEmailValid && isPhoneValid);

            //Call function to fill in email value
            fillEmailReadonlyValue();
            //Call function to fill in phone value
            fillPhoneReadonlyValue();
        }

        // Email validation and form handling
        //Check format email
        emailInput.addEventListener("input", () => {
            validateInputs(); // Call the validation function
        });

        // Check format phone
        phoneInput.addEventListener("input", () => {
            validateInputs(); // Call the validation function
        });

        //Email checking
        emailNPhoneForm.addEventListener("submit", (e) => {
            e.preventDefault();

            checkError.style.color = "";

            // Collect form data
            const emailNPhoneFormData = new FormData(emailNPhoneForm);

            // Send FormData to backend
            fetch("registerDri.php", {
                method: "POST",
                body: emailNPhoneFormData,
            })
            .then((response) => response.json())
            .then((data) => {
                console.log("Response from server:", data);  // Log response for debugging

                if (data.status === "exists_driver") {
                    checkError.style.color = "red"; 
                    checkError.textContent = "You are already registered as a driver.";

                    //Do not display any textfield
                    dividerSection.style.display = "none";
                    userFields.style.display = "none";
                    driverFields.style.display = "none";
                    vehicleFields.style.display = "none";
                    submitButton.style.display = "none";
                    
                } else if (data.status === "exists_user") {
                    checkError.textContent = "Email or phone number exists. Welcome!\nPlease enter your email's security code.";
                    checkError.style.whiteSpace = "pre-wrap";
                    
                    // Fill in the user's existing data
                    document.getElementById('fullName').value = data.userData.fullName;
                    document.getElementById('emailChecked').value = data.userData.emailChecked;
                    document.getElementById('phoneChecked').value = data.userData.phoneChecked;
                    document.getElementById('userType').value = data.userData.userType;
                    document.getElementById('birthDate').value = data.userData.birthDate;
                   
                    // Handle matric number display for existing user
                    const matricInput = document.getElementById('matricNoDisplay');
                    if (data.userData.matricNo) {
                        matricInput.value = data.userData.matricNo;
                    } else {
                        matricInput.value = '';  // Display null if no matric number
                    }
                    matricInput.setAttribute('readonly', true);  // Make it readonly for existing users

                    // Set gender radio button
                    if (data.userData.gender === 'M') {
                        document.getElementById('genderMale').checked = true;
                    } else {
                        document.getElementById('genderFemale').checked = true;
                    }

                    checkError.style.color = "green";
                    dividerSection.style.display = "none";
                    checkError.style.whiteSpace = "pre-wrap";
                    userFields.style.display = "none";
                    driverFields.style.display = "none";
                    vehicleFields.style.display = "none";
                    submitButton.style.display = "none";
                    submitButton.disabled = false;
                    checkButton.style.display = "none";
                    emailStep.style.display = "none"; 
                    phoneStep.style.display = "none";

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
                        validEmail = userData.emailChecked;
                    }

                } else if (data.status === "exists_none") {
                    checkError.style.color = "green";
                    dividerSection.style.display = "block"; 
                    checkError.textContent = "Email or phone number does not exist. Welcome, new user!\nKindly fill in all user information required as driver.";
                    checkError.style.whiteSpace = "pre-wrap";
                    userFields.style.display = "block";
                    driverFields.style.display = "block";
                    vehicleFields.style.display = "block";
                    submitButton.style.display = "block";
                    checkButton.style.display = "none";
                    emailStep.style.display = "none"; 
                    phoneStep.style.display = "none";
                }else if (data.status === "conflict"){
                    checkError.style.color = "red"; 
                    checkError.textContent = "The email and phone number belong to different users. Please verify your input.";

                    //Do not display any textfield
                    dividerSection.style.display = "none";
                    userFields.style.display = "none";
                    driverFields.style.display = "none";
                    vehicleFields.style.display = "none";
                    submitButton.style.display = "none";
                }else {
                    console.log("Unexpected response:", data); // Log unexpected responses
                }
            })
            .catch((err) => {
                console.error("Error:", err);
                checkError.textContent = "Unable to verify email. Please try again.";
            });
        });

        // Validate the security code when the button is clicked
        validateSecCodeButton.addEventListener("click", () => {
            const emailValue = validEmail.trim().toUpperCase();
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

        passwordInput.addEventListener("input", validateForm);

        // To ensure one of the input field is not correct, submit button cannot be clicked
        function validateForm() {
            const pwdValue = passwordInput.value.trim();
            const emailSecCodeValue = emailSecCodeInput.value.trim();
            const plateNo = document.getElementById('plateNo').value.trim();
            const matricNoDisplay = document.getElementById('matricNoDisplay').value.trim();

            // Reset error messages
            pwdError.textContent = "";
            emailSecCodeError.textContent = "";
            document.getElementById('plateError').textContent = "";
            // Don't reset matricNoDisplayError here since it's handled by the blur event
            
            let isValid = true;
            
            // Check if there's an existing matric number error
            if (matricNoDisplayError.textContent !== "") {
                isValid = false;
            }

            // Check if there's a license number error
            const licenseError = document.getElementById('licenseError');
            if (licenseError && licenseError.textContent !== "") {
                isValid = false;
            }

            //Validate Security Code
            if(!/^(?=(.*[a-z]))(?=(.*[A-Z]))(?=(.*[0-9]))(?=(.*[!@#$%^&*]))[a-zA-Z0-9!@#$%^&*]{4,8}$/.test(emailSecCodeValue)){
                emailSecCodeError.textContent = "Must contains lowercase, uppercase, number, special character(!@#$%^&*).";
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
        function fillEmailReadonlyValue() {
            // Get the value from the "email" input
            const emailValue = document.getElementById('email').value;
            
            // Set the value of the "emailChecked" input
            document.getElementById('emailChecked').value = emailValue;
        }

        //Set the phone value based on user enter before fill in all information
        function fillPhoneReadonlyValue() {
            // Get the value from the "phone" input
            const phoneValue = document.getElementById('phone').value;
            
            // Set the value of the "phoneChecked" input
            document.getElementById('phoneChecked').value = phoneValue;
        }

        // License number validation on blur
        document.getElementById('licenseNo').addEventListener('input', async (e) => {
            const licenseNo = document.getElementById('licenseNo').value.trim();
            const licenseError = document.getElementById('licenseError');
            
            // Reset error message
            licenseError.textContent = '';

            if (!licenseNo) {
                licenseError.textContent = 'License number is required';
                validateForm();
                return;
            }

            if (!/^\d{8}$/.test(licenseNo)) {
                licenseError.textContent = 'License number must be exactly 8 digits';
                validateForm();
                return;
            }
           
            // Check uniqueness only if format is valid
            try {
                const response = await fetch('validateLicenseNo.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ 
                        licenseNo: licenseNo,
                        currentUserId: null // null for new registration
                    })
                });
                const data = await response.json();
                if (data.status === 'exists') {
                    licenseError.textContent = 'License number already exists';
                    validateForm();
                } else {
                    licenseError.textContent = '';
                    validateForm();
                }
            } catch (error) {
                console.error('License validation error:', error);
                licenseError.textContent = 'Error validating license number';
                validateForm();
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

        // Matric No validation
        document.getElementById('matricNoDisplay').addEventListener('blur', async () => {
            const matricNoDisplay = document.getElementById('matricNoDisplay').value.trim().toUpperCase();
            
            // For exists_none, matric number is required
            const isNewUser = checkError.textContent.includes("exists_none");
            if (isNewUser && !matricNoDisplay) {
                matricNoDisplayError.textContent = "Matric number cannot be empty.";
                matricNoDisplayError.style.color = "red";
                validateForm();
                return;
            }

            //Check matric number format
            if (!/^[BMD][01][0-9]{8}$/.test(matricNoDisplay)){
                matricNoDisplayError.textContent = "Invalid matric number format and cannot be empty.";
                matricNoDisplayError.style.color = "red";
                validateForm();
                return;
            }

            try {
                const response = await fetch('validateMatricNo.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ matricNoDisplay: matricNoDisplay})
                });
                const data = await response.json();
                
                if (data.status === 'exists') {
                    matricNoDisplayError.textContent = "Matric number already exists.";
                    matricNoDisplayError.style.color = "red";
                    validateForm();
                } else {
                    matricNoDisplayError.textContent = "";
                    matricNoDisplayError.style.color = "green";
                    validateForm();
                }
            } catch (error) {
                console.error('Error:', error);
                matricNoDisplayError.textContent = "Error checking matric number.";
                matricNoDisplayError.style.color = "red";
                validateForm();
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
                    if (checkError.textContent.includes("Email or phone number exists")) {
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
                    isExistingUser: checkError.textContent.includes("Email or phone number exists")
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

        // Matric number validation before email and phone checking
        matricForm.addEventListener("submit", (e) => {
            e.preventDefault();
            const matricValue = matricNo.value.trim().toUpperCase();
            
            // Check matric number format
            // First character must be B, M, or D
            // Second character must be 0 or 1
            // Total length must be 10 characters
            const matricRegex = /^[BMD][01][0-9]{8}$/;
            
            if (!matricRegex.test(matricValue)) {
                matricError.textContent = "Invalid matric number format. Only UTeM students can become drivers.";
                matricError.style.color = "red";
                emailNPhoneForm.style.display = "none";
                return;
            }

            // If format is correct, show email and phone form
            matricError.textContent = "Valid matric number format!";
            matricError.style.color = "green";

            // Delay the display of the email and phone form by 1.5 seconds
            setTimeout(() => {
                emailNPhoneForm.style.display = "block";
                matricForm.style.display = "none";
            }, 1500);
            
            // Store matric number for later use
            sessionStorage.setItem('matricNo', matricValue);

            //Set the value of the matric number input in basic information
            matricNoDisplay.value = sessionStorage.getItem('matricNo');
        });
    </script>
</body>
</html>
