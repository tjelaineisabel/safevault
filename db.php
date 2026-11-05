<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "dash"; 

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function getPendingRequestCount($conn) {
    $sql = "SELECT COUNT(*) as total FROM users WHERE status = 'deactivated'";
    $result = $conn->query($sql);
    if ($result) {
        $row = $result->fetch_assoc();
        return $row['total'];
    }
    return 0;
}

function isAdmin($username) {
    return $username === 'jelaine123';
}
?>