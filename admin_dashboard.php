<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Logout functionality
if (isset($_GET['logout'])) {
    session_unset(); // Clear session variables
    session_destroy(); // Destroy session
    header('Location: index.html');
    exit;
}

// Database connection with error handling
try {
    $pdo = new PDO('mysql:host=localhost;dbname=npa_training', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle delete request with validation
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM participants WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    header('Location: admin_dashboard.php');
    exit;
}

// Handle search query with validation
$search = $_GET['search'] ?? '';
$query = "SELECT * FROM participants";
if ($search) {
    $query .= " WHERE name LIKE :search OR personal_number LIKE :search";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
} else {
    $stmt = $pdo->prepare($query);
}
$stmt->execute();
$participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get participant count per training type
$trainingQuery = "SELECT training_type, COUNT(*) as count FROM participants GROUP BY training_type";
$trainingStmt = $pdo->prepare($trainingQuery);
$trainingStmt->execute();
$trainingTypes = $trainingStmt->fetchAll(PDO::FETCH_ASSOC);

// Get total number of participants
$totalParticipantsQuery = "SELECT COUNT(*) as total FROM participants";
$totalParticipantsStmt = $pdo->prepare($totalParticipantsQuery);
$totalParticipantsStmt->execute();
$totalParticipants = $totalParticipantsStmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get total cost of participation
$totalCostQuery = "SELECT SUM(total_cost_of_participation) as total_cost FROM participants";
$totalCostStmt = $pdo->prepare($totalCostQuery);
$totalCostStmt->execute();
$totalCost = $totalCostStmt->fetch(PDO::FETCH_ASSOC)['total_cost'];

// Get total consultation amount
$totalConsultationQuery = "SELECT SUM(consultation_amount) as total_consultation FROM participants";
$totalConsultationStmt = $pdo->prepare($totalConsultationQuery);
$totalConsultationStmt->execute();
$totalConsultation = $totalConsultationStmt->fetch(PDO::FETCH_ASSOC)['total_consultation'];

// Get recent participants (last 5 added)
$recentParticipantsQuery = "SELECT * FROM participants ORDER BY id DESC LIMIT 5";
$recentParticipantsStmt = $pdo->prepare($recentParticipantsQuery);
$recentParticipantsStmt->execute();
$recentParticipants = $recentParticipantsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get participant status distribution
$statusQuery = "SELECT status, COUNT(*) as count FROM participants GROUP BY status";
$statusStmt = $pdo->prepare($statusQuery);
$statusStmt->execute();
$statusDistribution = $statusStmt->fetchAll(PDO::FETCH_ASSOC);

// Function to escape output safely
function escape($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - NPA Training Portal</title>
    <!-- Font Awesome for Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts for Better Typography -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Chart.js for Charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom Styles -->
    <style>
        /* Global Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f7f6;
            color: #333;
            line-height: 1.6;
        }
        h1, h2 {
            color: #2c3e50;
        }

        /* Header Styles */
        header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: #fff;
            padding: 20px 0;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        header h1 {
            margin-bottom: 10px;
            font-size: 2.5rem;
            font-weight: 600;
        }
        .logout-btn {
            background: #e74c3c;
            color: #fff;
            padding: 10px 20px;
            font-size: 16px;
            text-decoration: none;
            border-radius: 30px;
            transition: background 0.3s ease, transform 0.3s ease;
        }
        .logout-btn:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        /* Container Styles */
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        /* Welcome Text */
        .welcome-text {
            font-size: 1.2rem;
            color: #2c3e50;
            margin-bottom: 30px;
            text-align: center;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        /* Stats Cards */
        .stats-container {
            display: flex;
            justify-content: space-around;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        .stats-card {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 20%;
            margin: 10px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }
        .stats-card h3 {
            font-size: 1.5rem;
            color: #3498db;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .stats-card p {
            font-size: 1.2rem;
            color: #555;
        }

        /* Search Bar */
        .search-bar {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        .search-bar input[type="text"] {
            width: 300px;
            padding: 12px;
            font-size: 16px;
            border: 2px solid #3498db;
            border-radius: 30px;
            margin-right: 10px;
            outline: none;
            transition: border-color 0.3s ease;
        }
        .search-bar input[type="text"]:focus {
            border-color: #2980b9;
        }
        .search-bar button {
            background: #3498db;
            color: #fff;
            padding: 12px 20px;
            font-size: 16px;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.3s ease;
        }
        .search-bar button:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        /* Add Participant Button */
        .add-participant-btn {
            display: inline-block;
            background: linear-gradient(135deg, #3498db, #6dd5ed);
            color: white;
            padding: 12px 30px;
            font-size: 16px;
            border-radius: 50px;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 30px;
        }
        .add-participant-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            border-radius: 10px;
            overflow: hidden;
        }
        table th, table td {
            padding: 12px 15px;
            text-align: left;
        }
        table th {
            background: #34495e;
            color: #fff;
            text-transform: uppercase;
            font-size: 14px;
            font-weight: 600;
        }
        table tr:nth-child(even) {
            background: #f4f4f4;
        }
        table tr:hover {
            background: #f9f9f9;
        }
        .actions a {
            padding: 8px 15px;
            border-radius: 5px;
            font-weight: bold;
            text-decoration: none;
            color: #fff;
            font-size: 14px;
            transition: opacity 0.3s ease;
        }
        .actions a:hover {
            opacity: 0.9;
        }
        .actions .edit {
            background-color: #27ae60;
        }
        .actions .delete {
            background-color: #e74c3c;
        }

        /* Chart Container Styles */
        .charts-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        .chart-container {
            width: 48%; /* Adjusted width for side-by-side layout */
            background: #fff;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .chart-container canvas {
            max-height: 200px; /* Smaller chart height */
        }

        /* Recent Participants Section */
        .recent-participants {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        .recent-participants h2 {
            font-size: 1.8rem;
            color: #2c3e50;
            margin-bottom: 20px;
            font-weight: 600;
        }

        /* Footer Styles */
        footer {
            text-align: center;
            padding: 20px;
            background: #2c3e50;
            color: #fff;
            margin-top: 40px;
            box-shadow: 0 -4px 15px rgba(0, 0, 0, 0.1);
        }

        /* Media Queries */
        @media (max-width: 768px) {
            .stats-container {
                flex-direction: column;
                align-items: center;
            }
            .stats-card {
                width: 80%;
                margin-bottom: 20px;
            }
            .search-bar {
                flex-direction: column;
                align-items: center;
            }
            .search-bar input[type="text"] {
                margin-bottom: 10px;
            }
            .charts-container {
                flex-direction: column;
            }
            .chart-container {
                width: 100%;
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>Admin Dashboard</h1>
        <a href="?logout=true" class="logout-btn">Logout</a>
    </header>

    <div class="container">
        <div class="welcome-text">
            <p>Welcome, <?= escape($_SESSION['username']) ?>! You can manage all participants and view training statistics below.</p>
        </div>

        <!-- Stats Cards -->
        <div class="stats-container">
            <div class="stats-card">
                <h3>Total Participants</h3>
                <p><?= escape($totalParticipants) ?></p>
            </div>
            <div class="stats-card">
                <h3>Total Cost</h3>
                <p><?= escape(number_format($totalCost, 2)) ?></p>
            </div>
            <div class="stats-card">
                <h3>Consultant fees</h3>
                <p><?= escape(number_format($totalConsultation, 2)) ?></p>
            </div>
            <div class="stats-card">
                <h3>Training Types</h3>
                <p><?= escape(count($trainingTypes)) ?></p>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="search-bar">
            <form method="get" action="">
                <input type="text" name="search" placeholder="Search participants..." value="<?= escape($search) ?>" required>
                <button type="submit">Search</button>
            </form>
        </div>

        <!-- Charts Container -->
        <div class="charts-container">
            <!-- Training Types Chart -->
            <div class="chart-container">
                <canvas id="trainingChart"></canvas>
            </div>

            <!-- Status Distribution Chart -->
            <div class="chart-container">
                <canvas id="statusChart"></canvas>
            </div>
        </div>

        <!-- Add Participant Button -->
        <a href="add_participant.php" class="add-participant-btn">Add Participant</a>

        <!-- Participants Table -->
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Personal Number</th>
                    <th>Designation</th>
                    <th>Location</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($participants): ?>
                    <?php foreach ($participants as $participant): ?>
                        <tr>
                            <td><?= escape($participant['name']) ?></td>
                            <td><?= escape($participant['personal_number']) ?></td>
                            <td><?= escape($participant['designation']) ?></td>
                            <td><?= escape($participant['location']) ?></td>
                            <td class="actions">
                                <a href="edit_participant.php?id=<?= escape($participant['id']) ?>" class="edit">Edit</a>
                                <a href="?delete=<?= escape($participant['id']) ?>" class="delete" onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">No participants found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Chart Script -->
        <script>
            const trainingTypes = <?= json_encode($trainingTypes) ?>;
            const labels = trainingTypes.map(item => item.training_type);
            const data = trainingTypes.map(item => item.count);

            const ctx = document.getElementById('trainingChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Number of Participants per Training Type',
                        data: data,
                        backgroundColor: [
                            '#3498db', '#2ecc71', '#e74c3c', '#f1c40f', '#9b59b6'
                        ],
                        borderColor: [
                            '#2980b9', '#27ae60', '#c0392b', '#f39c12', '#8e44ad'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            const statusDistribution = <?= json_encode($statusDistribution) ?>;
            const statusLabels = statusDistribution.map(item => item.status);
            const statusData = statusDistribution.map(item => item.count);

            const statusCtx = document.getElementById('statusChart').getContext('2d');
            new Chart(statusCtx, {
                type: 'pie',
                data: {
                    labels: statusLabels,
                    datasets: [{
                        label: 'Participant Status Distribution',
                        data: statusData,
                        backgroundColor: [
                            '#3498db', '#2ecc71', '#e74c3c', '#f1c40f', '#9b59b6'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                }
            });
        </script>
    </div>

    <footer>
        &copy; <?= date('Y') ?> NPA Training. All rights reserved.
    </footer>
</body>
</html>