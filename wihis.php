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
include 'session_check.php';

require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

// Get user ID from query string
$id = intval($_GET['id']);

// Initialize an array to store withdrawal records
$withdrawals = [];

// Prepare the SQL query
$query = "SELECT id, amount, status, created_at FROM withdrawals WHERE user_id = ? ORDER BY id DESC";
$stmt = $conn->prepare($query);

// Check if the statement was prepared successfully
if (!$stmt) {
    die('Prepare failed: ' . htmlspecialchars($conn->error));
}

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

// Fetch the records
while ($row = $result->fetch_assoc()) {
    $withdrawals[] = $row;
}

// Close the statement
$stmt->close();

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdrawal History</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .history-container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            max-width: 800px;
            width: 100%;
            box-sizing: border-box;
        }

        .history-container h2 {
            margin-bottom: 20px;
            font-size: 24px;
            color: #333;
            text-align: center;
        }

        .table-container {
            max-height: 400px; /* Adjust the height as needed */
            overflow-y: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 12px;
            text-align: left;
        }

        th {
            background-color: #007bff;
            color: white;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        td {
            background-color: #f9f9f9;
        }

        .buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 16px;
            text-decoration: none;
            color: #fff;
            background-color: #007bff;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        .btn-secondary {
            background-color: #28a745;
        }

        .btn-secondary:hover {
            background-color: #218838;
        }

        @media (max-width: 768px) {
            .history-container {
                padding: 15px;
            }

            .history-container h2 {
                font-size: 22px;
            }

            table {
                font-size: 14px;
            }

            .btn {
                padding: 8px 16px;
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            .history-container {
                padding: 10px;
            }

            .history-container h2 {
                font-size: 20px;
            }

            table {
                font-size: 12px;
            }

            .btn {
                padding: 6px 12px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="history-container">
        <h2>Withdrawal History</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Serial No.</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date and Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($withdrawals) > 0): ?>
                        <?php foreach ($withdrawals as $index => $record): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($index + 1); ?></td>
                                <td><?php echo htmlspecialchars($record['amount']); ?></td>
                                <td><?php echo htmlspecialchars($record['status']); ?></td>
                                <td><?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($record['created_at']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">No records found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="buttons">
            <a href="javascript:history.back()" class="btn">Go Back</a>
            <a href="withdraw.php?id=<?php echo $id; ?>" class="btn btn-secondary">Withdraw</a>
        </div>
    </div>
</body>
</html>
