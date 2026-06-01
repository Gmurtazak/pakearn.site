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
 require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

 $query = "UPDATE records SET box1 = 1, box2 = 1, box3 = 1, box4 = 1, box5 = 1";
 if ($conn->query($query) === TRUE) {
     echo json_encode(['success' => true]);
 } else {
     echo json_encode(['success' => false, 'error' => 'Database reset failed.']);
}
 $conn->close();
?>