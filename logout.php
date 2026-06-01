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
<?php
session_start();
session_unset();  // Remove all session variables
session_destroy(); // Destroy the session
header("Location: login.php"); // Redirect to login page
exit();
?>
