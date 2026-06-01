<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}
// Include the connection file
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

// Initialize variables for display
$message = '';
$days = $hours = $minutes = $seconds = 0;

// Fetch the current timer value from the database (in minutes)
$query = "SELECT timer_value FROM timer_table WHERE id = 1";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$current_timer = $row['timer_value']; // Timer in minutes

// If form is submitted, update the timer
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_timer = intval($_POST['timer_value']); // Timer in minutes
    $update_query = "UPDATE timer_table SET timer_value = $new_timer WHERE id = 1";
    if (mysqli_query($conn, $update_query)) {
        // Convert the timer into days, hours, minutes, and seconds
        $total_seconds = $new_timer * 60;
        $days = floor($total_seconds / (24 * 60 * 60));
        $hours = floor(($total_seconds % (24 * 60 * 60)) / (60 * 60));
        $minutes = floor(($total_seconds % (60 * 60)) / 60);
        $seconds = $total_seconds % 60;

        // Prepare success message
        $message = "You have set the timer for <strong>$days days, $hours hours, $minutes minutes, and $seconds seconds</strong>.";
    } else {
        $message = "Error updating timer: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Timer</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f0f4f8;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
            text-align: center;
        }
        h1 {
            font-size: 24px;
            color: #4caf50;
            margin-bottom: 20px;
        }
        .message {
            margin-top: 20px;
            font-size: 18px;
            color: #333;
            background-color: #e9f7ef;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #d4edda;
            display: <?php echo $message ? 'block' : 'none'; ?>;
        }
        input[type="number"] {
            width: 100%;
            padding: 10px;
            margin: 15px 0;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        button {
            background-color: #4caf50;
            color: white;
            border: none;
            padding: 12px 20px;
            font-size: 16px;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
        }
        button:hover {
            background-color: #45a049;
        }
        @media (max-width: 480px) {
            .container {
                padding: 15px;
            }
            h1 {
                font-size: 20px;
            }
            button {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Update Timer</h1>
        <form method="post" action="">
            <label for="timer_value">Set Timer Value (in minutes):</label><br>
            <input type="number" name="timer_value" id="timer_value" value="<?php echo $current_timer; ?>" required><br>
            <button type="submit">Update Timer</button>
        </form>

        <!-- Display the message if the timer is updated -->
        <div class="message">
            <?php echo $message; ?>
        </div>
    </div>
</body>
</html>
