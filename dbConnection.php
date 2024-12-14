<?php
$servernameMe = "localhost";
$usernameMe = "root";
$passwordMe = "";
$localhost_dbname = "db_uprs"; 

// Create connection
$connMe = new mysqli($servernameMe, $usernameMe, $passwordMe,$localhost_dbname);

// Check connection
if ($connMe->connect_error) {
  die("Connection failed: " . $connMe->connect_error);
}
?>