<?php 
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Status Toggle</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(to right, #4facfe, #00f2fe);
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background: #fff;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            text-align: center;
            width: 100%;
            max-width: 400px;
            transition: transform 0.3s ease;
        }
        .container:hover {
            transform: translateY(-5px);
        }
        h1 {
            margin-bottom: 20px;
            color: #333;
            font-size: 1.8em;
        }
        input[type="text"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1em;
        }
        .status {
            margin: 20px 0;
            font-size: 1.3em;
            color: #444;
            font-weight: bold;
        }
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            cursor: pointer;
            margin-top: 10px;
            transition: background 0.3s, transform 0.3s;
        }
        .btn:hover {
            transform: scale(1.05);
        }
        .btn.lock {
            background: #ff4d4d;
            color: white;
        }
        .btn.unlock {
            background: #4caf50;
            color: white;
        }
        .btn.check-status {
            background: #008cba;
            color: white;
        }
        .btn.check-status:hover {
            background: #007bb5;
        }
        .info-message {
            font-size: 1.1em;
            color: #555;
            margin: 15px 0;
            padding: 10px;
            background: #f4f4f4;
            border-left: 5px solid #00bcd4;
            border-radius: 5px;
        }
        .info-message.locked {
            border-color: #ff4d4d;
        }
        .info-message.open {
            border-color: #4caf50;
        }
    </style>
</head>
<body>
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

    $statusMessage = "";
    $buttonClass = "unlock";
    $buttonText = "Unlock";
    $infoClass = "locked";

    if (isset($_POST['checkStatus'])) {
        $userInput = $_POST['userInput'];

        // Fetch the current status from the database
        $query = "SELECT stat FROM records WHERE id = ? OR email = ? LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $userInput, $userInput);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $currentStatus = $row['stat'];

            if ($currentStatus == 0) {
                $statusMessage = "Dashboard is Locked. Please click Unlock to open the dashboard.";
                $buttonClass = "unlock";
                $buttonText = "Unlock";
                $infoClass = "locked";
            } else {
                $statusMessage = "Dashboard is Open. Click Lock to secure the dashboard.";
                $buttonClass = "lock";
                $buttonText = "Lock";
                $infoClass = "open";
            }
        } else {
            $statusMessage = "No records found for the provided ID or Email.";
            $infoClass = "locked";
        }
    }

    if (isset($_POST['updateStatus'])) {
        $userInput = $_POST['userInput'];
        $newStatus = $_POST['currentStatus'] == 0 ? 1 : 0;
    
        // Prepare the SQL query
        $updateQuery = "UPDATE records SET stat = ?, ads_date = NULL, ads_date_start = NULL WHERE id = ? OR email = ?";
        $updateStmt = $conn->prepare($updateQuery);
    
        // Use proper data types for binding
        $updateStmt->bind_param("iis", $newStatus, $userInput, $userInput);
        $updateStmt->execute();
    
        // Set status messages and styles based on new status
        $statusMessage = $newStatus == 0
            ? "Dashboard is Locked. Please click Unlock to open the dashboard."
            : "Dashboard is Open. Click Lock to secure the dashboard.";
    
        $buttonClass = $newStatus == 0 ? "unlock" : "lock";
        $buttonText = $newStatus == 0 ? "Unlock" : "Lock";
        $infoClass = $newStatus == 0 ? "locked" : "open";
    }
    ?>

    <div class="container">
        <h1>User Status</h1>
        <form method="POST">
            <input type="text" name="userInput" placeholder="Enter User ID or Email" required>
            <button type="submit" name="checkStatus" class="btn check-status">Check Status</button>
        </form>

        <?php if ($statusMessage): ?>
            <div class="info-message <?php echo $infoClass; ?>"><?php echo $statusMessage; ?></div>
            <form method="POST">
                <input type="hidden" name="userInput" value="<?php echo htmlspecialchars($userInput); ?>">
                <input type="hidden" name="currentStatus" value="<?php echo $currentStatus; ?>">
                <button type="submit" name="updateStatus" class="btn <?php echo $buttonClass; ?>">
                    <?php echo $buttonText; ?>
                </button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
