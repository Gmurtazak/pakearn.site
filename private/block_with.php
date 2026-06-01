<?php
include 'connection.php';

/* 1. Ensure the control table exists */
$conn->query("
CREATE TABLE IF NOT EXISTS security_switch (
    name   VARCHAR(64) PRIMARY KEY,
    is_on  TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;
");

/* 2. Insert or re-enable trigger switches */
$withdrawal_switches = [
    'block_withdrawals_insert',
    'block_withdrawals_update',
    'block_withdrawals_delete'
];

$insert_withdrawal_switch = $conn->prepare("
    INSERT INTO security_switch (name, is_on)
    VALUES (?, 1)
    ON DUPLICATE KEY UPDATE is_on = 1
");

foreach ($withdrawal_switches as $switch_name) {
    $insert_withdrawal_switch->bind_param("s", $switch_name);
    $insert_withdrawal_switch->execute();
}

/* 3. Unique helper function to check if trigger exists */
function withdrawalTriggerExists(mysqli $conn, string $triggerName): bool {
    $query = "SELECT 1 FROM information_schema.TRIGGERS WHERE TRIGGER_NAME = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $triggerName);
    $stmt->execute();
    $stmt->store_result();
    return $stmt->num_rows > 0;
}

/* 4. Withdrawal-specific trigger definitions */
$withdrawal_trigger_defs = [

'block_withdrawals_insert' => "
CREATE TRIGGER block_withdrawals_insert
BEFORE INSERT ON withdrawals
FOR EACH ROW
BEGIN
    IF (SELECT is_on FROM security_switch
          WHERE name = 'block_withdrawals_insert') = 1 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Insert not allowed on withdrawals.';
    END IF;
END",

'block_withdrawals_update' => "
CREATE TRIGGER block_withdrawals_update
BEFORE UPDATE ON withdrawals
FOR EACH ROW
BEGIN
    IF (SELECT is_on FROM security_switch
          WHERE name = 'block_withdrawals_update') = 1 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Update not allowed on withdrawals.';
    END IF;
END",

'block_withdrawals_delete' => "
CREATE TRIGGER block_withdrawals_delete
BEFORE DELETE ON withdrawals
FOR EACH ROW
BEGIN
    IF (SELECT is_on FROM security_switch
          WHERE name = 'block_withdrawals_delete') = 1 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Delete not allowed on withdrawals.';
    END IF;
END"
];

/* 5. Create only missing triggers */
foreach ($withdrawal_trigger_defs as $trigger => $trigger_sql) {
    if (!withdrawalTriggerExists($conn, $trigger)) {
        $conn->query($trigger_sql);
    }
}
?>
