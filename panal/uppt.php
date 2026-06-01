<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include '../private/ap_block.php';
include '../private/unblock_updates.php';
include('../connection.php');

// counters
$records_updated = 0;
$users_updated = 0;
$not_found = 0;

// get all earnings user_ids
$get = mysqli_query($conn, "SELECT DISTINCT user_id FROM earnings");

while ($row = mysqli_fetch_assoc($get)) {

    $user_id = (int)$row['user_id'];

    // ================= CHECK IN RECORDS =================
    $check_record = mysqli_query($conn, "SELECT status FROM records WHERE id = $user_id LIMIT 1");

    if (mysqli_num_rows($check_record) > 0) {

        $record = mysqli_fetch_assoc($check_record);

        if ($record['status'] != 'active') {

            mysqli_query($conn, "UPDATE records SET status = 'active' WHERE id = $user_id");
            $records_updated++;
        }

    } else {
        $not_found++;
        continue;
    }

    // ================= CHECK IN USERS =================
    $check_user = mysqli_query($conn, "SELECT status FROM users WHERE id = $user_id LIMIT 1");

    if (mysqli_num_rows($check_user) > 0) {

        $user = mysqli_fetch_assoc($check_user);

        if ($user['status'] != 'active') {

            mysqli_query($conn, "UPDATE users SET status = 'active' WHERE id = $user_id");
            $users_updated++;
        }

    } else {
        $not_found++;
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Sync Status</title>
    <style>
        body{
            font-family:Arial;
            background:#f4f4f4;
            display:flex;
            justify-content:center;
            align-items:center;
            height:100vh;
        }
        .box{
            background:#8E44AD;
            color:white;
            padding:30px;
            border-radius:12px;
            width:350px;
            text-align:center;
            box-shadow:0 5px 15px rgba(0,0,0,0.3);
        }
        h2{
            margin-bottom:20px;
        }
        .item{
            margin:10px 0;
            font-size:18px;
        }
    </style>
</head>
<body>

<div class="box">

    <h2>Sync Completed</h2>

    <div class="item">Records Updated: <?php echo $records_updated; ?></div>
    <div class="item">Users Updated: <?php echo $users_updated; ?></div>
    <div class="item">Not Found: <?php echo $not_found; ?></div>

</div>

</body>
</html>