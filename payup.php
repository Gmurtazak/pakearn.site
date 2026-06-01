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
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

// Fetch URL for contact
$query = "SELECT url FROM single_link LIMIT 1"; // Assuming there's only one URL
$queryResult = $conn->query($query);

if ($queryResult->num_rows > 0) {
    $data = $queryResult->fetch_assoc();
    $url = $data['url'];
} else {
    $url = "#"; // Default value if no URL is found
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment and Registration</title>
     <style>
    /* Global Styles */
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f0f2f5;
        margin: 0;
        padding: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        overflow: hidden;
    }

    .container {
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        max-width: 500px;
        width: 100%;
        text-align: center;
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

    /* Instruction Styling */
    .instruction {
        font-size: 1.2em;
        color: #2f3542;
        margin-bottom: 10px;
    }

    /* Fee Styling */
    .fee {
        font-size: 1.5em;
        color: #333;
        margin-bottom: 20px;
    }

    /* Toggle Button Styles */
    .toggle-buttons {
        display: flex;
        justify-content: center;
        margin-bottom: 20px;
    }

    .toggle-buttons button {
        background: #f0f2f5;
        color: #333;
        border: 1px solid #ced6e0;
        padding: 10px 20px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
        width: 48%;
        margin: 1%;
        transition: background 0.3s ease-in-out, color 0.3s ease-in-out;
    }

    .toggle-buttons button.active {
        background: #1e90ff;
        color: white;
        border: 1px solid #1e90ff;
    }

    /* Account Details Styling */
    #account-details {
        display: none;
        text-align: center;
        margin-top: 20px;
    }

    #account-details h5 {
        font-size: 1.2em;
        margin: 10px 0;
    }

    .account-name {
        color: #ff6347;
        font-weight: bold;
    }

    .account-number {
        color: #1e90ff;
    }

    /* Form Styling */
    input[type="text"],
    input[type="tel"],
    input[type="email"] {
        padding: 12px;
        border: 1px solid #ced6e0;
        border-radius: 5px;
        font-size: 16px;
        width: 90%;
        margin-bottom: 15px;
        transition: border-color 0.3s ease-in-out;
    }

    input[type="text"]:focus,
    input[type="tel"]:focus,
    input[type="email"]:focus {
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
        width: 90%;
        transition: background 0.3s ease-in-out;
        margin-top: 10px;
    }

    button:hover {
        background: #3742fa;
    }

    .success,
    .error {
        text-align: center;
        margin-top: 20px;
        padding: 10px;
        border-radius: 5px;
        width: 90%;
        margin: 0 auto;
    }

    .success {
        background: #d4edda;
        color: #155724;
    }

    .error {
        background: #f8d7da;
        color: #721c24;
    }

    .btn-container {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        margin-top: 20px;
    }

    @media (max-width: 600px) {
        .container {
            padding: 20px;
        }

        input,
        button {
            width: 100%;
        }

        .btn-container {
            flex-direction: column;
        }
    }
    </style>
</head>

