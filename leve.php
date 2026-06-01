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
// user_info.php
include 'session_check.php';

// Database connection
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

// Get user ID from query string
$userId = intval($_GET['id']);

// Fetch user level from the level table
$query = "SELECT level FROM level WHERE us_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($level);
$stmt->fetch();
$stmt->close();

// Fetch active team members count
$query = "SELECT COUNT(*) FROM users WHERE reff_id = ? AND status = 'active'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($activeCount);
$stmt->fetch();
$stmt->close();

// Determine members needed for the next level
$membersNeeded = 5; // Default value for levels other than 2 and 3
if ($level == 2) 
{
    $membersNeeded = 10;
}
elseif ($level == 3) 
{
    $membersNeeded = 20;
}
elseif ($level == 4) 
{
    $membersNeeded = 45;
}
elseif ($level == 5) 
{
    $membersNeeded = 90;
}
 elseif ($level == 6) {
    $membersNeeded = 145;
}
elseif ($level == 7) 
{
    $membersNeeded = 215;
}
 elseif ($level == 8) 
{
    $membersNeeded = 290;
}
elseif ($level == 9) 
{
    $membersNeeded = 0;
}



$membersLeft = max(0, $membersNeeded - $activeCount); // Calculate members left to reach next level

// Close database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Level Info</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #e0f7fa;
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
            color: #00796b;
            font-size: 28px;
            margin-bottom: 20px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        table, th, td {
            border: 1px solid #00796b;
        }

        th {
            background-color: #004d40;
            color: #ffffff;
            padding: 12px;
            text-align: left; /* Left-align header text */
            font-size: 20px;
            border-bottom: 2px solid #00796b;
        }

        td {
            background-color: #e0f2f1;
            color: #004d40;
            padding: 12px;
            text-align: left; /* Left-align cell text */
            font-size: 18px;
            border-bottom: 1px solid #bdbdbd;
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

        @media (max-width: 600px) {
            .box {
                padding: 20px;
            }

            h1 {
                font-size: 24px;
            }

            table {
                font-size: 14px;
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
            <h1>Level Info</h1>
            <table>
                <tr>
                    <th>Current Level</th>
                    <td><?php echo htmlspecialchars($level); ?></td>
                </tr>
                <tr>
                    <th>Team Members</th>
                    <td><?php echo htmlspecialchars($activeCount); ?></td>
                </tr>
                <tr>
                    <th>Members Left to Reach Next Level</th>
                    <td><?php echo htmlspecialchars($membersLeft); ?></td>
                </tr>
            </table>
            <div class="buttons">
                <a href="javascript:history.back()" class="btn">Go Back</a>
                <a href="reflink.php?id=<?php echo $userId; ?>" class="btn">Add More</a>
            </div>
        </div>
    </div>
</body>
</html>
