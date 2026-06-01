<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}
include 'private/unblock_earnings.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Earnings</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .form-container {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 100%;
        }
        .form-container h2 {
            margin-bottom: 20px;
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }
        .form-group button {
            width: 100%;
            padding: 10px;
            background: #007bff;
            border: none;
            color: #fff;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .form-group button:hover {
            background: #0056b3;
        }
        .message {
            margin-top: 15px;
            padding: 10px;
            border-radius: 5px;
        }
        .success {
            background: #d4edda;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Update Earnings</h2>
        <form id="earningsForm" method="post" action="">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="price">Price:</label>
                <input type="number" id="price" name="price" required>
            </div>
            <div class="form-group">
                <label for="operation">Operation:</label>
                <select id="operation" name="operation" required>
                    <option value="add">Add</option>
                    <option value="subtract">Subtract</option>
                </select>
            </div>
            <div class="form-group">
                <button type="submit">Submit</button>
            </div>
        </form>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

            $email = $_POST['email'];
            $price = floatval($_POST['price']);
            $operation = $_POST['operation'];

            $sql = "SELECT id FROM records WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $id = $row['id'];

                if ($operation === 'add') {
                    $sql = "UPDATE earnings SET ads_earning = ads_earning + ?, total_earning = total_earning + ? WHERE id = ?";
                } else if ($operation === 'subtract') {
                    $sql = "UPDATE earnings SET ads_earning = ads_earning - ?, total_earning = total_earning - ? WHERE id = ?";
                }

                $stmt = $conn->prepare($sql);
                $stmt->bind_param('ddi', $price, $price, $id);
                $stmt->execute();

                echo '<div class="message success">Earnings updated successfully!</div>';
            } else {
                echo '<div class="message error">Error: Email not found.</div>';
            }

            $stmt->close();
            $conn->close();
        }
            include 'private/block_earnings.php';

        ?>
    </div>
</body>
</html>
