<?php
    session_start();
    include 'dbConnection.php';

    // Check if user is logged in
    if (!isset($_SESSION['AdminID'])) {
        header('Location: loginAdm.php');
        exit();
    }

    // Get admin ID from URL
    if (!isset($_GET['adminID'])) {
        header('Location: viewAdm.php');
        exit();
    }

    $adminID = $_GET['adminID'];

    // Fetch admin details
    try {
        $stmt = $connMe->prepare("
            SELECT a.*, u.* 
            FROM ADMIN a 
            JOIN USER u ON a.UserID = u.UserID 
            WHERE a.AdminID = ?
        ");
        $stmt->bind_param("s", $adminID);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();

        if (!$admin) {
            header('Location: viewAdm.php');
            exit();
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
        exit();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Admin Account</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/fontawesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/solid.min.css">
    <style>
        /* Copy styles from editPsgr.php and modify as needed */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
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
        }

        .nav-items.right {
            margin-left: auto;
        }

        .navbar-title {
            color: white;
            font-size: 20px;
            font-weight: bold;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
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

        .container {
            padding: 80px 20px 20px;
            max-width: 800px;
            margin: 0 auto;
        }

        .section {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .section-title {
            color: #2e7d32;
            margin-bottom: 20px;
            font-size: 24px;
            padding-bottom: 10px;
        }

        .form-group {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .form-group label {
            display: inline-block;
            width: 120px;
            margin-bottom: 5px;
            color: #333;
            font-weight: bold;
        }

        .form-control {
            width: calc(100% - 130px);
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #f5f5f5;
            color: #666;
            font-size: 16px;
        }

        .button-group {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .button {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
            color: white;
            background-color: #4caf50;
        }

        .button:hover {
            background-color: #388e3c;
        }

        .nav-dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #4caf50;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 5px;
            margin-top: 5px;
        }

        .nav-dropdown:hover .dropdown-content {
            display: block;
        }

        .dropdown-content a {
            color: white;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }

        .dropdown-content a:hover {
            background-color: #388e3c;
            border-radius: 5px;
        }

        .profile-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .profile-picture {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            margin-bottom: 15px;
            object-fit: cover;
            border: 3px solid #4caf50;
        }

        .profile-name {
            font-size: 24px;
            color: #000000;
            margin-bottom: 30px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <a href="viewAdm.php" class="nav-item">
            <i class="fas fa-arrow-left"></i>
            Back
        </a>
        <div class="navbar-title">View Admin Details</div>
        <div class="nav-items right">
            <a href="profileAdm.php" class="nav-item">
                <i class="fas fa-user"></i>
                Profile
            </a>
        </div>
    </nav>

    <div class="container">
        <!-- Profile Picture Section -->
        <div class="profile-section">
            <img src="<?php echo $admin['ProfilePicture'] ? 'data:image/jpeg;base64,' . $admin['ProfilePicture'] : 'https://img.freepik.com/premium-vector/green-circle-with-white-person-inside-icon_1076610-14570.jpg'; ?>" 
                 alt="Profile Picture" 
                 class="profile-picture">
            <div class="profile-name"><?php echo ucwords(strtolower($admin['FullName'])); ?></div>
        </div>

        <!-- Account Information Section -->
        <div class="section">
            <h2 class="section-title">Account Information</h2>
            <div class="form-group">
                <label>User ID</label>
                <input type="text" class="form-control" value="<?php echo $admin['UserID']; ?>" readonly>
            </div>
            <div class="form-group">
                <label>Admin ID</label>
                <input type="text" class="form-control" value="<?php echo $admin['AdminID']; ?>" readonly>
            </div>
            <div class="form-group">
                <label>Status</label>
                <input type="text" class="form-control" value="<?php echo ucwords(strtolower($admin['Status'])); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Username</label>
                <input type="text" class="form-control" value="<?php echo $admin['Username']; ?>" readonly>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" class="form-control" value="<?php echo strtolower($admin['EmailAddress']); ?>" readonly>
            </div>
        </div>

        <!-- Personal Information Section -->
        <div class="section">
            <h2 class="section-title">Personal Information</h2>
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" class="form-control" value="<?php echo ucwords(strtolower($admin['FullName'])); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" class="form-control" value="<?php echo $admin['PhoneNo']; ?>" readonly>
            </div>
            <div class="form-group">
                <label>Gender</label>
                <input type="text" class="form-control" value="<?php echo $admin['Gender']; ?>" readonly>
            </div>
            <div class="form-group">
                <label>Birth Date</label>
                <input type="text" class="form-control" value="<?php echo date('d/m/Y', strtotime($admin['BirthDate'])); ?>" readonly>
            </div>
        </div>

        <!-- Job Information Section -->
        <div class="section">
            <h2 class="section-title">Job Information</h2>
            <div class="form-group">
                <label>Department</label>
                <input type="text" class="form-control" value="<?php echo $admin['Department']; ?>" readonly>
            </div>
            <div class="form-group">
                <label>Position</label>
                <input type="text" class="form-control" value="<?php echo ucwords(strtolower($admin['Position'])); ?>" readonly>
            </div>
        </div>
    </div>
</body>
</html> 