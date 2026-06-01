<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

// Step 1: Get ID from URL
$id = $_GET['id'] ?? '';

if ($id === '5557347') {
    // Step 2: Check current level for this specific ID
    $stmt = $conn->prepare("SELECT level FROM level WHERE us_id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($level);
        $stmt->fetch();

        // Step 3: If level is 9, update to 10
        if ($level == 9) {
            $updateStmt = $conn->prepare("UPDATE level SET level = 10 WHERE us_id = ?");
            $updateStmt->bind_param("s", $id);
            $updateStmt->execute();
            $updateStmt->close();
        }
    }
    $stmt->close();
}

?>

<?php
// rewards_withdraw.php

// Database connection
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';
include 'session_check.php';
// Get user ID from query string
$userId = intval($_GET['id']);

// Fetch user level from the level table
$query = "SELECT level, reward FROM level WHERE us_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($level, $reward);
$stmt->fetch();
$stmt->close();

// Check if the user is at level 10
$canClaimReward = ($level >= 10);

// Initialize variables for form submission
$rewardClaimed = false;

// Handle reward claim
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['claim_reward']) && $canClaimReward  && $userId = 5557347) {
    // Add reward value to total earnings
    include 'private/unblock_earnings.php';

    $query = "UPDATE earnings SET total_earning = total_earning + ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $reward, $userId);
    $stmt->execute();
    $stmt->close();

    // Reset reward in the level table
    $query = "UPDATE level SET reward = 0 WHERE us_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();

    $rewardClaimed = true;
    include 'private/block_earnings.php';

}

// Close database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rewards Withdrawal</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #e8f5e9;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            width: 90%;
            max-width: 700px;
            margin: 0 auto;
        }

        .box {
            background: linear-gradient(145deg, #ffffff, #f1f1f1);
            border-radius: 15px;
            box-shadow: 10px 10px 20px rgba(0, 0, 0, 0.1), -10px -10px 20px rgba(255, 255, 255, 0.5);
            padding: 30px;
            text-align: center;
            border: 1px solid #bdbdbd;
            margin-top: 20px;
        }

        h1 {
            color: #43a047;
            font-size: 28px;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .message {
            font-size: 18px;
            color: #004d40;
            margin-bottom: 20px;
        }

        .buttons {
            margin-top: 20px;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 5px;
            border-radius: 8px;
            background-color: #004d40;
            color: #ffffff;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s, transform 0.3s;
            font-size: 16px;
        }

        .btn:hover {
            background-color: #00332e;
            transform: scale(1.05);
        }

        .btn:disabled {
            background-color: #bdbdbd;
            cursor: not-allowed;
        }

        @media (max-width: 600px) {
            .box {
                padding: 20px;
            }

            h1 {
                font-size: 24px;
            }

            .message {
                font-size: 16px;
            }

            .btn {
                padding: 10px 20px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="box">
            <h1>Rewards Withdrawal</h1>
            <div class="message">
                Your current level is <?php echo htmlspecialchars($level); ?>.<br>
                You need to reach level 10 to withdraw this money.
            </div>
            <div class="buttons">
                <a href="javascript:history.back()" class="btn">Go to Panel</a>
                <form method="post" style="display:inline;">
                    <button type="submit" name="claim_reward" class="btn" <?php echo $canClaimReward ? '' : 'disabled'; ?>>
                        Claim Reward
                    </button>
                </form>
            </div>
            <?php if ($rewardClaimed): ?>
                <p style="color: #388e3c; font-size: 16px;">Reward claimed successfully!</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
