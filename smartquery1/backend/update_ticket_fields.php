<?php
include 'db_connect.php';
session_start();
header('Content-Type: application/json');

// 🔒 Admin check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  echo json_encode(["success" => false, "message" => "Unauthorized"]);
  exit;
}

// 🔹 Read JSON input
$data = json_decode(file_get_contents("php://input"), true);
$ticketId = $data['ticket_id'] ?? null;
$field = $data['field'] ?? null;
$value = isset($data['value']) ? trim($data['value']) : null;

// ✅ Validate ticket and field (allow blank values)
if (empty($ticketId) || empty($field)) {
  echo json_encode(["success" => false, "message" => "Missing ticket ID or field name"]);
  exit;
}

// 🔐 Only allow safe fields
$allowed = ['priority', 'category'];
if (!in_array($field, $allowed)) {
  echo json_encode(["success" => false, "message" => "Invalid field"]);
  exit;
}

// 🟢 Prepare update
$stmt = $conn->prepare("UPDATE tickets SET $field = ? WHERE id = ?");
if (!$stmt) {
  echo json_encode(["success" => false, "message" => "Database prepare failed: " . $conn->error]);
  exit;
}

$stmt->bind_param("si", $value, $ticketId);

if ($stmt->execute()) {
  echo json_encode(["success" => true, "message" => "Ticket updated successfully"]);
} else {
  echo json_encode(["success" => false, "message" => "Failed to update ticket: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
