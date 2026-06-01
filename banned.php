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
date_default_timezone_set('Asia/Karachi');

if (!isset($_GET['id'])) {
    header("Location: login.php");
    exit();
}

$id = $_GET['id'];
$sql = "SELECT ban, dban FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($ban, $dban);
$stmt->fetch();
$stmt->close();

// If ban is 0 and dban is NULL, redirect to login
if ($ban == 0 && is_null($dban)) {
    header("Location: login.php");
    exit();
}

// Check for permanent ban (ban = 3)
if ($ban == 3) {
    $permanentBan = true;
} else {
    $permanentBan = false;
    // Calculate the exact 24-hour expiration time from dban
    $banExpireTime = strtotime($dban . ' +1 day');
    $currentTime = time();

    // If current time passed the ban expire time, update the user
    if ($currentTime >= $banExpireTime) {
        $update = $conn->prepare("UPDATE users SET ban = 0, dban = NULL WHERE id = ?");
        $update->bind_param("i", $id);
        $update->execute();
        $update->close();
        header("Location: login.php");
        exit();
    }

    // Time remaining in seconds
    $remainingSeconds = $banExpireTime - $currentTime;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Account Banned</title>
  <style>
    body {
      background: linear-gradient(to right, #fa709a, #fee140);
      font-family: 'Segoe UI', sans-serif;
      margin: 0;
      padding: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }
    .box {
      background: #fff;
      padding: 30px;
      border-radius: 20px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
      text-align: center;
      max-width: 400px;
    }
    .box i {
      font-size: 60px;
      color: #e63946;
      margin-bottom: 15px;
    }
    .box h2 {
      color: #333;
      margin-bottom: 10px;
    }
    .box p {
      color: #555;
      margin-bottom: 15px;
    }
    .timer {
      font-size: 30px;
      font-weight: bold;
      color: #e63946;
      margin-bottom: 10px;
    }
    .permanent {
      color: #b30000;
      font-weight: bold;
    }
  </style>
  <?php if (!$permanentBan): ?>
  <script>
    let seconds = <?= $remainingSeconds ?>;
    function updateTimer() {
      const timer = document.getElementById("timer");
      if (seconds <= 0) {
        location.reload();
        return;
      }
      const hrs = Math.floor(seconds / 3600);
      const mins = Math.floor((seconds % 3600) / 60);
      const secs = seconds % 60;
      timer.textContent = `${hrs.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
      seconds--;
    }
    setInterval(updateTimer, 1000);
    window.onload = updateTimer;
  </script>
  <?php endif; ?>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <div class="box">
    <i class="fas fa-ban"></i>
    <?php if ($permanentBan): ?>
      <h2>Your account has been permanently banned</h2>
      <p class="permanent">You have been permanently banned by the system because you've committed too many violations.</p>
    <?php else: ?>
      <h2>Your account has been temporarily banned</h2>
      <p><strong>Reason:</strong> Our system detected that you are illegally earning money.<br>This is a 24-hour temporary ban.</p>
      <div class="timer" id="timer">Loading...</div>
      <p style="color:#b30000;">If this happens again, your account may be permanently banned.</p>
    <?php endif; ?>
  </div>
</body>
</html>