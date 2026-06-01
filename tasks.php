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

// Update status if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["task_id"]) && isset($_POST["status"])) {
    $task_id = intval($_POST["task_id"]);
    $status = $conn->real_escape_string($_POST["status"]);
    
    // Update the task status
    $update_sql = "UPDATE tasks SET status='$status' WHERE id='$task_id' AND status='pending'";
    if ($conn->query($update_sql) === TRUE) {
        // Retrieve the user_id from the updated task
        $task_sql = "SELECT user_id FROM tasks WHERE id='$task_id'";
        $task_result = $conn->query($task_sql);
        
        if ($task_result->num_rows > 0) {
            $task_row = $task_result->fetch_assoc();
            $user_id = intval($task_row['user_id']);
            
            // Fetch the level and price from level and plan tables
            $level_sql = "SELECT level FROM level WHERE us_id='$user_id'";
            $level_result = $conn->query($level_sql);
            
            $plan_sql = "SELECT price FROM plan WHERE user_id='$user_id'";
            $plan_result = $conn->query($plan_sql);
            
            if ($level_result->num_rows > 0 && $plan_result->num_rows > 0) {
                $level_row = $level_result->fetch_assoc();
                $plan_row = $plan_result->fetch_assoc();
                
                $level = intval($level_row['level']);
                $price = floatval($plan_row['price']);
                
                // Initialize rewards and earnings update queries
                $update_level_sql = "";
                $update_earnings_sql = "";
                
                // Update the level table if conditions are met
                if ($level == 1 || $price == 500) {
                    $update_level_sql = "UPDATE level SET reward = reward + 20 WHERE us_id='$user_id'";
                    $update_earnings_sql = "UPDATE earnings SET ads_earning = ads_earning + 100,total_earning = total_earning + 100 WHERE id='$user_id'";
                }
                else if ($level == 2 || $price == 1000) {
                    $update_level_sql = "UPDATE level SET reward = reward + 40 WHERE us_id='$user_id'";
                    $update_earnings_sql = "UPDATE earnings SET ads_earning = ads_earning + 120,total_earning = total_earning + 100 WHERE id='$user_id'";
                }
                else if ($level == 3 || $price == 2000) {
                    $update_level_sql = "UPDATE level SET reward = reward + 60 WHERE us_id='$user_id'";
                    $update_earnings_sql = "UPDATE earnings SET ads_earning = ads_earning + 140,total_earning = total_earning + 100 WHERE id='$user_id'";
                }
                else if ($level == 4 || $price == 3500) {
                    $update_level_sql = "UPDATE level SET reward = reward + 80 WHERE us_id='$user_id'";
                    $update_earnings_sql = "UPDATE earnings SET ads_earning = ads_earning + 160,total_earning = total_earning + 100 WHERE id='$user_id'";
                }
                else if ($level == 5 || $price == 500) {
                    $update_level_sql = "UPDATE level SET reward = reward + 100 WHERE us_id='$user_id'";
                    $update_earnings_sql = "UPDATE earnings SET ads_earning = ads_earning + 180,total_earning = total_earning + 100 WHERE id='$user_id'";
                }
                else if ($level == 6 || $price == 1000) {
                    $update_level_sql = "UPDATE level SET reward = reward + 120 WHERE us_id='$user_id'";
                    $update_earnings_sql = "UPDATE earnings SET ads_earning = ads_earning + 200,total_earning = total_earning + 100 WHERE id='$user_id'";
                }
                else if ($level == 7 || $price ==2000) {
                    $update_level_sql = "UPDATE level SET reward = reward + 140 WHERE us_id='$user_id'";
                    $update_earnings_sql = "UPDATE earnings SET ads_earning = ads_earning + 220,total_earning = total_earning + 100 WHERE id='$user_id'";
                }
                else if ($level == 8 || $price == 3500) {
                    $update_level_sql = "UPDATE level SET reward = reward + 160 WHERE us_id='$user_id'";
                    $update_earnings_sql = "UPDATE earnings SET ads_earning = ads_earning + 240,total_earning = total_earning + 100 WHERE id='$user_id'";
                }
                else if ($level == 9 || $price == 500) {
                    $update_level_sql = "UPDATE level SET reward = reward + 180 WHERE us_id='$user_id'";
                    $update_earnings_sql = "UPDATE earnings SET ads_earning = ads_earning + 260,total_earning = total_earning + 100 WHERE id='$user_id'";
                }
                else if ($level == 10 || $price == 1000) {
                    $update_level_sql = "UPDATE level SET reward = reward + 200 WHERE us_id='$user_id'";
                    $update_earnings_sql = "UPDATE earnings SET ads_earning = ads_earning + 280,total_earning = total_earning + 100 WHERE id='$user_id'";
                }
                else {
                    $update_level_sql = "UPDATE level SET reward = reward + 20 WHERE us_id='$user_id'";
                    $update_earnings_sql = "UPDATE earnings SET ads_earning = ads_earning + 100,total_earning = total_earning + 100 WHERE id='$user_id'";
                }
                
                
                // Update the earnings table if conditions are met
                
                if ($update_level_sql) {
                    if ($conn->query($update_level_sql) === FALSE) {
                        echo "Error updating reward: " . $conn->error;
                    }
                }
                
                if ($conn->query($update_earnings_sql) === FALSE) {
                    echo "Error updating earnings: " . $conn->error;
                }
            } else {
                echo "Error fetching level or plan data.";
            }
        } else {
            echo "Error fetching task details.";
        }
        
        echo "Status updated successfully";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "Error updating record: " . $conn->error;
    }
}

// Fetch pending tasks
$sql = "SELECT id, task_number, status, pdf_file_path FROM tasks WHERE status='pending'";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Tasks</title>
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
    <h1>Pending Tasks</h1>
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
                        echo '<td>' . htmlspecialchars($row["id"]) . '</td>';
                        echo '<td>' . htmlspecialchars($row["task_number"]) . '</td>';
                        echo '<td class="status-' . strtolower(htmlspecialchars($row["status"])) . '">' . htmlspecialchars($row["status"]) . '</td>';
                        echo '<td><a href="' . $row["pdf_file_path"] . '" download>Download</a> | <button onclick="toggleForm(' . $row["id"] . ')">Change Status</button></td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="4">No pending tasks.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>

    <div id="form-container" class="form-container" style="display:none;">
        <form method="POST" action="">
            <input type="hidden" name="task_id" id="task_id">
            <label>
                <input type="radio" name="status" value="approved" required> Approved
            </label>
            <label>
                <input type="radio" name="status" value="rejected" required> Rejected
            </label>
            <input type="submit" value="Submit">
        </form>
    </div>
</body>
</html>

<?php
$conn->close();
?>
