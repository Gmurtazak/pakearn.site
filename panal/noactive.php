<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

// Fetch pending records
$sql = "SELECT reff_id, name, status, ph FROM users WHERE status='pending'";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Non Active Members</title>
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
        .ap {
            color: red;
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
        }
    </style>
</head>
<body>
    <h1>Non Active Members</h1>
    <div class="pending-box">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Referral ID</th>
                    <th>Phone</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo '<tr>';
                        echo '<td>' . $row["name"] . '</td>';
                        echo '<td>' . $row["reff_id"] . '</td>';
                        echo '<td>' . $row["ph"] . '</td>';
                        echo '<td class="ap">' . $row["status"] . '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="4">No pending records.</td></tr>';
                }
                ?>
            </tbody>
        </table>
        <div class="button-container">
            <button onclick="window.location.href='search.php'">Search Referral</button>
            <button onclick="window.location.href='../panal.php'">Go to Panal</button>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>
