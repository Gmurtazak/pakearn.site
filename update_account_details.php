<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';
include 'private/update_acc.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Account Details</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            text-align: center;
        }
        .update-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 100%;
            animation: fadeIn 1s ease-in-out;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        h1 {
            color: #ff4757;
            margin-bottom: 20px;
        }
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 16px;
        }
        .success {
            background-color: #2ed573;
            color: white;
        }
        .error {
            background-color: #ff4757;
            color: white;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        .radio-group {
            display: flex;
            justify-content: center;
            margin-bottom: 15px;
        }
        .radio-group input[type="radio"] {
            display: none;
        }
        .radio-group label {
            padding: 12px 20px;
            border: 1px solid #ced6e0;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin: 0 10px;
            transition: background-color 0.3s ease-in-out, border-color 0.3s ease-in-out;
        }
        .radio-group input[type="radio"]:checked + label {
            background-color: #1e90ff;
            color: white;
            border-color: #1e90ff;
        }
        input[type="text"],
        input[type="tel"] {
            padding: 12px;
            border: 1px solid #ced6e0;
            border-radius: 5px;
            font-size: 16px;
            width: 100%;
            margin-bottom: 15px;
            transition: border-color 0.3s ease-in-out;
        }
        input[type="text"]:focus,
        input[type="tel"]:focus {
            border-color: #1e90ff;
        }
        button {
            background: #1e90ff;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            transition: background 0.3s ease-in-out;
        }
        button:hover {
            background: #3742fa;
        }
        select {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-color: #ffffff;
    border: 1px solid #ced6e0;
    padding: 12px;
    border-radius: 5px;
    font-size: 16px;
    width: 100%;
    margin-bottom: 15px;
    cursor: pointer;
    transition: border-color 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
    background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20width%3D%2210%22%20height%3D%225%22%20viewBox%3D%220%200%2010%205%22%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%3E%3Cpath%20d%3D%22M0%200l5%205%205-5z%22%20fill%3D%22%23333%22/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 10px 5px;
}

select:focus {
    border-color: #1e90ff;
    box-shadow: 0 0 5px rgba(30, 144, 255, 0.5);
    outline: none;
}

    </style>
</head>
<body>
    <div class="update-container">
        <h1>Update Account Details</h1>
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="radio-group">
                <input type="radio" id="easypaisa" name="account_type" value="easypaisa" required>
                <label for="easypaisa">Easypaisa</label>

                <input type="radio" id="jazzcash" name="account_type" value="jazzcash" required>
                <label for="jazzcash">JazzCash</label>
            </div>
            <select name="account_name" id="account_name" required>
                <option value="">Select Account Name</option>
                <option value="QAR FAT BIBI">QAR FAT BIBI</option>
                <option value="Muhammad Rasheed">Muhammad Rasheed</option>
                <option value="Farzana BIBI">Farzana BIBI</option>
                <option value="Usama Sarwar">Usama Sarwar</option>
                <option value="Muhammad Anas">Muhammad Anas</option>
                <option value="Muhammad Waqas">Muhammad Waqas</option>


            </select>
            
            <!-- AccountNumber dropdown (auto‑sync ho‑ga) -->
            <select name="account_number" id="account_number" required>
                <option value="">Select Account Number</option>
                <option value="03475546557">03475546557</option>
                <option value="03410409661">03410409661</option>
                <option value="03442873902">03442873902</option>
                <option value="03414318979">03414318979</option>
                <option value="03437384407">03437384407</option>
                <option value="03494941851">03494941851</option>
            </select>            
            <button type="submit">Update</button>
        </form>
    </div>
    <script>
    const pair = {
      'QAR FAT BIBI':     '03475546557',
      'Muhammad Rasheed': '03410409661',
      'Farzana BIBI':     '03442873902',
      'Usama Sarwar': '03414318979',
      'Muhammad Anas': '03437384407',
      'Muhammad Waqas': '03494941851'
    };
    
    const nameSel  = document.getElementById('account_name');
    const numSel   = document.getElementById('account_number');
    
    nameSel.addEventListener('change', () => {
        numSel.value = pair[nameSel.value] || '';
    });
    </script>

</body>
</html>
