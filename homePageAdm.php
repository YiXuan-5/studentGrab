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
            <a href="https://192.168.193.55/workshop2/uprs/homePagePass.php" class="get-started-btn">Get Started</a>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <h2 class="section-title">Administrative Privileges</h2>
        <div class="features-grid">
            <div class="feature-card">
                <h3 class="feature-title">User Management</h3>
                <img src="https://www.pngitem.com/pimgs/m/364-3641869_user-management-cartoon-hd-png-download.png" 
                     alt="User Management" 
                     class="feature-image">
                <a href="userMgmt.php" class="feature-btn">Manage Users</a>
            </div>

            <div class="feature-card">
                <h3 class="feature-title">Payment and Billing Management</h3>
                <img src="https://img.freepik.com/free-vector/pay-balance-owed-abstract-concept-illustration-making-credit-payment-pay-owed-money-bank-irs-balance-due-debt-consolidation-management-taxpayer-bill_335657-1236.jpg" 
                     alt="Payment Management" 
                     class="feature-image">
                <a href="paymentBillingMgmt.php" class="feature-btn" style="white-space: nowrap;">Manage Payments</a>
            </div>

            <div class="feature-card">
                <h3 class="feature-title">Feedback and Rating Management</h3>
                <img src="https://img.freepik.com/premium-vector/application-rating-customer-user-reviews-5-stars-website-ranking-system-positive-feedback-evaluate-votes-flat-vector-illustration_128772-845.jpg" 
                     alt="Feedback Management" 
                     class="feature-image">
                <a href="https://192.168.193.196/adminFeedback.php" class="feature-btn" style="white-space: nowrap;">Manage Feedback/Rating</a>
            </div>
        </div>
    </section>
</body>
</html> 