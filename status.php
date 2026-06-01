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

require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

// Validate and sanitize GET inputs
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$name = isset($_GET['name']) ? trim($_GET['name']) : '';

if ($id <= 0 || empty($name)) {
    header("Location: login.php");
    exit();
}

// Prepare and bind the SQL statement securely
$stmt = $conn->prepare("SELECT * FROM records WHERE id = ? AND name = ?");
$stmt->bind_param("is", $id, $name);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $nameDisplay = htmlspecialchars($row['name']);
    
    if ($row['status'] === 'pending') {
        $message = "Hello, {$nameDisplay}!<br>Please buy a package by clicking the button below. If you've already bought a package, ignore this and check your email's spam/inbox folder.";
        $status_class = 'pending';
    } elseif ($row['status'] === 'rejected') {
        $message = "Hello, {$nameDisplay}! Your request has been rejected. This may be due to a wrong Txr-ID or transaction. If you believe this is an error, please contact our support team for assistance.";
        $status_class = 'rejected';
    } else {
        header("Location: login.php");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Display</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .status-box {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            max-width: 500px;
            width: 100%;
            text-align: center;
            position: relative;
        }
        .status-box h2 {
            margin-bottom: 20px;
            color: #333;
        }
        .status-box p {
            margin-bottom: 30px;
            color: #666;
            line-height: 1.6;
        }
        .status-box a {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007BFF;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }
        .status-box a:hover {
            background-color: #0056b3;
        }
        .pending {
            border-left: 5px solid #007BFF;
        }
        .rejected {
            border-left: 5px solid #dc3545;
        }
    </style>
</head>
<body>
    <div class="status-box <?php echo isset($status_class) ? htmlspecialchars($status_class) : ''; ?>">
        <h2>Status</h2>
        <p><?php echo isset($message) ? $message : 'No status message available.'; ?></p>
        <a href="https://pakearn.site/upgrade.php" target="_blank">Buy A Package</a>
    </div>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
