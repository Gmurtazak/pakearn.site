<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $price = $_POST['price'];
    $base = $_POST['base'];
    $reward_increment = $_POST['reward_increment'];

    $stmt = $conn->prepare("UPDATE price_values SET base = ?, reward_increment = ? WHERE price = ?");
    $stmt->bind_param("iis", $base, $reward_increment, $price);
    $stmt->execute();

    if ($stmt->error) {
        $message = "<p class='error'>Error updating values: " . $stmt->error . "</p>";
    } else {
        $message = "<p class='success'>Values updated successfully!</p>";
    }

    $stmt->close();
}

$prices = $conn->query("SELECT price FROM price_values ORDER BY price ASC");

if (isset($_GET['price'])) {
    $price = $_GET['price'];
    $result = $conn->query("SELECT base, reward_increment FROM price_values WHERE price = '$price'");
    $response = [];

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $response['base'] = $row['base'];
        $response['reward_increment'] = $row['reward_increment'];
    }

    echo json_encode($response);
    exit();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Price Values</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f7f7f7;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 500px;
            margin: auto;
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }
        .btn {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .error {
            color: red;
            text-align: center;
        }
        .success {
            color: green;
            text-align: center;
        }
        @media (max-width: 600px) {
            .container {
                width: 100%;
                padding: 10px;
            }
            .btn {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Update Price Values</h2>
    <form id="priceForm" action="" method="post">
        <div class="form-group">
            <label for="price">Select Price:</label>
            <select name="price" id="price" required>
                <option value="">Select a price</option>
                <?php while ($row = $prices->fetch_assoc()) : ?>
                    <option value="<?= $row['price'] ?>"><?= $row['price'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="base">Base Value:</label>
            <input type="number" name="base" id="base" required>
        </div>
        <div class="form-group">
            <label for="reward_increment">Reward Increment:</label>
            <input type="number" name="reward_increment" id="reward_increment" required>
        </div>
        <button type="submit" class="btn">Update</button>
    </form>
    <div id="message"><?php if (isset($message)) echo $message; ?></div>
</div>

<script>
document.getElementById('price').addEventListener('change', function() {
    var price = this.value;
    if (price) {
        fetch('?price=' + price)
            .then(response => response.json())
            .then(data => {
                document.getElementById('base').value = data.base || '';
                document.getElementById('reward_increment').value = data.reward_increment || '';
            })
            .catch(error => console.error('Error fetching data:', error));
    } else {
        document.getElementById('base').value = '';
        document.getElementById('reward_increment').value = '';
    }
});
</script>

</body>
</html>
