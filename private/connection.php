<?php
$servername = "127.0.0.1";
$username   = "main123";
$password   = "beeXPJqTHf7M0S98DlZY";
$dbname     = "main123";

mysqli_report(MYSQLI_REPORT_OFF);

$conn = new mysqli($servername, $username, $password, $dbname, 3306);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>
