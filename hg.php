<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

// ================= INSERT ADMIN =================
if (isset($_POST['submit'])) {

    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = trim($_POST['password']);
    $secret_code = mysqli_real_escape_string($conn, trim($_POST['secret_code']));

    // Hash password securely
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Check existing email
    $check = mysqli_query($conn, "SELECT * FROM admins WHERE email='$email'");

    if (mysqli_num_rows($check) > 0) {

        $message = "Email already exists!";
        $type = "error";

    } else {

        $insert = mysqli_query($conn, "
            INSERT INTO admins(email, password, secret_code)
            VALUES('$email', '$hashed_password', '$secret_code')
        ");

        if ($insert) {

            $message = "Admin Added Successfully!";
            $type = "success";

        } else {

            $message = "Something went wrong!";
            $type = "error";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Admin</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    padding:20px;
    background:linear-gradient(135deg,#6a11cb,#2575fc);
    font-family:Arial, sans-serif;
}

.container{
    width:100%;
    max-width:420px;
    background:#8E44AD;
    padding:35px 30px;
    border-radius:20px;
    box-shadow:0 10px 30px rgba(0,0,0,0.3);
    color:white;
    text-align:center;
}

h2{
    margin-bottom:25px;
    font-size:30px;
}

input{
    width:100%;
    padding:14px;
    margin:10px 0;
    border:none;
    border-radius:10px;
    outline:none;
    font-size:16px;
}

button{
    width:100%;
    padding:14px;
    margin-top:10px;
    border:none;
    border-radius:10px;
    background:#27ae60;
    color:white;
    font-size:17px;
    cursor:pointer;
    transition:0.3s;
}

button:hover{
    background:#1e8449;
}

.success{
    background:#27ae60;
    padding:12px;
    border-radius:10px;
    margin-bottom:15px;
    font-weight:bold;
}

.error{
    background:#e74c3c;
    padding:12px;
    border-radius:10px;
    margin-bottom:15px;
    font-weight:bold;
}

@media(max-width:480px){
    .container{
        padding:30px 20px;
    }

    h2{
        font-size:25px;
    }
}

</style>

</head>

<body>

<div class="container">

    <h2>Add Admin</h2>

    <?php if(isset($message)){ ?>
        <div class="<?php echo $type; ?>">
            <?php echo $message; ?>
        </div>
    <?php } ?>

    <form method="POST">

        <input 
            type="email" 
            name="email" 
            placeholder="Enter Email"
            required
        >

        <input 
            type="text" 
            name="password" 
            placeholder="Enter Password"
            required
        >

        <input 
            type="text" 
            name="secret_code" 
            placeholder="Enter Secret Code"
            required
        >

        <button type="submit" name="submit">
            Add Admin
        </button>

    </form>

</div>

</body>
</html>