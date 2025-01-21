<?php
include 'db.php';
$data = json_decode(file_get_contents("php://input"));

if (isset($data->id)) {
    $stmt = $conn->prepare("UPDATE events SET title=?, description=?, event_date=? WHERE id=?");
    $stmt->bind_param("sssi", $data->title, $data->description, $data->date, $data->id);
    $stmt->execute();
    $stmt->close();
}
$conn->close();
?>
