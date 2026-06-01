<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

// Function to fetch notifications
function getNotifications($conn, $user_id) {
    $query = "SELECT * FROM notify WHERE user_id = '$user_id' ORDER BY created_at DESC";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Check for ID in URL or email submission
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$user_id && isset($_POST['email'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $query = "SELECT id FROM users WHERE email = '$email' LIMIT 1";
    $result = mysqli_query($conn, $query);
    if ($row = mysqli_fetch_assoc($result)) {
        $user_id = $row['id'];
    }
}

$notifications = [];
if ($user_id) {
    $notifications = getNotifications($conn, $user_id);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Notifications</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #4a6fa5;
            --secondary: #6b8cae;
            --accent: #ff7e5f;
            --light: #f8f9fa;
            --dark: #2d3e50;
            --text: #4a4a4a;
            --success: #4caf50;
            --card-bg: #ffffff;
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ed 100%);
            min-height: 100vh;
            padding: 40px 20px;
            color: var(--text);
            line-height: 1.6;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background-color: var(--card-bg);
            border-radius: 16px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }
        
        .header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
        }
        
        .header p {
            font-size: 1rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .back-btn {
            position: absolute;
            left: 25px;
            top: 50%;
            transform: translateY(-50%);
            color: white;
            font-size: 1.2rem;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            transform: translateY(-50%) scale(1.1);
        }
        
        .search-box {
            padding: 30px;
            background-color: #f8f9fa;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .search-form {
            display: flex;
            max-width: 600px;
            margin: 0 auto;
            border-radius: 50px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .search-input {
            flex: 1;
            padding: 15px 25px;
            border: none;
            font-size: 1rem;
            outline: none;
            background: white;
        }
        
        .search-btn {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border: none;
            padding: 0 30px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .search-btn:hover {
            opacity: 0.9;
            padding: 0 35px;
        }
        
        .notifications-container {
            padding: 30px;
        }
        
        .no-notifications {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .no-notifications i {
            font-size: 3rem;
            margin-bottom: 20px;
            color: var(--primary);
            opacity: 0.5;
        }
        
        .notification {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 25px;
            margin-bottom: 25px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            border-left: 4px solid var(--primary);
        }
        
        .notification:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.12);
        }
        
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .notification-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .notification-title i {
            color: var(--primary);
            font-size: 1.2rem;
        }
        
        .notification-date {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .notification-body {
            margin-bottom: 15px;
        }
        
        .notification-message {
            font-size: 1rem;
            margin-bottom: 10px;
            padding: 10px 0;
        }
        
        .amount {
            font-weight: 600;
            color: var(--success);
        }
        
        .notification-details {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px dashed #e9ecef;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            color: var(--text);
            gap: 8px;
        }
        
        .detail-item i {
            color: var(--primary);
            font-size: 0.9rem;
        }
        
        .error-message {
            text-align: center;
            padding: 40px 20px;
            color: #d63031;
        }
        
        .error-message i {
            font-size: 3rem;
            margin-bottom: 20px;
        }
        
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 12px 25px;
            border-radius: 50px;
            text-decoration: none;
            margin-top: 20px;
            font-weight: 500;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }
        
        @media (max-width: 768px) {
            .header h1 {
                font-size: 1.8rem;
                padding-top: 10px;
            }
            
            .header p {
                font-size: 0.9rem;
            }
            
            .back-btn {
                left: 15px;
                font-size: 1rem;
            }
            
            .search-form {
                flex-direction: column;
                border-radius: 12px;
            }
            
            .search-input {
                border-radius: 12px 12px 0 0;
            }
            
            .search-btn {
                padding: 15px;
                border-radius: 0 0 12px 12px;
                justify-content: center;
            }
            
            .notification-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .notification-date {
                align-self: flex-end;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <?php if ($user_id): ?>
                <a href="?" class="back-btn"><i class="fas fa-arrow-left"></i></a>
            <?php endif; ?>
            <h1><i class="fas fa-bell"></i> Account Notifications</h1>
            <p>View all your account activities and balance updates</p>
        </div>
        
        <?php if (!$user_id): ?>
            <div class="search-box">
                <form method="POST" class="search-form">
                    <input type="email" name="email" class="search-input" placeholder="Enter your email address..." required>
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i> Find Notifications
                    </button>
                </form>
            </div>
        <?php endif; ?>
        
        <div class="notifications-container">
            <?php if ($user_id && empty($notifications)): ?>
                <div class="no-notifications">
                    <i class="fas fa-bell-slash"></i>
                    <h3>No notifications found</h3>
                    <p>You don't have any notifications at this time.</p>
                </div>
            <?php elseif (isset($_POST['email']) && !$user_id): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <h3>Account not found</h3>
                    <p>We couldn't find an account with that email address.</p>
                    <a href="?" class="btn">Try Again</a>
                </div>
            <?php elseif ($user_id): ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification">
                        <div class="notification-header">
                            <div class="notification-title">
                                <i class="fas fa-<?php echo $notification['name'] ? 'user-tag' : 'coins'; ?>"></i>
                                <?php echo $notification['name'] ? 'Account Notification' : 'Balance Update'; ?>
                            </div>
                            <div class="notification-date">
                                <i class="far fa-calendar-alt"></i>
                                <?php echo date('j M, Y \a\t g:i A', strtotime($notification['created_at'])); ?>
                            </div>
                        </div>
                        
                        <div class="notification-body">
                            <div class="notification-message">
                                <?php if (empty($notification['name'])): ?>
                                    <span class="amount">+<?php echo number_format($notification['total_add'], 2); ?></span>
                                    <?php echo htmlspecialchars($notification['message']); ?>
                                <?php else: ?>
                                    <?php echo htmlspecialchars($notification['name']) . ' - ' . htmlspecialchars($notification['message']); ?>
                                    <span class="amount">+<?php echo number_format($notification['total_add'], 2); ?></span>

                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($notification['name']) || !empty($notification['old_earn'])): ?>
                            <div class="notification-details">
                                <div class="detail-item">
                                    <i class="fas fa-wallet"></i>
                                    Old Balance: <span class="amount"><?php echo number_format($notification['old_earn'], 2); ?></span>
                                </div>
                                
                                <div class="detail-item">
                                    <i class="fas fa-coins"></i>
                                    Current Balance: <span class="amount"><?php echo number_format($notification['current_earn'], 2); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>