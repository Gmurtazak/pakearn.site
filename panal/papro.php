<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

// Fetch all records from the email_request table
$sql_fetch = "SELECT id, email FROM email_requests";
$result = $conn->query($sql_fetch);

// Handle record deletion
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $sql_delete = "DELETE FROM email_requests WHERE id = ?";
    $stmt = $conn->prepare($sql_delete);
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "Error deleting record: " . $conn->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Requests</title>
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
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .email-box {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .email-box table {
            width: 100%;
            border-collapse: collapse;
        }
        .email-box th, .email-box td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .email-box th {
            background-color: #f2f2f2;
        }
        .email-box tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .email-box .delete-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }
        .email-box .delete-btn:hover {
            background-color: #c82333;
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
        .button-container .search-btn {
            background-color: #28a745;
        }
        .button-container .search-btn:hover {
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
            .email-box {
                padding: 15px;
            }
            .email-box th, .email-box td {
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
            .email-box {
                padding: 10px;
            }
            .email-box th, .email-box td {
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
    <div class="container">
        <h1>Email Requests</h1>
        <div class="email-box">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Email</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo '<tr>';
                            echo '<td>' . $row["id"] . '</td>';
                            echo '<td>' . $row["email"] . '</td>';
                            echo '<td><a href="?delete_id=' . $row["id"] . '" class="delete-btn">Delete</a></td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="3">No records found.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <div class="button-container">
            <button onclick="window.location.href='../panal.php'">Go to Panal</button>
            <button class="search-btn" onclick="window.location.href='search.php'">Search User</button>
        </div>
    </div>
    <script>
        // Any necessary JavaScript code here
    </script>
</body>
</html>

<?php
$conn->close();
?>
