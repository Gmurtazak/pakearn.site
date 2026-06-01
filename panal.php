<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'connection.php';

// Array to hold queries and their respective counts
$queries = [
    "total_members" => "SELECT COUNT(*) AS count FROM records WHERE status = 'active' AND newapprove=1",
    "total_members2" => "SELECT COUNT(*) AS count FROM records WHERE status = 'active' AND newapprove=0",
    "member_pending" => "SELECT COUNT(*) AS count FROM records WHERE status = 'pending' AND unique_id!=0",
    "withdraw_pending" => "SELECT COUNT(*) AS count FROM withdrawals WHERE status = 'pending'",
    "total_users_active" => "SELECT COUNT(*) AS count FROM users WHERE status = 'active'",
    "withdraw_active" => "SELECT COUNT(*) AS count FROM withdrawals WHERE status = 'active'",
    "user_pending" => "SELECT COUNT(*) AS count FROM users WHERE status = 'pending'",
    "tasks_pending" => "SELECT COUNT(*) AS count FROM tasks WHERE status = 'pending'",
    "tasks_approved" => "SELECT COUNT(*) AS count FROM tasks WHERE status = 'approved'",
    "total_videos" => "SELECT COUNT(*) AS count FROM videos",
    "email_requests" => "SELECT COUNT(*) AS count FROM email_requests",
];

// Array to hold results
$results = [];

