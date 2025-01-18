<?php
/*php code*/

session_start(); // Start the session
include 'dbConnection.php'; // Include database connection
include 'auditLog.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate POST parameters when sending request to HTTP server
    //$_POST[name of textfield], not id for textfield

    if (!isset($_POST['driUsername']) || !isset($_POST['driPwd'])) { 
        echo "Invalid request: Username or password cannot be empty."; // Plain text response
        exit; //stop further execution
    }

    $usernameDri = $_POST['driUsername'];
    $passwordDri = $_POST['driPwd'];

    // Prepare and execute the SQL query
    $stmt = $connMe->prepare("
        SELECT d.DriverID, d.UserID 
        FROM DRIVER d
        JOIN USER u ON d.UserID = u.UserID 
        WHERE d.Username = ? AND d.Password = ? AND u.Status = 'ACTIVE'
    ");
    if (!$stmt) {
        echo "Database error."; // Plain text response
        exit;
    }

    $stmt->bind_param("ss", $usernameDri, $passwordDri); // Bind parameters
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // Successful login
        $row = $result->fetch_assoc(); // fetches a result row as an associative array.
        $_SESSION['DriverID'] = $row['DriverID']; //store in Session array to bring to next page
        $_SESSION['UserID'] = $row['UserID'];
        
        // Log successful login
        logUserActivity($row['UserID'], 'DRIVER', 'LOGIN', 'SUCCESS');

        // Return JSON response instead of plain text
        echo json_encode([
            'status' => 'success',
            'redirect' => "profileDri.php"
            //'redirect' => "http://192.168.214.55/workshop2/uprs/homePageDriv.php?UserID=" . $row['UserID'] . "&DriverID=" . $row['DriverID']
        ]);
    } else {
        // Check if user exists but is deactivated
        $checkStmt = $connMe->prepare("
            SELECT d.UserID, u.Status 
            FROM DRIVER d
            JOIN USER u ON d.UserID = u.UserID 
            WHERE d.Username = ? AND d.Password = ?
        ");
        $checkStmt->bind_param("ss", $usernameDri, $passwordDri);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows === 1) {
            $checkRow = $checkResult->fetch_assoc();
            // Defined user but account is deactivated
            if ($checkRow['Status'] === 'DEACTIVATED') {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Your account has been deactivated. Please contact administrator.'
                ]);
                logUserActivity($checkRow['UserID'], 'DRIVER', 'LOGIN', 'FAILED - ACCOUNT DEACTIVATED');
            } else {
                // Defined user but wrong password
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Wrong username or password.'
                ]);
                logUserActivity($checkRow['UserID'], 'DRIVER', 'LOGIN', 'FAILED');
            }
            $checkStmt->close();
        } else {
            // Undefined user login
            // Log failed login attempt
            logUserActivity('NA', 'DRIVER', 'LOGIN', 'FAILED');
            echo json_encode([
                'status' => 'error',
                'message' => 'Wrong username or password.'
            ]);
        }
    }

    $stmt->close(); // Close statement
    $connMe->close(); // Close connection
    exit;
}
?>

