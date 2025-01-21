<?php
include 'db.php';

$data = json_decode(file_get_contents('php://input'), true);
$event_id = $data['id'];

$stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
$stmt->bind_param("i", $event_id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Event deleted successfully.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to delete event.']);
}

$stmt->close();
$conn->close();
?>
