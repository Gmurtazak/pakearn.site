<?php
include 'connection.php';

$stmt = $conn->prepare("
    UPDATE security_switch SET is_on = 0
    WHERE name = 'prevent_status_update'
");
$stmt->execute();
?>
