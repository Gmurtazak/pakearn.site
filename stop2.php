<?php include $_SERVER['DOCUMENT_ROOT'] . '/../private/security.php'; ?>
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';
date_default_timezone_set('Asia/Karachi');

$remaining_time = 0;
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';

    if (!empty($email)) {
        $query = "SELECT ads_date FROM records WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($ads_date);
        $stmt->fetch();
        $stmt->close();

        if (!empty($ads_date)) {
            $ads_timestamp = strtotime($ads_date);
            $now = time();
            $remaining_time = abs($now - $ads_timestamp);
        } else {
            $error_message = "No record found with valid ads_date.";
        }
    } else {
        $error_message = "Please enter a valid email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Input Page</title>
    <style>
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f7f6;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            background-color: #ffffff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            text-align: center;
        }
        
        .container h2 {
            margin-bottom: 20px;
            color: #343a40;
            font-size: 1.5rem;
        }
        
        .input-group {
            margin-bottom: 20px;
        }
        
        .input-group input {
            width: 100%;
            padding: 12px 15px;
            border-radius: 5px;
            border: 1px solid #ced4da;
            font-size: 16px;
        }
        
        .btn {
            background-color: #007bff;
            color: white;
            padding: 12px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #0056b3;
        }
        
        .timer-box {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 10px;
            border: 2px solid #ced4da;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            min-height: 80px;
        }
        
        .error-message {
            color: #dc3545;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f8d7da;
            border-radius: 5px;
            border: 1px solid #f5c6cb;
        }
        
        /* Responsive adjustments */
        @media (max-width: 480px) {
            .container {
                padding: 20px 15px;
            }
            
            .container h2 {
                font-size: 1.3rem;
                margin-bottom: 15px;
            }
            
            .input-group input {
                padding: 10px 12px;
                font-size: 15px;
            }
            
            .btn {
                padding: 10px 12px;
            }
            
            .timer-box {
                font-size: 1.2rem;
                padding: 12px;
            }
        }
        
        @media (max-width: 360px) {
            .container h2 {
                font-size: 1.2rem;
            }
            
            .timer-box {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Enter Your Email</h2>

    <?php if (!empty($error_message)): ?>
        <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <form method="post" action="">
        <div class="input-group">
            <input type="email" name="email" placeholder="Enter your email" required>
        </div>
        <button type="submit" class="btn">Submit</button>
    </form>

    <?php if ($remaining_time > 0): ?>
        <div class="timer-box">
            <span id="timer-display"><?php echo gmdate("d:H:i:s", $remaining_time); ?></span>
        </div>
    <?php elseif ($remaining_time === 0 && $_SERVER['REQUEST_METHOD'] === 'POST' && empty($error_message)): ?>
        <div class="timer-box">
            <span>Timer Ended</span>
        </div>
    <?php endif; ?>
</div>

<script>
    let remainingTime = <?php echo $remaining_time; ?>;
    const timerDisplay = document.getElementById('timer-display');

    function startTimer() {
        if (remainingTime > 0) {
            let days = Math.floor(remainingTime / (60 * 60 * 24));
            let hours = Math.floor((remainingTime % (60 * 60 * 24)) / (60 * 60));
            let minutes = Math.floor((remainingTime % (60 * 60)) / 60);
            let seconds = remainingTime % 60;

            timerDisplay.textContent = `${String(days).padStart(2, '0')}:${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;

            remainingTime--;
            setTimeout(startTimer, 1000);
        } else if (timerDisplay) {
            timerDisplay.textContent = 'Timer Ended';
        }
    }

    if (remainingTime > 0 && timerDisplay) {
        startTimer();
    }
</script>

</body>
</html>