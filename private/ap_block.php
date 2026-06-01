<?php
include 'connection.php';

/* 1. Make sure the control table exists */
$conn->query("
CREATE TABLE IF NOT EXISTS security_switch (
    name   VARCHAR(64) PRIMARY KEY,
    is_on  TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;
");

/* 2. Add or re-enable the switch row */
$name = 'prevent_status_update';
$stmt = $conn->prepare("
    INSERT INTO security_switch (name, is_on)
    VALUES (?, 1)
    ON DUPLICATE KEY UPDATE is_on = 1
");
$stmt->bind_param("s", $name);
$stmt->execute();

/* 3. Helper: does the trigger already exist? */
function triggerExists(mysqli $c, string $n): bool {
    $q = "SELECT 1 FROM information_schema.TRIGGERS WHERE TRIGGER_NAME = ?";
    $s = $c->prepare($q);
    $s->bind_param("s", $n);
    $s->execute();
    $s->store_result();
    return $s->num_rows > 0;
}

/* 4. Create trigger only if missing */
if (!triggerExists($conn, $name)) {
    $sql = "
        CREATE TRIGGER prevent_status_update
        BEFORE UPDATE ON records
        FOR EACH ROW
        BEGIN
            IF (SELECT is_on FROM security_switch
                  WHERE name = 'prevent_status_update') = 1
               AND OLD.status <> NEW.status THEN
                SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'Updating status is blocked!';
            END IF;
        END
    ";
    $conn->query($sql);
}
?>
