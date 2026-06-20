<?php
// Include database connection
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

// Get single link URL
$query = "SELECT url FROM single_link LIMIT 1";
$queryResult = $conn->query($query);
$url = ($queryResult->num_rows > 0) ? $queryResult->fetch_assoc()['url'] : "#";

// Function to securely fetch account details
function getAccountDetails($accountType, $conn) {
    $accountType = strtolower($accountType);

    if (!in_array($accountType, ['easypaisa', 'jazzcash'])) {
        return false;
    }

    $stmt = $conn->prepare("SELECT account_name, account_number FROM payment_accounts WHERE account_type = ? LIMIT 1");
    $stmt->bind_param("s", $accountType);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }

    return false;
}

// Get account details
$easypaisaDetails = getAccountDetails('easypaisa', $conn);
$jazzcashDetails = getAccountDetails('jazzcash', $conn);

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    include 'private/unblock_updates.php';

    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $number = preg_replace('/[^0-9]/', '', $_POST['number']);
    $sphone = preg_replace('/[^0-9]/', '', $_POST['sphone']);
    $fee = isset($_GET['amount']) ? intval($_GET['amount']) : 700;

    // Validate Trx ID
    if (!ctype_digit($number)) {
        echo "<div class='error'>Trx_ID must contain only numbers (0-9).</div>";
        $conn->close();
        exit;
    }

    // Check duplicate Trx ID
    $trx_check_sql = "SELECT * FROM records WHERE unique_id=?";
    $stmt = $conn->prepare($trx_check_sql);
    $stmt->bind_param("s", $number);
    $stmt->execute();
    $trx_check_result = $stmt->get_result();

    if ($trx_check_result->num_rows > 0) {

        echo "<div class='error'>This Trx_ID is already in use. Please enter a unique one.</div>";

    } else {

        // Check user exists
        $user_check_sql = "SELECT * FROM users WHERE email=?";
        $stmt = $conn->prepare($user_check_sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user_check_result = $stmt->get_result();

        if ($user_check_result->num_rows > 0) {

            // Check records exists
            $record_check_sql = "SELECT * FROM records WHERE email=?";
            $stmt = $conn->prepare($record_check_sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $record_check_result = $stmt->get_result();

            if ($record_check_result->num_rows > 0) {

                $record_row = $record_check_result->fetch_assoc();
                $record_id = $record_row['id'];

                // Update records
                $update_sql = "UPDATE records SET unique_id=?, sphone=?, amount=? WHERE email=?";
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param("ssis", $number, $sphone, $fee, $email);

                if ($stmt->execute()) {

                    // Update user status
                    $update_user_status_sql = "UPDATE users SET status='pending' WHERE email=? AND status='rejected'";
                    $stmt = $conn->prepare($update_user_status_sql);
                    $stmt->bind_param("s", $email);
                    $stmt->execute();

                    // Update record status
                    $update_record_status_sql = "UPDATE records SET status='pending' WHERE email=? AND status='rejected'";
                    $stmt = $conn->prepare($update_record_status_sql);
                    $stmt->bind_param("s", $email);
                    $stmt->execute();

                    echo "<script>
                        window.location.href='st.php?id=$record_id';
                    </script>";

                } else {

                    echo "<div class='error'>Error: " . $conn->error . "</div>";

                }

            } else {

                echo "<div class='error'>Email not found in records. Please contact support.</div>";

            }

        } else {

            echo "<div class='error'>Please register your email first.</div>";

        }

    }

    $conn->close();

    include 'private/block_updates.php';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment and Registration</title>

    <style>

    body{
        font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;
        background:#f0f2f5;
        margin:0;
        padding:0;
        display:flex;
        justify-content:center;
        align-items:center;
        height:100vh;
        overflow:hidden;
    }

    .container{
        background:white;
        padding:30px;
        border-radius:15px;
        box-shadow:0 8px 16px rgba(0,0,0,0.1);
        max-width:500px;
        width:100%;
        text-align:center;
        animation:fadeIn 1s ease-in-out;
    }

    @keyframes fadeIn{
        from{
            opacity:0;
            transform:translateY(20px);
        }
        to{
            opacity:1;
            transform:translateY(0);
        }
    }

    h1{
        color:#ff4757;
        margin-bottom:20px;
    }

    .instruction{
        font-size:1.2em;
        color:#2f3542;
        margin-bottom:10px;
    }

    .fee{
        font-size:1.5em;
        color:#333;
        margin-bottom:20px;
    }

    .toggle-buttons{
        display:flex;
        justify-content:center;
        margin-bottom:20px;
    }

    .toggle-buttons button{
        background:#f0f2f5;
        color:#333;
        border:1px solid #ced6e0;
        padding:10px 20px;
        border-radius:5px;
        cursor:pointer;
        font-size:16px;
        width:48%;
        margin:1%;
        transition:0.3s;
    }

    .toggle-buttons button.active{
        background:#1e90ff;
        color:white;
        border:1px solid #1e90ff;
    }

    #account-details{
        display:none;
        text-align:center;
        margin-top:20px;
        padding:15px;
        background:#f8f9fa;
        border-radius:8px;
        border:1px solid #dee2e6;
    }

    #details{
        font-size:18px;
        line-height:1.8;
    }

    .account-name{
        color:#ff6347;
        font-weight:bold;
    }

    .account-number{
        color:#1e90ff;
        font-weight:bold;
    }

    .not-available{
        color:red;
        font-weight:bold;
        font-size:17px;
    }

    input[type="text"],
    input[type="tel"],
    input[type="email"]{
        padding:12px;
        border:1px solid #ced6e0;
        border-radius:5px;
        font-size:16px;
        width:90%;
        margin-bottom:15px;
        transition:0.3s;
    }

    input:focus{
        border-color:#1e90ff;
        outline:none;
    }

    button{
        background:#1e90ff;
        color:white;
        padding:12px 20px;
        border:none;
        border-radius:5px;
        cursor:pointer;
        font-size:16px;
        width:90%;
        transition:0.3s;
        margin-top:10px;
    }

    button:hover{
        background:#3742fa;
    }

    .success,
    .error{
        text-align:center;
        margin-top:20px;
        padding:10px;
        border-radius:5px;
        width:90%;
        margin:0 auto;
    }

    .success{
        background:#d4edda;
        color:#155724;
    }

    .error{
        background:#f8d7da;
        color:#721c24;
    }

    .btn-container{
        display:flex;
        justify-content:space-between;
        gap:10px;
        margin-top:20px;
    }

    @media(max-width:600px){

        .container{
            padding:20px;
        }

        input,
        button{
            width:100%;
        }

        .btn-container{
            flex-direction:column;
        }
    }

    </style>

