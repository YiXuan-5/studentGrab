<?php
include 'dbConnection.php'; // Ensure database connection is included.

//display any PHP errors directly on the page
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
    // Check if user is a admin
    $stmtAdmin = $connMe->prepare("SELECT AdminID FROM ADMIN WHERE UserID = ?");
    $stmtAdmin->bind_param("s", $userID);
    $stmtAdmin->execute();
    $resultAdmin = $stmtAdmin->get_result();

    if ($resultAdmin->num_rows === 1) {
        $response['status'] = 'exists_admin';
    } else {
        $response['status'] = 'exists_user';

        // Fetch user details
        $stmtUser = $connMe->prepare("SELECT FullName, EmailAddress, PhoneNo, UserType, BirthDate, Gender, EmailSecCode, SecQues1, SecQues2 FROM USER WHERE UserID = ?");
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
            'secQues2' => $userDetails['SecQues2']
        ];
        $stmtUser->close();
    }
    $stmtAdmin->close();
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
    <title>Registration Admin</title>
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
        <a href="loginAdm.php" class="close-icon">✖</a>
        <h1>Registration Admin</h1>

        <form id="emailNPhoneForm" method = "POST">
            <!-- Email Input -->
            <div class="form-group" id="emailStep" style="display: none;">
                <label for="email">Email Address:</label>
                <input type="text" id="email" name="email" required placeholder="Use staff email if applicable" autocomplete="off"> <!--check email format-->
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

            <!--check seccode with email-->
            <div id="securityCodeSection" style="display: none;">
                <label for="securityCode">Enter Security Code:</label>
                <input type="password" id="securityCode" name="securityCode" maxLength=8 autocomplete="off">
                <button type="button" id="validateSecCodeButton">Validate Security Code</button>
            </div>
            <span id="securityCodeError" style="color: red;"></span>

        </form>

        <form id="registerForm" method = "POST">
            <!-- Horizontal Divider -->
            <div id="dividerSection" style="display: none;">
                <hr class="divider">
            </div>

            <!-- Common User Information -->
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
                    <label for="phoneChecked">Phone Number:</label>
                    <input type="text" id="phoneChecked" name="phoneChecked" readonly>
                </div>

                <div class="form-group">
                    <label for="userType">Type of User:</label>
                    <!--readonly input attribute can allow the value to be passed when click submit button, compared to "disabled"-->
                    <input type="text" id="userType" name="userType" value="Admin" readonly> 
                </div>

                <div class="form-group">
                    <label for="birthDate">Birthday Date:</label>
                    <input type="date" id="birthDate" name="birthDate" required>
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

            <!-- Admin-Specific Information -->
            <div id="adminFields" style="display: none;">
            <h3>Specific Admin Information</h3>
                <div class="form-group">
                    <label for="department">Department:</label>
                    <div class="radio-group">
                        <label class="radio-label">
                            <!-- Since the radio button under the same group, so the name must be the same -->
                            <input type="radio" id="departHepa" name="department" required value="hepa"> HEPA
                        </label>
                        <label class="radio-label">
                            <input type="radio" id="departSpku" name="department" value="spku"> SPKU
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="position">Position:</label>
                    <input type="text" id="position" name="position" required autocomplete="off">
                </div>
                
                <!-- Check Username's constraint -->
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" maxLength=20 required autocomplete="off">
                </div>
                <!--Seperate from div part to avoid the error message occur side-by-side-->
                <span id="usernameError" class="error"></span> <!-- Error message will be displayed here -->

                <!-- Check Password's constraint -->
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" maxLength=16 required autocomplete="off">
                </div>
                <!--Seperate from div part to avoid the error message occur side-by-side-->
                <span id="pwdError" class="error"></span> <!-- Error message will be displayed here -->
            </div>

            <!-- Submit Button -->
            <button type="submit" class="button" id="submitButton">Register</button>
        </form>

    </div>

    <script>

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

    const adminFields = document.getElementById("adminFields");
    const passwordInput = document.getElementById("password");
    const pwdError = document.getElementById("pwdError");
    const emailSecCodeInput = document.getElementById("emailSecCode");
    const emailSecCodeError = document.getElementById("emailSecCodeError");
    const submitButton = document.getElementById("submitButton");

    const securityCodeSection = document.getElementById("securityCodeSection");
    const securityCodeInput = document.getElementById("securityCode");
    const securityCodeError = document.getElementById("securityCodeError");
    const validateSecCodeButton = document.getElementById("validateSecCodeButton");

    let validEmail = null;
    // Initialize the form based on the radio button state when the page loads
    document.addEventListener("DOMContentLoaded", () => {
            emailStep.style.display = "block"; //Show email input
            phoneStep.style.display = "block";
            dividerSection.style.display = "none";
            userFields.style.display = "none";
            adminFields.style.display = "none";
            checkButton.style.display = "block"; //Show check email button
            submitButton.style.display = "none";
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
        e.preventDefault(); // Prevent form from submitting

        checkError.style.color = "";

        // Collect form data
        const emailNPhoneFormData = new FormData(emailNPhoneForm);

        // Send FormData to backend
        fetch("registerAdm.php", {
            method: "POST",
            body: emailNPhoneFormData,
        })
        .then((response) => response.json()) // Parse the response as JSON
        .then((data) => {
            console.log("Response from server:", data);  // Log response for debugging

            // Handle response based on the JSON data
            if (data.status === "exists_admin") {
                checkError.style.color = "red"; 
                checkError.textContent = "You are already registered as a admin.";

                //Do not display any textfield
                dividerSection.style.display = "none";
                userFields.style.display = "none";
                adminFields.style.display = "none";
                submitButton.style.display = "none";
                
            } else if (data.status === "exists_user") {
                checkError.style.color = "green"; // Green for 'exists_user'

                //show admin specific field only
                dividerSection.style.display = "none";
                checkError.textContent = "Email or phone number exists. Welcome!\nPlease enter your email's security code.";
                checkError.style.whiteSpace = "pre-wrap"; // Ensures the \n is interpreted as a line break.
                userFields.style.display = "none";
                adminFields.style.display = "none"; //Hide admin field
                submitButton.style.display = "none"; //Hide submit button
                submitButton.disabled = false; // Make sure the button is enabled
                checkButton.style.display = "none";
                emailStep.style.display = "none"; 
                phoneStep.style.display = "none";

                // Show the security code input field
                securityCodeSection.style.display = "block";

                // Prefill the form with user data
                const registerForm = document.getElementById('registerForm');
                const userData = data.userData; // Get user data from the response

                //if userData got value = go through process of retrieving data of user to get common attribute
                if (userData) {
                    //loop each element in the userData array
                    for (const key in userData) {
                        //# is the CSS selector for an id.
                        //${key} dynamically inserts the value of the key from the loop.
                        //If an element with the matching id exists inside the form, it will return a reference 
                        //to that HTML element. Example: <input id="name"> will be returned as inputElement.
                        const inputElement = registerForm.querySelector(`#${key}`);
                        //if the key of user data matches with input id of html tag
                        if (inputElement) {
                            //set HTML element's value
                            inputElement.value = userData[key];
                        }
                    }
                    validEmail = userData.emailChecked;
                }

            } else if (data.status === "exists_none") {
                checkError.style.color = "green"; // Green for 'exists_user'

                //Show all textfield
                dividerSection.style.display = "block"; 
                checkError.textContent = "Email or phone number does not exist. Welcome, new user!\nKindly fill in all user information required as admin.";
                checkError.style.whiteSpace = "pre-wrap"; // Ensures the \n is interpreted as a line break.
                userFields.style.display = "block";
                adminFields.style.display = "block";
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
                adminFields.style.display = "none";
                submitButton.style.display = "none";
            }else {
                console.log("Unexpected response:", data); // Log unexpected responses
            }
        })
        
        .catch((err) => {
            console.error("Error:", err);
            emailError.textContent = "Unable to verify email. Please try again.";
        });
    });

    // Since will keep checking the input whenever user react to the input field
    //so, if error is cleared, it will "loop" back to the isValid "true"
    //no need put parentheses to the function, cuz are passing the reference to the validateForm function, not calling it directly. 
    emailSecCodeInput.addEventListener("input", validateForm);

    passwordInput.addEventListener("input", validateForm);

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
                securityCodeError.textContent = "Security code is valid!\nKindly complete your specific admin information below.";
                securityCodeError.style.whiteSpace = "pre-wrap"; 
                dividerSection.style.display = "block";
                securityCodeSection.style.display = "none"; // Hide security code section
                adminFields.style.display = "block"; // Show admin-specific fields
                submitButton.style.display = "block"; // Show submit button
            } else {
                securityCodeError.style.color = "red";
                securityCodeError.textContent = "Invalid security code. Please try again.";
                adminFields.style.display = "none"; // Hide admin fields
                submitButton.style.display = "none"; // Hide submit button
            }
        })
        .catch((err) => {
            console.error("Error:", err);
            securityCodeError.textContent = "An error occurred. Please try again.";
        });
    });

    //validate username input blur: check after user finish typing and leave the field
    document.getElementById("username").addEventListener("blur", () => {
        const username = document.getElementById("username").value.trim();
        const userType = "ADMIN";
        const usernameError = document.getElementById("usernameError");

        usernameError.textContent = ""; // Reset error message

        if (username === "") {
            usernameError.textContent = "Username cannot be empty.";
            return; 
        }

        // Send the username and userType to the backend
        fetch("validateUsername.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({ username: username, userType: userType }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.status === "exists") {
                    usernameError.textContent = "Username already exists. Please use another username.";
                } else if (data.status === "available") {
                    usernameError.textContent = ""; // Clear error
                } else {
                    usernameError.textContent = data.message;
                }
            })
            .catch((err) => {
                console.error("Error:", err);
                usernameError.textContent = "Error validating username. Please try again.";
            });
    });


    // Register button clicked
    registerForm.addEventListener("submit", (e) => {
        e.preventDefault();
        
        // Collect all form data using FormData
        const formData = new FormData(registerForm);

        // Build a confirmation message dynamically
        let confirmationMessage = "Please confirm the details below:\n\n";

        //loop each input
        // name attribute = key, 
        //the user’s input as the value.
        for (const [key, value] of formData.entries()) {
            console.log(key, value); // This will log each form field's name and value
        
            // Special handling for security questions
            if (key === 'secQues1') {
                confirmationMessage += `SECURITY QUESTION 1: ${value}\n\n`;
                continue;
            }
            if (key === 'secQues2') {
                confirmationMessage += `SECURITY QUESTION 2: ${value}\n\n`;
                continue;
            }
            
            // Find the associated label using `for` attribute
            //registerForm refers to the actual form element (the DOM node) that selected (like label)
            const label = registerForm.querySelector(`label[for="${key}"]`);

            // Get the label's text or fallback to the key if label is missing
            const labelText = label ? label.textContent : key;

            // Convert the label text and value to uppercase
            const upperCaseLabel = labelText.toUpperCase();
            //use "let" instead of const cuz further will change the value
            let inputValue = value;

            console.log("inputValue:", inputValue);

            //in order to make current role concat with what the user register now
            if(upperCaseLabel === "TYPE OF USER:") {
                if (checkError.textContent.includes("Email or phone number exists")) {
                    // For existing users, concatenate with current value
                    inputValue = inputValue.concat(" ", "ADMIN");
                } else {
                    // For new users, just set as ADMIN
                    inputValue = "ADMIN";
                }
            }
            
            //Inputs except password and username will make it in upper case
            if(upperCaseLabel != "PASSWORD:" && upperCaseLabel != "USERNAME:" && upperCaseLabel != "EMAIL'S SECURITY CODE:"){
                inputValue = inputValue.toUpperCase();
            }

            if(upperCaseLabel === "GENDER:"){
                if(inputValue === "MALE" ){
                    inputValue = "M";
                } else if (inputValue === "FEMALE"){
                    inputValue = "F";
                }
            }

            // Add to the confirmation message
            confirmationMessage += `${upperCaseLabel} ${inputValue}\n\n`;

        }

        // Show confirmation dialog
        if (window.confirm(confirmationMessage)) {
            // Create an object to hold all form data and the user status
            const registrationData = {
                formData: Object.fromEntries(formData),
                isExistingUser: checkError.textContent.includes("Email or phone number exists") // Check if it's an existing user
            };

            // Send to processRegAdm.php
            fetch("processRegAdm.php", {
                method: "POST",
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(registrationData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Create a custom alert message with the IDs
                    const message = `Registration successful!\n\n` +
                                   `Your User ID: ${data.userID}\n` +
                                   `Your Admin ID: ${data.adminID}\n\n` +
                                   `Click OK to proceed to login page.`;
                    
                    alert(message);
                    location.href = "loginAdm.php"; // Redirect to login page
                } else {
                    alert(`Error: ${data.message}`);
                }
            })
            .catch(err => {
                console.error("Error:", err);
                alert("An unexpected error occurred. Please try again.");
            });
        } else {
            // User clicked "Cancel"
            alert("Registration canceled.");
        }
    });

    // Function to format the date to YYYY-MM-DD
    function formatDate(date) {
        let year = date.getFullYear();
                                                    //padStart(targetLength, padString)
        let month = (date.getMonth() + 1).toString().padStart(2, '0'); // Months are 0-11, so add 1
        let day = date.getDate().toString().padStart(2, '0');
        return `${year}-${month}-${day}`;
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

    // To ensure one of the input field is not correct, submit button cannot be clicked
    //phone no, pwd checking
    function validateForm() {
        const pwdValue = passwordInput.value.trim();
        const emailSecCodeValue = emailSecCodeInput.value.trim();

        // Reset error messages
        pwdError.textContent = "";
        emailSecCodeError.textContent = "";

        let isValid = true;

        //Vaidate Security Code
        //if not contain at least 1 lower Case, 1 upperCase, 1 number, 1 special characters, min 4charac, max 8 characters
        if(!/^(?=(.*[a-z]))(?=(.*[A-Z]))(?=(.*[0-9]))(?=(.*[!@#$%^&*]))[a-zA-Z0-9!@#$%^&*]{4,8}$/.test(emailSecCodeValue)){
            emailSecCodeError.textContent = "Must contains lowercase, uppercase, number, special character(!@#$%^&*).";
            isValid = false;
        }

        // Validate password
        if (pwdValue.length < 8) {
            pwdError.textContent = "Minimum length of password must be 8 characters.";
            isValid = false;
        }
        
        // Enable or disable the submit button based on overall form validity
        submitButton.disabled = !isValid;
    }

    // Get current date
    let today = new Date();

    // Set max to today
    let maxDate = formatDate(today);

    // Subtract 125 years for min date
    let minDate = new Date(today);
    minDate.setFullYear(today.getFullYear() - 125);
    minDate = formatDate(minDate);

    // Set the min and max attributes
    document.getElementById('birthDate').setAttribute('min', minDate);
    document.getElementById('birthDate').setAttribute('max', maxDate);

    </script>

</body>
</html>
