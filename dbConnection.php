<?php
$servernameMe = "localhost";
$usernameMe = "root";
$passwordMe = "";
$localhost_dbnameMe = "db_uprs"; 

// Create connection
$connMe = new mysqli($servernameMe, $usernameMe, $passwordMe,$localhost_dbnameMe);

// Check connection
if ($connMe->connect_error) {
  die("Connection failed: " . $connMe->connect_error);
}

//Aishah db connection
$servernameAishah = "192.168.193.55";
$usernameAishah = "wong";
$passwordAishah = "abc123";
$localhost_dbnameAishah = "workshop2"; 

// Create connection
$connAishah = new mysqli($servernameAishah, $usernameAishah, $passwordAishah,$localhost_dbnameAishah);

// Check connection
if ($connAishah->connect_error) {
  die("Connection failed: " . $connAishah->connect_error);
}
/*
//Aimi db connection
$host = "192.168.193.196";
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
    echo "Connection failed: " . $e->getMessage();
}
*/
//Zul db connection
/*
$servernameZul = "192.168.193.254";
$usernameZul = "wong";
$passwordZul = "abc123";
$localhost_dbnameZul = "peerride"; 

// Create connection
$connZul = new mysqli($servernameZul, $usernameZul, $passwordZul,$localhost_dbnameZul);

// Check connection
if ($connZul->connect_error) {
  die("Connection failed: " . $connZul->connect_error);
}
*/
?>