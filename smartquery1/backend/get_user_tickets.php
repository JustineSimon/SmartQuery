<?php
include 'db_connect.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  echo json_encode(["success" => false, "message" => "You must be logged in."]);
  exit;
}

$user_id = (int)$_SESSION['user_id'];

$sql = "SELECT id, subject, status, priority, created_at 
        FROM tickets 
        WHERE user_id = ? 
        ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$tickets = [];
while ($row = $result->fetch_assoc()) {
  $tickets[] = $row;
}

echo json_encode(["success" => true, "tickets" => $tickets]);

$stmt->close();
$conn->close();
?>
