<?php
include 'db_connect.php';
session_start();

header('Content-Type: application/json');

// Only admin can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  echo json_encode(["success" => false, "message" => "Unauthorized"]);
  exit;
}

$sql = "SELECT 
          t.id, 
          t.subject, 
          t.message, 
          t.category, 
          t.status, 
          t.priority, 
          t.created_at, 
          u.username 
        FROM tickets t
        JOIN users u ON t.user_id = u.id
        ORDER BY t.created_at DESC";

$result = $conn->query($sql);

if (!$result) {
  echo json_encode(["success" => false, "message" => "Database query failed"]);
  exit;
}

$tickets = [];
while ($row = $result->fetch_assoc()) {
  $tickets[] = [
    "id" => $row["id"],
    "username" => $row["username"],
    "subject" => $row["subject"],
    "message" => $row["message"],
    "category" => $row["category"],
    "priority" => $row["priority"],
    "status" => $row["status"],
    "created_at" => $row["created_at"]
  ];
}

echo json_encode(["success" => true, "tickets" => $tickets]);
$conn->close();
?>
