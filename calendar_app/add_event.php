<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $event_date = $_POST['date'];

    if (empty($title) || empty($description) || empty($event_date)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO events (title, description, event_date) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $title, $description, $event_date);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Event added successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to add event.']);
    }

    $stmt->close();
}
$conn->close();
?>
