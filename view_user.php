<?php
// Start session for secure user management (e.g., authentication)
session_start();

// Database connection using PDO (better than MySQLi)
try {
    $pdo = new PDO('mysql:host=localhost;dbname=npa_training', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("There was an issue connecting to the database. Please try again later.");
}

// Check if 'id' is passed and is numeric
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid ID. Please provide a valid numeric ID.");
}

$id = (int)$_GET['id']; // Type-casting to integer for added security

// Query to fetch participant details using PDO
$sql = "SELECT * FROM participants WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':id', $id, PDO::PARAM_INT);

try {
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("Participant not found.");
    }
} catch (PDOException $e) {
    error_log("SQL Error: " . $e->getMessage());
    die("An error occurred while fetching participant details. Please try again later.");
}

// Function to safely escape output (avoiding XSS)
function escape($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Calculate progress percentage
$startDate = new DateTime($user['start_date']);
$completionDate = new DateTime($user['completion_date']);
$currentDate = new DateTime();
$totalDays = $startDate->diff($completionDate)->days;
$daysCompleted = $startDate->diff($currentDate)->days;
$progressPercentage = min(100, max(0, ($daysCompleted / $totalDays) * 100));

// Calculate the number of days between start date and completion date
$numberOfDays = $startDate->diff($completionDate)->days;

// Closing the PDO connection
$pdo = null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Participant Details - NPA Training Portal</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;600&family=Poppins:wght@500;700&display=swap" rel="stylesheet">
    <!-- Font Awesome for Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Chart.js for Interactive Charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- jsPDF for PDF Export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <!-- Custom Styles -->
    <style>
        /* General Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Body and Layout */
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f4f8;
            color: #333;
            line-height: 1.6;
        }

        /* Header */
        header {
            background: linear-gradient(135deg, #2980b9, #2c3e50);
            color: white;
            padding: 80px 0;
            text-align: center;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            animation: fadeInDown 1s ease-in-out;
        }

        header h1 {
            font-size: 3rem;
            margin: 0;
            text-shadow: 2px 2px 6px rgba(0, 0, 0, 0.2);
        }

        /* Main Content */
        main {
            margin-top: 60px;
            padding: 20px;
            animation: fadeInUp 1s ease-in-out;
        }

        /* Section Styles */
        section h2 {
            font-size: 2rem;
            color: #34495e;
            margin-bottom: 25px;
            font-weight: 600;
            text-align: center;
            animation: fadeIn 1.5s ease-in-out;
        }

        /* Table Styling */
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 0 12px rgba(0, 0, 0, 0.1);
            background-color: #fff;
            animation: slideInLeft 1s ease-in-out;
        }

        .details-table th,
        .details-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .details-table th {
            background-color: #2c3e50;
            color: white;
            width: 220px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .details-table td {
            background-color: #fff;
            color: #555;
        }

        .details-table tr:nth-child(odd) {
            background-color: #f9f9f9;
        }

        .details-table tr:hover {
            background-color: #ecf0f1;
            transform: translateX(4px);
        }

        .details-table tr:nth-child(odd):hover {
            background-color: #dce2e8;
        }

        .details-table td,
        .details-table th {
            padding-left: 20px;
            padding-right: 20px;
        }

        /* Progress Bar */
        .progress-bar {
            width: 100%;
            background-color: #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 10px;
        }

        .progress-bar-fill {
            height: 10px;
            background-color: #3498db;
            border-radius: 10px;
            transition: width 0.5s ease;
        }

        /* Chart Container */
        .chart-container {
            width: 300px;
            height: 300px;
            margin: 0 auto;
            position: relative;
        }

        /* Back Button */
        .back-section {
            text-align: center;
            margin-top: 30px;
            animation: fadeIn 2s ease-in-out;
        }

        .back-button {
            background-color: #3498db;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 10px;
            font-size: 1.2rem;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s, transform 0.3s ease, box-shadow 0.3s ease;
        }

        .back-button:hover {
            background-color: #2980b9;
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .back-button:active {
            transform: scale(1);
        }

        .back-button i {
            margin-right: 8px;
        }

        /* Footer */
        footer {
            background-color: #2c3e50;
            color: white;
            text-align: center;
            padding: 25px 0;
            margin-top: 80px;
            font-size: 1.1rem;
            letter-spacing: 0.5px;
            box-shadow: 0 -4px 10px rgba(0, 0, 0, 0.1);
            animation: fadeInUp 1s ease-in-out;
        }

        /* Animations */
        @keyframes fadeInDown {
            0% {
                opacity: 0;
                transform: translateY(-50px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            0% {
                opacity: 0;
                transform: translateY(50px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            0% {
                opacity: 0;
            }
            100% {
                opacity: 1;
            }
        }

        @keyframes slideInLeft {
            0% {
                opacity: 0;
                transform: translateX(-50px);
            }
            100% {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Responsive Design */
        @media screen and (max-width: 768px) {
            .details-table th, .details-table td {
                padding: 12px;
            }

            .back-button {
                font-size: 1rem;
                padding: 12px 25px;
            }

            header h1 {
                font-size: 2.5rem;
            }

            section h2 {
                font-size: 1.6rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>Participant Details</h1>
        </div>
    </header>

    <main>
        <div class="container">
            <section>
                <h2>Participant Information</h2>
                <table class="details-table">
                    <tr>
                        <th>Name</th>
                        <td><?= escape($user['name']) ?></td>
                    </tr>
                    <tr>
                        <th>Personal Number</th>
                        <td><?= escape($user['personal_number']) ?></td>
                    </tr>
                    <tr>
                        <th>Oracle Number</th>
                        <td><?= isset($user['oracle_number']) ? escape($user['oracle_number']) : 'N/A' ?></td>
                    </tr>
                    <tr>
                        <th>Designation</th>
                        <td><?= escape($user['designation']) ?></td>
                    </tr>
                    <tr>
                        <th>Participant Location</th>
                        <td><?= escape($user['location']) ?></td>
                    </tr>
                    <tr>
                        <th>Course Location</th> <!-- Added Venue field -->
                        <td><?= escape($user['venue']) ?></td>
                    </tr>
                    <tr>
                        <th>Training Description</th>
                        <td><?= escape($user['training_description']) ?></td>
                    </tr>
                    <tr>
                        <th>Start Date</th>
                        <td><?= escape($user['start_date']) ?></td>
                    </tr>
                    <tr>
                        <th>Completion Date</th>
                        <td><?= escape($user['completion_date']) ?></td>
                    </tr>
                    <tr>
                        <th>Number of Days</th>
                        <td><?= $numberOfDays ?> days</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            <?= escape($user['status']) ?>
                            <div class="progress-bar">
                                <div class="progress-bar-fill" style="width: <?= $progressPercentage ?>%;"></div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th>Training Type</th>
                        <td><?= escape($user['training_type']) ?></td>
                    </tr>
                    <tr>
                        <th>Total Cost of Participation</th>
                        <td><?= escape($user['total_cost_of_participation']) ?></td>
                    </tr>
                    <tr>
                        <th>Name of Consultant</th>
                        <td><?= isset($user['consultant_name']) ? escape($user['consultant_name']) : 'N/A' ?></td>
                    </tr>
                    <tr>
                        <th>Consultant Fee</th>
                        <td><?= isset($user['consultation_amount']) ? escape($user['consultation_amount']) : 'N/A' ?></td>
                    </tr>
                    <tr>
                        <th>Remark</th>
                        <td><?= escape($user['remark']) ?></td>
                    </tr>
                </table>
            </section>

            <!-- Interactive Chart -->
            <section>
                <h2>Training Progress</h2>
                <div class="chart-container">
                    <canvas id="trainingChart"></canvas>
                </div>
            </section>

            <!-- Back and Edit Buttons -->
            <section class="back-section">
                <a href="index.html" class="back-button"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                <a href="edit_participant.php?id=<?= $id ?>" class="back-button"><i class="fas fa-edit"></i> Edit Participant</a>
                <button class="back-button" onclick="downloadPDF()"><i class="fas fa-download"></i> Download PDF</button>
            </section>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2024 NPA Training Portal. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Chart.js for Training Progress
        const ctx = document.getElementById('trainingChart').getContext('2d');
        const trainingChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'Remaining'],
                datasets: [{
                    label: 'Training Progress',
                    data: [<?= $progressPercentage ?>, <?= 100 - $progressPercentage ?>],
                    backgroundColor: [
                        'rgba(52, 152, 219, 0.8)', // Blue for completed
                        'rgba(224, 224, 224, 0.8)' // Gray for remaining
                    ],
                    borderColor: [
                        'rgba(52, 152, 219, 1)',
                        'rgba(224, 224, 224, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: {
                                size: 14
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.label}: ${context.raw}%`;
                            }
                        }
                    }
                }
            }
        });

        // Download PDF Functionality
        function downloadPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            // Set margins and initial Y position
            const margin = 10;
            let y = margin;

            // Add header with background color
            doc.setFillColor(41, 128, 185); // Blue background
            doc.rect(0, 0, doc.internal.pageSize.getWidth(), 20, 'F');
            doc.setFontSize(18);
            doc.setTextColor(255, 255, 255); // White text
            doc.text("Participant Details", margin, 15);

            // Reset text color and font size for content
            doc.setTextColor(0, 0, 0); // Black text
            doc.setFontSize(12);

            // Define participant details
            const details = [
                { label: "Name", value: '<?= escape($user['name']) ?>' },
                { label: "Personal Number", value: '<?= escape($user['personal_number']) ?>' },
                { label: "Oracle Number", value: '<?= isset($user['oracle_number']) ? escape($user['oracle_number']) : 'N/A' ?>' },
                { label: "Designation", value: '<?= escape($user['designation']) ?>' },
                { label: "Participant Location", value: '<?= escape($user['location']) ?>' },
                { label: "Course Location", value: '<?= escape($user['venue']) ?>' }, // Added Venue field
                { label: "Training Description", value: '<?= escape($user['training_description']) ?>' },
                { label: "Start Date", value: '<?= escape($user['start_date']) ?>' },
                { label: "Completion Date", value: '<?= escape($user['completion_date']) ?>' },
                { label: "Number of Days", value: '<?= $numberOfDays ?> days' },
                { label: "Status", value: '<?= escape($user['status']) ?>' },
                { label: "Training Type", value: '<?= escape($user['training_type']) ?>' },
                { label: "Total Cost", value: '<?= escape($user['total_cost_of_participation']) ?>' },
                { label: "Name of Consultant", value: '<?= isset($user['consultant_name']) ? escape($user['consultant_name']) : 'N/A' ?>' },
                { label: "Consultant Fee", value: '<?= isset($user['consultation_amount']) ? escape($user['consultation_amount']) : 'N/A' ?>' },
                { label: "Remark", value: '<?= escape($user['remark']) ?>' }
            ];

            // Add details to the PDF
            y += 20; // Move Y position down after the header
            details.forEach(detail => {
                if (detail.label === "Training Description") {
                    // Handle Training Description separately to wrap long text
                    const splitText = doc.splitTextToSize(detail.value, 180); // Wrap text to fit within 180mm width
                    doc.setFontSize(12);
                    doc.setFont(undefined, 'bold');
                    doc.text(`${detail.label}:`, margin, y);
                    doc.setFont(undefined, 'normal');
                    y += 10; // Move Y position down for the description text
                    doc.text(splitText, margin + 10, y);
                    y += splitText.length * 10; // Adjust Y position based on the number of lines
                } else {
                    // Add other details
                    doc.setFontSize(12);
                    doc.setFont(undefined, 'bold');
                    doc.text(`${detail.label}:`, margin, y);
                    doc.setFont(undefined, 'normal');
                    doc.text(detail.value, margin + 50, y);
                    y += 10; // Move Y position down for the next line
                }
            });

            // Add progress bar to PDF
            y += 10; // Add some space before the progress bar
            doc.setFontSize(12);
            doc.setFont(undefined, 'bold');
            doc.text("Progress:", margin, y);
            doc.setFont(undefined, 'normal');
            y += 10;

            // Draw progress bar background
            doc.setFillColor(224, 224, 224); // Light gray background
            doc.rect(margin, y, 180, 10, 'F');

            // Draw progress bar fill
            doc.setFillColor(52, 152, 219); // Blue fill
            doc.rect(margin, y, 180 * (<?= $progressPercentage ?> / 100), 10, 'F');

            // Add progress percentage text
            y += 20;
            doc.text(`Progress: ${<?= $progressPercentage ?>}%`, margin, y);

            // Add footer
            y += 20;
            doc.setFontSize(10);
            doc.setTextColor(100, 100, 100); // Gray text
            doc.text("Generated by NPA Training Portal", margin, y);

            // Save the PDF
            doc.save('participant_details.pdf');
        }
    </script>
</body>
</html>