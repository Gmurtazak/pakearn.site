<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $idToDelete = $_POST['delete'];

    // Fetch the phone number for the record to be deleted
    $fetchPhoneSql = "SELECT ph FROM users WHERE id = ?";
    $stmt = $conn->prepare($fetchPhoneSql);
    $stmt->bind_param("i", $idToDelete);
    $stmt->execute();
    $stmt->bind_result($phoneNumber);
    $stmt->fetch();
    $stmt->close();

    if ($phoneNumber) {
        // Delete from users table based on phone number
        $deleteUserSql = "DELETE FROM users WHERE ph = ?";
        $stmt = $conn->prepare($deleteUserSql);
        $stmt->bind_param("s", $phoneNumber);
        $stmt->execute();
        $stmt->close();

        // Delete from records table
        $deleteRecordSql = "DELETE FROM records WHERE phone = ?";
        $stmt = $conn->prepare($deleteRecordSql);
        $stmt->bind_param("s", $phoneNumber);
        $stmt->execute();
        $stmt->close();

        echo 'Record deleted successfully from both tables.';
    } else {
        echo 'Failed to fetch phone number.';
    }
}

// Fetch all user records
$sql = "SELECT id, name, ph, status FROM users";
$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Records</title>
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
        .user-list {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 20px auto;
            overflow-x: auto;
        }
        .user-list table {
            width: 100%;
            border-collapse: collapse;
        }
        .user-list th, .user-list td {
            padding: 10px;
            text-align: left;
        }
        .user-list th {
            background-color: #f2f2f2;
        }
        .user-list tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .user-list button {
            background-color: #dc3545;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }
        .user-list button:hover {
            background-color: #c82333;
        }
        .back-button {
            display: flex;
            align-items: center;
            background-color: #007bff;
            color: white;
            width: 20%;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            margin-bottom: 20px;
            transition: background-color 0.3s ease;
        }
        .back-button:hover {
            background-color: #0056b3;
        }
        .back-button i {
            margin-right: 10px;
        }
        @media (max-width: 768px) {
            .user-list {
                padding: 15px;
            }
            .user-list th, .user-list td {
                padding: 8px;
                font-size: 14px;
            }
            .user-list button {
                padding: 4px 8px;
                font-size: 12px;
            }
            .back-button {
                padding: 8px 16px;
                font-size: 14px;
            }
        }
        @media (max-width: 480px) {
            .user-list {
                padding: 10px;
            }
            .user-list th, .user-list td {
                padding: 5px;
                font-size: 12px;
            }
            .user-list button {
                padding: 3px 6px;
                font-size: 10px;
            }
            .back-button {
                padding: 6px 12px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <a href="../panal.php" class="back-button"><i class="fas fa-arrow-left"></i> Back to Panel</a>
    <h1>User Records</h1>
    <div class="user-list">
        <table>
            <thead>
                <tr>
                    <th>S.No</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    $serialNumber = 1;
                    while ($row = $result->fetch_assoc()) {
                        echo '<tr>';
                        echo '<td>' . $serialNumber . '</td>';
                        echo '<td>' . $row["name"] . '</td>';
                        echo '<td>' . $row["ph"] . '</td>';
                        echo '<td>' . $row["status"] . '</td>';
                        echo '<td>';
                        echo '<form style="display:inline;" method="post" action="udelete.php">';
                        echo '<input type="hidden" name="delete" value="' . $row["id"] . '">';
                        echo '<button type="submit">Delete</button>';
                        echo '</form>';
                        echo '</td>';
                        echo '</tr>';
                        $serialNumber++;
                    }
                } else {
                    echo '<tr><td colspan="5">No records found.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Include FontAwesome for the back arrow icon -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>
