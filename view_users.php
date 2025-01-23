<?php
// Start the session
session_start();

// Initialize the database connection
$conn = new mysqli('localhost', 'root', '', 'npa_training');

// Check for database connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch search and filter inputs
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_location = isset($_GET['location']) ? trim($_GET['location']) : '';
$filter_status = isset($_GET['status']) ? trim($_GET['status']) : '';
$year = isset($_GET['year']) ? (int)$_GET['year'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10000000000; // Number of records per page
$offset = ($page - 1) * $per_page;

// Validate and sanitize inputs
$search = $conn->real_escape_string($search);
$filter_location = $conn->real_escape_string($filter_location);
$filter_status = $conn->real_escape_string($filter_status);

// Construct the base query with filters
$query = "SELECT * FROM participants WHERE 1";

if (!empty($search)) {
    $query .= " AND (name LIKE '%$search%' OR personal_number LIKE '%$search%' OR training_description LIKE '%$search%')";
}
if (!empty($filter_location)) {
    $query .= " AND location = '$filter_location'";
}
if (!empty($filter_status)) {
    $query .= " AND status = '$filter_status'";
}
if ($year > 0) {
    $query .= " AND YEAR(start_date) = $year";
}

// Add pagination to the query
$query .= " LIMIT $offset, $per_page";

// Execute the query
$result = $conn->query($query);

// Handle errors in the query
if (!$result) {
    die("Error executing query: " . $conn->error);
}

// Get total record count for pagination
$count_query = "SELECT COUNT(*) AS total FROM participants WHERE 1";
if (!empty($search)) {
    $count_query .= " AND (name LIKE '%$search%' OR personal_number LIKE '%$search%' OR training_description LIKE '%$search%')";
}
if (!empty($filter_location)) {
    $count_query .= " AND location = '$filter_location'";
}
if (!empty($filter_status)) {
    $count_query .= " AND status = '$filter_status'";
}
if ($year > 0) {
    $count_query .= " AND YEAR(start_date) = $year";
}

$count_result = $conn->query($count_query);
if (!$count_result) {
    die("Error executing count query: " . $conn->error);
}

$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $per_page);