<body>
    <div class="container">
        <h1>Pak Earn</h1>
        <div class="instruction">
            Pay the following fee to activate your account:
        </div>
        <div class="fee">
            Fee: Rs.
            <?php
            $fee = isset($_GET['amount']) ? intval($_GET['amount']) : 700;
            echo $fee . '/-';
            ?>
        </div>

        <div class="toggle-buttons">
            <button id="easypaisa-btn">EasyPaisa</button>
            <button id="jazzcash-btn">JazzCash</button>
        </div>

        <div id="account-details">
            <h5 id="details"></h5>
        </div>

        <form id="registrationForm" method="POST">
            <input type="text" id="number" name="number" placeholder="Enter your Trx_id" required>
            <input type="tel" id="sphone" name="sphone" placeholder="Sender Phone" required>
            <input type="email" id="email" name="email" placeholder="Your Registered Email" required>
            <div class="btn-container">
                <button type="submit" id="submit-btn">Submit</button>
                <p><a href="<?php echo htmlspecialchars($url); ?>">Contact Us</a> If You Have Trx_ID Issue?</p>
            </div>
        </form>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';
    $email = $_POST['email'];
    $number = $_POST['number'];
    $sphone = $_POST['sphone'];
    $fee = isset($_GET['amount']) ? intval($_GET['amount']) : 700;

    // Check if any record with this Trx_ID (unique_id) has 'pending' status
    $trx_check_sql = "SELECT * FROM records WHERE unique_id='$number' AND status='pending'";
    $trx_check_result = $conn->query($trx_check_sql);

    if ($trx_check_result->num_rows > 0) {
        // If any record with 'pending' status is found, show the error message
        echo "<div class='error'>This Trx_ID is already in use. Please enter a unique one.</div>";
    } else {
        // Proceed with checking the email
        $user_check_sql = "SELECT * FROM users WHERE email='$email'";
        $user_check_result = $conn->query($user_check_sql);

        if ($user_check_result->num_rows > 0) {
            // Check if the email exists in records table and if the status is active
            $record_check_sql = "SELECT * FROM records WHERE email='$email' AND status='active'";
            $record_check_result = $conn->query($record_check_sql);

            if ($record_check_result->num_rows > 0) {
                $update_sql = "UPDATE records SET unique_id='$number', sphone='$sphone', amount='$fee', status='pending' WHERE email='$email'";
                if ($conn->query($update_sql) === TRUE) {
                    echo "<div class='success'>Trx_ID successfully updated and status set to pending.</div>";
                    echo "<script>
                            window.location.href = 'st.php';
                          </script>";
                } 
                else 
                {
                    echo "<div class='error'>Error: " . $conn->error . "</div>";
                }
            } 
            else
            {
                $update_sql = "UPDATE records SET unique_id='$number', sphone='$sphone', amount='$fee' WHERE email='$email'";
                if ($conn->query($update_sql) === TRUE) {
                    echo "<div class='success'>Trx_ID successfully updated </div>";
                    echo "<script>
                            window.location.href = 'st.php';
                          </script>";
                } 
                else 
                {
                    echo "<div class='error'>Error: " . $conn->error . "</div>";
                }
            }
        } else {
            echo "<div class='error'>Please register your email first.</div>";
        }
    }

    $conn->close();
}
?>

    </div>

    <script>
    const easypaisaBtn = document.getElementById('easypaisa-btn');
    const jazzcashBtn = document.getElementById('jazzcash-btn');
    const details = document.getElementById('details');
    const submitBtn = document.getElementById('submit-btn');
    let selectedAccount = '';

    function updateDetails(accountName, accountNumber) {
        details.innerHTML =
            `Account Name: <span class="account-name">${accountName}</span><br>Account Number: <span class="account-number">${accountNumber}</span>`;
    }

    // Fetch account details from database
    function fetchAccountDetails(accountType) {
        fetch(`fetch_account_details.php?account_type=${accountType}`)
            .then(response => response.json())
            .then(data => {
                updateDetails(data.account_name, data.account_number);
                submitBtn.disabled = false;
            })
            .catch(error => {
                console.error('Error fetching account details:', error);
            });
    }

    easypaisaBtn.addEventListener('click', () => {
        easypaisaBtn.classList.add('active');
        jazzcashBtn.classList.remove('active');
        selectedAccount = 'easypaisa';
        fetchAccountDetails(selectedAccount);
        document.getElementById('account-details').style.display = 'block';
    });

    jazzcashBtn.addEventListener('click', () => {
        jazzcashBtn.classList.add('active');
        easypaisaBtn.classList.remove('active');
        selectedAccount = 'jazzcash';
        fetchAccountDetails(selectedAccount);
        document.getElementById('account-details').style.display = 'block';
    });

    document.getElementById('registrationForm').addEventListener('submit', function(event) {
        if (!selectedAccount) {
            alert('Please select either EasyPaisa or JazzCash before submitting.');
            event.preventDefault();
        }
    });
    </script>
</body>

</html>
