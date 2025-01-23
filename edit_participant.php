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

// Fetch participant details for editing
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM participants WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $participant = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$participant) {
        die("Participant not found.");
    }
} else {
    die("Invalid participant ID.");
}

// Handle form submission for updating participant details
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'name', 'personal_number', 'designation', 'location', 'training_description', 
        'start_date', 'completion_date', 'number_of_days', 'status', 'training_type', 
        'total_cost_of_participation', 'remark', 'oracle_number', 'consultant_name', 'consultation_amount', 'venue'
    ];

    $data = [];
    foreach ($fields as $field) {
        $data[$field] = $_POST[$field] ?? '';
    }

    $stmt = $pdo->prepare("UPDATE participants SET 
        name = :name,
        personal_number = :personal_number,
        designation = :designation,
        location = :location,
        training_description = :training_description,
        start_date = :start_date,
        completion_date = :completion_date,
        number_of_days = :number_of_days,
        status = :status,
        training_type = :training_type,
        total_cost_of_participation = :total_cost_of_participation,
        remark = :remark,
        oracle_number = :oracle_number,
        consultant_name = :consultant_name,
        consultation_amount = :consultation_amount,
        venue = :venue
        WHERE id = :id");

    $data['id'] = $id;
    $stmt->execute($data);

    header("Location: admin_dashboard.php");
    exit;
}

// Safely escape output
function escape($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Participant</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        /* Global styles */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }

        header {
            text-align: center;
            margin-bottom: 30px;
        }

        header h1 {
            font-size: 2rem;
            color: #2c3e50;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            margin-top: 15px;
            font-weight: bold;
            color: #34495e;
        }

        input, textarea, select {
            padding: 12px;
            font-size: 1rem;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 8px;
            width: 100%;
            box-sizing: border-box;
            transition: all 0.3s;
        }

        input[type="submit"] {
            margin-top: 20px;
            background-color: #2980b9;
            color: white;
            font-weight: bold;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
            padding: 15px;
            border-radius: 5px;
        }

        input[type="submit"]:hover {
            background-color: #21618c;
        }

        footer {
            text-align: center;
            padding: 20px;
            background: #2c3e50;
            color: #fff;
            margin-top: 30px;
            font-size: 0.9rem;
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            .container {
                margin: 20px;
                padding: 15px;
            }

            header h1 {
                font-size: 1.5rem;
            }

            label {
                font-size: 0.9rem;
            }

            input, textarea, select {
                font-size: 1rem;
            }
        }

        /* Focused input styles */
        input:focus, textarea:focus, select:focus {
            border-color: #2980b9;
            outline: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Edit Participant</h1>
        </header>

        <form method="post" action="">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" value="<?= escape($participant['name']) ?>" required>

            <label for="personal_number">Personal Number</label>
            <input type="text" id="personal_number" name="personal_number" value="<?= escape($participant['personal_number']) ?>" required>

            <label for="designation">Designation</label>
            <input type="text" id="designation" name="designation" value="<?= escape($participant['designation']) ?>" required>

            <label for="location">Location</label>
            <input type="text" id="location" name="location" value="<?= escape($participant['location']) ?>" required>

            <label for="venue">Venue</label> <!-- Added Venue field -->
            <input type="text" id="venue" name="venue" value="<?= escape($participant['venue']) ?>" required>

            <label for="training_description">Training Description</label>
            <textarea id="training_description" name="training_description" rows="3" required><?= escape($participant['training_description']) ?></textarea>

            <label for="start_date">Start Date</label>
            <input type="date" id="start_date" name="start_date" value="<?= escape($participant['start_date']) ?>" required>

            <label for="completion_date">Completion Date</label>
            <input type="date" id="completion_date" name="completion_date" value="<?= escape($participant['completion_date']) ?>" required>

            <label for="number_of_days">Number of Days</label>
            <input type="number" id="number_of_days" name="number_of_days" value="<?= escape($participant['number_of_days']) ?>" required>

            <label for="status">Status</label>
            <select id="status" name="status" required>
                <option value="Completed" <?= $participant['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                <option value="In Progress" <?= $participant['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="Not Started" <?= $participant['status'] === 'Not Started' ? 'selected' : '' ?>>Not Started</option>
                <option value="Rescheduled" <?= $participant['status'] === 'Rescheduled' ? 'selected' : '' ?>>Rescheduled</option>
            </select>

            <label for="training_type">Training Type</label>
            <input type="text" id="training_type" name="training_type" value="<?= escape($participant['training_type']) ?>" required>

            <label for="total_cost_of_participation">Total Cost of Participation</label>
            <input type="number" id="total_cost_of_participation" name="total_cost_of_participation" value="<?= escape($participant['total_cost_of_participation']) ?>" required>

            <label for="oracle_number">Oracle Number</label>
            <input type="text" id="oracle_number" name="oracle_number" value="<?= escape($participant['oracle_number']) ?>" required>

            <label for="consultant_name">Name of Consultant</label>
            <input type="text" id="consultant_name" name="consultant_name" value="<?= escape($participant['consultant_name']) ?>" required>

            <label for="consultation_amount">Amount for Consultation</label>
            <input type="number" id="consultation_amount" name="consultation_amount" value="<?= escape($participant['consultation_amount']) ?>" required>

            <label for="remark">Remark</label>
            <textarea id="remark" name="remark" rows="3"><?= escape($participant['remark']) ?></textarea>

            <input type="submit" value="Update Participant">
        </form>
    </div>

    <footer>
        <p>&copy; 2024 NPA Training</p>
    </footer>
</body>
</html>