// Initialize the total cost variables
$total_cost_of_participation_all = 0;
$total_consultation_amount_all = 0; // New variable for total consultation amount
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participants List - NPA Training Portal</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Animate.css for Animations -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <!-- Custom Styles -->
    <style>
        /* Global Styles */
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            color: #333;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        header {
            text-align: center;
            padding: 20px 0;
            background: linear-gradient(135deg, #3498db, #2ecc71);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        header p {
            font-size: 0.7rem;
            font-weight: 300;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 10px;
        }

        /* Glassmorphism Card */
        .filter-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            padding: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 10px;
        }

        .filter-card input,
        .filter-card select {
            margin-bottom: 8px;
            border-radius: 6px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            padding: 6px;
            background: rgba(255, 255, 255, 0.9);
            color: #333;
            font-size: 0.8rem;
        }

        .filter-card input:focus,
        .filter-card select:focus {
            border-color: #3498db;
            box-shadow: 0 0 8px rgba(52, 152, 219, 0.5);
        }

        /* Responsive Table */
        .table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .table th,
        .table td {
            padding: 6px;
            text-align: center;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            font-size: 0.8rem; /* Smaller font size for mobile */
        }

        .table th {
            background-color: #3498db;
            color: white;
            font-weight: 600;
            font-size: 0.9rem; /* Slightly larger font size for headers */
        }

        .table tr:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }

        /* Buttons */
        .btn {
            font-size: 0.8rem;
            border-radius: 6px;
            padding: 6px 10px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: #3498db;
            border-color: #3498db;
        }

        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }

        #print-btn {
            background-color: #2ecc71;
            border-color: #2ecc71;
            color: white;
            font-size: 0.8rem;
            margin-bottom: 8px;
        }

        #print-btn:hover {
            background-color: #27ae60;
            border-color: #27ae60;
        }

        /* Pagination */
        .pagination {
            justify-content: center;
            margin-top: 10px;
        }

        .pagination .page-item.active .page-link {
            background-color: #3498db;
            border-color: #3498db;
        }

        .pagination .page-link {
            color: #3498db;
            font-size: 0.8rem;
            padding: 6px 10px;
        }

        /* Total Cost */
        .total-cost {
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 10px;
            text-align: right;
            color: #3498db;
        }

        /* Back Button */
        .back-button {
            display: inline-block;
            margin-top: 10px;
            padding: 6px 10px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: background-color 0.3s ease;
            font-size: 0.8rem;
        }

        .back-button:hover {
            background-color: #2980b9;
        }

        /* Floating Action Button */
        .fab {
            position: fixed;
            bottom: 10px;
            right: 10px;
            background-color: #3498db;
            color: white;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            text-align: center;
            line-height: 35px;
            font-size: 14px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .fab:hover {
            background-color: #2980b9;
            transform: scale(1.1);
        }
    </style>
</head>
<body>
    <header class="animate__animated animate__fadeInDown">
        <h1>Participants List</h1>
        <p>Manage and view all training participants</p>
    </header>

    <div class="container animate__animated animate__fadeInUp">
        <!-- Search and Filter Form -->
        <div class="filter-card">
            <form class="row g-2" id="filter-form">
                <div class="col-12 col-md-6 col-lg-3">
                    <input 
                        type="text" 
                        name="search" 
                        class="form-control" 
                        placeholder="Search by Name, Number, or Training Description" 
                        value="<?= htmlspecialchars($search) ?>"
                    >
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <select name="location" class="form-select">
                        <option value="">Filter by Location</option>
                        <?php
                        $locations = $conn->query("SELECT DISTINCT location FROM participants");
                        while ($loc = $locations->fetch_assoc()):
                        ?>
                            <option value="<?= htmlspecialchars($loc['location']) ?>" <?= $filter_location === $loc['location'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($loc['location']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <select name="status" class="form-select">
                        <option value="">Filter by Status</option>
                        <option value="Completed" <?= $filter_status === 'Completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="In Progress" <?= $filter_status === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                        <option value="Not Started" <?= $filter_status === 'Not Started' ? 'selected' : '' ?>>Not Started</option>
                        <option value="Rescheduled" <?= $filter_status === 'Rescheduled' ? 'selected' : '' ?>>Rescheduled</option>
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <input type="number" name="year" class="form-control" placeholder="Year" min="1960" max="2100" value="<?= htmlspecialchars($year) ?>">
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                </div>
            </form>
        </div>

        <!-- Results Table -->
        <button class="btn" id="print-btn">Print</button>
        <table class="table" id="printable-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Personal Number</th>
                    <th>Location</th>
                    <th>Venue</th> <!-- Added Venue field -->
                    <th>Status</th>
                    <th>Training Description</th>
                    <th>Total Cost</th>
                    <th>Consultation Amount</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php
                        $total_cost = $row['total_cost_of_participation'] ?? $row['number_of_days'] * 50;
                        $total_cost_of_participation_all += $total_cost;
                        $total_consultation_amount_all += $row['consultation_amount']; // Add to total consultation amount
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= htmlspecialchars($row['personal_number']) ?></td>
                            <td><?= htmlspecialchars($row['location']) ?></td>
                            <td><?= htmlspecialchars($row['venue']) ?></td> <!-- Added Venue field -->
                            <td><?= htmlspecialchars($row['status']) ?></td>
                            <td><?= htmlspecialchars($row['training_description']) ?></td>
                            <td><?= number_format($total_cost, 2) ?></td>
                            <td><?= number_format($row['consultation_amount'], 2) ?></td>
                            <td><a href="view_user.php?id=<?= $row['id'] ?>" class="btn btn-info btn-sm">View</a></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="text-center">No records found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Display Total Cost and Total Consultation Amount -->
        <div class="total-cost" id="printable-total-cost">
            <p>Total Cost of Participation for All Participants: <strong><?= number_format($total_cost_of_participation_all, 2) ?></strong></p>
            <p>Total Consultation Amount: <strong><?= number_format($total_consultation_amount_all, 2) ?></strong></p>
        </div>

        <!-- Pagination -->
        <nav>
            <ul class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&search=<?= htmlspecialchars($search) ?>&location=<?= htmlspecialchars($filter_location) ?>&status=<?= htmlspecialchars($filter_status) ?>&year=<?= htmlspecialchars($year) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>

    <!-- Floating Action Button -->
    <a href="add_participant.php" class="fab animate__animated animate__fadeInUp">
        <i class="fas fa-plus"></i>
    </a>

    <!-- Back Button -->
    <section class="container">
        <a href="index.html" class="back-button"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </section>

    <!-- JavaScript for Printing -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#print-btn').click(function() {
                var printWindow = window.open('', '', 'width=800,height=600');
                printWindow.document.write('<html><head><title>Participants List</title>');
                printWindow.document.write('<style> body { font-family: Arial, sans-serif; } .table { width: 100%; border-collapse: collapse; } .table th, .table td { padding: 8px 12px; text-align: center; border: 1px solid #ddd; } .total-cost { font-size: 1.2rem; font-weight: bold; text-align: right; margin-top: 20px; } </style></head><body>');
                printWindow.document.write('<h2>Participants List</h2>');
                printWindow.document.write(document.getElementById('printable-table').outerHTML); // Print only the table
                printWindow.document.write(document.getElementById('printable-total-cost').outerHTML); // Print total cost and consultation amount
                printWindow.document.close();
                printWindow.print();
            });
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>