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
          t.status, 
          t.priority, 
          t.category, 
          t.urgency, 
          t.created_at, 
          u.username 
        FROM tickets t
        JOIN users u ON t.user_id = u.id
        ORDER BY t.created_at DESC";


$result = $conn->query($sql);

$tickets = [];
while ($row = $result->fetch_assoc()) {
  $tickets[] = $row;
}

echo json_encode(["success" => true, "tickets" => $tickets]);
$conn->close();
?>