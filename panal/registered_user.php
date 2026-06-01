<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

// Fetch pending records where amount is 0
$sql = "SELECT unique_id, name, email, amount, status FROM records WHERE status='pending' AND amount=0";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Members (Amount 0)</title>
    <link rel="stylesheet" href="pemem.css?v=<?php echo time(); ?>">
    <style>
        .pending-box {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 8px 12px;
            border: 1px solid #ddd;
        }
        .button-container {
            margin-top: 20px;
            display: flex;
            justify-content: space-around;
        }
    </style>
</head>
<body>
    <h1>Registered Members</h1>
    <div class="pending-box">
        <table>
            <thead>
                <tr>
                    <th>Serial No</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $serial_no = 1;
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo '<tr>';
                        echo '<td>' . $serial_no++ . '</td>';
                        echo '<td>' . $row["name"] . '</td>';
                        echo '<td>' . $row["email"] . '</td>';
                        echo '<td class="status-' . strtolower($row["status"]) . '">' . $row["status"] . '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="4">No pending records with amount 0.</td></tr>';
                }
                ?>
            </tbody>
        </table>
        <div class="button-container">
            <button onclick="window.location.href='tomem.php'">Approved Page</button>
            <button onclick="window.location.href='../panal.php'">Go to Panel</button>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>
