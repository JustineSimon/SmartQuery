<?php
header('Content-Type: application/json');

// 🔒 Replace with your own uClassify credentials
$readApiKey = 'Rq3s1OQiLAIC';
$className = 'raine/ticket_priority_ai'; // Example: "diego/priority-classifier"

// get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$text = isset($input['text']) ? trim($input['text']) : '';

if ($text === '') {
    echo json_encode(['success' => false, 'message' => 'No text provided']);
    exit;
}

try {
    // uClassify classify endpoint
    $url = "https://api.uclassify.com/v1/{$className}/classify";

    // Prepare request
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

    if (curl_errno($ch)) {
        throw new Exception(curl_error($ch));
    }
    curl_close($ch);

    $data = json_decode($response, true);
    if (!is_array($data) || !isset($data[0]['classification'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid response', 'raw' => $response]);
        exit;
    }

    // Find label with highest confidence
    $bestLabel = 'Normal';
    $highest = 0;
    foreach ($data[0]['classification'] as $c) {
        if ($c['p'] > $highest) {
            $highest = $c['p'];
            $bestLabel = $c['className'];
        }
    }

    // Normalize possible variants (e.g. lowercase, underscores, etc.)
    $label = strtolower(trim($bestLabel));
    if ($label === 'urgent') $label = 'Urgent';
    elseif ($label === 'not urgent' || $label === 'not_urgent' || $label === 'noturgent') $label = 'Not urgent';
    else $label = 'Normal';

    echo json_encode(['success' => true, 'priority' => $label]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
