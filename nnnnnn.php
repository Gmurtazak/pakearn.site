<?php include $_SERVER['DOCUMENT_ROOT'] . '/../private/security.php'; ?>
<?php
// Block common SQL injection attempts via GET parameters (case-insensitive)
$blacklist = ['select', 'insert', 'update', 'delete', 'drop', 'truncate', 'alter', '--', ';', '/*', '*/', '@@', '@', 'exec', '1=1'];

foreach ($_GET as $param => $value) {
    if (is_array($value)) continue;

    $value = strtolower($value); // convert input to lowercase

    foreach ($blacklist as $word) {
        if (strpos($value, $word) !== false) {
            http_response_code(403);
            exit("Blocked: Malicious keyword '$word' detected in URL.");
        }
    }
}
?>
// Check reff_id in users table and count records with 'active' status
    $user_query = "SELECT COUNT(*) FROM users WHERE reff_id = ? AND status = 'active'";
    $user_stmt = $conn->prepare($user_query);
    $user_stmt->bind_param("i", $id);
    $user_stmt->execute();
    $user_stmt->bind_result($new_count);
    $user_stmt->fetch();
    $user_stmt->close();

    // Compare prev and new values
    if ($prev != $new_count) {
        // Update stat to 1 if prev and new do not match
        $update_stat_query = "UPDATE records SET stat = 1, prev = ? WHERE id = ?";
        $update_stat_stmt = $conn->prepare($update_stat_query);
        $update_stat_stmt->bind_param("ii", $new_count, $id);
        $update_stat_stmt->execute();
        $update_stat_stmt->close();

        $stat = 1;

    }