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

// Fetch ID from URL
$id = isset($_GET['id']) ? $_GET['id'] : 0;

// Fetch user data from records table
$query = "SELECT email, password, name, phone,join_hoi FROM records WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($email, $password, $name, $phone,$time);
$stmt->fetch();

// Add prefix '0' to the phone number
$phone = '0' . ltrim($phone, '0');
if ($time) {
    $datetime = new DateTime($time);
    $formatted_date = $datetime->format('d:m:Y'); // Format date as dd:mm:yyyy
    $formatted_time = $datetime->format('h:i A'); // Format time as hh:mm AM/PM
} else {
    $formatted_date = "N/A"; // Handle case where no time is available
    $formatted_time = "N/A";
}
// Fetch level and reward from level table
$query_level = "SELECT level, reward FROM level WHERE us_id = ?";
$stmt_level = $conn->prepare($query_level);
$stmt_level->bind_param("i", $id);
$stmt_level->execute();
$stmt_level->store_result();
$stmt_level->bind_result($level, $reward);
$stmt_level->fetch();

// Fetch price from plan table matching user_id
$query_plan = "SELECT price FROM plan WHERE user_id = ?";
$stmt_plan = $conn->prepare($query_plan);
$stmt_plan->bind_param("i", $id);
$stmt_plan->execute();
$stmt_plan->store_result();
$stmt_plan->bind_result($price);
$stmt_plan->fetch();

// Sum active withdrawals by matching user_id
$query_withdrawals = "SELECT SUM(amount) FROM withdrawals WHERE user_id = ? AND status = 'active'";
$stmt_withdrawals = $conn->prepare($query_withdrawals);
$stmt_withdrawals->bind_param("i", $id);
$stmt_withdrawals->execute();
$stmt_withdrawals->store_result();
$stmt_withdrawals->bind_result($withdrawals_sum);
$stmt_withdrawals->fetch();
$withdrawals_sum = $withdrawals_sum ? $withdrawals_sum : 0; // Set to 0 if no records found

// Fetch reff_id from users table (Leader's ID)
$query_reff = "SELECT reff_id FROM users WHERE id = ?";
$stmt_reff = $conn->prepare($query_reff);
$stmt_reff->bind_param("i", $id);
$stmt_reff->execute();
$stmt_reff->store_result();
$stmt_reff->bind_result($reff_id);
$stmt_reff->fetch();

// Fetch leader data using reff_id
$query_leader = "SELECT name, email, phone FROM records WHERE id = ?";
$stmt_leader = $conn->prepare($query_leader);
$stmt_leader->bind_param("i", $reff_id);
$stmt_leader->execute();
$stmt_leader->store_result();
$stmt_leader->bind_result($leader_name, $leader_email, $leader_phone);
$stmt_leader->fetch();

// Add prefix '0' to the leader's phone number
$leader_phone = '0' . ltrim($leader_phone, '0');

// Count active referrals
$query_referrals = "SELECT COUNT(*) FROM users WHERE reff_id = ? AND status = 'active'";
$stmt_referrals = $conn->prepare($query_referrals);
$stmt_referrals->bind_param("i", $id);
$stmt_referrals->execute();
$stmt_referrals->store_result();
$stmt_referrals->bind_result($active_referrals_count);
$stmt_referrals->fetch();

// Fetch total earning from earnings table
$query_earnings = "SELECT total_earning FROM earnings WHERE id = ?";
$stmt_earnings = $conn->prepare($query_earnings);
$stmt_earnings->bind_param("i", $id);
$stmt_earnings->execute();
$stmt_earnings->store_result();
$stmt_earnings->bind_result($total_earning);
$stmt_earnings->fetch();
$total_earning = $total_earning ? $total_earning : 0; // Set to 0 if no records found

