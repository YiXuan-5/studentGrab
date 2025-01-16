<?php
session_start();
// Check if user is not logged in
if (!isset($_SESSION['AdminID'])) {
    header('Location: loginAdm.php');
    exit();
}

// Get user data
include 'dbConnection.php';
$stmt = $connMe->prepare("SELECT u.FullName FROM USER u JOIN ADMIN a ON u.UserID = a.UserID WHERE a.AdminID = ?");
$stmt->bind_param("s", $_SESSION['AdminID']);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
$stmt->close();
$connMe->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Home Page</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
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

        /* Hero Section Styles */
        .hero {
            background-image: url('https://grabdrivermy.com/wp-content/uploads/2020/06/grab-gif-bye.gif');
            background-size: cover;
            background-position: center;
            height: 500px;
            display: flex;
            align-items: center;
            text-align: center;
            color: #ffffff;
            position: relative;
        }

        .hero-content {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            z-index: 1;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
        }

        .hero h1 {
            font-size: 48px;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .hero p {
            font-size: 24px;
            margin-bottom: 30px;
        }

        .get-started-btn, .feature-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4caf50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            transition: background-color 0.3s;
        }

        .get-started-btn:hover, .feature-btn:hover {
            background-color: #388e3c;
        }

        /* Features Section Styles */
        .features {
            padding: 80px 0;
            background-color: #fff3cd;
        }

        .section-title {
            text-align: center;
            font-size: 32px;
            font-weight: 700;
            color: #333;
            margin-bottom: 40px;
            padding: 0 20px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .feature-card {
            text-align: center;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
            display: flex;
            flex-direction: column;
            height: 100%;
            align-items: center;
            background-color: #ffffff;
        }

        .feature-card:hover {
            transform: translateY(-10px);
        }

        .feature-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #2e7d32;
            width: 100%;
        }

        .feature-image {
            width: 250px;
            height: 250px;
            object-fit: contain;
            margin-bottom: 20px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }

        .feature-btn {
            background-color: #4caf50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
            margin-top: auto;
            width: 250px;
            white-space: nowrap;
        }

        .feature-btn:hover {
            background-color: #388e3c;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding-top: 60px;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/fontawesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/solid.min.css">
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

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Welcome, Administrator<br><?php echo ucwords(strtolower($userData['FullName'])); ?> 😊</h1>
            <p>Access the ride management page where you can monitor and manage all ride details</p>
            <a href="http://192.168.214.55/workshop2/uprs/RideMgmtAdm.php?UserID=<?php echo $_SESSION['UserID']; ?>&AdminID=<?php echo $_SESSION['AdminID']; ?>" class="get-started-btn">Get Started</a>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <h2 class="section-title">Administrative Privileges</h2>
        <div class="features-grid">
            <div class="feature-card">
                <h3 class="feature-title">User Management</h3>
                <img src="data:image/jpeg;base64,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" 
                     alt="User Management" 
                     class="feature-image">
                <a href="userMgmt.php" class="feature-btn">Manage Users</a>
            </div>

            <div class="feature-card">
                <h3 class="feature-title">Payment and Billing Management</h3>
                <img src="https://img.freepik.com/free-vector/pay-balance-owed-abstract-concept-illustration-making-credit-payment-pay-owed-money-bank-irs-balance-due-debt-consolidation-management-taxpayer-bill_335657-1236.jpg" 
                     alt="Payment Management" 
                     class="feature-image">
                <a href="http://192.168.214.254/paymentBillingMgmt.php?UserID=<?php echo $_SESSION['UserID']; ?>&AdminID=<?php echo $_SESSION['AdminID']; ?>" class="feature-btn" style="white-space: nowrap;">Manage Payments</a>
            </div>

            <div class="feature-card">
                <h3 class="feature-title">Feedback and Rating Management</h3>
                <img src="https://img.freepik.com/premium-vector/application-rating-customer-user-reviews-5-stars-website-ranking-system-positive-feedback-evaluate-votes-flat-vector-illustration_128772-845.jpg" 
                     alt="Feedback Management" 
                     class="feature-image">
                <a href="http://192.168.214.196/FRMgmt.php?UserID=<?php echo $_SESSION['UserID']; ?>&AdminID=<?php echo $_SESSION['AdminID']; ?>" class="feature-btn" style="white-space: nowrap;">Manage Feedback/Rating</a>
            </div>
        </div>
    </section>
</body>
</html> 