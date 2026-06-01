<?php
include 'connection.php';

$stmt = $conn->prepare("
    UPDATE security_switch
       SET is_on = 0
     WHERE name   = 'block_earnings_update'
");
$stmt->execute();
?>
