<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

error_reporting(E_ALL); // Report all types of errors
ini_set('display_errors', 1); // Display errors to the browser
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

date_default_timezone_set('Asia/Karachi');

// Update status if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["user_id"]) && isset($_POST["price"]) && isset($_POST["status"])) {
    $user_id = $_POST["user_id"];
    $price = $_POST["price"];
    $status = $_POST["status"];

    // Sanitize inputs to prevent SQL injection
    $user_id = mysqli_real_escape_string($conn, $user_id);
    $price = mysqli_real_escape_string($conn, $price);
    $status = mysqli_real_escape_string($conn, $status);
    include '../private/ap_open.php';

    // Update status in the records table with Pakistani time zone
    $update_sql = "UPDATE records SET status='$status', time=CONVERT_TZ(NOW(), @@session.time_zone, '+05:00') 
                   WHERE unique_id='$user_id' AND status='pending'";

    if ($conn->query($update_sql) === TRUE) {
        include '../private/ap_block.php';
        include '../private/unblock_updates.php';

        if ($status == 'active') {
            // Fetch the primary key ID from records
            $fetch_id_sql = "SELECT id FROM records WHERE unique_id='$user_id'";
            $id_result = $conn->query($fetch_id_sql);
            
            if ($id_result->num_rows > 0) {
                $row = $id_result->fetch_assoc();
                $primary_key = $row['id'];
                // Step 1: Get reff_id from the given id
                $reffQuery = "SELECT reff_id FROM users WHERE id = '$primary_key'";
                $reffResult = mysqli_query($conn, $reffQuery);
                
                if ($reffResult && mysqli_num_rows($reffResult) > 0) {
                    $reffRow = mysqli_fetch_assoc($reffResult);
                    $reff_id = $reffRow['reff_id'];
                
                   // Step 2: Update stat = 1 where id = reff_id
                    $updateQuerytyy = "UPDATE records 
                                       SET stat = 1, ads_date = NULL, ads_date_start = NULL 
                                       WHERE id = ?";
                    
                    $updateStmt = $conn->prepare($updateQuerytyy);
                    
                    $updateStmt->bind_param("i", $reff_id);
                    
                    $updateStmt->execute();
                    
                }
                date_default_timezone_set('Asia/Karachi'); // Set timezone to Pakistan
                $current_datetime = date('Y-m-d H:i:s');
                
                // Corrected variable name (was $update_queryyy123 but used $update_queryyy)
                $update_query = "UPDATE records SET join_hoi = '$current_datetime' WHERE id = '$primary_key'";
                mysqli_query($conn, $update_query);

                // Check for duplicate entry in plan table
                $check_duplicate_sql = "SELECT * FROM plan WHERE user_id='$primary_key'";
                $duplicate_result = $conn->query($check_duplicate_sql);
                
                if ($duplicate_result->num_rows > 0) {
                    // Update the existing plan record
                    $update_plan_sql = "UPDATE plan SET price='$price' WHERE user_id='$primary_key'";
                    $conn->query($update_plan_sql);
                    $update_records_sql = "UPDATE records SET amount='$price' WHERE id='$primary_key'";
                    $conn->query($update_records_sql);
                } else {
                    // Insert new plan record
                    $insert_plan_sql = "INSERT INTO plan (user_id, price) VALUES ('$primary_key', '$price')";
                    $conn->query($insert_plan_sql);
                    $update_records_sql = "UPDATE records SET amount='$price' WHERE id='$primary_key'";
                    $conn->query($update_records_sql);
                }
                
                $update_newapprove = "UPDATE records SET newapprove=0 WHERE id='$primary_key'";
                $conn->query($update_newapprove);
                
                // Fetch `reff_id` from users table
                $fetch_reff_id_sql = "SELECT reff_id FROM users WHERE id='$primary_key'";
                $reff_id_result = $conn->query($fetch_reff_id_sql);
                
                if ($reff_id_result->num_rows > 0) {
                    $reff_row = $reff_id_result->fetch_assoc();
                    $reff_id = $reff_row['reff_id'];
                    // Update timer for the referred user
                    $update_timer_sql = "UPDATE records SET timer=0 WHERE id='$reff_id'";
                    $conn->query($update_timer_sql);
                }
            }
        } 
        elseif ($status == 'rejected') {
            // Fetch the primary key ID from records
            $fetch_id_sql = "SELECT id FROM records WHERE unique_id='$user_id'";
            $id_result = $conn->query($fetch_id_sql);
            
            if ($id_result->num_rows > 0) {
                $row = $id_result->fetch_assoc();
                $primary_key = $row['id'];
                // Update status in users table
                $update_user_sql = "UPDATE users SET status='rejected' WHERE id='$primary_key'";
                $conn->query($update_user_sql);
            }
        }
        include '../private/block_updates.php';

    } else {
        // Handle query error
        echo "Error updating record: " . $conn->error;
    }
}

// Fetch pending records where amount is not 0
$sql = "SELECT unique_id, name, sphone, amount, status FROM records WHERE status='pending' AND amount != 0";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Pending</title>
    <link rel="stylesheet" href="pemem.css?v=<?php echo time(); ?>">
    <script>
        function toggleForm() {
            var formContainer = document.getElementById('form-container');
            formContainer.style.display = formContainer.style.display === "none" ? "block" : "none";
        }
    </script>
    <style>
        .pending-box { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px 12px; border: 1px solid #ddd; }
        .button-container { margin-top: 20px; display: flex; justify-content: space-around; }
        .form-container { display: none; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>Member Pending</h1>
    <div class="pending-box">
        <table>
            <thead>
                <tr>
                    <th>Trx ID</th>
                    <th>Name</th>
                    <th>S.Phone</th>
                    <th>Package</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo '<tr>';
                        echo '<td>' . $row["unique_id"] . '</td>';
                        echo '<td>' . $row["name"] . '</td>';
                        echo '<td>' . $row["sphone"] . '</td>';
                        echo '<td>' . $row["amount"] . '</td>';
                        echo '<td class="status-' . strtolower($row["status"]) . '">' . $row["status"] . '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="5">No pending records.</td></tr>';
                }
                ?>
            </tbody>
        </table>
        <div class="button-container">
            <button onclick="window.location.href='tomem.php'">Approved Page</button>
            <button onclick="toggleForm()">Change Status</button>
            <button onclick="window.location.href='../panal.php'">Go to Panal</button>
        </div>
        <div id="form-container" class="form-container">
            <form method="POST" action="">
                <input type="text" name="user_id" placeholder="Enter Received TxR_ID" required>
                <select name="price" required>
                    <option value="" disabled selected>Select Price</option>
                    <option value="600">600</option>
                    <option value="1200">1200</option>
                    <option value="1800">1800</option>
                    <option value="3600">3600</option>
                </select>
                <div class="radio-container">
                    <label><input type="radio" name="status" value="active" required> Active</label>
                    <label><input type="radio" name="status" value="rejected" required> Rejected</label>
                </div>
                <input type="submit" value="Submit">
            </form>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
