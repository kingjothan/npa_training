<?php
// Start session for authentication and secure management
session_start();

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Database connection
try {
    $pdo = new PDO('mysql:host=localhost;dbname=npa_training', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Function to sanitize input
function sanitizeInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    // Collect and sanitize form data
    $name = sanitizeInput($_POST['name'] ?? '');
    $personal_number = sanitizeInput($_POST['personal_number'] ?? '');
    $designation = sanitizeInput($_POST['designation'] ?? '');
    $location = sanitizeInput($_POST['location'] ?? '');
    $training_description = sanitizeInput($_POST['training_description'] ?? '');
    $start_date = sanitizeInput($_POST['start_date'] ?? '');
    $completion_date = sanitizeInput($_POST['completion_date'] ?? '');
    $number_of_days = sanitizeInput($_POST['number_of_days'] ?? '');
    $status = sanitizeInput($_POST['status'] ?? '');
    $training_type = sanitizeInput($_POST['training_type'] ?? '');
    $total_cost_of_participation = sanitizeInput($_POST['total_cost_of_participation'] ?? '');
    $remark = sanitizeInput($_POST['remark'] ?? '');
    $oracle_number = sanitizeInput($_POST['oracle_number'] ?? '');
    $consultant_name = sanitizeInput($_POST['consultant_name'] ?? '');
    $consultation_amount = sanitizeInput($_POST['consultation_amount'] ?? '');

    // Check if personal_number already exists
    $checkSql = "SELECT COUNT(*) FROM participants WHERE personal_number = :personal_number";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->bindParam(':personal_number', $personal_number);
    $checkStmt->execute();

    if ($checkStmt->fetchColumn() > 0) {
        $error = "A participant with the same personal number already exists.";
    } else {
        // Insert data into the database
        $sql = "INSERT INTO participants (
                    name, personal_number, designation, location, training_description, 
                    start_date, completion_date, number_of_days, status, training_type, 
                    total_cost_of_participation, remark, oracle_number, consultant_name, consultation_amount
                ) VALUES (
                    :name, :personal_number, :designation, :location, :training_description, 
                    :start_date, :completion_date, :number_of_days, :status, :training_type, 
                    :total_cost_of_participation, :remark, :oracle_number, :consultant_name, :consultation_amount
                )";

        $stmt = $pdo->prepare($sql);
        
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':personal_number', $personal_number);
        $stmt->bindParam(':designation', $designation);
        $stmt->bindParam(':location', $location);
        $stmt->bindParam(':training_description', $training_description);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':completion_date', $completion_date);
        $stmt->bindParam(':number_of_days', $number_of_days);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':training_type', $training_type);
        $stmt->bindParam(':total_cost_of_participation', $total_cost_of_participation);
        $stmt->bindParam(':remark', $remark);
        $stmt->bindParam(':oracle_number', $oracle_number);
        $stmt->bindParam(':consultant_name', $consultant_name);
        $stmt->bindParam(':consultation_amount', $consultation_amount);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Participant added successfully!";
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $error = "Failed to add participant. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Participant - NPA Training Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #d4e1e3, #ffffff);
            font-family: 'Poppins', sans-serif;
            color: #333;
        }
        .form-container {
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 50px auto;
            transition: all 0.3s ease;
        }
        .form-container:hover {
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }
        .form-container h1 {
            color: #28a745;
            font-size: 2.5rem;
            font-weight: 600;
            text-align: center;
            margin-bottom: 30px;
        }
        .form-control, .form-select, .form-label {
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #28a745;
            box-shadow: 0 0 8px rgba(40, 167, 69, 0.5);
        }
        .btn-primary {
            background-color: #28a745;
            border: none;
            border-radius: 8px;
            padding: 12px 30px;
            font-size: 1rem;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #218838;
        }
        .btn-secondary {
            background-color: #6c757d;
            border-radius: 8px;
            padding: 12px 30px;
            font-size: 1rem;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .spinner-border {
            display: none;
            width: 3rem;
            height: 3rem;
            border-width: 0.3em;
        }
        .alert {
            text-align: center;
            font-weight: bold;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
        }
        .back-link {
            color: #28a745;
            font-size: 1.1rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .back-link:hover {
            text-decoration: underline;
            color: #218838;
        }
        .form-control, .form-select {
            box-shadow: none;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Add Participant</h1>
        <div id="alert-container">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success_message']) ?></div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
        </div>

        <form id="participantForm" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" name="name" id="name" class="form-control" required placeholder="Enter full name">
                </div>
                <div class="col-md-6">
                    <label for="personal_number" class="form-label">Personal Number</label>
                    <input type="text" name="personal_number" id="personal_number" class="form-control" required placeholder="Enter personal number">
                </div>
                <div class="col-md-6">
                    <label for="oracle_number" class="form-label">Oracle Number</label>
                    <input type="text" name="oracle_number" id="oracle_number" class="form-control" placeholder="Enter Oracle Number" required>
                </div
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="consultant_name" class="form-label">Name of Consultant</label>
                    <input type="text" name="consultant_name" id="consultant_name" class="form-control" placeholder="Enter Consultant Name" required>
                </div>
                <div class="col-md-6">
                    <label for="consultation_amount" class="form-label">Amount for Consultation</label>
                    <input type="number" name="consultation_amount" id="consultation_amount" class="form-control" step="0.01" placeholder="Enter Consultation Amount" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="designation" class="form-label">Designation</label>
                    <input type="text" name="designation" id="designation" class="form-control" placeholder="Enter designation">
                </div>
                <div class="col-md-6">
                    <label for="location" class="form-label">Location</label>
                    <input type="text" name="location" id="location" class="form-control" placeholder="Enter location">
                </div>
            </div>

            <div class="mb-3">
                <label for="training_description" class="form-label">Training Description</label>
                <input name="training_description" id="training_description" class="form-control" rows="4" placeholder="Enter training description">
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label for="completion_date" class="form-label">Completion Date</label>
                    <input type="date" name="completion_date" id="completion_date" class="form-control" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="Completed">Completed</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Not Started">Not Started</option>
                        <option value="Rescheduled">Rescheduled</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="training_type" class="form-label">Training Type</label>
                    <select name="training_type" id="training_type" class="form-select">
                        <option value="Short_COURSES">Short-COURSES</option>
                        <option value="Conference">Conference</option>
                        <option value="Mandatories">Mandatories</option>
                        <option value="In_House">In-House</option>
                        <option value="In_Plant">In-Plant</option>
                        <option value="Overseas_Short_COURSES">Overseas Short-COURSES</option>
                        <option value="Carrier_Growth">Carrier Growth</option>
                        <option value="Sensitization">Sensitization</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>

            <div class="col-md-6" id="other_training_type" style="display:none;">
                <label for="other_training_type_input" class="form-label">Please Specify</label>
                <input type="text" name="other_training_type" id="other_training_type_input" class="form-control" placeholder="Enter other training type">
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="total_cost_of_participation" class="form-label">Total Cost of Participation</label>
                    <input type="number" name="total_cost_of_participation" id="total_cost_of_participation" class="form-control" step="0.01" placeholder="Enter total cost" required>
                </div>
                >
            </div>

           
            <div class="mb-3">
                <label for="remark" class="form-label">Remark</label>
                <textarea name="remark" id="remark" class="form-control" rows="4" placeholder="Enter remarks"></textarea>
            </div>

            <div class="text-center">
                <button type="submit" class="btn btn-primary" id="submitBtn">Add Participant</button>
                <a href="index.html" class="btn btn-secondary back-link">Back to Dashboard</a>
            </div>

            <div class="text-center mt-3">
                <div id="loader" class="spinner-border text-primary" role="status"></div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        document.getElementById('training_type').addEventListener('change', function () {
            var otherField = document.getElementById('other_training_type');
            otherField.style.display = this.value === 'Other' ? 'block' : 'none';
        });

        document.getElementById('participantForm').addEventListener('submit', function (e) {
            var isValid = true;
            var name = document.getElementById('name').value;
            var personalNumber = document.getElementById('personal_number').value;
            var startDate = document.getElementById('start_date').value;
            var completionDate = document.getElementById('completion_date').value;
            var totalCost = document.getElementById('total_cost_of_participation').value;
            var oracleNumber = document.getElementById('oracle_number').value;
            var consultantName = document.getElementById('consultant_name').value;
            var consultationAmount = document.getElementById('consultation_amount').value;

            if (name === "" || personalNumber === "" || startDate === "" || completionDate === "" || totalCost === "" || oracleNumber === "" || consultantName === "" || consultationAmount === "") {
                isValid = false;
                toastr.error('Please fill out all required fields correctly.');
            }

            if (new Date(completionDate) < new Date(startDate)) {
                isValid = false;
                toastr.error('Completion date must be after the start date.');
            }

            if (!isValid) {
                e.preventDefault();
            } else {
                document.getElementById('loader').style.display = 'block';
            }
        });
    </script>
</body>
</html>