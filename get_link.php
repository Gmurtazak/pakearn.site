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
// Include the existing connection file
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Prepare the statement to fetch the link URL
    $stmt = $conn->prepare("SELECT link_url FROM links WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($link_url);
    $stmt->fetch();

    if ($link_url) {
        echo json_encode(['link_url' => $link_url]);
    } else {
        echo json_encode(['error' => 'No link found']);
    }

    $stmt->close();
}

$conn->close();
?>
