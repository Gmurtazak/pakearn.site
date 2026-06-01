<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

$searchUniqueId = '';
$records = [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $searchUniqueId = trim($_POST['search_unique_id']);
    
    if (!empty($searchUniqueId)) {
        $stmt = $conn->prepare("SELECT * FROM records WHERE unique_id = ?");
        $stmt->bind_param("s", $searchUniqueId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $records = $result->fetch_all(MYSQLI_ASSOC);
        } else {
            $error = "No records found with this Unique ID";
        }
        
        $stmt->close();
    } else {
        $error = "Please enter a Unique ID to search";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Records Search | Unique ID</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Pacifico&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #8e44ad;
            --secondary: #9b59b6;
            --light: #f5f5f5;
            --dark: #333;
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Montserrat', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f9f9f9 0%, #e8e8e8 100%);
            min-height: 100vh;
            padding: 20px;
            color: var(--dark);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Header with beautiful gradient */
        .header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
            padding-bottom: 20px;
        }
        
        .header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border-radius: 2px;
        }
        
        .header h1 {
            font-size: 3rem;
            margin-bottom: 10px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
        }
        
        .header p {
            font-size: 1.1rem;
            color: #666;
        }
        
        /* Search box with floating effect */
        .search-box {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: var(--shadow);
            margin-bottom: 40px;
            text-align: center;
            transition: all 0.3s;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .search-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        
        .search-form {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            position: relative;
        }
        
        .search-icon {
            position: absolute;
            left: 20px;
            color: var(--primary);
            font-size: 1.2rem;
        }
        
        .search-input {
            padding: 15px 20px 15px 50px;
            border: 2px solid #e0e0e0;
            border-radius: 50px;
            font-size: 1rem;
            width: 400px;
            max-width: 100%;
            transition: all 0.3s;
            background: #f9f9f9;
        }
        
        .search-input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(142, 68, 173, 0.2);
            background: white;
        }
        
        .search-btn {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 50px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(142, 68, 173, 0.3);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(142, 68, 173, 0.4);
        }
        
        /* Error message styling */
        .error-message {
            color: #e74c3c;
            margin-top: 20px;
            text-align: center;
            font-weight: 500;
            background: rgba(231, 76, 60, 0.1);
            padding: 10px;
            border-radius: 5px;
            display: inline-block;
        }
        
        /* Records container with beautiful grid */
        .records-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        
        /* Gorgeous record cards */
        .record-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: var(--shadow);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            border-top: 5px solid var(--primary);
        }
        
        .record-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }
        
        .record-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        
        .record-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px dashed #eee;
        }
        
        .user-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .record-title {
            font-weight: 600;
            font-size: 1.2rem;
            color: var(--primary);
        }
        
        .record-field {
            margin-bottom: 12px;
            display: flex;
            align-items: center;
        }
        
        .field-icon {
            width: 30px;
            height: 30px;
            background: rgba(142, 68, 173, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            margin-right: 10px;
            flex-shrink: 0;
        }
        
        .field-label {
            font-weight: 600;
            min-width: 100px;
            color: #666;
        }
        
        .field-value {
            color: var(--dark);
            word-break: break-word;
        }
        
        /* Beautiful view button */
        .view-btn {
            display: block;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            text-decoration: none;
            padding: 12px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 1rem;
            transition: all 0.3s;
            text-align: center;
            width: 100%;
            border: none;
            cursor: pointer;
            font-weight: 500;
            box-shadow: 0 4px 10px rgba(142, 68, 173, 0.3);
        }
        
        .view-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(142, 68, 173, 0.4);
        }
        
        .view-btn i {
            margin-left: 5px;
        }
        
        /* No records message */
        .no-records {
            text-align: center;
            color: #666;
            font-size: 1.2rem;
            margin-top: 40px;
            grid-column: 1 / -1;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: var(--shadow);
        }
        
        .no-records i {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 15px;
            display: block;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .header h1 {
                font-size: 2.2rem;
            }
            
            .search-form {
                flex-direction: column;
            }
            
            .search-input {
                width: 100%;
                padding-left: 45px;
            }
            
            .search-btn {
                width: 100%;
                justify-content: center;
            }
            
            .records-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Find User Records</h1>
            <p>Search by Unique ID to view user details</p>
        </div>
        
        <div class="search-box">
            <form class="search-form" method="POST" action="">
                <input 
                    type="text" 
                    class="search-input" 
                    name="search_unique_id" 
                    placeholder="Enter TRX_ID..." 
                    value="<?php echo htmlspecialchars($searchUniqueId); ?>"
                    required
                >
                <button type="submit" class="search-btn">
                    <i class="fas fa-search"></i> Search
                </button>
            </form>
            
            <?php if (!empty($error)): ?>
                <p class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></p>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($records)): ?>
            <div class="records-container">
                <?php foreach ($records as $record): ?>
                    <div class="record-card">
                        <div class="record-header">
                            <div class="user-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="record-title">User Details</div>
                        </div>
                        
                        <div class="record-field">
                            <div class="field-icon">
                                <i class="fas fa-id-card"></i>
                            </div>
                            <div class="field-label">TRX_ID:</div>
                            <div class="field-value"><?php echo htmlspecialchars($record['unique_id']); ?></div>
                        </div>
                        
                        <div class="record-field">
                            <div class="field-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="field-label">Email:</div>
                            <div class="field-value"><?php echo htmlspecialchars($record['email']); ?></div>
                        </div>
                        
                        <div class="record-field">
                            <div class="field-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="field-label">Phone:</div>
                            <div class="field-value"><?php echo htmlspecialchars($record['sphone']); ?></div>
                        </div>
                        
                        
                        
                        <a 
                            href="smart_login4.php?id=<?php echo htmlspecialchars($record['id']); ?>" 
                            class="view-btn"
                        >
                            View Full Profile <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)): ?>
            <div class="no-records">
                <i class="fas fa-user-slash"></i>
                <p>No user records found with this Unique ID</p>
                <p>Please try a different Unique ID</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>