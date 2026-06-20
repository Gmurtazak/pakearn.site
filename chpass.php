<?php
include 'session_check.php';
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
        // Attempt to unblock/block password logic if exists
        include 'private/unblock_pass.php'; 
        
        // Update in records table
        $updateRecords = $conn->prepare("UPDATE records SET password = ? WHERE id = ?");
        $updateRecords->bind_param("si", $newPassword, $userId);
        $updateRecords->execute();
        $affected1 = $updateRecords->affected_rows;
        $updateRecords->close();

        // Update in users table
        $updateUsers = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $updateUsers->bind_param("si", $newPassword, $userId);
        $updateUsers->execute();
        $affected2 = $updateUsers->affected_rows;
        $updateUsers->close();

        if ($affected1 > 0 || $affected2 > 0) {
            $message = "Password updated successfully in at least one table.";
            $success = true;
        } else {
            $err1 = $conn->error;
            $message = "Error: No rows updated. User ID: $userId. DB Error if any: $err1";
        }
        
        // include $_SERVER['DOCUMENT_ROOT'] . '/../private/block_pass.php';
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
      color: white;
      text-decoration: none;
      text-align: center;
      padding: 12px;
      border-radius: 6px;
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
    <form method="POST" action="chpass.php?id=<?php echo $userId; ?>">
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
      <a href="user_page.php?id=<?php echo $userId; ?>" class="close-btn">Go Back</a>
    <?php endif; ?>
  </div>
</body>
</html>
