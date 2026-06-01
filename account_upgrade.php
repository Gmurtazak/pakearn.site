<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php'; // Database connection
include 'private/unblock_updates.php';

$message = "";
$plans = [];
$email = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'])) {
    $email = $_POST['email'];

    // Step 1: Fetch id and amount from records table
    $query = "SELECT id, amount FROM records WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_id = $user['id'];
        $amount = $user['amount'];

        // Step 2: Fetch price from plan table
        $plan_query = "SELECT price FROM plan WHERE user_id = ?";
        $plan_stmt = $conn->prepare($plan_query);
        $plan_stmt->bind_param('i', $user_id);
        $plan_stmt->execute();
        $plan_result = $plan_stmt->get_result();

        if ($plan_result->num_rows > 0) {
            $plan = $plan_result->fetch_assoc();
            $price = $plan['price'];

            // Step 3: Compare price with amount
            if ($price == $amount) {
            } else {
                // Step 4: Update amount in records table
                $update_sql = "UPDATE records SET amount = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param('di', $price, $user_id);
                if ($update_stmt->execute()) {
                    $amount = $price; // updated amount
                } else {
                }
            }

            // Optional: Use amount to determine plans
            if ($amount == 600) {
                $plans = ['Gold', 'Platinum', 'Special'];
            } elseif ($amount == 1200) {
                $plans = ['Platinum', 'Special'];
            } elseif ($amount == 1800) {
                $plans = ['Special'];
            } elseif ($amount == 3600) {
                $message .= " You have already purchased the highest package!";
            }

        } else {
            $message = "Plan not found for this user.";
        }
    } else {
        $message = "No user found with this email.";
    }
}


// Handle dropdown submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['plan']) && isset($_POST['user_id'])) {
    $selected_plan = $_POST['plan'];
    $user_id = $_POST['user_id'];
    $new_amount = 0;

    if ($selected_plan == 'Gold') {
        $new_amount = 1200;
    } elseif ($selected_plan == 'Platinum') {
        $new_amount = 1800;
    } elseif ($selected_plan == 'Special') {
        $new_amount = 3600;
    }

    // Update the records and plan tables
    $update_records = "UPDATE records SET amount = ? WHERE id = ?";
    $stmt1 = $conn->prepare($update_records);
    $stmt1->bind_param('ii', $new_amount, $user_id);
    $stmt1->execute();

    $update_plan = "UPDATE plan SET price = ? WHERE user_id = ?";
    $stmt2 = $conn->prepare($update_plan);
    $stmt2->bind_param('ii', $new_amount, $user_id);
    $stmt2->execute();
    include 'private/block_updates.php';

    // Redirect to panal.php after successful update
    header("Location: panal.php");
    exit();
}
include 'private/block_updates.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Plan</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(to right, #6a11cb, #2575fc);
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .container {
            background: #ffffff;
            color: #333;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            max-width: 400px;
            width: 100%;
            text-align: center;
        }
        h1 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #2575fc;
        }
        form {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        input[type="email"], select, button {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: none;
            border-radius: 5px;
            font-size: 16px;
        }
        input[type="email"] {
            border: 1px solid #ddd;
            background: #f9f9f9;
        }
        select {
            border: 1px solid #ddd;
            background: #f9f9f9;
        }
        button {
            background: #2575fc;
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        button:hover {
            background: #6a11cb;
        }
        .message {
            margin: 15px 0;
            font-size: 14px;
            color: #2575fc;
        }
        .error {
            color: red;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Update Your Plan</h1>
        <?php if (empty($plans) && empty($_POST['plan'])): ?>
            <!-- Show email input form -->
            <form method="POST">
                <input type="email" name="email" placeholder="Enter your email" required>
                <button type="submit">Check Plans</button>
            </form>
        <?php elseif (!empty($plans)): ?>
            <!-- Show dropdown with plans -->
            <form method="POST">
                <input type="hidden" name="user_id" value="<?= $user_id ?>">
                <select name="plan" required>
                    <option value="">Select a Plan</option>
                    <?php foreach ($plans as $plan): ?>
                        <option value="<?= $plan ?>"><?= $plan ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Update Plan</button>
            </form>
        <?php endif; ?>
        <!-- Display message -->
        <?php if ($message): ?>
            <div class="message <?= empty($plans) ? 'error' : '' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