// Sum of total earnings and withdrawals
$total_earnings_sum = $withdrawals_sum + $total_earning;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Info & Leader Info</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #2C3E50;
            margin: 0;
            padding: 0;
            user-select:none;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #fff;
        }
        .container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 40px;
            max-width: 900px;
            width: 90%;
            box-shadow: 0 30px 40px rgba(0, 0, 0, 0.3);
            text-align: left;
        }
        h2 {
            font-size: 28px;
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 3px;
            text-align: center;
            color: #fff;
        }

        /* Personal Info */
        .personal-info {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center; /* Center content */
        }
        .personal-info .card {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            border-radius: 10px;
            padding-top: 10px;
            width: 100%; /* Full width on mobile */
            max-width: 100%; /* Ensure full width */
            box-shadow: 0px 10px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
            text-align: center; /* Center text */
        }
        .personal-info .card:hover {
            transform: translateY(-5px);
        }

        /* Info boxes (Level, Reward, etc.) */
        .grid-boxes {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        .info-box {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            border-radius: 10px;
            padding: 20px;
            padding-top:25px;
            text-align: center; /* Center text */
            box-shadow: 0px 10px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        .info-box:hover {
            transform: translateY(-5px);
        }

        /* Leader Box Styling */
        .leader-box {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0px 15px 25px rgba(0, 0, 0, 0.3);
            text-align: center; /* Center text */
            transition: all 0.3s ease;
        }
        .leader-box:hover {
            transform: translateY(-10px) scale(1.05);
        }
        .leader-box h2 {
            color: #fff; /* Gold color */
            margin-bottom: 20px;
        }
        .leader-box p {
            font-size: 18px;
            color: #fff;
            margin: 5px 0;
            padding: 5px; /* Add padding for spacing */
            border-radius: 5px; /* Rounded corners */
            background: rgba(0, 0, 0, 0.2); /* Slightly darker background */
        }

        .back-button {
            display: block;
            margin: 20px auto;
            padding: 12px 30px;
            background: #ff5e62;
            color: #fff;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            border-radius: 5px;
            text-decoration: none;
            transition: background 0.3s;
            text-align: center;
        }
         .time-display {
            font-size: 18px;
            font-weight: bold;
            color: #FFF;
            margin-top: 10px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            padding: 10px;
        }
        .back-button:hover {
            background: #ff9966;
        }

        @media screen and (max-width: 768px) {
            .personal-info .card {
                width: 100%; /* Full width on mobile */
            }
            .grid-boxes {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h2 id="personal-heading">My Info<hr></h2>

    <!-- My Personal Info -->
    <div class="personal-info">
        <div class="card">
            <strong>Email:</strong> <p><?php echo htmlspecialchars($email); ?></p>
        </div>
        
        <div class="card">
            <strong>Name:</strong> <p><?php echo htmlspecialchars($name); ?></p>
        </div>
        <div class="card">
            <strong>Phone:</strong> <p><?php echo htmlspecialchars($phone); ?></p>
        </div>
         <div class="card time-display">
            <strong>Joining Date:</strong> <p><?php echo htmlspecialchars($formatted_date); ?></p>
            <strong>Time:</strong> <p><?php echo htmlspecialchars($formatted_time); ?></p>
        </div>
    </div>

    <h2 id="additional-heading"><hr>My Profile </h2>

    <!-- Info boxes (Level, Reward, etc.) -->
    <div class="grid-boxes">
        <div class="info-box">
            <strong>Level:</strong> <p><?php echo htmlspecialchars($level); ?></p>
        </div>
         <div class="info-box">
            <strong>Current Balance:</strong> <p><?php echo htmlspecialchars($total_earning); ?></p>
        </div>
        <div class="info-box">
            <strong>Plan Price:</strong> <p><?php echo htmlspecialchars($price); ?></p>
        </div>
       
        <div class="info-box">
            <strong>Total Withdrawals:</strong> <p><?php echo htmlspecialchars($withdrawals_sum); ?></p>
        </div>
        <div class="info-box">
            <strong>Total Earnings:</strong> <p><?php echo htmlspecialchars($total_earnings_sum); ?></p>
        </div>
        <div class="info-box">
            <strong>My Team Members:</strong> <p><?php echo htmlspecialchars($active_referrals_count); ?></p>
        </div>
    </div>

    <h2 id="leader-heading"><hr>Leader Info</h2>

    <!-- My Leader Info -->
    <div class="leader-box">
        <h2>Leader</h2>
        <p> <?php echo htmlspecialchars($reff_id); ?></p>
        <p><?php echo htmlspecialchars($leader_name); ?></p>
        <p><?php echo htmlspecialchars($leader_email); ?></p>
    </div>

    <!-- Back Button -->
    <a href="javascript:history.back()" class="back-button">Go Back</a>
</div>

</body>
</html>
