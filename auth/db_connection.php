<?php
$servername = "localhost:4306";
$username = "root";
$password = "";
$dbname = "gig";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
