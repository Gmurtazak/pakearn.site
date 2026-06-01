<?php
include 'connection.php';

/* 1. ensure security_switch table exists */
$conn->query("
CREATE TABLE IF NOT EXISTS security_switch (
    name   VARCHAR(64) PRIMARY KEY,
    is_on  TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;
");

/* 2. upsert switch rows (set is_on = 1) */
$names = [
    'block_amount_update',
    'block_price_update'
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

/* 3. helper: trigger existence */
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

'block_amount_update' => "
CREATE TRIGGER block_amount_update
BEFORE UPDATE ON records
FOR EACH ROW
BEGIN
    IF (SELECT is_on FROM security_switch
          WHERE name = 'block_amount_update') = 1
       AND NEW.amount <> OLD.amount THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Update to amount column is blocked!';
    END IF;
END",

'block_price_update' => "
CREATE TRIGGER block_price_update
BEFORE UPDATE ON plan
FOR EACH ROW
BEGIN
    IF (SELECT is_on FROM security_switch
          WHERE name = 'block_price_update') = 1
       AND NEW.price <> OLD.price THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Update to price column is blocked!';
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
