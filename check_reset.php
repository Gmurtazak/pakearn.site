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
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php'; // Assumes connection.php connects to your database
include 'session_check.php';
// Set the timezone
date_default_timezone_set('Asia/Karachi');

// Get user ID from the URL, defaulting to 1 if not provided
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 1;

// Get today's date dynamically
$current_date = date('Y-m-d');
echo "Current Date: $current_date<br>";

// Fetch the last reset date for the specified user
$query = "SELECT last_reset_date FROM users WHERE id = $user_id";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $last_reset_date = $row['last_reset_date'];
    echo "Last Reset Date: $last_reset_date<br>";

    // Check if today is past midnight and the last reset date is from a previous day
    if ($last_reset_date !== $current_date) {
        // Update the reset date for the specified user
        $update_query = "UPDATE users SET last_reset_date = '$current_date' WHERE id = $user_id";
        if (mysqli_query($conn, $update_query)) {
            echo "Reset Date Updated Successfully.<br>";
        } else {
            echo "Error updating reset date: " . mysqli_error($conn);
        }

        // Now update the box values in the records table
        $update_boxes_query = "UPDATE records SET box1 = 1, box2 = 1, box3 = 1, box4 = 1, box5 = 1 WHERE id = $user_id";
        if (mysqli_query($conn, $update_boxes_query)) {
            echo "Boxes Updated Successfully.<br>";
        } else {
            echo "Error updating boxes: " . mysqli_error($conn);
        }

        // Return success response to indicate a reset is needed
        echo json_encode(['success' => true]);
    } else {
        // Return response to indicate no reset needed
        echo json_encode(['success' => false]);
    }
} else {
    // Handle case if user is not found
    echo json_encode(['success' => false, 'error' => 'User not found']);
}
?>
