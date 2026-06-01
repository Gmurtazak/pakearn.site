<?php include $_SERVER['DOCUMENT_ROOT'] . '/../private/security.php'; ?>
<?php
// Block common SQL injection attempts via GET parameters (case-insensitive)
$blacklist = ['select', 'insert', 'update', 'delete', 'drop', 'truncate', 'alter', '--', ';', '/*', '*/', '@@', '@', 'exec', '1=1'];
foreach ($_GET as $param => $value) {
    if (is_array($value)) continue;
    $value = strtolower($value);
    foreach ($blacklist as $word) {
        if (strpos($value, $word) !== false) {
            http_response_code(403);
            exit("Blocked: Malicious keyword '$word' detected in URL.");
        }
    }
}

include 'session_check.php';
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

$message = "";
$success = false;

// Get user ID from URL
$userId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($newPassword !== $confirmPassword) {
        $message = "New passwords do not match.";
    }
    else {
        include $_SERVER['DOCUMENT_ROOT'] . '/../private/unblock_pass.php';
        // Update in records table
        $updateRecords = $conn->prepare("UPDATE records SET password = ? WHERE id = ?");
        $updateRecords->bind_param("si", $newPassword, $userId);
        $updateRecords->execute();
        $updateRecords->close();

        // Update in users table
        $updateUsers = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $updateUsers->bind_param("si", $newPassword, $userId);
        $updateUsers->execute();
        $updateUsers->close();

        $message = "Password updated in both tables successfully.";
        $success = true;
        include $_SERVER['DOCUMENT_ROOT'] . '/../private/block_pass.php';

    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Update Password</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f5f6fa;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }

    .box {
      background: white;
      padding: 30px;
      border-radius: 10px;
      width: 100%;
      max-width: 500px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .box h2 {
      text-align: center;
      color: #333;
    }

    form {
      display: flex;
      flex-direction: column;
      gap: 15px;
      margin-top: 20px;
    }

    label {
      font-weight: bold;
    }

    input[type="password"] {
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 6px;
    }

    .btn {
      background-color: #007bff;
      border: none;
      color: white;
      padding: 12px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 16px;
    }

    .btn:hover {
      background-color: #0056b3;
    }

    .message {
      margin-top: 20px;
      text-align: center;
      font-weight: bold;
      color: <?php echo $success ? '#28a745' : '#dc3545'; ?>;
    }

    .close-btn {
      margin-top: 15px;
      display: block;
      width: 100%;
      background-color: #6c757d;
    }

    .close-btn:hover {
      background-color: #495057;
    }

    @media (max-width: 600px) {
      .box {
        padding: 20px;
      }

      .btn, .close-btn {
        padding: 10px;
        font-size: 15px;
      }
    }
  </style>
</head>
<body>
  <div class="box">
    <h2>Update Password</h2>
    <form method="POST">
      <label for="new_password">New Password:</label>
      <input type="password" id="new_password" name="new_password" required />

      <label for="confirm_password">Confirm Password:</label>
      <input type="password" id="confirm_password" name="confirm_password" required />

      <button type="submit" class="btn">Update Password</button>
    </form>

    <?php if ($message): ?>
      <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
      <button class="btn close-btn" onclick="window.history.back();">Close</button>
    <?php endif; ?>
  </div>
</body>
</html>
