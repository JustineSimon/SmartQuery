<?php
require_once 'db_connect.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['ticket_id']) || !isset($data['message'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}

$ticket_id = intval($data['ticket_id']);
$message = trim($data['message']);

$stmt = $conn->prepare("INSERT INTO ticket_responses (ticket_id, responder, message, created_at) VALUES (?, 'admin', ?, NOW())");
$stmt->bind_param('is', $ticket_id, $message);

if ($stmt->execute()) {
    // Mark ticket as resolved
    $update = $conn->prepare("UPDATE tickets SET status='resolved' WHERE id=?");
    $update->bind_param('i', $ticket_id);
    $update->execute();

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to send response.']);
}
