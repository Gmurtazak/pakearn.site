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

require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php'; // Database connection

// Handle insert
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $link_title = $_POST['link_title'];
    $link_url = $_POST['link_url'];
    $stmt = $conn->prepare("INSERT INTO links (link_title, link_url) VALUES (?, ?)");
    $stmt->bind_param("ss", $link_title, $link_url);
    $stmt->execute();
    $stmt->close();
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM links WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $id = $_POST['id'];
    $link_title = $_POST['link_title'];
    $link_url = $_POST['link_url'];
    $stmt = $conn->prepare("UPDATE links SET link_title = ?, link_url = ? WHERE id = ?");
    $stmt->bind_param("ssi", $link_title, $link_url, $id);
    $stmt->execute();
    $stmt->close();
}

// Fetch records
$result = $conn->query("SELECT * FROM links ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Links Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f2f5f9;
            color: #333;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
        }
        h1 {
            color: #444;
            margin-bottom: 20px;
        }
        .table-container {
            width: 100%;
            max-width: 900px;
            background-color: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95em;
        }
        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        table th {
            background-color: #556080;
            color: #fff;
        }
        table td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
        }
        .btn {
            padding: 8px 16px;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 0 4px;
            transition: 0.3s;
        }
        .btn-edit {
            background-color: #0073e6;
            
        }
        .btn-delete {
            background-color: #ff4d4d;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .form-container {
            width: 100%;
            max-width: 500px;
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .form-container input[type="text"], .form-container input[type="hidden"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }
        .form-container input[type="submit"] {
            width: 100%;
            padding: 12px;
            background-color: #0073e6;
            color: white;
            font-size: 1em;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.3s;
        }
        .form-container input[type="submit"]:hover {
            background-color: #005bb5;
        }
        .responsive-table {
            overflow-x: auto;
        }
        @media (max-width: 600px) {
            .btn { 
                font-size: 0.9em; 
                padding: 6px 10px;
            }
            table td, table th {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <h1>Manage Links</h1>

    <div class="table-container responsive-table">
        <table>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Link URL</th>
                <th>Actions</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['link_title']); ?></td>
                    <td title="<?php echo $row['link_url']; ?>">
                        <?php echo strlen($row['link_url']) > 30 ? substr($row['link_url'], 0, 30) . '...' : $row['link_url']; ?>
                    </td>
                    <td>
                        <a href="links.php?edit=<?php echo $row['id']; ?>" class="btn btn-edit">Edit</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>

    <div class="form-container">
        <?php if (isset($_GET['edit'])):
            $id = intval($_GET['edit']);
            $edit_result = $conn->query("SELECT * FROM links WHERE id = $id");
            $edit_row = $edit_result->fetch_assoc();
        ?>
            <h2>Update Link</h2>
            <form action="links.php" method="post">
                <input type="hidden" name="id" value="<?php echo $edit_row['id']; ?>">
                <input type="text" name="link_title" value="<?php echo htmlspecialchars($edit_row['link_title']); ?>" placeholder="Link Title" required>
                <input type="text" name="link_url" value="<?php echo htmlspecialchars($edit_row['link_url']); ?>" placeholder="Link URL" required>
                <input type="submit" name="update" value="Update Link">
            </form>
        <?php else: ?>
            <h2>Add New Link</h2>
            <form action="links.php" method="post">
                <input type="text" name="link_title" placeholder="Link Title" required>
                <input type="text" name="link_url" placeholder="Link URL" required>
                <input type="submit" name="add" value="Add Link">
            </form>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
$conn->close();
?>
