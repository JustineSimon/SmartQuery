<?php
header('Content-Type: application/json');

// ✅ Support both GET (manual tests) and POST (production)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['text'])) {
    $text = trim($_GET['text']);
} else {
    $input = json_decode(file_get_contents("php://input"), true);
    $text = trim($input['text'] ?? '');
}

if (empty($text)) {
    echo json_encode(["success" => false, "message" => "No text provided"]);
    exit;
}

// ✅ Your classifier details
$username = 'raine'; // <-- your uClassify username
$classifierName = 'ticket_category_ai';
$readKey = 'Rq3s1OQiLAIC';

// ✅ Build correct uClassify API URL
$encodedText = urlencode($text);
$url = "https://api.uclassify.com/v1/$username/$classifierName/classify/?readKey=$readKey&text=$encodedText";

// ✅ Initialize cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// ✅ Handle API errors
if ($response === false || $httpCode !== 200) {
    echo json_encode([
        "success" => false,
        "message" => "Failed to reach uClassify API",
        "error" => $error,
        "http_code" => $httpCode,
        "raw_response" => $response
    ]);
    exit;
}

// ✅ Parse API response
$data = json_decode($response, true);

if (!$data || !is_array($data)) {
    echo json_encode(["success" => false, "message" => "Invalid response from uClassify"]);
    exit;
}

// ✅ Determine highest probability category
$bestCategory = null;
$bestProb = 0.0;
foreach ($data as $category => $prob) {
    if ($prob > $bestProb) {
        $bestCategory = $category;
        $bestProb = $prob;
    }
}

// ✅ Confidence threshold logic
$threshold = 0.6; // adjust if needed

if ($bestCategory !== null && $bestProb >= $threshold) {
    // Confident classification
    echo json_encode([
        "success" => true,
        "category" => $bestCategory,
        "confidence" => $bestProb
    ]);
} else {
    // Low confidence → mark as Unclassified
    echo json_encode([
        "success" => true,
        "category" => "Unclassified",
        "confidence" => $bestProb
    ]);
}
?>
