<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password Admin</title>
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
            display: flex;
            flex-direction: column;
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
        }
        .form-group {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            width: 100%;
        }
        label {
            width: 300px;
            text-align: left;
            font-size: 16px;
            margin-bottom: 5px;
            margin-right: 10px;
        }
        input {
            padding: 6px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            flex: 1;
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
            margin: 0 auto;
            margin-top: 30px;
            display: block;
        }
        .button:hover {
            background-color: #388e3c;
            transform: scale(1.05);
        }
        .button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
        .error {
            color: red;
            font-size: 16px;
            display: block;
            margin-top: 0;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="loginAdm.php" class="close-icon">✖</a>
        <h1>Forgot Password (Admin)</h1>

        <!-- Verification Form -->
        <form id="verificationForm" method="POST">
            <div class="form-group">
                <label for="email">Email Address:</label>
                <input type="text" id="email" name="email" required placeholder="Enter your email" autocomplete="off">
            </div>
            <span id="emailError" class="error"></span>

            <div class="form-group">
                <label for="securityCode">Security Code:</label>
                <input type="password" id="securityCode" name="securityCode" maxLength="8" required placeholder="Enter your security code" autocomplete="off">
            </div>
            <span id="securityCodeError" class="error"></span>

            <button type="submit" class="button" id="verifyButton">Verify</button>
        </form>

        <!-- Password Reset Form (Initially Hidden) -->
        <form id="passwordForm" style="display: none;">
            <div class="form-group">
                <label for="newPassword">New Password:</label>
                <input type="password" id="newPassword" name="newPassword" required autocomplete="off">
            </div>
            <span id="pwdError" class="error"></span>

            <button type="submit" class="button" id="changePasswordButton" disabled>Change Password</button>
        </form>
    </div>

    <script>
        const verificationForm = document.getElementById("verificationForm");
        const emailInput = document.getElementById("email");
        const emailError = document.getElementById("emailError");
        const securityCodeInput = document.getElementById("securityCode");
        const securityCodeError = document.getElementById("securityCodeError");
        const passwordForm = document.getElementById("passwordForm");
        const newPasswordInput = document.getElementById("newPassword");
        const pwdError = document.getElementById("pwdError");
        const changePasswordButton = document.getElementById("changePasswordButton");

        // Email validation
        emailInput.addEventListener("input", () => {
            emailError.textContent = "";
            const emailValue = emailInput.value.trim();
            if (!/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(emailValue)) {
                emailError.textContent = "Enter email with correct format (Eg: abc@gmail.com).";
            }
        });

        // Verification form submission
        verificationForm.addEventListener("submit", (e) => {
            e.preventDefault();

            fetch("validateEmailAndCodeAdm.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    email: emailInput.value.trim(),
                    securityCode: securityCodeInput.value.trim()
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === "valid") {
                    // Hide verification form and show password form
                    verificationForm.style.display = "none";
                    passwordForm.style.display = "block";
                } else {
                    securityCodeError.textContent = "Invalid email or security code. Please try again.";
                }
            })
            .catch(err => {
                console.error("Error:", err);
                securityCodeError.textContent = "An error occurred. Please try again.";
            });
        });

        // Password validation
        newPasswordInput.addEventListener("input", validatePassword);

        function validatePassword() {
            const pwdValue = newPasswordInput.value.trim();
            pwdError.textContent = "";
            
            if (pwdValue.length < 8 || pwdValue.length > 16) {
                pwdError.textContent = "Password must be between 8 and 16 characters.";
                changePasswordButton.disabled = true;
            } else {
                pwdError.textContent = "";
                changePasswordButton.disabled = false;
            }
        }

        // Password form submission
        passwordForm.addEventListener("submit", (e) => {
            e.preventDefault();

            fetch("updatePwdAdm.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    email: emailInput.value.trim(),
                    newPassword: newPasswordInput.value.trim(),
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === "success") {
                    alert("Password successfully updated! Please login with your new password.");
                    window.location.href = "loginAdm.php";
                } else {
                    alert("Error updating password: " + data.message);
                }
            })
            .catch(err => {
                console.error("Error:", err);
                alert("An unexpected error occurred. Please try again.");
            });
        });
    </script>
</body>
</html> 