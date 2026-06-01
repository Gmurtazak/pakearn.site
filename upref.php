<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

// Handle fetching values
if (isset($_GET['price'])) {
    $price = $_GET['price'];

    $sql = "SELECT referral_earning_start, reward_bonus_start FROM referral_values WHERE price = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $price);
    $stmt->execute();
    $stmt->bind_result($referral_earning_start, $reward_bonus_start);
    $stmt->fetch();
    $stmt->close();

    echo json_encode([
        'referral_earning_start' => $referral_earning_start,
        'reward_bonus_start' => $reward_bonus_start
    ]);
    exit;
}

// Handle updating values
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $price = $_POST['price'];
    $referral_earning_start = $_POST['referral_earning_start'];
    $reward_bonus_start = $_POST['reward_bonus_start'];

    $sql = "UPDATE referral_values SET referral_earning_start = ?, reward_bonus_start = ? WHERE price = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $referral_earning_start, $reward_bonus_start, $price);

    if ($stmt->execute()) {
        echo "Values updated successfully!";
    } else {
        echo "Error updating values: " . $conn->error;
    }

    $stmt->close();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" type="x-icon" href="hum.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Referral Values Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        select, input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background-color: #218838;
        }
        @media (max-width: 480px) {
            .container {
                padding: 15px;
            }
            select, input, button {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Manage Referral Values</h2>
    <select id="priceSelect">
        <option value="" disabled selected>Select Price</option>
        <?php
        $sql = "SELECT price FROM referral_values";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo '<option value="'.$row['price'].'">'.$row['price'].'</option>';
            }
        }
        ?>
    </select>

    <input type="number" id="referralEarning" placeholder="Referral Earning">
    <input type="number" id="rewardBonus" placeholder="Reward Bonus">
    <button id="updateBtn" disabled>Update</button>
</div>

<script>
document.getElementById('priceSelect').addEventListener('change', function() {
    var price = this.value;
    if (price) {
        fetch(`?price=${price}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('referralEarning').value = data.referral_earning_start;
                document.getElementById('rewardBonus').value = data.reward_bonus_start;
                document.getElementById('updateBtn').disabled = false;
            });
    }
});

document.getElementById('updateBtn').addEventListener('click', function() {
    var price = document.getElementById('priceSelect').value;
    var referralEarning = document.getElementById('referralEarning').value;
    var rewardBonus = document.getElementById('rewardBonus').value;

    var formData = new FormData();
    formData.append('price', price);
    formData.append('referral_earning_start', referralEarning);
    formData.append('reward_bonus_start', rewardBonus);

    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        alert(data);
    });
});
</script>

</body>
</html>
