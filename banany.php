<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';
date_default_timezone_set('Asia/Karachi');

$alreadyBanned = false;
$success = false;
$error = false;
$action = '';
$currentBanStatus = null;
$userExists = false;

// Check ban status (for AJAX requests)
if (isset($_POST['ajax_check'])) {
    header('Content-Type: application/json');
    $input = trim($_POST['identifier'] ?? '');
    
    if (!empty($input)) {
        if (is_numeric($input)) {
            $stmt = $conn->prepare("SELECT ban FROM users WHERE id = ?");
            $stmt->bind_param("i", $input);
        } else {
            $stmt = $conn->prepare("SELECT ban FROM users WHERE email = ?");
            $stmt->bind_param("s", $input);
        }

        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($ban);
            $stmt->fetch();
            echo json_encode(['status' => $ban]);
        } else {
            echo json_encode(['error' => 'User not found']);
        }
        $stmt->close();
    } else {
        echo json_encode(['error' => 'No input provided']);
    }
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['ajax_check'])) {
    $input = trim($_POST['identifier']);
    $actionType = $_POST['action_type'] ?? '';

    if (!empty($input)) {
        if (is_numeric($input)) {
            $stmt = $conn->prepare("SELECT ban FROM users WHERE id = ?");
            $stmt->bind_param("i", $input);
        } else {
            $stmt = $conn->prepare("SELECT ban FROM users WHERE email = ?");
            $stmt->bind_param("s", $input);
        }

        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $userExists = true;
            $stmt->bind_result($ban);
            $stmt->fetch();
            $currentBanStatus = $ban;

            if ($actionType === 'ban') {
                if ($ban == 0) {
                    $now = date('Y-m-d H:i:s');
                    if (is_numeric($input)) {
                        $update = $conn->prepare("UPDATE users SET ban = 1, dban = ? WHERE id = ?");
                        $update->bind_param("si", $now, $input);
                    } else {
                        $update = $conn->prepare("UPDATE users SET ban = 1, dban = ? WHERE email = ?");
                        $update->bind_param("ss", $now, $input);
                    }
                    $update->execute();
                    $update->close();
                    $success = true;
                    $action = 'banned';
                    $currentBanStatus = 1;
                } else {
                    $alreadyBanned = true;
                }
            } elseif ($actionType === 'unban') {
                if ($ban != 0) {
                    if (is_numeric($input)) {
                        $update = $conn->prepare("UPDATE users SET ban = 0, dban = NULL WHERE id = ?");
                        $update->bind_param("i", $input);
                    } else {
                        $update = $conn->prepare("UPDATE users SET ban = 0, dban = NULL WHERE email = ?");
                        $update->bind_param("s", $input);
                    }
                    $update->execute();
                    $update->close();
                    $success = true;
                    $action = 'unbanned';
                    $currentBanStatus = 0;
                } else {
                    $error = true;
                    $action = 'not_banned';
                }
            } elseif ($actionType === 'permaban') {
                if ($ban != 3) {
                    if (is_numeric($input)) {
                        $update = $conn->prepare("UPDATE users SET ban = 3, dban = NULL WHERE id = ?");
                        $update->bind_param("i", $input);
                    } else {
                        $update = $conn->prepare("UPDATE users SET ban = 3, dban = NULL WHERE email = ?");
                        $update->bind_param("s", $input);
                    }
                    $update->execute();
                    $update->close();
                    $success = true;
                    $action = 'permanently_banned';
                    $currentBanStatus = 3;
                } else {
                    $alreadyBanned = true;
                    $action = 'already_perma_banned';
                }
            }
        } else {
            $error = true;
        }
        $stmt->close();
    } else {
        $error = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Ban User</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      background: linear-gradient(to right, #ffe3e3, #ffc9de);
      font-family: 'Segoe UI', sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }
    .container {
      background: #fff;
      padding: 30px;
      border-radius: 15px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
      width: 90%;
      max-width: 400px;
      text-align: center;
    }
    h2 {
      color: #c92a2a;
      margin-bottom: 20px;
    }
    input {
      width: 90%;
      padding: 12px;
      margin-bottom: 15px;
      border: 2px solid #f1a1a8;
      border-radius: 8px;
      font-size: 16px;
    }
    button {
      padding: 10px 20px;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      cursor: pointer;
      margin: 5px;
      transition: all 0.3s ease;
    }
    .submit-btn {
      background-color: #fa5252;
      color: white;
    }
    .submit-btn:disabled {
      background-color: #ffc9c9;
      cursor: not-allowed;
    }
    .unban-btn {
      background-color: #40c057;
      color: white;
    }
    .unban-btn:disabled {
      background-color: #d3f9d8;
      cursor: not-allowed;
    }
    .permaban-btn {
      background-color: #212529;
      color: white;
    }
    .permaban-btn:disabled {
      background-color: #868e96;
      cursor: not-allowed;
    }
    .back-btn {
      background-color: #4dabf7;
      color: white;
    }
    .back-btn:hover {
      background-color: #339af0;
    }
    .message {
      margin-top: 15px;
      color: green;
    }
    .error {
      color: red;
    }
    i {
      font-size: 48px;
      color: #fa5252;
      margin-bottom: 10px;
    }
    .btn-container {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
    }
    .status-info {
      margin: 10px 0;
      padding: 8px;
      border-radius: 5px;
      background-color: #f8f9fa;
      font-size: 14px;
    }
  </style>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <div class="container">
    <i class="fas fa-user-lock"></i>
    <h2>User Ban Management</h2>
    <form method="POST" id="banForm">
      <input type="text" name="identifier" placeholder="Enter User ID or Email" oninput="checkBanStatus()">
      <div class="status-info" id="status-display">Status: Enter user ID/email</div>
      <input type="hidden" name="action_type" id="action_type" value="">
      <div class="btn-container">
        <button type="submit" class="submit-btn" onclick="document.getElementById('action_type').value='ban'" 
                <?php echo ($currentBanStatus === 1 || $currentBanStatus === 3) ? 'disabled' : ''; ?>>Temporary Ban</button>
        <button type="submit" class="unban-btn" onclick="document.getElementById('action_type').value='unban'"
                <?php echo ($currentBanStatus === 0 || !$userExists) ? 'disabled' : ''; ?>>Unban</button>
        <button type="submit" class="permaban-btn" onclick="document.getElementById('action_type').value='permaban'"
                <?php echo ($currentBanStatus === 3 || !$userExists) ? 'disabled' : ''; ?>>Permanent Ban</button>
        <button type="button" class="back-btn" onclick="window.location.href='panal.php'">Go Back</button>
      </div>
    </form>

    <?php if ($success): ?>
      <div class="message">
        <?php if ($action === 'banned'): ?>
          ✅ User has been temporarily banned.
        <?php elseif ($action === 'unbanned'): ?>
          ✅ User has been unbanned.
        <?php elseif ($action === 'permanently_banned'): ?>
          ✅ User has been permanently banned.
        <?php endif; ?>
      </div>
    <?php elseif ($alreadyBanned): ?>
      <div class="error">
        <?php if ($action === 'already_perma_banned'): ?>
          ⚠️ User is already permanently banned.
        <?php else: ?>
          ⚠️ User is already banned.
        <?php endif; ?>
      </div>
    <?php elseif ($error): ?>
      <div class="error">
        <?php if ($action === 'not_banned'): ?>
          ⚠️ User is not currently banned.
        <?php else: ?>
          ❌ Invalid input or user not found.
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <script>
    function checkBanStatus() {
      const input = document.querySelector('input[name="identifier"]').value.trim();
      if (input.length === 0) {
        resetButtonStates();
        return;
      }

      const formData = new FormData();
      formData.append('identifier', input);
      formData.append('ajax_check', 'true');

      fetch(window.location.href, {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.error) {
          document.getElementById('status-display').textContent = "Status: " + data.error;
          document.getElementById('status-display').style.color = "red";
          resetButtonStates();
        } else {
          updateButtonStates(data.status);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        resetButtonStates();
      });
    }

    function updateButtonStates(banStatus) {
      const tempBanBtn = document.querySelector('button[onclick*="ban"]');
      const unbanBtn = document.querySelector('button[onclick*="unban"]');
      const permabanBtn = document.querySelector('button[onclick*="permaban"]');

      // Enable all buttons first
      tempBanBtn.disabled = false;
      unbanBtn.disabled = false;
      permabanBtn.disabled = false;

      // Disable appropriate buttons based on status
      if (banStatus == 1 || banStatus == 3) {
        tempBanBtn.disabled = true;
        permabanBtn.disabled = true;
      } else if (banStatus == 0) {
        unbanBtn.disabled = true;
      }

      // Update status display
      const statusDisplay = document.getElementById('status-display');
      if (banStatus == 0) {
        statusDisplay.textContent = "Status: User is not banned";
        statusDisplay.style.color = "#40c057";
      } else if (banStatus == 1) {
        statusDisplay.textContent = "Status: User is temporarily banned";
        statusDisplay.style.color = "#fa5252";
      } else if (banStatus == 3) {
        statusDisplay.textContent = "Status: User is permanently banned";
        statusDisplay.style.color = "#212529";
      } else {
        statusDisplay.textContent = "Status: Unknown";
        statusDisplay.style.color = "#868e96";
      }
    }

    function resetButtonStates() {
      const tempBanBtn = document.querySelector('button[onclick*="ban"]');
      const unbanBtn = document.querySelector('button[onclick*="unban"]');
      const permabanBtn = document.querySelector('button[onclick*="permaban"]');
      
      tempBanBtn.disabled = false;
      unbanBtn.disabled = false;
      permabanBtn.disabled = false;

      document.getElementById('status-display').textContent = "Status: Enter user ID/email";
      document.getElementById('status-display').style.color = "#868e96";
    }

    // Initialize button states on page load
    document.addEventListener('DOMContentLoaded', function() {
      <?php if ($currentBanStatus !== null): ?>
        updateButtonStates(<?php echo $currentBanStatus; ?>);
      <?php endif; ?>
    });
  </script>
</body>
</html>