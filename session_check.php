<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php'; // Database connection include karna zaroori hai

// Check if user_id exists

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Agar URL mein id nahi di gayi ya match nahi hoti
if (!isset($_GET['id']) || $_GET['id'] != $_SESSION['user_id']) {
    header("Location: login.php");
    exit();
}
?>
