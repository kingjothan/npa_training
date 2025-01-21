<?php
include 'db.php';

$result = $conn->query("SELECT id, title, description, event_date FROM events");

$events = [];
while ($row = $result->fetch_assoc()) {
    $events[] = [
        'id' => $row['id'],
        'title' => $row['title'],
        'description' => $row['description'],
        'start' => $row['event_date']
    ];
}

echo json_encode($events);
$conn->close();
?>
