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
// Include the database connection
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

// Get the ID from the URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$remaining_time = 0;
$stat = 0;
$prev = 0;

if ($id > 0) {
    // Fetch the timer value from the timer_update table
    $timer_query = "SELECT timer_value FROM timer_table WHERE id = 1";
    $timer_stmt = $conn->prepare($timer_query);
    $timer_stmt->execute();
    $timer_stmt->bind_result($timer_minutes);
    $timer_stmt->fetch();
    $timer_stmt->close();

    // Convert minutes to seconds
    $timer_seconds = $timer_minutes * 60;
    
    // Fetch the record from the database
    $query = "SELECT stat, timer, prev FROM records WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($stat, $timer, $prev);
    $stmt->fetch();
    $stmt->close();

    // Check if stat is 1 and timer is 0
    if ($stat == 1 && $timer == 0) {
        // Set the timer to current time + configured seconds
        $new_timer = time() + $timer_seconds;

        // Update the timer in the database
        $update_query = "UPDATE records SET timer = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ii", $new_timer, $id);
        $update_stmt->execute();
        $update_stmt->close();

        $timer = $new_timer;
    }

    // Calculate the remaining time
    $remaining_time = max($timer - time(), 0);

    // If the remaining time is 0, update the stat and timer to 0
    if ($remaining_time == 0 && $stat == 1) {
        $update_stat_query = "UPDATE records SET stat = 0, timer = 0 WHERE id = ?";
        $update_stat_stmt = $conn->prepare($update_stat_query);
        $update_stat_stmt->bind_param("i", $id);
        $update_stat_stmt->execute();
        $update_stat_stmt->close();

        $stat = 0;
        $timer = 0;
    }

    $amount_query = "SELECT amount FROM records WHERE id='$id'";
    $result = mysqli_query($conn, $amount_query);
    $row = mysqli_fetch_assoc($result);
    $amount = $row['amount'];


    // Check reff_id in users table and count records with 'active' status
    $user_query = "SELECT id FROM users WHERE reff_id = ? AND status = 'active' ORDER BY id DESC LIMIT 1";
    $user_stmt = $conn->prepare($user_query);
    $user_stmt->bind_param("i", $id);
    $user_stmt->execute();
    $user_stmt->bind_result($last_user_id);
    $user_stmt->fetch();
    $user_stmt->close();

 

    // Check new count of active referrals
    $user_count_query = "SELECT COUNT(*) FROM users WHERE reff_id = ? AND status = 'active'";
    $user_count_stmt = $conn->prepare($user_count_query);
    $user_count_stmt->bind_param("i", $id);
    $user_count_stmt->execute();
    $user_count_stmt->bind_result($new_count);
    $user_count_stmt->fetch();
    $user_count_stmt->close();

    // Compare prev and new values, and compare the amounts
    if ($prev != $new_count && $last_amount >= $amount) {
        // Update stat to 1 if conditions are met
        $update_stat_query = "UPDATE records SET stat = 1, prev = ? WHERE id = ?";
        $update_stat_stmt = $conn->prepare($update_stat_query);
        $update_stat_stmt->bind_param("ii", $new_count, $id);
        $update_stat_stmt->execute();
        $update_stat_stmt->close();

        $stat = 1;
    }
} else {
    echo "Invalid ID.";
    exit;
}

if($stat == 0) {
    header("Location: referral.php?id=$id");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timer Page</title>
    <style>
        .timer-box {
            width: 300px;
            height: 150px;
            background-color: #f8f9fa;
            border: 2px solid #ced4da;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 50px auto;
        }
    </style>
</head>
<body>

<div class="timer-box" id="timer-box">
    <?php if ($stat == 1): ?>
        <span id="timer-display"><?php echo gmdate("d:H:i:s", $remaining_time); ?></span>
    <?php else: ?>
        <span>Timer Ended</span>
    <?php endif; ?>
</div>

<script>
    let remainingTime = <?php echo $remaining_time; ?>;
    const timerDisplay = document.getElementById('timer-display');

    function updateTimer() {
        if (remainingTime > 0) {
            let days = Math.floor(remainingTime / (60 * 60 * 24));
            let hours = Math.floor((remainingTime % (60 * 60 * 24)) / (60 * 60));
            let minutes = Math.floor((remainingTime % (60 * 60)) / 60);
            let seconds = remainingTime % 60;

            timerDisplay.textContent = `${String(days).padStart(2, '0')}:${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;

            remainingTime--;

            setTimeout(updateTimer, 1000);
        } else {
            timerDisplay.textContent = 'Timer Ended';
            // Redirect after timer ends
            window.location.href = `referral.php?id=<?php echo $id; ?>`;
        }
    }

    if (remainingTime > 0) {
        updateTimer();
    }
</script>

</body>
</html>
<?php
// Handle the AJAX request to update stat and timer to 0 when the timer ends
if (isset($_GET['update_stat']) && $_GET['update_stat'] == 1 && $id > 0) {
    // Also check if prev and new are different and update stat
    $update_stat_query = "UPDATE records SET stat = 1 WHERE id = ? AND prev != (SELECT COUNT(*) FROM users WHERE reff_id = ? AND status = 'active')";
    $update_stat_stmt = $conn->prepare($update_stat_query);
    $update_stat_stmt->bind_param("ii", $id, $id);
    $update_stat_stmt->execute();
    $update_stat_stmt->close();
}
?>
