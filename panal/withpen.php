<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';


// Tables to check triggers for
$tables = ['earnings', 'withdrawals'];

// Loop through each table
foreach ($tables as $table) {
    // Get triggers for this table
    $result = $conn->query("SHOW TRIGGERS WHERE `Table` = '{$table}'");

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $trigger_name = $row['Trigger'];
            // Drop the trigger silently
            $conn->query("DROP TRIGGER IF EXISTS `{$trigger_name}`");
        }
    }
}
?>

<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

date_default_timezone_set("Asia/Karachi");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["user_id"]) && isset($_POST["status"])) {
    $user_id = $_POST["user_id"];
    $status = $_POST["status"];
    $current_date_time = date("Y-m-d H:i:s");

    if ($status == "active") {

            $update_sql = "UPDATE withdrawals SET status='active', created_at='$current_date_time' WHERE id='$user_id' AND status='pending'";
            if ($conn->query($update_sql) === TRUE) {
        
                // ✅ Step 1: Fetch user_id and amount
                $fetch_sql = "SELECT user_id, amount FROM withdrawals WHERE id='$user_id'";
                $result = $conn->query($fetch_sql);
                if ($result && $result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $fetched_user_id = $row['user_id'];
                    $amount = $row['amount'];
        
                    // ✅ Step 2: Get current total_earning
                    $earning_sql = "SELECT total_earning FROM earnings WHERE id='$fetched_user_id'";
                    $earning_result = $conn->query($earning_sql);
                    if ($earning_result && $earning_result->num_rows > 0) {
                        $earning_row = $earning_result->fetch_assoc();
                        $total_earning = $earning_row['total_earning'];
        
                        // ✅ Step 3: Notify values
                        $old_earn = $total_earning + $amount;
                        $current_earn = $total_earning;
                        $total_add = $amount;
                        $message = "withdraw approved";
        
                        // ✅ Step 4: Check if notify record already exists
                        $check_stmt = $conn->prepare("SELECT id FROM notify WHERE user_id=? AND message=? AND created_at=?");
                        $check_stmt->bind_param("iss", $fetched_user_id, $message, $current_date_time);
                        $check_stmt->execute();
                        $check_result = $check_stmt->get_result();
        
                        if ($check_result->num_rows == 0) {
                            // ✅ Insert notify record
                            $stmt = $conn->prepare("INSERT INTO notify (user_id, name, message, old_earn, total_add, current_earn, created_at)
                                                    VALUES (?, NULL, ?, ?, ?, ?, ?)");
                            $stmt->bind_param("isiiis", $fetched_user_id, $message, $old_earn, $total_add, $current_earn, $current_date_time);
                            $stmt->execute();
                        }
                    }
                }
                echo "<script>alert('Record updated successfully with date and time: $current_date_time'); setTimeout(function(){ window.location.href = 'withpen.php'; }, 2000);</script>";
            } else {
                echo "Error updating record: " . $conn->error;
            }
        }

    elseif ($status == "rejected") {
        $fetch_sql = "SELECT user_id, amount FROM withdrawals WHERE id='$user_id'";
        $result = $conn->query($fetch_sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $user_id_fetch = $row['user_id'];
            $amount = $row['amount'];


            $update_withdrawals = "UPDATE withdrawals SET status='rejected' WHERE id='$user_id'";
            if ($conn->query($update_withdrawals) === TRUE) {
    
                // ✅ Step 1: Get current total_earning
                $fetch_last_earning = "SELECT * FROM earnings WHERE id='$user_id_fetch' ORDER BY id DESC LIMIT 1";
                $earning_result = $conn->query($fetch_last_earning);
                if ($earning_result->num_rows > 0) {
                    $earning_row = $earning_result->fetch_assoc();
                    $total_earning = $earning_row['total_earning'] + $amount;
    
                    // ✅ Update earnings
                    $update_earning = "UPDATE earnings SET total_earning='$total_earning' WHERE id='{$earning_row['id']}'";
                    if ($conn->query($update_earning) === TRUE) {
    
                        // ✅ Step 2: Prepare notify values
                        $old_earn = $total_earning - $amount;
                        $current_earn = $total_earning;
                        $total_add = $amount;
                        $message = "withdraw rejected";
    
                        // ✅ Step 3: Check if notify already exists
                        $check_stmt = $conn->prepare("SELECT id FROM notify WHERE user_id=? AND message=? AND created_at=?");
                        $check_stmt->bind_param("iss", $user_id_fetch, $message, $current_date_time);
                        $check_stmt->execute();
                        $check_result = $check_stmt->get_result();
    
                        if ($check_result->num_rows == 0) {
                            // ✅ Insert into notify
                            $stmt = $conn->prepare("INSERT INTO notify (user_id, name, message, old_earn, total_add, current_earn, created_at)
                                                    VALUES (?, NULL, ?, ?, ?, ?, ?)");
                            $stmt->bind_param("isiiis", $user_id_fetch, $message, $old_earn, $total_add, $current_earn, $current_date_time);
                            $stmt->execute();
                        }
    
                   

                        echo "<script>alert('Record updated successfully'); setTimeout(function(){ window.location.href = 'withpen.php'; }, 2000);</script>";
                    } else {
                        echo "Error updating earnings: " . $conn->error;
                    }
                }
            } else {
                echo "Error updating withdrawals: " . $conn->error;
            }
        }
    }
}

$sql = "SELECT w.id, w.user_id,w.payment_method, w.name, w.phone, w.amount, w.status, w.created_at, r.email FROM withdrawals w JOIN records r ON w.user_id = r.id WHERE w.status='pending'";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdraw History</title>
    <script>
        function toggleForm() {
            var formContainer = document.getElementById('form-container');
            formContainer.style.display = (formContainer.style.display === "none" || formContainer.style.display === "") ? "block" : "none";
        }
    </script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 20px;
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .pending-box {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 20px auto;
            overflow-x: auto;
        }
        .ap {
            color: red;
        }
        .pending-box table {
            width: 100%;
            border-collapse: collapse;
        }
        .pending-box th, .pending-box td {
            padding: 10px;
            text-align: left;
        }
        .pending-box th {
            background-color: #f2f2f2;
        }
        .pending-box tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .button-container {
            text-align: center;
            margin-top: 20px;
        }
        .button-container button {
            background-color: #007BFF;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
            margin: 5px;
        }
        .button-container button:hover {
            background-color: #0056b3;
        }
        .form-container {
            display: none;
            text-align: center;
            margin-top: 20px;
        }
        .form-container form {
            display: inline-block;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .form-container input[type="text"] {
            padding: 10px;
            font-size: 16px;
            margin-bottom: 10px;
            width: calc(100% - 22px);
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .form-container input[type="submit"] {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }
        .form-container input[type="submit"]:hover {
            background-color: #218838;
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            h1 {
                font-size: 24px;
            }
            .pending-box {
                padding: 15px;
            }
            .pending-box th, .pending-box td {
                padding: 8px;
                font-size: 14px;
            }
            .button-container button {
                padding: 8px 16px;
                font-size: 14px;
            }
            .form-container input[type="text"] {
                font-size: 14px;
                padding: 8px;
            }
            .form-container input[type="submit"] {
                padding: 8px 16px;
                font-size: 14px;
            }
        }
        @media (max-width: 480px) {
            h1 {
                font-size: 20px;
            }
            .pending-box {
                padding: 10px;
            }
            .pending-box th, .pending-box td {
                padding: 5px;
                font-size: 12px;
            }
            .button-container button {
                padding: 6px 12px;
                font-size: 12px;
            }
            .form-container input[type="text"] {
                font-size: 12px;
                padding: 6px;
            }
            .form-container input[type="submit"] {
                padding: 6px 12px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <h1>Withdraw History</h1>
    <div class="pending-box">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Method</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>User Page</th>
                    <th>Notify</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo '<tr>';
                        echo '<td>' . $row["id"] . '</td>';
                        echo '<td>' . $row["payment_method"] . '</td>';
                        echo '<td>' . $row["name"] . '</td>';
                        echo '<td>' . $row["email"] . '</td>';
                        echo '<td>' . $row["phone"] . '</td>';
                        echo '<td>' . $row["amount"] . '</td>';
                        echo '<td class="ap">Pending</td>';
                        echo '<td>' . $row["created_at"] . '</td>';
                        echo '<td><button class="user-btn" onclick="window.location.href=\'../withme.php?id=' . $row["user_id"] . '\'">Profile</button></td>';
                        echo '<td><button class="user-btn" onclick="window.location.href=\'../notify.php?id=' . $row["user_id"] . '\'">Notify</button></td>';

                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="8">No pending records.</td></tr>';
                }
                ?>
            </tbody>
        </table>
        <div class="button-container">
            <button onclick="window.location.href='withhis.php'">Approved Page</button>
            <button onclick="toggleForm()">Change Status</button>
            <button onclick="window.location.href='../panal.php'">Go to Panal</button>
        </div>
        <div id="form-container" class="form-container" style="display:none;">
            <form method="POST" action="">
                <input type="text" name="user_id" placeholder="Enter User ID" required>
                <label>
                    <input type="radio" name="status" value="active" required> Active
                </label>
                <label>
                    <input type="radio" name="status" value="rejected" required> Rejected
                </label>
                <input type="submit" value="Submit">
            </form>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>
