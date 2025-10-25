<?php
header('Content-Type: application/json');

// Include DB connection (adjust path if needed)
require_once 'db_connect.php';

// Your uClassify details
$username = 'raine';
$classifierName = 'ticket_category_ai';
$readKey = 'Rq3s1OQiLAIC';

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
    $encodedText = urlencode($text);
    $url = "https://api.uclassify.com/v1/$username/$classifierName/classify/?readKey=$readKey&text=$encodedText";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $httpCode !== 200) {
        continue; // Skip on error
    }

    $data = json_decode($response, true);
    if (!$data || !is_array($data)) continue;

    // Find best category
    $bestCategory = null;
    $bestProb = 0.0;
    foreach ($data as $category => $prob) {
        if ($prob > $bestProb) {
            $bestCategory = $category;
            $bestProb = $prob;
        }
    }

    $threshold = 0.6;
    $category = ($bestCategory && $bestProb >= $threshold) ? $bestCategory : 'Unclassified';

    // Update DB
    $stmt = $conn->prepare("UPDATE tickets SET category = ? WHERE id = ?");
    $stmt->bind_param("si", $category, $ticketId);
    if ($stmt->execute()) {
        $updates[] = ['ticketId' => $ticketId, 'category' => $category];
    }
    $stmt->close();
}

$conn->close();
echo json_encode(['success' => true, 'updates' => $updates]);
?>