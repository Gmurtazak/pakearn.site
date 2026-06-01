<?php
// Show all PHP errors (development only — disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
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
        $sql = "SELECT email, name, status FROM records WHERE status = 'active' AND newapprove = 1 AND email LIKE '%$searchEmail%' ORDER BY id DESC";
    } else {
        $sql = "SELECT email, name, status FROM records WHERE status = 'active' AND newapprove = 1 ORDER BY id DESC LIMIT 10";
    }

    $result = $conn->query($sql);
    $output = '';

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $userDetails = getUserDetails($row["email"], $conn);
            $id = $userDetails['id'];
            $reff_id = $userDetails['reff_id'];
            $password = $userDetails['ph'];

            $output .= '<tr>';
            $output .= '<td class="email">' . htmlspecialchars($row["email"]) . '</td>';
            $output .= '<td class="name">' . htmlspecialchars($row["name"]) . '</td>';
            $output .= '<td class="password">' . htmlspecialchars($password) . '</td>';
            $output .= '<td>' . htmlspecialchars($row["status"]) . '</td>';
            $output .= '<td class="action-buttons">';
            $output .= '<button class="khud-btn" onclick="checkStatusAndRedirect(\'' . $id . '\', \'' . htmlspecialchars($row["email"]) . '\', \'' . htmlspecialchars($password) . '\')">Khud</button>';
            $output .= '<button class="lead-btn" onclick="window.location.href=\'https://pakearn.site/smart_login4.php?id=' . $reff_id . '\'">Lead</button>';
            $output .= '</td>';
            $output .= '</tr>';
        }
    } else {
        $output = '<tr><td colspan="5">No records found.</td></tr>';
    }

    echo $output;
    $conn->close();
    exit();
}

function getUserDetails($email, $conn) {
    $query = "SELECT id, reff_id, ph FROM users WHERE email = '$email'";
    $res = $conn->query($query);
    return $res->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Approved</title>
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
        }
        body {
            font-size: 12px;
        }
        h1 {
            text-align: center;
            color: #333;
            font-size: 18px;
            margin-top: 10px;
        }
        .search-container {
            text-align: center;
            margin: 10px 0;
        }
        .search-container input {
            padding: 6px 10px;
            width: 80%;
            max-width: 400px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 12px;
        }
        .pending-box {
            background: #fff;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            max-width: 100%;
            height: calc(100vh - 80px);
            overflow: auto;
            margin: 0 auto;
            box-sizing: border-box;
        }
        .pending-box table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        .pending-box th, .pending-box td {
            padding: 2px 4px;
            text-align: left;
            white-space: nowrap;
            border: 1px solid #ddd;
        }
        .pending-box th {
            background-color: #f2f2f2;
        }
        .pending-box tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .pending-box td.email,
        .pending-box td.name,
        .pending-box td.password {
            max-width: 100px;
            overflow: hidden;
            text-overflow: ellipsis;
            word-break: break-all;
        }
        .button-container {
            text-align: center;
            margin-top: 10px;
        }
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin-top: 10px;
        }
        .action-buttons button {
            background-color: #007BFF;
            color: white;
            padding: 6px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            transition: background-color 0.3s ease;
        }
        .khud-btn {
            background-color: #6f42c1;
        }
        .khud-btn:hover {
            background-color: #563d7c;
        }
        .lead-btn {
            background-color: #e83e8c;
        }
        .lead-btn:hover {
            background-color: #d6336c;
        }

        @media (max-width: 768px) {
            .pending-box {
                padding: 5px;
                height: calc(100vh - 70px);
            }
            .pending-box table {
                font-size: 10px;
            }
            .pending-box th, .pending-box td {
                padding: 1px 2px;
            }
            .pending-box td.email,
            .pending-box td.name,
            .pending-box td.password {
                max-width: 80px;
            }
            .action-buttons button {
                font-size: 10px;
                padding: 5px 8px;
            }
        }

        @media (max-width: 480px) {
            .pending-box {
                height: calc(100vh - 70px);
            }
            .pending-box table {
                font-size: 9px;
            }
            .pending-box th, .pending-box td {
                padding: 1px 1px;
            }
            .pending-box td.email,
            .pending-box td.name,
            .pending-box td.password {
                max-width: 60px;
            }
            .action-buttons {
                gap: 3px;
            }
            .action-buttons button {
                font-size: 9px;
                padding: 4px 6px;
            }
        }
    </style>
</head>
<body>
    <h1>Member Approved</h1>
    <div class="search-container">
        <input type="text" id="searchInput" placeholder="Search by email...">
    </div>
    <div class="pending-box">
        <table id="recordsTable">
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Name</th>
                    <th>Password</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';
                
                // Default: show only last 10 records
                $sql = "SELECT email, name, status FROM records WHERE status = 'active' AND newapprove = 1 ORDER BY id DESC LIMIT 10";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $userDetails = getUserDetails($row["email"], $conn);
                        $id = $userDetails['id'];
                        $reff_id = $userDetails['reff_id'];
                        $password = $userDetails['ph'];

                        echo '<tr>';
                        echo '<td class="email">' . htmlspecialchars($row["email"]) . '</td>';
                        echo '<td class="name">' . htmlspecialchars($row["name"]) . '</td>';
                        echo '<td class="password">' . htmlspecialchars($password) . '</td>';
                        echo '<td>' . htmlspecialchars($row["status"]) . '</td>';
                        echo '<td class="action-buttons">';
                        echo '<button class="khud-btn" onclick="checkStatusAndRedirect(\'' . $id . '\', \'' . htmlspecialchars($row["email"]) . '\', \'' . htmlspecialchars($password) . '\')">Khud</button>';
                        echo '<button class="lead-btn" onclick="window.location.href=\'https://pakearn.site/smart_login4.php?id=' . $reff_id . '\'">Lead</button>';
                        echo '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="5">No records found.</td></tr>';
                }
                $conn->close();
                ?>
            </tbody>
        </table>
    </div>
    <script>
        function checkStatusAndRedirect(id, email, password) {
            fetch('?checkStatus=true&id=' + id)
                .then(response => response.text())
                .then(status => {
                    if (status.includes('active')) {
                        window.location.href = 'https://pakearn.site/smart_login4.php?id=' + id;
                    } else if (status.includes('pending')) {
                        window.location.href = 'https://pakearn.site/login2.php?email=' + encodeURIComponent(email) + '&password=' + encodeURIComponent(password);
                    } else {
                        alert('Error: Could not determine user status.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        // AJAX search function
        function searchTable() {
            const searchTerm = document.getElementById('searchInput').value.trim();
            
            fetch(`?ajaxSearch=true&search=${encodeURIComponent(searchTerm)}`)
                .then(response => response.text())
                .then(data => {
                    document.querySelector('#recordsTable tbody').innerHTML = data;
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        // Add event listener with debounce to prevent too many requests
        let searchTimeout;
        document.getElementById('searchInput').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(searchTable, 300);
        });

        // Focus search input on page load
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('searchInput').focus();
        });
    </script>
</body>
</html>