<?php
include 'db_connect.php';
session_start();
header('Content-Type: application/json');

// Admin check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  echo json_encode(["success" => false, "message" => "Unauthorized"]);
  exit;
}

// Get JSON data
$data = json_decode(file_get_contents("php://input"), true);
$ticketId = $data['ticket_id'] ?? null;
$field = $data['field'] ?? null;
$value = $data['value'] ?? null;

if (!$ticketId || !$field || !$value) {
  echo json_encode(["success" => false, "message" => "Invalid input"]);
  exit;
}

// Allow only specific fields for security
$allowed = ['priority', 'category'];
if (!in_array($field, $allowed)) {
  echo json_encode(["success" => false, "message" => "Invalid field"]);
  exit;
}

$stmt = $conn->prepare("UPDATE tickets SET $field = ? WHERE id = ?");
$stmt->bind_param("si", $value, $ticketId);

if ($stmt->execute()) {
  echo json_encode(["success" => true]);
} else {
  echo json_encode(["success" => false, "message" => "Database update failed"]);
}

$stmt->close();
$conn->close();
?>
