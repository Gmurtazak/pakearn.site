<?php
include 'connection.php';

/* 1. Ensure control table exists */
$conn->query("
CREATE TABLE IF NOT EXISTS security_switch (
    name   VARCHAR(64) PRIMARY KEY,
    is_on  TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;
");

/* 2. Add / re-enable switch row */
$name = 'block_earnings_update';
$stmt = $conn->prepare("
    INSERT INTO security_switch (name, is_on)
    VALUES (?, 1)
    ON DUPLICATE KEY UPDATE is_on = 1
");
$stmt->bind_param("s", $name);
$stmt->execute();

/* 3. Check if trigger already exists */
function trigExists(mysqli $c, string $n): bool {
    $q = "SELECT 1 FROM information_schema.TRIGGERS WHERE TRIGGER_NAME = ?";
    $s = $c->prepare($q);
    $s->bind_param("s", $n);
    $s->execute();
    $s->store_result();
    return $s->num_rows > 0;
}

/* 4. Create trigger only if missing */
if (!trigExists($conn, $name)) {
    $sql = "
        CREATE TRIGGER block_earnings_update
        BEFORE UPDATE ON earnings
        FOR EACH ROW
        BEGIN
            IF (SELECT is_on FROM security_switch
                  WHERE name = 'block_earnings_update') = 1
               AND (
                    OLD.referral_earning <> NEW.referral_earning OR
                    OLD.ads_earning      <> NEW.ads_earning      OR
                    OLD.total_earning    <> NEW.total_earning
               ) THEN
                SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'Updating earnings fields is blocked!';
            END IF;
        END
    ";
    $conn->query($sql);
}
?>
