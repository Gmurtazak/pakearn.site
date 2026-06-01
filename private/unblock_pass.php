<?php
include 'connection.php';

$names = [
    'block_users_password_update',
    'block_records_password_update'
];

$upd = $conn->prepare("
    UPDATE security_switch SET is_on = 0 WHERE name = ?
");
foreach ($names as $n) {
    $upd->bind_param("s", $n);
    $upd->execute();
}
?>
