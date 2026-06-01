<?php
// private/create_trigger.php
include'connection.php';
$sql = "
DELIMITER //

CREATE TRIGGER block_insert_myrecords
BEFORE INSERT ON myrecords
FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000'
  SET MESSAGE_TEXT = 'Insert not allowed on myrecords table';
END;
//

CREATE TRIGGER block_insert_users
BEFORE INSERT ON users
FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000'
  SET MESSAGE_TEXT = 'Insert not allowed on users table';
END;
//

DELIMITER ;
";

if (mysqli_multi_query($conn, $sql)) {
} else {
}
?>
