<?php
$host = "sql306.infinityfree.com"; 
$user = "if0_43371250";       
$password = "SafeVault123";    
$dbname = "if0_43371250_database";   

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>