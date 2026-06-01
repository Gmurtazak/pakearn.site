<?php
include 'connection.php';

$names = [
    'block_amount_update',
    'block_price_update'
];

$upd = $conn->prepare("
    UPDATE security_switch SET is_on = 0 WHERE name = ?
");
foreach ($names as $n) {
    $upd->bind_param("s", $n);
    $upd->execute();
}
?>
