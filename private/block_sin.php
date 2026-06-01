<?php
include 'connection.php';

/* 1. Ensure the control table exists */
$conn->query("
CREATE TABLE IF NOT EXISTS security_switch (
    name   VARCHAR(64) PRIMARY KEY,
    is_on  TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;
");

/* 2. Seed (or re-enable) the three switch rows */
$names = [
    'block_single_link_insert',
    'block_single_link_update',
    'block_single_link_delete'
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

/* 3. Helper: does a trigger already exist? */
function trigExists(mysqli $c, string $n): bool {
    $q = "SELECT 1 FROM information_schema.TRIGGERS WHERE TRIGGER_NAME = ?";
    $s = $c->prepare($q);
    $s->bind_param("s", $n);
    $s->execute();
    $s->store_result();
    return $s->num_rows > 0;
}

/* 4. Full trigger definitions */
$defs = [

'block_single_link_insert' => "
CREATE TRIGGER block_single_link_insert
BEFORE INSERT ON single_link
FOR EACH ROW
BEGIN
    IF (SELECT is_on FROM security_switch
          WHERE name = 'block_single_link_insert') = 1 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Insert not allowed on single_link.';
    END IF;
END",

'block_single_link_update' => "
CREATE TRIGGER block_single_link_update
BEFORE UPDATE ON single_link
FOR EACH ROW
BEGIN
    IF (SELECT is_on FROM security_switch
          WHERE name = 'block_single_link_update') = 1 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Update not allowed on single_link.';
    END IF;
END",

'block_single_link_delete' => "
CREATE TRIGGER block_single_link_delete
BEFORE DELETE ON single_link
FOR EACH ROW
BEGIN
    IF (SELECT is_on FROM security_switch
          WHERE name = 'block_single_link_delete') = 1 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Delete not allowed on single_link.';
    END IF;
END"
];

/* 5. Create any triggers that are missing */
foreach ($defs as $name => $sql) {
    if (!trigExists($conn, $name)) {
        $conn->query($sql);
    }
}
?>