<!--html code-->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Login</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: rgba(0, 0, 0, 0.3);
        }
        .container {
            position: relative;
            max-width: 500px;
            border: 2px solid #8bc34a;
            border-radius: 10px;
            padding: 30px;
            padding-left: 50px; /* Equal white space on the left */
            padding-right: 50px; /* Equal white space on the right */
            background-color: #ffffff;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.7);
            text-align: center;
        }
        .back-arrow {
            position: absolute;
            top: 25px;
            left: 10px;
            text-decoration: none;
            color: #4caf50;
        }
        .back-arrow svg { /*Sets the size and color of the arrow icon.*/
            width: 65px;
            height:55px;
            fill: #4caf50;
            transition: fill 0.3s ease;
        }
        .back-arrow:hover svg {
            fill: #388e3c;
        }
        h1 {
            font-size: 40px;
            margin: 0 0 20px 0;
            font-family: "Palatino", serif;
            background: #000000;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 20px;
            align-items: center;
            margin-bottom: 20px;
        }
        .form-group {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        label {
            font-size: 18px;
            text-align: left; /* Aligns labels to the left */
        }
        input {
            width: 100%;
            padding: 8px;
            font-size: 18px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; /* Ensures padding doesn't affect the width */
        }
        .button {
            display: inline-block;
            padding: 8px 20px;
            font-size: 18px;
            color: white;
            background-color: #4caf50;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s ease;
            margin-top: 10px;
        }
        .button:hover {
            background-color: #388e3c;
            transform: scale(1.05); /* Slightly enlarges the button on hover */
        }
        .links { /*Centers the additional links and adds spacing.*/
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 20px;
            gap: 10px;
        }
        .vl { /*Adds a vertical line as a divider.*/
            border-left: 2px solid grey;
            height: 15px;
        }
        a {
            text-decoration: none;
            font-size: 16px;
            color: #007bff;
        }
        .otherOption:hover{
            text-decoration: underline;
        }
        #message {
            color: red;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Back arrow -->
        <a href="mainPagePsgrDri.html" class="back-arrow">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"> <!-- top-left corner;width and height of the viewBox-->
                <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/>
            </svg>
        </a>

        <!-- Centered heading -->
        <h1>Driver Login</h1><br>
        
        <!-- Form for login -->
         <!--when user submit the form, it will trigger the POST-->
        <form id="loginForm" method="POST"> <!--method POST should include-->
            <!-- Username field -->
            <div class="form-group">
                <label for="driUsername">Username:</label>
                <input type="text" id="driUsername" name="driUsername" maxlength="20" autofocus required> 
                <!-- maxlength: only allow user to enter how many characters that set -->
                 <!--autofocus: make it focus when user enter the website; required: require field-->
            </div>

            <!-- Password field -->
            <div class="form-group">
                <label for="driPwd">Password:</label>
                <input type="password" id="driPwd" name="driPwd" maxlength="16" required>
            </div>

            <!-- Success and Error Messages -->
            <div id="message" class=""></div>

            <!-- login button -->
            <button type="submit" class="button">Login</button> 
        </form>

        <!-- Links with a vertical divider -->
        <div class="links">
            <a href="registerDri.php" class="otherOption">Register New Account</a>
            <div class="vl"></div> <!--A vertical line styled as a divider between the links.-->
            <a href="forgotDri.php" class="otherOption">Forgot Password?</a>
        </div>
    </div>

    <!--User Action: Click "Login" button. ↓
        JavaScript: Intercepts form submission and sends POST request to PHP.
        ↓
        PHP: Validates data, checks the database, and returns a response (e.g., "success").
        ↓
        JavaScript: Receives response and updates <div id="message">.-->

    <script>
        document.getElementById("loginForm").addEventListener("submit", function (event) {
        //The form submission is intercepted, and the default behavior of submitting the form directly 
        //to the server is stopped
            event.preventDefault(); // Prevent default form submission

        // Collect form data
        const formData = new FormData(event.target);
        const messageDiv = document.getElementById("message");  // Display in the <div>message part in html

        // Send AJAX request to PHP backend
        fetch("loginDri.php", {
            method: "POST",
            body: formData,
        })
        //.then(): response text returned from the PHP backend
        //converts the PHP response into plain text 
        .then(response => response.json())  // Change to parse JSON instead of text
        .then(data => {
            if (data.status === 'success') {
                // Handle success
                messageDiv.textContent = "Dear driver, login successful!";
                messageDiv.style.color = "green";
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 2000);
            } else {
                // Handle error
                messageDiv.textContent = data.message;
                messageDiv.style.color = "red";
            }
        })
        .catch(error => {
            console.error("Fetch Error:", error); // Log fetch errors
            messageDiv.textContent = "Something went wrong. Please try again.";
            messageDiv.style.color = "red";
        });
    });


    </script>
</body>
</html>
