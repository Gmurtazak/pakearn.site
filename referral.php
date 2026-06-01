<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';
include 'session_check.php';
include 'adss.php';

$id = $_GET['id'];

// Query to fetch user details by ID
$sql = "SELECT * FROM records WHERE id='$id'";
$result = $conn->query($sql);

if ($result === false || $result->num_rows === 0) {
    // Handle case where user ID is not found or query fails
    $name = "Unknown";
    $referralLink = "Invalid user ID";
    $stat = null;
} else {
    $row = $result->fetch_assoc();
    $name = $row['name'];
    $referralLink = "https://pakearns.net/reg.php?id=$id&name=" . urlencode($name);
    $stat = $row['stat'];
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
            background-color: #f0f8ff;
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
            padding: 30px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }
        .alert-message {
            color: #e74c3c;
            margin-bottom: 20px;
            font-size: 16px;
            border: 1px solid #e74c3c;
            padding: 10px;
            border-radius: 6px;
            background-color: #fbe3e2;
        }
        .referral-container h2 {
            color: #333;
            font-size: 24px;
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
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 16px;
            margin-right: 10px;
            box-sizing: border-box;
        }
        .referral-box button {
            padding: 12px 20px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .referral-box button:hover {
            background-color: #218838;
            transform: scale(1.05);
        }
        .button-container {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        .my-info-button, .back-button {
            display: inline-block;
            padding: 10px 20px;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 16px;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .my-info-button {
            background-color: #007bff;
        }
        .my-info-button:hover {
            background-color: #0069d9;
            transform: scale(1.05);
        }
        .back-button {
            background-color: #ffc107;
        }
        .back-button:hover {
            background-color: #e0a800;
            transform: scale(1.05);
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
    <div class="referral-container">
    
        <?php if ($stat === '0'): ?>
            <div class="alert-message">
                Please add one more member through your link. Then you will get your Dashboard back. Shukriya!
            </div>
        <?php endif; ?>
        <h2>Referral Link</h2>
        <div class="referral-box">
            <input type="text" value="<?php echo $referralLink; ?>" id="referralLink" readonly>
            <button onclick="copyToClipboard()">Copy</button>
        </div>
        <div class="button-container">
            <a href="pdata.php?id=<?php echo $id; ?>" class="my-info-button">MY INFO</a>
            <a href="user_page.php?id=<?php echo $id; ?>" class="back-button">Go Back to Dashboard</a>
        </div>
    </div>

    <script>
    setTimeout(function(){
            window.location.reload(true);
    }, 20000);

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
$id_to_match = $id; // Replace with the actual ID you want to match
// Query to get the stat value based on the ID
$sql = "SELECT stat FROM records WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_to_match);
$stmt->execute();
$stmt->bind_result($stat_value);
$stmt->fetch();

// Store the stat value in a variable
$stat = $stat_value;
if($stat==1)
{
    header("Location: user_page.php?id=$id");
}
// Close the statement and connection
$stmt->close();
$conn->close();
?>
