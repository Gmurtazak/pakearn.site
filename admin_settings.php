<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

// Initialize variables
$message = "";

// Fetch current settings
$sql = "SELECT first_withdraw_amount, subsequent_withdraw_amount FROM settings WHERE id = 1";
$result = $conn->query($sql);
$settings = $result->fetch_assoc();

$first_withdraw_amount = $settings['first_withdraw_amount'];
$subsequent_withdraw_amount = $settings['subsequent_withdraw_amount'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_first_withdraw = $_POST['first_withdraw_amount'];
    $new_subsequent_withdraw = $_POST['subsequent_withdraw_amount'];

    // Update settings in the database
    $update_sql = "UPDATE settings SET first_withdraw_amount = ?, subsequent_withdraw_amount = ? WHERE id =1";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("dd", $new_first_withdraw, $new_subsequent_withdraw);
    $stmt->execute();
    $stmt->close();

    $message = "Withdrawal limits updated successfully!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings</title>
    <style>
        /* Your styles here */
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

        .settings-container {
            background: #3498DB;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            padding: 30px;
            max-width: 400px;
            width: 100%;
            text-align: center;
            box-sizing: border-box;
        }

        .settings-container h2 {
            font-size: 24px;
            color: #fff;
            margin-bottom: 20px;
        }

        .settings-container form input[type="number"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .settings-container form .submit-btn {
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

        .settings-container form .submit-btn:hover {
            background-color: #e6b800;
        }

        .settings-container .message {
            margin-top: 20px;
            font-size: 16px;
            color: #28a745;
        }
    </style>
</head>
<body>
    <div class="settings-container">
        <h2>Update Withdrawal Limits</h2>
        <form method="POST" action="">
            <input type="number" name="first_withdraw_amount" value="<?php echo $first_withdraw_amount; ?>" placeholder="First Withdrawal Amount" required>
            <input type="number" name="subsequent_withdraw_amount" value="<?php echo $subsequent_withdraw_amount; ?>" placeholder="Subsequent Withdrawal Amount" required>
            <button type="submit" class="submit-btn">Update</button>
        </form>
        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
    </div>
</body>
</html>

