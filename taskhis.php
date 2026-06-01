<?php include $_SERVER['DOCUMENT_ROOT'] . '/../private/security.php'; ?>
<?php
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Content-Security-Policy: default-src 'self'");

// Secure session configuration
session_start([
    'cookie_lifetime' => 600, // 30 minutes
    'cookie_secure' => true,   // Only send over HTTPS
    'cookie_httponly' => true, // Prevent JavaScript access
    'use_strict_mode' => true  // Prevent session fixation
]);

// Check if user is logged in
if (!isset($_SESSION['admin']['logged_in'])) {
    header('Location: admin_login.php');
    exit();
}

// Verify session security
if (
    $_SESSION['admin']['ip'] !== $_SERVER['REMOTE_ADDR'] ||
    $_SESSION['admin']['user_agent'] !== $_SERVER['HTTP_USER_AGENT']
) {
    session_unset();
    session_destroy();
    header('Location: admin_login.php');
    exit();
}

// Session timeout management
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 600)) {
    session_unset();
    session_destroy();
    header('Location: admin_login.php');
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

// Block common SQL injection attempts via GET parameters (case-insensitive)
$blacklist = ['select', 'insert', 'update', 'delete', 'drop','post', 'truncate', 'alter', '--', ';', '/*', '*/', '@@', '@', 'exec', '1=1'];

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

// Fetch approved tasks
$sql = "SELECT user_id, task_number, status, pdf_file_path FROM tasks WHERE status='approved'";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approved Tasks</title>
    <link rel="stylesheet" href="tasks.css">
    <script>
        function toggleForm(taskId) {
            document.getElementById('task_id').value = taskId;
            var formContainer = document.getElementById('form-container');
            if (formContainer.style.display === "none" || formContainer.style.display === "") {
                formContainer.style.display = "block";
            } else {
                formContainer.style.display = "none";
            }
        }
    </script>
</head>
<body>
    <h1>Approved Tasks</h1>
    <div class="tasks-box">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Task Number</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($row["user_id"]) . '</td>';
                        echo '<td>' . htmlspecialchars($row["task_number"]) . '</td>';
                        echo '<td class="status-' . strtolower(htmlspecialchars($row["status"])) . '">' . htmlspecialchars($row["status"]) . '</td>';
                        echo '<td><a href="' . $row["pdf_file_path"] . '" download>Download</a></td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="4">No approved tasks.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>

<?php
$conn->close();
?>
