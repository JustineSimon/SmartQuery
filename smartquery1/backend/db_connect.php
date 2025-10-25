<?php
$servername = "localhost";
$username = "root";   // default XAMPP MySQL user
$password = "";       // default is empty
$dbname = "smartquery_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
?>
