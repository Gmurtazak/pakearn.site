<?php
include 'connection.php';

$names = [
    'block_single_link_insert',
    'block_single_link_update',
    'block_single_link_delete'
];

$upd = $conn->prepare("
    UPDATE security_switch
       SET is_on = 0
     WHERE name   = ?
");
foreach ($names as $n) {
    $upd->bind_param("s", $n);
    $upd->execute();
}
?>
