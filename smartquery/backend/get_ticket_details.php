<?php
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing ticket ID']);
    exit;
}

$ticket_id = intval($_GET['id']);

$stmt = $conn->prepare("
    SELECT t.id, t.subject, t.status, t.priority, t.created_at, u.username
    FROM tickets t
    JOIN users u ON t.user_id = u.id
    WHERE t.id = ?
");
$stmt->bind_param('i', $ticket_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Ticket not found.']);
    exit;
}

$ticket = $result->fetch_assoc();

// Fetch all responses
$res_stmt = $conn->prepare("
    SELECT responder, message, created_at 
    FROM ticket_responses 
    WHERE ticket_id = ? 
    ORDER BY created_at ASC
");
$res_stmt->bind_param('i', $ticket_id);
$res_stmt->execute();
$res_result = $res_stmt->get_result();

$responses = [];
while ($row = $res_result->fetch_assoc()) {
    $responses[] = $row;
}

echo json_encode(['success' => true, 'ticket' => $ticket, 'responses' => $responses]);