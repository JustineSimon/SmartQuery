<?php
header('Content-Type: application/json');

// Include DB connection
require_once 'db_connect.php';

// Your uClassify details
$readApiKey = 'Rq3s1OQiLAIC';
$className = 'raine/ticket_priority_ai';

$input = json_decode(file_get_contents('php://input'), true);
$tickets = $input['tickets'] ?? [];

if (empty($tickets) || !is_array($tickets)) {
    echo json_encode(['success' => false, 'message' => 'No tickets provided']);
    exit;
}

$updates = [];
foreach ($tickets as $ticket) {
    $ticketId = $ticket['ticketId'] ?? '';
    $text = trim($ticket['text'] ?? '');
    if (!$ticketId || !$text) continue;

    // Classify via uClassify
    $url = "https://api.uclassify.com/v1/{$className}/classify";
    $payload = json_encode(['texts' => [$text]]);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Token $readApiKey",
            "Content-Type: application/json"
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    if (curl_errno($ch) || !$response) continue;

    $data = json_decode($response, true);
    if (!is_array($data) || !isset($data[0]['classification'])) continue;

    // Find best priority
    $bestLabel = 'Normal';
    $highest = 0;
    foreach ($data[0]['classification'] as $c) {
        if ($c['p'] > $highest) {
            $highest = $c['p'];
            $bestLabel = $c['className'];
        }
    }

    $label = strtolower(trim($bestLabel));
    if ($label === 'urgent') $priority = 'Urgent';
    elseif (in_array($label, ['not urgent', 'not_urgent', 'noturgent'])) $priority = 'Not urgent';
    else $priority = 'Normal';

    // Update DB
    $stmt = $conn->prepare("UPDATE tickets SET priority = ? WHERE id = ?");
    $stmt->bind_param("si", $priority, $ticketId);
    if ($stmt->execute()) {
        $updates[] = ['ticketId' => $ticketId, 'priority' => $priority];
    }
    $stmt->close();
}

$conn->close();
echo json_encode(['success' => true, 'updates' => $updates]);
?>