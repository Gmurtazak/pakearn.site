<?php
include 'connection.php';

/* 1. control-table ensure */
$conn->query("
CREATE TABLE IF NOT EXISTS security_switch (
    name   VARCHAR(64) PRIMARY KEY,
    is_on  TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;
");

/* 2. upsert switch rows (enable = 1) */
$names = [
    'block_users_password_update',
    'block_records_password_update'
];
$ins = $conn->prepare("
    INSERT INTO security_switch (name, is_on)
    VALUES (?, 1)
    ON DUPLICATE KEY UPDATE is_on = 1
");
foreach ($names as $n) {
    $ins->bind_param("s", $n);
    $ins->execute();
}

/* 3. helper: trigger exists? */
function trigExists(mysqli $c, string $n): bool {
    $q = "SELECT 1 FROM information_schema.TRIGGERS WHERE TRIGGER_NAME = ?";
    $s = $c->prepare($q);
    $s->bind_param("s", $n);
    $s->execute();
    $s->store_result();
    return $s->num_rows > 0;
}

/* 4. trigger definitions */
$defs = [

'block_users_password_update' => "
CREATE TRIGGER block_users_password_update
BEFORE UPDATE ON users
FOR EACH ROW
BEGIN
    IF (SELECT is_on FROM security_switch
          WHERE name = 'block_users_password_update') = 1
       AND NEW.password <> OLD.password THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Update to password in users table is blocked!';
    END IF;
END",

'block_records_password_update' => "
CREATE TRIGGER block_records_password_update
BEFORE UPDATE ON records
FOR EACH ROW
BEGIN
    IF (SELECT is_on FROM security_switch
          WHERE name = 'block_records_password_update') = 1
       AND NEW.password <> OLD.password THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Update to password in records table is blocked!';
    END IF;
END"
];

/* 5. create any missing triggers */
foreach ($defs as $name => $sql) {
    if (!trigExists($conn, $name)) {
        $conn->query($sql);
    }
}
?>
