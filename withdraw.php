<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
include 'connection.php';
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
// Step 1: Get the ID from URL
if (isset($_GET['id'])) {
    $id = $_GET['id']; // ID fetch from URL
} 

// Step 2: Check the status from the records table
$query = "SELECT stat FROM records WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if ($row['stat'] == 0) {
        // Redirect to user_page.php with the id if status is 0
        header("Location: user_page.php?id=" . $id);
        exit();
    }
} 


// Set timezone to Pakistan
date_default_timezone_set('Asia/Karachi');

$id = $_GET['id'];

// Initialize variables
$message = "";
$success = false;

// Fetch first and subsequent withdrawal values from settings table
$sql = "SELECT first_withdraw_amount, subsequent_withdraw_amount FROM settings WHERE id = 1";
$stmt = $conn->prepare($sql);
$stmt->execute();
$stmt->bind_result($first_withdraw_amount, $subsequent_withdraw_amount);
$stmt->fetch();
$stmt->close();

// Check if there's a pending withdrawal
$sql = "SELECT COUNT(*) AS pending_count FROM withdrawals WHERE user_id = ? AND status = 'pending'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($pending_count);
$stmt->fetch();
$stmt->close();

if ($pending_count > 0) {
    $message = "You already have a pending withdrawal request. Please wait until it is processed.";
} else {
    // Fetch user's total earning and first withdrawal status
    $sql = "SELECT total_earning, first_withdraw_done FROM earnings WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($total_earning, $first_withdraw_done);
    $stmt->fetch();
    $stmt->close();

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $name = $_POST['name'];
        $payment_method = $_POST['payment_method'];
        $phone = $_POST['phone'];
        $rupees = $_POST['rupees'];

        // First withdrawal rules
        if (!$first_withdraw_done && $rupees < $first_withdraw_amount) {
            $message = "The first withdrawal amount must be at least $first_withdraw_amount.";
        } elseif ($first_withdraw_done && $rupees < $subsequent_withdraw_amount) {
            $message = "The minimum amount for subsequent withdrawals is $subsequent_withdraw_amount.";
        } elseif ($rupees > $total_earning) {
            $message = "Insufficient funds.";
        } else {
            // Deduct the amount from total earnings
            $new_total = $total_earning - $rupees;

            // Update earnings and mark first withdrawal as done
            $update_sql = "UPDATE earnings SET total_earning = ?, referral_earning = 0.0, ads_earning = 0.0, first_withdraw_done = TRUE WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("di", $new_total, $id);
            $update_stmt->execute();
            $update_stmt->close();

            // Set current Pakistani date and time
            $current_datetime = date("Y-m-d H:i:s");

            // Insert withdrawal request with current timestamp
            $insert_sql = "INSERT INTO withdrawals (user_id, name, payment_method, phone, amount, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("isssds", $id, $name, $payment_method, $phone, $rupees, $current_datetime);
            $insert_stmt->execute();
            $insert_stmt->close();

            $message = "Your withdrawal request is in process. Please wait, we will inform you soon.";
            $success = true;
        }
    }
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" type="x-icon" href="hum.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdraw Funds</title>
    <style>
        /* CSS styles remain unchanged */
         body {
            font-family: 'Arial', sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .withdraw-container {
            background: #8E44AD;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            padding: 30px;
            max-width: 400px;
            width: 100%;
            text-align: center;
            box-sizing: border-box;
            transition: transform 0.3s;
        }

        .withdraw-container:hover {
            transform: translateY(-10px);
        }

        .withdraw-container h2 {
            font-size: 24px;
            color: #fff;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
            margin-bottom: 20px;
        }

        .withdraw-container form {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .withdraw-container form .radio-group {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        .withdraw-container form label {
            font-weight: bold;
            color: #fff;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
            margin-right: 20px;
        }

        .withdraw-container form input[type="text"],
        .withdraw-container form input[type="number"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .withdraw-container form input[type="radio"] {
            margin-right: 5px;
        }

        .withdraw-container form .submit-btn {
            width: 100%;
            padding: 12px;
            background-color: #007bff;
            border: none;
            border-radius: 25px;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            box-sizing: border-box;
        }

        .withdraw-container form .submit-btn:hover {
            background-color: #e6b800;
        }

        .withdraw-container .message {
            margin-top: 20px;
            font-size: 16px;
            color: <?php echo $success ? '#28a745' : '#dc3545'; ?>;
        }

        .withdraw-container .close-btn {
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #007bff;
            border: none;
            border-radius: 25px;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            box-sizing: border-box;
        }

        .withdraw-container .close-btn:hover {
            background-color: #0056b3;
        }

        @media (max-width: 600px) {
            .withdraw-container {
                padding: 20px;
            }

            .withdraw-container h2 {
                font-size: 20px;
            }

            .withdraw-container form .submit-btn {
                font-size: 14px;
                padding: 10px;
            }

            .withdraw-container .close-btn {
                font-size: 14px;
                padding: 10px 20px;
            }

            .withdraw-container form .radio-group label {
                margin-right: 10px;
            }
        }
         .ad-container iframe {
            max-width: 100%;
            margin-top:20px;
        }
           .ad-container2 iframe {
            max-width: 100%;
            margin-top:0px;
        }
      .ad-container {
    width: 100%;
    text-align: center;
    margin: 5px 0; /* Add equal spacing above and below */
      }
    </style>
</head>
<body>
    <div class="withdraw-container">
        
        <h2>Withdraw Funds</h2>
        <form method="POST" action="">
            <div class="radio-group">
                <label>
                    <input type="radio" name="payment_method" value="JazzCash" required> JazzCash
                </label>
                <label>
                    <input type="radio" name="payment_method" value="EasyPaisa" required> EasyPaisa
                </label>
            </div>
            <input type="text" name="name" placeholder="Enter Account Holder Name" required>
            <input type="text" name="phone" placeholder="Enter Account Holder Phone Number" required>
            <input type="number" name="rupees" placeholder="Enter Amount" 
                <?php 
                    echo $first_withdraw_done ? 
                    "min=\"$subsequent_withdraw_amount\"" : 
                    "min=\"$first_withdraw_amount\""; 
                ?> required>
            <button type="submit" class="submit-btn" <?php echo $pending_count > 0 ? 'disabled' : ''; ?>>Submit</button>
        </form>
        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <button class="close-btn" onclick="window.history.back();">Close</button>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
$conn->close();
?>
