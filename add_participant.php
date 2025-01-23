<?php
// Start session for authentication and secure management
session_start();

// Database connection
try {
    $pdo = new PDO('mysql:host=localhost;dbname=npa_training', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $name = $_POST['name'] ?? '';
    $personal_number = $_POST['personal_number'] ?? '';
    $designation = $_POST['designation'] ?? '';
    $location = $_POST['location'] ?? '';
    $training_description = $_POST['training_description'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $completion_date = $_POST['completion_date'] ?? '';
    $number_of_days = $_POST['number_of_days'] ?? '';
    $status = $_POST['status'] ?? '';
    $training_type = $_POST['training_type'] ?? '';
    $total_cost_of_participation = $_POST['total_cost_of_participation'] ?? '';
    $remark = $_POST['remark'] ?? '';
    $oracle_number = $_POST['oracle_number'] ?? '';
    $consultant_name = $_POST['consultant_name'] ?? '';
    $consultation_amount = $_POST['consultation_amount'] ?? '';
    $venue = $_POST['venue'] ?? ''; // Added Venue field

    // Insert data into the database
    $sql = "INSERT INTO participants (
                name, personal_number, designation, location, training_description, 
                start_date, completion_date, number_of_days, status, training_type, 
                total_cost_of_participation, remark, oracle_number, consultant_name, consultation_amount, venue
            ) VALUES (
                :name, :personal_number, :designation, :location, :training_description, 
                :start_date, :completion_date, :number_of_days, :status, :training_type, 
                :total_cost_of_participation, :remark, :oracle_number, :consultant_name, :consultation_amount, :venue
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
    $stmt->bindParam(':venue', $venue); // Added Venue field

    if ($stmt->execute()) {
        header('Location: admin_dashboard.php');
        exit;
    } else {
        $error = "Failed to add participant. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Participant</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        /* General Styles */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            width: 100%;
            max-width: 900px;
            padding: 30px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            color: #2c3e50;
            font-size: 2rem;
            margin-bottom: 20px;
        }

        /* Form Styles */
        form {
            display: flex;
            flex-direction: column;
        }

        label {
            margin: 12px 0 5px;
            font-weight: bold;
            color: #34495e;
        }

        input, textarea, select {
            padding: 12px;
            font-size: 16px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            transition: border-color 0.3s ease-in-out;
        }

        input:focus, textarea:focus, select:focus {
            border-color: #2980b9;
            outline: none;
        }

        button {
            padding: 12px 20px;
            font-size: 18px;
            background-color: #2980b9;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #21618c;
        }

        /* Error Message */
        .error {
            color: #e74c3c;
            text-align: center;
            margin-bottom: 15px;
            font-weight: bold;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            h1 {
                font-size: 1.5rem;
            }

            button {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <h1>Add Participant</h1>

        <!-- Display Error Message if any -->
        <?php if (isset($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <!-- Form -->
        <form method="post" action="">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" required>

            <label for="personal_number">Personal Number</label>
            <input type="text" id="personal_number" name="personal_number" required>

            <label for="designation">Designation</label>
            <input type="text" id="designation" name="designation" required>

            <label for="location">Location</label>
            <input type="text" id="location" name="location" required>

            <label for="venue">Venue</label> <!-- Added Venue field -->
            <input type="text" id="venue" name="venue" required>

            <label for="training_description">Training Description</label>
            <textarea id="training_description" name="training_description" rows="4" required></textarea>

            <label for="start_date">Start Date</label>
            <input type="date" id="start_date" name="start_date" required>

            <label for="completion_date">Completion Date</label>
            <input type="date" id="completion_date" name="completion_date" required>

            <label for="number_of_days">Number of Days</label>
            <input type="number" id="number_of_days" name="number_of_days" required>

            <label for="status">Status</label>
            <select id="status" name="status" required>
                    <<option value="Completed">Completed</option>
                    <option value="In Progress" >In Progress</option>
                    <option value="Not Started" >Not Started</option>
                    <option value="Rescheduled" >Rescheduled</option>
            </select>

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
                        <option value="Other">Other</option> <!-- New "Other" option -->
                    </select>
            </div>

            <div class="col-md-6" id="other_training_type" style="display:none;">
                <label for="other_training_type_input" class="form-label">Please Specify</label>
                <input type="text" name="other_training_type" id="other_training_type_input" class="form-control" placeholder="Enter other training type">
            </div>

            <script>
                // JavaScript to toggle the input field based on the "Other" option selected
                document.getElementById('training_type').addEventListener('change', function () {
                    var otherField = document.getElementById('other_training_type');
                    var selectedValue = this.value;
                    if (selectedValue === 'Other') {
                        otherField.style.display = 'block';
                    } else {
                        otherField.style.display = 'none';
                    }
                });
            </script>

            <label for="total_cost_of_participation">Total Cost of Participation</label>
            <input type="number" id="total_cost_of_participation" name="total_cost_of_participation" step="0.01" required>

            <label for="oracle_number">Oracle Number</label>
            <input type="text" id="oracle_number" name="oracle_number" required>

            <label for="consultant_name">Name of Consultant</label>
            <input type="text" id="consultant_name" name="consultant_name" required>

            <label for="consultation_amount">Amount for Consultation</label>
            <input type="number" id="consultation_amount" name="consultation_amount" step="0.01" required>

            <label for="remark">Remark</label>
            <textarea id="remark" name="remark" rows="4"></textarea>

            <button type="submit">Add Participant</button>
        </form>
    </div>

</body>
</html>