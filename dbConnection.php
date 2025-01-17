<?php
$servernameMe = "localhost";
$usernameMe = "root";
$passwordMe = "w2Group1"; // Wrong password for testing
$localhost_dbnameMe = "db_uprs";

// Enable error reporting for debugging (can be removed in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // Create connection
    $connMe = new mysqli($servernameMe, $usernameMe, $passwordMe, $localhost_dbnameMe);

} catch (mysqli_sql_exception $e) {
    // Handle the exception and show a custom error message
    echo "Connection to database " . $localhost_dbnameMe . " for IP address 192.168.214.5 failed<br> Please check your database credentials or the connectivity.";
}


/*
$connMe = new mysqli($servernameMe, $usernameMe, $passwordMe,$localhost_dbnameMe);

// Check connection
if ($connMe->connect_error) {
  die ("Connection failed: " . $connMe->connect_error);
  //echo "Connection Error";
}

*/

//Aishah db connection
$servernameAishah = "192.168.214.55";
$usernameAishah = "wong";
$passwordAishah = "abc123";
$localhost_dbnameAishah = "workshop2"; 

// Create connection
try {
    // Create connection
    $connAishah = new mysqli($servernameAishah, $usernameAishah, $passwordAishah,$localhost_dbnameAishah);

} catch (mysqli_sql_exception $e) {
    // Handle the exception and show a custom error message
    echo "Connection to database " . $localhost_dbnameAishah . " for IP address " . $servernameAishah . "failed<br> Please check database credentials or the connectivity.";
}


//Aimi db connection
$host = "192.168.214.196";
$port = "5432";
$dbname = "UTeM_Peer_Rides_System"; // Replace with your database name
$user = "WONG";          // Replace with your PostgreSQL username
$password = "password";  // Replace with your PostgreSQL password

// Connection string (DSN)
$dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

try {
    $connAimi = new PDO($dsn, $user, $password);
    $connAimi->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // No success message is output here
} catch (PDOException $e) {
    echo "Connection to database " . $dbname . " for IP address " . $host . "failed<br> Please check database credentials or the connectivity.";
}

//Zul db connection
/*
$servernameZul = "192.168.214.166";
$usernameZul = "wong";
$passwordZul = "abc123";
$localhost_dbnameZul = "peerride"; 

// Create connection
try {
    // Create connection
    $connZul = new mysqli($servernameZul, $usernameZul, $passwordZul,$localhost_dbnameZul);

} catch (mysqli_sql_exception $e) {
    // Handle the exception and show a custom error message
    echo "Connection to database " . $localhost_dbnameZul . " for IP address " . $servernameZul . "failed<br> Please check database credentials or the connectivity.";
}
*/
?>