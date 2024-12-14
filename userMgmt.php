<?php
session_start();
// Check if user is not logged in
if (!isset($_SESSION['AdminID'])) {
    header('Location: loginAdm.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management Page</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/fontawesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/solid.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        /* Navigation Bar Styles */
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
            gap: 5px;
            transition: background-color 0.3s;
        }

        .nav-item:hover {
            background-color: #388e3c;
        }

        .nav-items.right {
            margin-left: auto;
        }

        /* Main Content Styles */
        .container {
            display: flex;
            height: calc(100vh - 60px);
            padding-top: 60px;
            overflow: hidden;
        }

        .left-section {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 0;
            background-color: white;
            overflow: hidden;
            height: 100%;
        }

        .right-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 0;
            background-color: #fff3cd;
            height: 100%;
        }

        .search-image {
            width: 100%;
            height: 100%;
            border: none;
            object-fit: contain;
            padding: 40px;
            display: block;
            max-height: 100vh;
        }

        .instruction {
            text-align: left;
            font-size: 20px;
            color: black;
            margin-bottom: 30px;
            font-family: Arial, sans-serif;
            width: 100%;
            max-width: 650px;
            padding-left: 20px;
        }

        .button-container {
            display: flex;
            flex-direction: row;
            gap: 20px;
            width: 100%;
            max-width: 650px;
            justify-content: center;
            align-items: center;
        }

        .view-button {
            padding: 25px 40px;
            font-size: 20px;
            color: white;
            background-color: #4caf50;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            text-decoration: none;
            min-width: 200px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .view-button i {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .button-desc {
            font-size: 14px;
            margin-top: 5px;
            opacity: 0.9;
        }

        .view-button:hover {
            background-color: #388e3c;
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            overflow: hidden;
        }

        .section-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .main-title {
            font-size: 32px;
            color: #2e7d32;
            margin-bottom: 15px;
            font-weight: bold;
        }

        .sub-title {
            font-size: 18px;
            color: #666;
            margin-bottom: 40px;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <a href="homePageAdm.php" class="nav-item">
            <i class="fas fa-home"></i>
            Home
        </a>
        <div class="nav-items right">
            <a href="profileAdm.php" class="nav-item">
                <i class="fas fa-user"></i>
                Profile
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <!-- Left Section with Image -->
        <div class="left-section">
            <img src="https://static.vecteezy.com/system/resources/previews/026/994/815/non_2x/tiny-people-browsing-online-information-surfing-internet-with-binocular-search-bars-seo-concept-modern-flat-cartoon-style-illustration-on-white-background-vector.jpg" 
                 alt="Search Illustration" 
                 class="search-image">
        </div>

        <!-- Right Section with Buttons -->
        <div class="right-section">
            <div class="section-header">
                <h1 class="main-title">User Management System</h1>
                <p class="sub-title">Access and manage user information efficiently</p>
            </div>
            <p class="instruction">Please select a user type to view their details:</p>
            <div class="button-container">
                <div class="button-group" style="display: inline-block; margin-right: 10px;">
                    <a href="viewPsgr.php" class="view-button">
                        <i class="fas fa-users"></i>
                        <span>Passenger</span>
                        <p class="button-desc">View passenger details and accounts</p>
                    </a>
                </div>
                <div class="button-group" style="display: inline-block;">
                    <a href="viewDri.php" class="view-button">
                        <i class="fas fa-car"></i>
                        <span>Driver</span>
                        <p class="button-desc">View driver details and accounts</p>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 