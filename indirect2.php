<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/connection.php';

// Fetch all prices from the indirect table
$price_query = "SELECT DISTINCT price2 FROM indirect2 ORDER BY price2 ASC";
$price_result = $conn->query($price_query);

// Update commission if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_commission'])) {
    $selected_price = $conn->real_escape_string($_POST['selected_price']);
    $new_commission = $conn->real_escape_string($_POST['commission']);
    
    $update_query = "UPDATE indirect2 SET comm2 = '$new_commission' WHERE price2 = '$selected_price'";
    if ($conn->query($update_query)) {
        $success_message = "Commission updated successfully!";
    } else {
        $error_message = "Error updating commission: " . $conn->error;
    }
}

// Fetch commission when price is selected
if (isset($_GET['price'])) {
    $selected_price = $conn->real_escape_string($_GET['price']);
    $comm_query = "SELECT comm2 FROM indirect2 WHERE price2 = '$selected_price' LIMIT 1";
    $comm_result = $conn->query($comm_query);
    
    if ($comm_result && $comm_result->num_rows > 0) {
        $row = $comm_result->fetch_assoc();
        echo $row['comm2'];
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Price & Commission Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #f8f9fc;
            --accent-color: #2e59d9;
            --text-color: #5a5c69;
        }
        
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fc;
            color: var(--text-color);
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            transition: transform 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            text-align: center;
            border-radius: 15px 15px 0 0 !important;
            padding: 1.25rem 1.5rem;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        
        .select-wrapper {
            position: relative;
        }
        
        .select-wrapper::after {
            content: "▼";
            font-size: 12px;
            color: var(--primary-color);
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
        }
        
        .alert {
            border-radius: 10px;
        }
        
        .loading-spinner {
            display: none;
            color: var(--primary-color);
        }
        h4
        {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="m-0 font-weight-bold">
                            Indirect 2 Commission
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success_message)): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <?php echo $success_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php elseif (isset($error_message)): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <?php echo $error_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form id="commissionForm" method="POST">
                            <div class="mb-4">
                                <label for="priceSelect" class="form-label fw-bold">
                                    <i class="fas fa-tags me-2"></i>Select Price
                                </label>
                                <div class="select-wrapper">
                                    <select class="form-select py-2" id="priceSelect" name="selected_price" required>
                                        <option value="" selected disabled>-- Select a Price --</option>
                                        <?php while ($row = $price_result->fetch_assoc()): ?>
                                            <option value="<?php echo htmlspecialchars($row['price2']); ?>">
                                                <?php echo htmlspecialchars($row['price2']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="commissionInput" class="form-label fw-bold">
                                    <i class="fas fa-percentage me-2"></i>Commission Value
                                </label>
                                <div class="input-group">
                                    <input type="text" class="form-control py-2" id="commissionInput" name="commission" required>
                                    <span class="input-group-text">%</span>
                                </div>
                                
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary py-2 fw-bold" name="update_commission">
                                    <i class="fas fa-save me-2"></i>Update Commission
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // When price selection changes
            $('#priceSelect').change(function() {
                const price = $(this).val();
                if (price) {
                    $('.card-header i.fa-cog').addClass('fa-spin');
                    
                    // Fetch commission via AJAX
                    $.get('indirect2.php', { price: price }, function(data) {
                        $('#commissionInput').val(data);
                        $('.card-header i.fa-cog').removeClass('fa-spin');
                    }).fail(function() {
                        alert('Error fetching commission data');
                        $('.card-header i.fa-cog').removeClass('fa-spin');
                    });
                } else {
                    $('#commissionInput').val('');
                }
            });
            
            // Form submission feedback
            $('#commissionForm').submit(function() {
                $('button[name="update_commission"]').html('<i class="fas fa-spinner fa-spin me-2"></i>Updating...');
            });
        });
    </script>
</body>
</html>