</head>

<body>

<div class="container">

    <h1>Pak Earn</h1>

    <div class="instruction" style="line-height: 1.5; margin-bottom: 20px;">
        Pak Earn is a task-based system. Complete tasks to earn rewards. <br>
        To activate, pay the signup fee of 
        <span style="color: #ff4757; font-weight: bold;">Rs. 
        <?php
        $fee = isset($_GET['amount']) ? intval($_GET['amount']) : 700;
        echo $fee . '/-';
        ?>
        </span>.
        Submit details below and share screenshot on WhatsApp.
    </div>

    <div class="toggle-buttons">
        <button id="easypaisa-btn">EasyPaisa</button>
        <button id="jazzcash-btn">JazzCash</button>
    </div>

    <div id="account-details">
        <h5 id="details"></h5>
    </div>

    <form id="registrationForm" method="POST" style="margin-top:15px;">

        <input type="text" id="number" name="number" placeholder="Enter your Trx_id (Numbers only)" required>

        <input type="tel" id="sphone" name="sphone" placeholder="Sender Phone" required>

        <input type="email" id="email" name="email" placeholder="Your Registered Email" required>

        <div class="btn-container">
            <button type="submit" id="submit-btn" disabled>Submit</button>

            <p>
                <a href="<?php echo htmlspecialchars($url); ?>">Contact Us</a>
                If You Have Trx_ID Issue?
            </p>
        </div>

    </form>

</div>

<p style="font-size:10px;color:#bbb;">
    <a href="/blog.php" style="color:#bbb;text-decoration:none;">c</a>
</p>

<script>

const easypaisaBtn = document.getElementById('easypaisa-btn');
const jazzcashBtn = document.getElementById('jazzcash-btn');
const details = document.getElementById('details');
const accountDetails = document.getElementById('account-details');
const submitBtn = document.getElementById('submit-btn');

let selectedAccount = '';

const accountData = {

    easypaisa: <?php
        echo $easypaisaDetails ? json_encode([
            'account_name' => htmlspecialchars($easypaisaDetails['account_name']),
            'account_number' => htmlspecialchars($easypaisaDetails['account_number'])
        ]) : 'null';
    ?>,

    jazzcash: <?php
        echo $jazzcashDetails ? json_encode([
            'account_name' => htmlspecialchars($jazzcashDetails['account_name']),
            'account_number' => htmlspecialchars($jazzcashDetails['account_number'])
        ]) : 'null';
    ?>

};

function showAccountDetails(accountName, accountNumber){

    details.innerHTML = `
        Account Name:
        <span class="account-name">${accountName}</span>
        <br>
        Account Number:
        <span class="account-number">${accountNumber}</span>
    `;

}

function showNotAvailable(message){

    details.innerHTML = `
        <span class="not-available">${message}</span>
    `;

}

easypaisaBtn.addEventListener('click', () => {

    easypaisaBtn.classList.add('active');
    jazzcashBtn.classList.remove('active');

    accountDetails.style.display = 'block';

    if(accountData.easypaisa){

        selectedAccount = 'easypaisa';

        showAccountDetails(
            accountData.easypaisa.account_name,
            accountData.easypaisa.account_number
        );

        submitBtn.disabled = false;

    }else{

        selectedAccount = '';

        showNotAvailable(
            'EasyPaisa account not available. Please Use JazzCash.'
        );

        submitBtn.disabled = true;

    }

});

jazzcashBtn.addEventListener('click', () => {

    jazzcashBtn.classList.add('active');
    easypaisaBtn.classList.remove('active');

    accountDetails.style.display = 'block';

    if(accountData.jazzcash){

        selectedAccount = 'jazzcash';

        showAccountDetails(
            accountData.jazzcash.account_name,
            accountData.jazzcash.account_number
        );

        submitBtn.disabled = false;

    }else{

        selectedAccount = '';

        showNotAvailable(
            'JazzCash account not available. Please Use EasyPaisa.'
        );

        submitBtn.disabled = true;

    }

});

document.getElementById('registrationForm').addEventListener('submit', function(event){

    const trxIdInput = document.getElementById('number');
    const trxIdValue = trxIdInput.value;

    if(!/^\d+$/.test(trxIdValue)){

        alert('Trx_ID must contain only numbers (0-9)');
        event.preventDefault();
        return;

    }

    if(!selectedAccount){

        alert('Please select available payment method.');
        event.preventDefault();

    }

});

</script>

</body>
</html>