<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php'; // Database connection

// Count referrals
$query = "
    SELECT 
        r.reff_id, 
        COUNT(*) AS total_referrals 
    FROM users r 
    WHERE r.reff_id IS NOT NULL 
    GROUP BY r.reff_id 
    ORDER BY total_referrals DESC 
    LIMIT 500
";
$result = mysqli_query($conn, $query);

$topReferrers = [];

while ($row = mysqli_fetch_assoc($result)) {
    $reff_id = $row['reff_id'];
    $ref_count = $row['total_referrals'];

    // Fetch user details
    $userQuery = "SELECT id, name, email, ph FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $userQuery);
    mysqli_stmt_bind_param($stmt, "i", $reff_id);
    mysqli_stmt_execute($stmt);
    $userResult = mysqli_stmt_get_result($stmt);

    if ($user = mysqli_fetch_assoc($userResult)) {
        $user['total'] = $ref_count;
        $topReferrers[] = $user;
    }
}

// CSV EXPORT IF ?download=1
if (isset($_GET['download']) && $_GET['download'] == '1') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="top_referrers.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Sr #', 'Email', 'Name', 'Phone', 'Total Referrals']);

    $sr = 1;
    foreach ($topReferrers as $user) {
        fputcsv($output, [
            $sr++,
            $user['email'],
            $user['name'],
            $user['ph'],
            $user['total']
        ]);
    }

    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Top 50 Referrers</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f9f9f9;
            padding: 40px;
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
        }

        .btn-download {
            display: block;
            width: fit-content;
            margin: 0 auto 30px;
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            font-weight: bold;
            border-radius: 5px;
        }

        .btn-download:hover {
            background-color: #218838;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        th, td {
            padding: 12px;
            border: 1px solid #ccc;
            text-align: center;
        }

        th {
            background-color: #333;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

    </style>
</head>
<body>

    <h2>🏆 Top 300 Referrers</h2>

    <a href="?download=1" class="btn-download">⬇️ Download CSV</a>

    <table>
        <thead>
            <tr>
                <th>Sr #</th>
                <th>Email</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Total Referrals</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $sr = 1;
            foreach ($topReferrers as $user): ?>
                <tr>
                    <td><?= $sr++ ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['name']) ?></td>
                    <td><?= htmlspecialchars($user['ph']) ?></td>
                    <td><strong><?= $user['total'] ?></strong></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>
</html>
