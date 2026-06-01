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
    header('Location: ../admin_login.php');
    exit();
}

// Verify session security
if (
    $_SESSION['admin']['ip'] !== $_SERVER['REMOTE_ADDR'] ||
    $_SESSION['admin']['user_agent'] !== $_SERVER['HTTP_USER_AGENT']
) {
    session_unset();
    session_destroy();
    header('Location: ../admin_login.php');
    exit();
}

// Session timeout management
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 600)) {
    session_unset();
    session_destroy();
    header('Location: ../admin_login.php');
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
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["user_id"]) && isset($_POST["price"]) && isset($_POST["status"])) {
    $user_id = $_POST["user_id"];
    $price = $_POST["price"];
    $status = $_POST["status"];

    // Update status in the records table
    $update_sql = "UPDATE records SET status='$status' WHERE unique_id='$user_id' AND status='pending'";
    if ($conn->query($update_sql) === TRUE) {
        if ($status == 'active') {
            // Fetch the primary key for the user_id
            $fetch_id_sql = "SELECT id FROM records WHERE unique_id='$user_id'";
            $id_result = $conn->query($fetch_id_sql);

            if ($id_result->num_rows > 0) {
                $row = $id_result->fetch_assoc();
                $primary_key = $row['id'];

                // Check for duplicate entry in plan table
                $check_duplicate_sql = "SELECT * FROM plan WHERE user_id='$primary_key'";
                $duplicate_result = $conn->query($check_duplicate_sql);
                if ($duplicate_result->num_rows == 0) {
                    // Insert into the plan table using the primary key
                    $insert_plan_sql = "INSERT INTO plan (user_id, price) VALUES ('$primary_key', '$price')";
                    if ($conn->query($insert_plan_sql) !== TRUE) {
                        echo "Error inserting plan record: " . $conn->error;
                    }
                } else {
                    echo "Error: Duplicate entry for user_id: $user_id";
                }
            } else {
                echo "No record found with user_id: $user_id";
            }
        } else if ($status == 'rejected') {
            // Fetch the primary key for the user_id
            $fetch_id_sql = "SELECT id FROM records WHERE unique_id='$user_id'";
            $id_result = $conn->query($fetch_id_sql);

            if ($id_result->num_rows > 0) {
                $row = $id_result->fetch_assoc();
                $primary_key = $row['id'];

                // Update the corresponding user status in the users table
                $update_user_sql = "UPDATE users SET status='rejected' WHERE id='$primary_key'";
                if ($conn->query($update_user_sql) !== TRUE) {
                    echo "Error updating user status: " . $conn->error;
                }
            } else {
                echo "No record found with user_id: $user_id";
            }
        }
    } else {
        echo "Error updating record: " . $conn->error;
    }
}

// Fetch pending records
$sql = "SELECT unique_id, name, sphone, amount, status FROM records WHERE status='pending'";
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
            if (formContainer.style.display === "none" || formContainer.style.display === "") {
                formContainer.style.display = "block";
            } else {
                formContainer.style.display = "none";
            }
        }
    </script>
    <style>
        .pending-box {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 8px 12px;
            border: 1px solid #ddd;
        }
        .button-container {
            margin-top: 20px;
            display: flex;
            justify-content: space-around;
        }
        .form-container {
            display: none;
            margin-top: 20px;
        }
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
                    <option value="300">300</option>
                    <option value="700">700</option>
                    <option value="1000">1000</option>
                    <option value="1800">1800</option>
                    <option value="2500">2500</option>
                    <option value="3800">3800</option>
                    <option value="5000">5000</option>
                    <option value="10000">10000</option>


                </select>
                <div class="radio-container">
                    <label>
                        <input type="radio" name="status" value="active" required> Active
                    </label>
                    <label>
                        <input type="radio" name="status" value="rejected" required> Rejected
                    </label>
                </div>
                <input type="submit" value="Submit">
            </form>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>