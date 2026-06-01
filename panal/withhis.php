<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

// Update status if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["user_id"])) {
    $user_id = $_POST["user_id"];
    $update_sql = "UPDATE withdrawals SET status='pending' WHERE id='$user_id' AND status='active'";
    if ($conn->query($update_sql) === TRUE) {
        echo "Record updated successfully";
    } else {
        echo "Error updating record: " . $conn->error;
    }
}

// Fetch pending records
$sql = "SELECT w.id, w.payment_method, w.name, w.phone, w.amount, w.status, w.created_at, r.email 
        FROM withdrawals w 
        JOIN records r ON w.user_id = r.id 
        WHERE w.status='active'";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdraw History</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 20px;
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .pending-box {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 20px auto;
            overflow-x: auto;
        }
        .ap
        {
            color:green;
        }
        .pending-box table {
            width: 100%;
            border-collapse: collapse;
        }
        .pending-box th, .pending-box td {
            padding: 10px;
            text-align: left;
        }
        .pending-box th {
            background-color: #f2f2f2;
        }
        .pending-box tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .button-container {
            text-align: center;
            margin-top: 20px;
        }
        .button-container button {
            background-color: #007BFF;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
            margin: 5px;
        }
        .button-container button:hover {
            background-color: #0056b3;
        }
        .form-container {
            display: none;
            text-align: center;
            margin-top: 20px;
        }
        .form-container form {
            display: inline-block;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .form-container input[type="text"] {
            padding: 10px;
            font-size: 16px;
            margin-bottom: 10px;
            width: calc(100% - 22px);
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .form-container input[type="submit"] {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }
        .form-container input[type="submit"]:hover {
            background-color: #218838;
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            h1 {
                font-size: 24px;
            }
            .pending-box {
                padding: 15px;
            }
            .pending-box th, .pending-box td {
                padding: 8px;
                font-size: 14px;
            }
            .button-container button {
                padding: 8px 16px;
                font-size: 14px;
            }
            .form-container input[type="text"] {
                font-size: 14px;
                padding: 8px;
            }
            .form-container input[type="submit"] {
                padding: 8px 16px;
                font-size: 14px;
            }
        }
        @media (max-width: 480px) {
            h1 {
                font-size: 20px;
            }
            .pending-box {
                padding: 10px;
            }
            .pending-box th, .pending-box td {
                padding: 5px;
                font-size: 12px;
            }
            .button-container button {
                padding: 6px 12px;
                font-size: 12px;
            }
            .form-container input[type="text"] {
                font-size: 12px;
                padding: 6px;
            }
            .form-container input[type="submit"] {
                padding: 6px 12px;
                font-size: 12px;
            }
        }
    </style>
    <script>
        function toggleForm() {
            var formContainer = document.getElementById('form-container');
            if (formContainer.style.display === "none" || formContainer.style.display === "") {
                formContainer.style.display = "block";
            } else {
                formContainer.style.display = "none";
            }
        }
    </script>
</head>
<body>
    <h1>Withdraw History</h1>
    <div class="pending-box">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Method</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo '<tr>';
                        echo '<td>' . $row["id"] . '</td>';
                        echo '<td>' . $row["payment_method"] . '</td>';
                        echo '<td>' . $row["name"] . '</td>';
                        echo '<td>' . $row["email"] . '</td>';
                        echo '<td>' . $row["phone"] . '</td>';
                        echo '<td>' . $row["amount"] . '</td>';
                        echo '<td class="ap">Approved</td>';
                        echo '<td>' . $row["created_at"] . '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="6">No pending records.</td></tr>';
                }
                ?>
            </tbody>
        </table>
        <div class="button-container">
            <button onclick="window.location.href='withpen.php'">Pending</button>
            <button onclick="toggleForm()">Change Status</button>
            <button onclick="window.location.href='../panal.php'">Back</button>
        </div>
        <div id="form-container" class="form-container">
            <form method="POST" action="">
                <input type="text" name="user_id" placeholder="Enter User ID" required>
                <input type="submit" value="Submit">
            </form>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>
