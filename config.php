<?php
$servername = "localhost";
$dbname = "moneycraft"; 
$username = "root"; 
$password = "";

// Create a connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check if there's a connection error
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// If no errors, the connection is successful
$conn->set_charset("utf8");
?>
