<?php
include 'connection.php';

$triggerNames = [
    'block_payment_accounts_insert',
    'block_payment_accounts_update',
    'block_payment_accounts_delete'
];

$stmt = $conn->prepare("
    UPDATE security_switch SET is_on = 0 WHERE name = ?
");

foreach ($triggerNames as $name) {
    $stmt->bind_param("s", $name);
    $stmt->execute();
}
?>
