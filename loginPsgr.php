<?php
/*php code*/

session_start(); // Start the session
include 'dbConnection.php'; // Include database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate POST parameters when sending request to HTTP server
    //$_POST[name of textfield], not id for textfield
    //checks whether a variable is set and is not null
    // contains data sent from the form via an HTTP POST request.
    if (!isset($_POST['psgrUsername']) || !isset($_POST['psgrPwd'])) { 
        echo "Invalid request: Username or password cannot be empty."; // Plain text response
        exit; //stop further execution
    }

    $usernamePsgr = $_POST['psgrUsername'];
    $passwordPsgr = $_POST['psgrPwd'];

    // Prepare and execute the SQL query
    $stmt = $connMe->prepare("SELECT PsgrID, UserID FROM PASSENGER WHERE Username = ? AND Password = ?");
    if (!$stmt) {
        echo "Database error."; // Plain text response
        exit;
    }

    $stmt->bind_param("ss", $usernamePsgr, $passwordPsgr); // Bind parameters
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // Successful login
        $row = $result->fetch_assoc(); // fetches a result row as an associative array.
        $_SESSION['PsgrID'] = $row['PsgrID']; //store in Session array to bring to next page
        $_SESSION['UserID'] = $row['UserID'];

        echo "success"; // Plain text response
    } else {
        // Login failed
        echo "Wrong username or password."; // Plain text response
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
    <title>Passenger Login</title>
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
        <a href="mainPage.html" class="back-arrow">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"> <!-- top-left corner;width and height of the viewBox-->
                <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/>
            </svg>
        </a>

        <!-- Centered heading -->
        <h1>Passenger Login</h1><br>
        
        <!-- Form for login -->
         <!--when user submit the form, it will trigger the POST-->
        <form id="loginForm" method="POST"> <!--method POST should include-->
            <!-- Username field -->
            <div class="form-group">
                <label for="psgrUsername">Username:</label>
                <input type="text" id="psgrUsername" name="psgrUsername" maxlength="20" autofocus required> 
                <!-- maxlength: only allow user to enter how many characters that set -->
                 <!--autofocus: make it focus when user enter the website; required: require field-->
            </div>

            <!-- Password field -->
            <div class="form-group">
                <label for="psgrPwd">Password:</label>
                <input type="password" id="psgrPwd" name="psgrPwd" maxlength="16" required>
            </div>

            <!-- Success and Error Messages -->
            <div id="message" class=""></div>

            <!-- login button -->
            <button type="submit" class="button">Login</button> 
        </form>

        <!-- Links with a vertical divider -->
        <div class="links">
            <a href="registerPsgr.php" class="otherOption">Register New Account</a>
            <div class="vl"></div> <!--A vertical line styled as a divider between the links.-->
            <a href="forgotPsgr.php" class="otherOption">Forgot Password?</a>
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
        fetch("loginPsgr.php", {
            method: "POST",
            body: formData,
        })
        //.then(): response text returned from the PHP backend
        //converts the PHP response into plain text 
        .then(response => response.text()) 
        .then(data => {
            if (data.trim() === "success") {
                // Handle success
                messageDiv.textContent = "Dear passenger, login successful!";
                messageDiv.style.color = "green";
                //
                setTimeout(() => {
                    window.location.href = "profilePsgr.php"; // Redirect to next page
                }, 2000); //2000ms = 2s to wait bfr move to next page
            } else {
                // Handle error
                messageDiv.textContent = data; // Display the error message
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

