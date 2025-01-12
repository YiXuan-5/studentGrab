<?php
include 'dbConnection.php'; // Ensure database connection is included.

//display any PHP errors directly on the page
ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];

    // Check if the user already exists
    //Make sure no matter user insert lower or upper case
    $stmt = $connMe->prepare("SELECT UserID FROM USER WHERE EmailAddress = UPPER(?)");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $userID = $row['UserID'];

        // Check if the user is already registered as a passenger
        $stmt = $connMe->prepare("SELECT PsgrID FROM PASSENGER WHERE UserID = ?");
        $stmt->bind_param("s", $userID);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            // User is already a passenger
            $response['status'] = 'exists_passenger';
        } else {
            // Send user data as part of the response
            $response['status'] = 'exists_user';

            //Store common attribute into userData association array
            $stmt = $connMe->prepare("SELECT FullName, EmailAddress, PhoneNo, UserType, BirthDate, Gender, EmailSecCode FROM USER WHERE UserID = ?");
            $stmt->bind_param("s", $userID);
            $stmt->execute();
            $result = $stmt->get_result();

            $row = $result->fetch_assoc(); // fetches a result row as an associative array.
            //key name of the array need to same as the id name in html to make the process of 
            //search the id input easier (querySelector())

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

    // Send the response as JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    exit; //Avoid execute the code below
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Passenger</title>
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
        <a href="loginPsgr.php" class="close-icon">✖</a>
        <h1>Registration Passenger</h1>

        <form id="emailForm" method = "POST">
            <!-- Email Input -->
            <div class="form-group" id="emailStep" style="display: none;">
                <label for="email">Email Address:</label>
                <input type="text" id="email" name="email" required placeholder="Your active email" autocomplete="off"> <!--check email format-->
            </div>
            <span id="emailError" class="error"></span>

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
                    <label for="phoneNo">Phone Number:</label>
                    <input type="text" id="phoneNo" name="phoneNo" maxLength=12 required autocomplete="off">
                </div>
                <!--Seperate from div part to avoid the error message occur side-by-side-->
                <span id="phoneError" class="error"></span> <!-- Error message will be displayed here -->

                <div class="form-group">
                    <label for="userType">Type of User:</label>
                    <!--readonly input attribute can allow the value to be passed when click submit button, compared to "disabled"-->
                    <input type="text" id="userType" name="userType" value="Passenger" readonly> 
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

            <!-- Passenger-Specific Information -->
            <div id="passengerFields" style="display: none;">
            <h3>Specific Passenger Information</h3>
                <div class="form-group">
                    <label for="favPickUpLoc">Favorite Pickup Location:</label>
                    <input type="text" id="favPickUpLoc" name="favPickUpLoc" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="favDropOffLoc">Favorite Drop-Off Location:</label>
                    <input type="text" id="favDropOffLoc" name="favDropOffLoc" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="role">Role of Passenger:</label>
                    <div class="radio-group">
                        <label class="radio-label">
                            <!-- Since the radio button under the same group, so the name must be the same -->
                            <input type="radio" id="roleStud" name="role" required value="student"> Student
                        </label>
                        <label class="radio-label">
                            <input type="radio" id="roleStaff" name="role" value="staff"> Staff
                        </label>
                        <label class="radio-label">
                            <input type="radio" id="roleVisitor" name="role" value="visitor"> Visitor
                        </label>
                    </div>
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
    const checkButton = document.getElementById("checkButton");
    const emailForm = document.getElementById("emailForm");
    const emailInput = document.getElementById("email");
    const emailError = document.getElementById("emailError");

    const registerForm = document.getElementById("registerForm");
    const userFields = document.getElementById("userFields");
    const phoneInput = document.getElementById("phoneNo");
    const phoneError = document.getElementById("phoneError");
    const passengerFields = document.getElementById("passengerFields");
    const passwordInput = document.getElementById("password");
    const pwdError = document.getElementById("pwdError");
    const emailSecCodeInput = document.getElementById("emailSecCode");
    const emailSecCodeError = document.getElementById("emailSecCodeError");
    const submitButton = document.getElementById("submitButton");

    const securityCodeSection = document.getElementById("securityCodeSection");
    const securityCodeInput = document.getElementById("securityCode");
    const securityCodeError = document.getElementById("securityCodeError");
    const validateSecCodeButton = document.getElementById("validateSecCodeButton");

    // Initialize the form based on the radio button state when the page loads
    document.addEventListener("DOMContentLoaded", () => {
            emailStep.style.display = "block"; //Show email input
            dividerSection.style.display = "none";
            userFields.style.display = "none";
            passengerFields.style.display = "none";
            checkButton.style.display = "block"; //Show check email button
            submitButton.style.display = "none";
    });


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
            //Call function
            fillReadonlyValue();
        }
    });

    //Email checking
    emailForm.addEventListener("submit", (e) => {
        e.preventDefault(); // Prevent form from submitting

        emailError.style.color = "";

        // Collect form data
        const emailFormData = new FormData(emailForm);

        // Send FormData to backend
        fetch("registerPsgr.php", {
            method: "POST",
            body: emailFormData,
        })
        .then((response) => response.json()) // Parse the response as JSON
        .then((data) => {
            console.log("Response from server:", data);  // Log response for debugging

            // Handle response based on the JSON data
            if (data.status === "exists_passenger") {
                emailError.style.color = "red"; 
                emailError.textContent = "You are already registered as a passenger.";

                //Do not display any textfield
                dividerSection.style.display = "none";
                userFields.style.display = "none";
                passengerFields.style.display = "none";
                submitButton.style.display = "none";
                
            } else if (data.status === "exists_user") {
                emailError.style.color = "green"; // Green for 'exists_user'

                //show passenger specific field only
                dividerSection.style.display = "none";
                emailError.textContent = "Email exists. Welcome!\nPlease enter your email's security code.";
                emailError.style.whiteSpace = "pre-wrap"; // Ensures the \n is interpreted as a line break.
                userFields.style.display = "none";
                passengerFields.style.display = "none"; //Hide passenger field
                submitButton.style.display = "none"; //Hide submit button
                submitButton.disabled = false; // Make sure the button is enabled
                checkButton.style.display = "none";
                emailStep.style.display = "none"; 

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
                }

            } else if (data.status === "exists_none") {
                emailError.style.color = "green"; // Green for 'exists_none'

                //Show all textfield
                dividerSection.style.display = "block"; 
                emailError.textContent = "Email does not exist. Welcome, new user!\nKindly fill in all user information required as passenger.";
                emailError.style.whiteSpace = "pre-wrap"; // Ensures the \n is interpreted as a line break.
                userFields.style.display = "block";
                passengerFields.style.display = "block";
                submitButton.style.display = "block";
                checkButton.style.display = "none";
                emailStep.style.display = "none"; 

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
                        userType: 'PASSENGER'
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
                    securityCodeError.textContent = "Security code is valid!\nKindly complete your specific passenger information below.";
                    securityCodeError.style.whiteSpace = "pre-wrap"; 
                    dividerSection.style.display = "block";
                    securityCodeSection.style.display = "none"; // Hide security code section
                    passengerFields.style.display = "block"; // Show passenger-specific fields
                    submitButton.style.display = "block"; // Show submit button
                } else {
                    securityCodeError.style.color = "red";
                    securityCodeError.textContent = "Invalid security code. Please try again.";
                    passengerFields.style.display = "none"; // Hide passenger fields
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
        const userType = "PASSENGER";
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

            //in order to make current role concat with what the user register now
            if(upperCaseLabel === "TYPE OF USER:") {
                if (emailError.textContent.includes("Email exists")) {
                    // For existing users, concatenate with current value
                    inputValue = inputValue.concat(" ", "PASSENGER");
                } else {
                    // For new users, just set as PASSENGER
                    inputValue = "PASSENGER";
                }
            }
            
            //Inputs except password, username, email's security code will make it in upper case
            if(upperCaseLabel != "PASSWORD:" && 
               upperCaseLabel != "USERNAME:" && 
               upperCaseLabel != "EMAIL'S SECURITY CODE:") {
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
                isExistingUser: emailError.textContent.includes("Email exists") // Check if it's an existing user
            };

            // Send to processRegPsgr.php
            fetch("processRegPsgr.php", {
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
                                   `Your User ID: ${data.userId}\n` +
                                   `Your Passenger ID: ${data.psgrId}\n\n` +
                                   `Click OK to proceed to login page.`;
                    
                    alert(message);
                    location.href = "loginPsgr.php"; // Redirect to login page
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
    function fillReadonlyValue() {
        // Get the value from the "email" input
        const emailValue = document.getElementById('email').value;
        
        // Set the value of the "emailChecked" input
        document.getElementById('emailChecked').value = emailValue;
    }

    // To ensure one of the input field is not correct, submit button cannot be clicked
    //phone no, pwd checking
    function validateForm() {
        const phoneValue = phoneInput.value.trim();
        const pwdValue = passwordInput.value.trim();
        const emailSecCodeValue = emailSecCodeInput.value.trim();

        // Reset error messages
        phoneError.textContent = "";
        pwdError.textContent = "";
        emailSecCodeError.textContent = "";

        let isValid = true;

        //Vaidate Security Code
        //if not contain at least 1 lower Case, 1 upperCase, 1 number, 1 special characters, min 4charac, max 8 characters
        if(!/^(?=(.*[a-z]))(?=(.*[A-Z]))(?=(.*[0-9]))(?=(.*[!@#$%^&*]))[a-zA-Z0-9!@#$%^&*]{4,8}$/.test(emailSecCodeValue)){
            emailSecCodeError.textContent = "Must contains lowercase, uppercase, number, special character(!@#$%^&*).";
            isValid = false;
        }

        // Validate phone number
        //If not valid
        if (!/^[0-9]{3}-[0-9]{7,8}$/.test(phoneValue)) {
            phoneError.textContent = "Enter phone number (Eg:012-34567890).";
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
