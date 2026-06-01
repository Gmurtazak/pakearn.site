<?php
include 'connection.php';

$withdrawal_switches = [
    'block_withdrawals_insert',
    'block_withdrawals_update',
    'block_withdrawals_delete'
];

$disable_trigger_stmt = $conn->prepare("
    UPDATE security_switch
       SET is_on = 0
     WHERE name = ?
");

foreach ($withdrawal_switches as $switch_name) {
    $disable_trigger_stmt->bind_param("s", $switch_name);
    $disable_trigger_stmt->execute();
}
?>