// Execute queries
foreach ($queries as $key => $query) {
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $results[$key] = $row['count'];
    } else {
        $results[$key] = 0; // Default value if no records found
    }
}
$sql = "SELECT COUNT(*) AS active_count15 FROM tasks WHERE status = 'approved'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Fetching result
    $row = $result->fetch_assoc();
    $activeCount15 = $row['active_count15'];
} else {
    $activeCount15 = 0; // Default value if no active records found
}
$sql = "SELECT COUNT(*) AS active_count14 FROM imgg ";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Fetching result
    $row = $result->fetch_assoc();
    $activeCount14 = $row['active_count14'];
} else {
    $activeCount14 = 0; // Default value if no active records found
}
$sql = "SELECT COUNT(*) AS active_count13 FROM tasks WHERE status = 'pending'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Fetching result
    $row = $result->fetch_assoc();
    $activeCount13 = $row['active_count13'];
} else {
    $activeCount13 = 0; // Default value if no active records found
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" type="x-icon" href="hum.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta property="og:title" content="My Admin Panel">
    <meta property="og:site_name" content="Typing Work">
    <meta property="og:url" content="https://typingwork.kesug.com/panal.php">
    <meta property="og:description" content="This is Admin Panel.">
    <meta property="og:type" content="website">
    <meta property="og:image" content="https://typingwork.kesug.com/um.png">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            text-align: center;
        }

        .main-box {
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            padding: 20px;
            width: 90%;
        }

        .welcome {
            font-size: 2.5em;
            margin-bottom: 20px;
            color: #333;
        }

        .grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
        }

        .box {
            background: #8E44AD; /* Purple background */
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            padding: 20px;
            width: calc(50% - 10px);
            max-width: 280px;
            text-align: center;
            transition: transform 0.3s;
            color: #fff;
        }

        .box:hover {
            transform: translateY(-10px);
        }

        .box h2 {
            font-size: 1.8em;
            margin: 0 0 10px;
            color: #fff;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }

        .box p {
            font-size: 1.5em;
            margin: 10px 0;
            color: #fff;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }

        .box i {
            margin-bottom: 20px;
            color: yellow;
        }

        @media (max-width: 600px) {
            .box {
                width: 30%;
            }
            .box h2 {
                font-size: 1.3em;
                margin: 0 0 10px;
                color: #fff;
                text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="main-box">
        <div class="welcome">Admin Panel</div>

        <div class="grid">

            <div class="box" onclick="window.location.href='panal/tomem.php';">
                <i class="fas fa-users fa-3x"></i>
                <h2>Total Members</h2>
                <p><?php echo $results['total_members']; ?></p>
            </div>

            <div class="box" onclick="window.location.href='panal/pemem.php';">
                <i class="fas fa-user-clock fa-3x"></i>
                <h2>Member Pending</h2>
                <p><?php echo $results['member_pending']; ?></p>
            </div>

            <div class="box" onclick="window.location.href='newtomem.php';">
                <i class="fas fa-user-check fa-3x"></i>
                <h2>Approve Member</h2>
                <p><?php echo $results['total_members2']; ?></p>
            </div>

            <div class="box" onclick="window.location.href='panal/registered_user.php';">
                <i class="fas fa-user-plus fa-3x"></i>
                <h2>Registered User</h2>
            </div>

            <div class="box" onclick="window.location.href='panal/search.php';">
                <i class="fas fa-search fa-3x"></i>
                <h2>Search User</h2>
                <p><?php echo $results['total_users_active']; ?></p>
            </div>

            <div class="box" onclick="window.location.href='panal/papro.php';">
                <i class="fas fa-key fa-3x"></i>
                <h2>Password Issue</h2>
                <p><?php echo $results['email_requests']; ?></p>
            </div>

            <div class="box" onclick="window.location.href='edittxt.php';">
                <i class="fas fa-pen-alt fa-3x"></i>
                <h2>Update Moving Text</h2>
            </div>

            <div class="box" onclick="window.location.href='stop2.php';">
                <i class="fas fa-ad fa-3x"></i>
                <h2>Check Ads Stop</h2>
            </div>
            <div class="box" onclick="window.location.href='usr.php';">
                <i class="fas fa-ad fa-3x"></i>
                <h2>User Panels</h2>
            </div>
            <div class="box" onclick="window.location.href='banany.php';">
                <i class="fas fa-user-lock fa-3x"></i>
                <h2>Ban User</h2>
            </div>

            <div class="box" onclick="window.location.href='chan_contact.php';">
                <i class="fas fa-link fa-3x"></i>
                <h2>Channel Link</h2>
            </div>

            <div class="box" onclick="window.location.href='panal/withpen.php';">
                <i class="fas fa-wallet fa-3x"></i>
                <h2>Withdraw Pending</h2>
                <p><?php echo $results['withdraw_pending']; ?></p>
            </div>

            <div class="box" onclick="window.location.href='panal/udelete.php';">
                <i class="fas fa-user-minus fa-3x"></i>
                <h2>Remove Member</h2>
            </div>

            <div class="box" onclick="window.location.href='account_upgrade.php';">
                <i class="fas fa-level-up-alt fa-3x"></i>
                <h2>Upgrade Package</h2>
            </div>

            <div class="box" onclick="window.location.href='top.php';">
                <i class="fas fa-trophy fa-3x"></i>
                <h2>Top Referral</h2>
            </div>

            <div class="box" onclick="window.location.href='upt.php';">
                <i class="fas fa-stopwatch fa-3x"></i>
                <h2>Update Timer</h2>
            </div>

            <div class="box" onclick="window.location.href='update_status.php';">
                <i class="fas fa-sync-alt fa-3x"></i>
                <h2>Status Update</h2>
            </div>

            <div class="box" onclick="window.location.href='earnup.php';">
                <i class="fas fa-coins fa-3x"></i>
                <h2>Update Price Values</h2>
            </div>

            <div class="box" onclick="window.location.href='trxid.php';">
                <i class="fas fa-receipt fa-3x"></i>
                <h2>Check TRXid</h2>
            </div>

            <div class="box" onclick="window.location.href='indirect.php';">
                <i class="fas fa-project-diagram fa-3x"></i>
                <h2>Indirect 1</h2>
            </div>

            <div class="box" onclick="window.location.href='indirect2.php';">
                <i class="fas fa-sitemap fa-3x"></i>
                <h2>Indirect 2</h2>
            </div>

            <div class="box" onclick="window.location.href='lock.php';">
                <i class="fas fa-lock fa-3x"></i>
                <h2>Lock/Unlock</h2>
            </div>

            <div class="box" onclick="window.location.href='upref.php';">
                <i class="fas fa-hand-holding-usd fa-3x"></i>
                <h2>Update Referral Price</h2>
            </div>

            <div class="box" onclick="window.location.href='rejected.php';">
                <i class="fas fa-user-times fa-3x"></i>
                <h2>Rejected Members</h2>
            </div>

            <div class="box" onclick="window.location.href='notify.php';">
                <i class="fas fa-bell fa-3x"></i>
                <h2>Notify</h2>
            </div>

            <div class="box" onclick="window.location.href='update_account_details.php';">
                <i class="fas fa-university fa-3x"></i>
                <h2>Update Accounts</h2>
            </div>

            <div class="box" onclick="window.location.href='sumearning.php';">
                <i class="fas fa-dollar-sign fa-3x"></i>
                <h2>Add Price</h2>
            </div>

            <div class="box" onclick="window.location.href='admin_settings.php';">
                <i class="fas fa-money-check-alt fa-3x"></i>
                <h2>Change Withdraw</h2>
            </div>

            <div class="box" onclick="window.location.href='panal/withhis.php';">
                <i class="fas fa-history fa-3x"></i>
                <h2>Withdraw History</h2>
                <p><?php echo $results['withdraw_active']; ?></p>
            </div>

            <div class="box" onclick="location.href='panal/noactive.php'">
                <i class="fas fa-user-slash fa-3x"></i>
                <h2>Non Active Members</h2>
                <p><?php echo $results['user_pending']; ?></p>
            </div>

            <div class="box" onclick="location.href='update_link.php'">
                <i class="fas fa-link fa-3x"></i>
                <h2>Update Contact Link</h2>
            </div>
            <div class="box" onclick="location.href='withme.php'">
                <i class="fas fa-link fa-3x"></i>
                <h2>Withdraw Timeline</h2>
            </div>
            <div class="box" onclick="location.href='upt.php'">
                <i class="fas fa-link fa-3x"></i>
                <h2>Update Timer</h2>
            </div>
            <div class="box" onclick="location.href='admin_logout.php'">
                <i class="fas fa-sign-out-alt fa-3x"></i>
                <h2>Logout</h2>
            </div>

        </div>
    </div>
</div>

</body>
</html>
