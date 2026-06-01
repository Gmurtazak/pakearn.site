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
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';
include 'session_check.php';
$id = $_GET['id'];

// Query to fetch user details by ID
$sql = "SELECT * FROM records WHERE id='$id'";
$result = $conn->query($sql);

if ($result === false || $result->num_rows === 0) {
    // Handle case where user ID is not found or query fails
    $name = "Unknown";
    $referralLink = "Invalid user ID";
} else {
    $row = $result->fetch_assoc();
    $name = $row['name'];
    $referralLink = "https://pakearn.site/reg.php?id=$id&name=" . urlencode($name);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Referral Link</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .referral-container {
            text-align: center;
            max-width: 600px;
            width: 90%;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .alert-message {
            color: #e74c3c;
            margin-bottom: 20px;
            font-size: 16px;
            border: 1px solid #e74c3c;
            padding: 10px;
            border-radius: 4px;
            background-color: #fbe3e2;
            box-sizing: border-box;
            word-wrap: break-word;
        }
        .referral-container h2 {
            color: #333;
            margin-bottom: 20px;
        }
        .referral-box {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            width: 100%;
        }
        .referral-box input[type="text"] {
            flex: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
            margin-right: 10px;
            box-sizing: border-box;
            width: calc(100% - 120px);
        }
        .referral-box button {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            width: 100px;
        }
        .referral-box button:hover {
            background-color: #45a049;
        }
        .back-button {
            display: inline-block;
            margin-top: 10px;
            color: #4CAF50;
            text-decoration: none;
            border: 1px solid #4CAF50;
            padding: 8px 16px;
            border-radius: 4px;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        .back-button:hover {
            background-color: #4CAF50;
            color: white;
        }
    </style>
</head>
<body>
    <div class="referral-container">
        <h2>Referral Link</h2>
        <div class="referral-box">
            <input type="text" value="<?php echo $referralLink; ?>" id="referralLink" readonly>
            <button onclick="copyToClipboard()">Copy</button>
        </div>
        <a href="user_page.php?id=<?php echo $id; ?>" class="back-button">Go Back to Dashboard</a>
    </div>

    <script>
        function copyToClipboard() {
            var copyText = document.getElementById("referralLink");
            copyText.select();
            document.execCommand("copy");
            var copyButton = document.querySelector('.referral-box button');
            copyButton.textContent = "Copied";
            copyButton.disabled = true;
        }
    </script>
</body>
</html>

<?php
$conn->close();
?>
