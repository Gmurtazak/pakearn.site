<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';
// Initialize variables
$id = 0;
$email = '';
$user_name = '';
$error = '';

// Check if email is submitted
if (isset($_POST['email']) && !empty($_POST['email'])) {
    $email = trim($_POST['email']);
    
    // Get user ID from email
    $userQuery = "SELECT id, name FROM users WHERE email = ?";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $id = $user['id'];
        $user_name = $user['name'];
    } else {
        $error = "No user found with this email address";
    }
} elseif (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Get user name from ID
    $userQuery = "SELECT name FROM users WHERE id = ?";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_name = $user['name'];
    }
}

// Function to format date
function formatDate($date) {
    return date('d M Y h:i A', strtotime($date));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdrawal Records Timeline</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #6c5ce7;
            --secondary: #a29bfe;
            --accent: #fd79a8;
            --light: #f8f9fa;
            --dark: #2d3436;
            --success: #00b894;
            --warning: #fdcb6e;
            --danger: #d63031;
            --info: #0984e3;
            --white: #ffffff;
            --gray: #dfe6e9;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: #f5f7fa;
            min-height: 100vh;
            padding: 20px;
            color: var(--dark);
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background-color: var(--white);
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        
        header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--info) 100%);
            color: var(--white);
            padding: 25px;
            text-align: center;
            position: relative;
        }
        
        header h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        header p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .user-info {
            background-color: var(--white);
            border-radius: 10px;
            padding: 15px;
            margin: -15px auto 20px;
            width: calc(100% - 40px);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .user-info .user-name {
            font-weight: 600;
            color: var(--primary);
            font-size: 16px;
        }
        
        .user-info .login-btn {
            background: linear-gradient(135deg, var(--accent) 0%, var(--danger) 100%);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 50px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 13px;
        }
        
        .email-form {
            background-color: var(--white);
            border-radius: 10px;
            padding: 20px;
            margin: 20px auto;
            width: calc(100% - 40px);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            text-align: center;
        }
        
        .email-form h2 {
            color: var(--primary);
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid var(--gray);
            border-radius: 50px;
            font-size: 14px;
        }
        
        .submit-btn {
            background: linear-gradient(135deg, var(--primary) 0%, var(--info) 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            width: 100%;
        }
        
        .error-message {
            color: var(--danger);
            text-align: center;
            margin: 15px;
            font-weight: 500;
        }
        
        .timeline {
            padding: 0 20px 30px;
            
        }
        
        
        
        .withdrawal-box {
            background-color: var(--white);
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            padding: 20px;
            position: relative;
            border: 3px solid purple;
            margin-bottom: 15px;
        }
        
        .withdrawal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--gray);
        }
        
        .withdrawal-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .withdrawal-id {
            color: var(--primary);
            font-weight: 700;
        }
        
        .withdrawal-status {
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-pending {
            background-color: var(--warning);
            color: #856404;
        }
        
        .status-completed {
            background-color: var(--success);
            color: white;
        }
        
        .status-failed {
            background-color: var(--danger);
            color: white;
        }
        
        .withdrawal-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .detail-item {
            margin-bottom: 8px;
        }
        
        .detail-label {
            font-size: 12px;
            color: var(--dark);
            opacity: 0.7;
            margin-bottom: 3px;
            font-weight: 500;
        }
        
        .detail-value {
            font-size: 14px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .user-records {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px dashed var(--gray);
        }
        
        .user-records-title {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .user-record {
            background-color: var(--light);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 10px;
            border-left: 3px solid var(--secondary);
        }
        
        .user-record-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .user-record-id {
            font-weight: 700;
            color: var(--primary);
            font-size: 13px;
        }
        
        .user-record-login {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 3px 8px;
            border-radius: 50px;
            font-size: 11px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }
        
        .user-record-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }
        
        .no-records {
            text-align: center;
            padding: 15px;
            color: var(--dark);
            opacity: 0.7;
            font-style: italic;
            background-color: var(--light);
            border-radius: 8px;
            font-size: 13px;
        }
        
        @media (max-width: 600px) {
            .withdrawal-details,
            .user-record-details {
                grid-template-columns: 1fr;
            }
            
            header {
                padding: 20px 15px;
            }
            
            header h1 {
                font-size: 20px;
            }
            
            .container {
                border-radius: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1> Withdrawal History</h1>
            <p>View your complete withdrawal timeline</p>
        </header>
        
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($id == 0): ?>
            <div class="email-form">
                <h2><i class="fas fa-envelope"></i> Enter Your Email</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <input type="email" name="email" placeholder="Your registered email" required>
                    </div>
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-search"></i> View Records
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="user-info" style="margin-top:20px;">
                <div class="user-name">
                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($user_name); ?>
                </div>
                <a href="smart_login4.php?id=<?php echo $id; ?>" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
            </div>
            
<div class="timeline">
    <?php
    // Get all withdrawal records for this ID (user_id) ordered by date
    $withdrawalQuery = "SELECT * FROM withdrawals WHERE user_id = ? ORDER BY created_at ASC";
    $stmt = $conn->prepare($withdrawalQuery);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $withdrawalResult = $stmt->get_result();
    
    if ($withdrawalResult->num_rows > 0) {
        $previousDate = null;
        $withdrawals = $withdrawalResult->fetch_all(MYSQLI_ASSOC);
        
        foreach ($withdrawals as $index => $withdrawal) {
            $currentDate = $withdrawal['created_at'];
    ?>
            <div class="timeline-item">
                <div class="withdrawal-box">
                    <div class="withdrawal-header">
                        <div class="withdrawal-title">
                            <i class="fas fa-money-bill-wave"></i> Withdrawal 
                            <span class="withdrawal-id">#<?php echo $index + 1; ?></span> <!-- Serial Number Display -->
                        </div>
                        <div class="withdrawal-status status-<?php echo strtolower($withdrawal['status']); ?>">
                            <?php echo $withdrawal['status']; ?>
                        </div>
                    </div>
                    
                    <div class="withdrawal-details">
                        <div class="detail-item">
                        <div class="detail-item">
                            <div class="detail-label">Name</div>
                            <div class="detail-value"><?php echo htmlspecialchars($withdrawal['name']); ?></div>
                        </div>
                        <div class="detail-label">Amount</div>
                            <div class="detail-value"><?php echo number_format($withdrawal['amount'], 2); ?> pkr</div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Method</div>
                            <div class="detail-value"><?php echo htmlspecialchars($withdrawal['payment_method']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Name</div>
                            <div class="detail-value"><?php echo htmlspecialchars($withdrawal['payment_method']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Date</div>
                            <div class="detail-value"><?php echo formatDate($withdrawal['created_at']); ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Phone</div>
                            <div class="detail-value"><?php echo htmlspecialchars($withdrawal['phone']); ?></div>
                        </div>
                    </div>
                    
                    <div class="user-records">
                        <div style="text-align: center; margin: 15px 0;">
                            <?php
                            // Beautiful styling
                            $style = "display: inline-block; padding: 4px 10px; margin: 0 5px; background: #e0f7fa; 
                                      border-radius: 10px; font-size: 14px; color: #00796b; font-weight: 500;";
                        
                            // Format: 02-Feb
                            if ($index === 0) {
                                echo '<span style="' . $style . '">' . date('d-M', strtotime($currentDate)) . '</span>';
                            } else {
                                echo '<span style="' . $style . '">' . date('d-M', strtotime($previousDate)) . '</span>';
                                echo '<span style="margin: 0 5px; color: #888;">to</span>';
                                echo '<span style="' . $style . '">' . date('d-M', strtotime($currentDate)) . '</span>';
                            }
                            ?>
                        </div>
                        <?php
                       if ($index === 0) {
                            $userQuery = "SELECT u.id, u.name, u.email, r.join_hoi ,r.amount,r.status ,r.unique_id
                                         FROM users u 
                                         JOIN records r ON u.id = r.id 
                                         WHERE u.reff_id = ? 
                                         AND r.join_hoi <= ? AND r.status ='active'
                                         ORDER BY r.join_hoi DESC";
                            $stmtUsers = $conn->prepare($userQuery);
                            $stmtUsers->bind_param("is", $id, $currentDate);
                        } else {
                            $userQuery = "SELECT u.id, u.name, u.email, r.join_hoi ,r.amount,r.status ,r.unique_id
                                         FROM users u 
                                         JOIN records r ON u.id = r.id 
                                         WHERE u.reff_id = ? 
                                         AND r.join_hoi > ? 
                                         AND r.join_hoi <= ? AND r.status ='active'
                                         ORDER BY r.join_hoi DESC";
                            $stmtUsers = $conn->prepare($userQuery);
                            $stmtUsers->bind_param("iss", $id, $previousDate, $currentDate);
                        }

                        
                        $stmtUsers->execute();
                        $userResult = $stmtUsers->get_result();
                        
                        if ($userResult->num_rows > 0) {
                            while ($user = $userResult->fetch_assoc()) {
                        ?>
                                <div class="user-record">
                                    <div class="user-record-header">
                                        <div class="user-record-id">User #<?php echo $user['id']; ?></div>
                                        <a href="smart_login4.php?id=<?php echo $user['id']; ?>" class="user-record-login">
                                            <i class="fas fa-sign-in-alt"></i> Login
                                        </a>
                                    </div>
                                    <div class="user-record-details">
                                        <div class="detail-item">
                                            <div class="detail-label">TRX_Id</div>
                                            <div class="detail-value"><?php echo htmlspecialchars($user['unique_id']); ?></div>
                                        </div>
                                        <div class="detail-item">
                                            <div class="detail-label">Plan</div>
                                            <div class="detail-value"><?php echo htmlspecialchars($user['amount']); ?></div>
                                        </div>
                                        <div class="detail-item">
                                            <div class="detail-label">Joined</div>
                                            <div class="detail-value"><?php echo formatDate($user['join_hoi']); ?></div>
                                        </div>
                                    </div>
                                </div>
                        <?php
                            }
                        } else {
                            echo '<div class="no-records">No users found in this period</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
    <?php
            // Update previous date for next iteration
            $previousDate = $currentDate;
        }
    } else {
        echo '<div class="no-records">No withdrawal records found for this user</div>';
    }
    ?>
</div>
        <?php endif; ?>
    </div>
</body>
</html>