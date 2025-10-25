<?php
header('Content-Type: application/json');

// Include DB connection
require_once 'db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);
$ticketId = $input['ticket_id'] ?? '';

if (!$ticketId || !is_numeric($ticketId)) {
    echo json_encode(['success' => false, 'message' => 'Invalid ticket ID']);
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM tickets WHERE id = ?");
    $stmt->bind_param("i", $ticketId);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete ticket']);
    }
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>