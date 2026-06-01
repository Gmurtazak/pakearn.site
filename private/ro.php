<?php
// private/drop_trigger.php
include 'connection.php';

$sql = "
DROP TRIGGER IF EXISTS block_insert_myrecords;
DROP TRIGGER IF EXISTS block_insert_users;
";

if (mysqli_multi_query($conn, $sql)) {
} else {
}
?>
