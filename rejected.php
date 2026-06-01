<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}

// Handle AJAX search request
if (isset($_GET['ajaxSearch'])) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';
    
    $searchEmail = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
    
    if (!empty($searchEmail)) {
        $query = "SELECT email, status FROM records WHERE status = 'rejected' AND email LIKE '%$searchEmail%' ORDER BY id DESC";
    } else {
        $query = "SELECT email, status FROM records WHERE status = 'rejected' ORDER BY id DESC LIMIT 10";
    }
    
    $result = mysqli_query($conn, $query);
    $output = '';
    
    if ($result && mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
            $output .= '<tr>';
            $output .= '<td>' . htmlspecialchars($row['email']) . '</td>';
            $output .= '<td>
                <form method="POST" onsubmit="return confirm(\'Are you sure you want to move this record back to pending?\')">
                    <input type="hidden" name="email" value="' . htmlspecialchars($row['email']) . '">
                    <button type="submit" name="go_back">Go Back</button>
                </form>
            </td>';
            $output .= '</tr>';
        }
    } else {
        $output = '<tr><td colspan="2">No rejected records found.</td></tr>';
    }
    
    echo $output;
    mysqli_close($conn);
    exit();
}

// Handle the go back action
if (isset($_POST['go_back'])) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    mysqli_begin_transaction($conn);
    try {
        include 'private/ap_open.php';

        $updateRecords = "UPDATE records SET status='pending' WHERE email='$email'";
        if (!mysqli_query($conn, $updateRecords)) {
            throw new Exception("Error updating records table: " . mysqli_error($conn));
        }
        $updateUsers = "UPDATE users SET status='pending' WHERE email='$email'";
        if (!mysqli_query($conn, $updateUsers)) {
            throw new Exception("Error updating users table: " . mysqli_error($conn));
        }
        mysqli_commit($conn);
        include 'private/ap_block.php';

        echo "<script>window.location.href='rejected.php';</script>";
        exit();

    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "<script>alert('Transaction failed: " . addslashes($e->getMessage()) . "');</script>";
    }
    mysqli_close($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rejected Records</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5dc;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            width: 90%;
            max-width: 800px;
            background: white;
            padding: 15px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            text-align: center;
        }
        h2 {
            font-size: 18px;
            color: #333;
            margin-bottom: 10px;
        }
        input[type="text"] {
            width: 90%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .table-container {
            overflow-x: auto;
            max-height: 60vh;
            overflow-y: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            font-size: 14px;
            text-align: center;
            white-space: nowrap;
        }
        th {
            background: #ff7e5f;
            color: white;
            position: sticky;
            top: 0;
        }
        td {
            background: #fffaf0;
        }
        button {
            background: #28a745;
            color: white;
            padding: 6px 10px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            transition: 0.3s;
        }
        button:hover {
            background: #218838;
        }
        @media (max-width: 768px) {
            .container {
                width: 95%;
            }
            input[type="text"], button {
                font-size: 13px;
                padding: 6px;
            }
            th, td {
                font-size: 12px;
                padding: 6px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Rejected Records</h2>
        <input type="text" id="search" placeholder="Search by Email" autocomplete="off">
        
        <div class="table-container">
            <table id="recordsTable">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';
                    $query = "SELECT email, status FROM records WHERE status = 'rejected' ORDER BY id DESC LIMIT 10";
                    $result = mysqli_query($conn, $query);
                    
                    if ($result && mysqli_num_rows($result) > 0) {
                        while($row = mysqli_fetch_assoc($result)) {
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($row['email']) . '</td>';
                            echo '<td>
                                <form method="POST" onsubmit="return confirm(\'Are you sure you want to move this record back to pending?\')">
                                    <input type="hidden" name="email" value="' . htmlspecialchars($row['email']) . '">
                                    <button type="submit" name="go_back">Go Back</button>
                                </form>
                            </td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="2">No rejected records found.</td></tr>';
                    }
                    mysqli_close($conn);
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // AJAX search function with debounce
        let searchTimeout;
        
        document.getElementById('search').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                const searchTerm = document.getElementById('search').value.trim();
                fetch(`?ajaxSearch=true&search=${encodeURIComponent(searchTerm)}`)
                    .then(response => response.text())
                    .then(data => {
                        document.querySelector('#recordsTable tbody').innerHTML = data;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            }, 300);
        });

        // Focus search input on page load
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('search').focus();
        });
    </script>
</body>
</html>