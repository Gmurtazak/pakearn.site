<?php
include 'connection.php';

/* ── 1. Create table if not exists ── */
$conn->query("
CREATE TABLE IF NOT EXISTS security_switch (
    name   VARCHAR(64) PRIMARY KEY,
    is_on  TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;
");

/* ── 2. Insert trigger switch rows ── */
$triggerNames = [
    'block_payment_accounts_insert',
    'block_payment_accounts_update',
    'block_payment_accounts_delete'
];

$stmtSeed = $conn->prepare("
    INSERT INTO security_switch (name, is_on)
    VALUES (?, 1)
    ON DUPLICATE KEY UPDATE is_on = 1
");
foreach ($triggerNames as $t) {
    $stmtSeed->bind_param("s", $t);
    $stmtSeed->execute();
}

/* ── 3. Check if trigger exists ── */
function triggerExists(mysqli $conn, string $name): bool {
    $stmt = $conn->prepare("
        SELECT 1 FROM information_schema.TRIGGERS
        WHERE TRIGGER_NAME = ?
    ");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $stmt->store_result();
    return $stmt->num_rows > 0;
}

/* ── 4. Trigger SQL definitions ── */
$defs = [
    'block_payment_accounts_insert' => "
        CREATE TRIGGER block_payment_accounts_insert
        BEFORE INSERT ON payment_accounts
        FOR EACH ROW
        BEGIN
            IF (SELECT is_on FROM security_switch
                WHERE name = 'block_payment_accounts_insert') = 1 THEN
                SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'Insert not allowed on payment_accounts.';
            END IF;
        END",

    'block_payment_accounts_update' => "
        CREATE TRIGGER block_payment_accounts_update
        BEFORE UPDATE ON payment_accounts
        FOR EACH ROW
        BEGIN
            IF (SELECT is_on FROM security_switch
                WHERE name = 'block_payment_accounts_update') = 1 THEN
                SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'Update not allowed on payment_accounts.';
            END IF;
        END",

    'block_payment_accounts_delete' => "
        CREATE TRIGGER block_payment_accounts_delete
        BEFORE DELETE ON payment_accounts
        FOR EACH ROW
        BEGIN
            IF (SELECT is_on FROM security_switch
                WHERE name = 'block_payment_accounts_delete') = 1 THEN
                SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'Delete not allowed on payment_accounts.';
            END IF;
        END"
];

/* ── 5. Create missing triggers ── */
foreach ($defs as $name => $sql) {
    if (!triggerExists($conn, $name)) {
        $conn->query($sql);
    }
}
?>
