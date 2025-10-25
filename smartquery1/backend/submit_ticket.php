<?php
include 'db_connect.php';
session_start();
header('Content-Type: application/json');

// Check login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "You must be logged in."]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Validate form fields
if (!isset($_POST['subject']) || !isset($_POST['message'])) {
    echo json_encode(["success" => false, "message" => "Invalid request."]);
    exit;
}

$subject = trim($_POST['subject']);
$message = trim($_POST['message']);
$priority = "";   // blank instead of "Normal"
$category = "";   // blank instead of "Refund"
$attachment_path = null;

// Handle optional file upload
if (!empty($_FILES['attachment']['name'])) {
    $upload_dir = __DIR__ . "/../uploads/";
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $orig_name = basename($_FILES["attachment"]["name"]);
    $orig_name = preg_replace('/[^a-zA-Z0-9_\.\-]/', '_', $orig_name);
    $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','pdf','txt'];

    if (!in_array($ext, $allowed)) {
        echo json_encode(["success" => false, "message" => "Invalid file type. Allowed: " . implode(',', $allowed)]);
        exit;
    }

    $file_name = time() . "_" . $orig_name;
    $target_file = $upload_dir . $file_name;

    if (move_uploaded_file($_FILES["attachment"]["tmp_name"], $target_file)) {
        $attachment_path = $file_name;
    }
}

// Insert ticket with blank category & priority
$stmt = $conn->prepare("
    INSERT INTO tickets (user_id, subject, message, category, priority, status, created_at, attachment)
    VALUES (?, ?, ?, ?, ?, 'Open', NOW(), ?)
");
if (!$stmt) {
    echo json_encode(["success" => false, "message" => "DB prepare failed: " . $conn->error]);
    exit;
}

$stmt->bind_param("isssss", $user_id, $subject, $message, $category, $priority, $attachment_path);

if (!$stmt->execute()) {
    $err = $stmt->error;
    $stmt->close();
    $conn->close();
    echo json_encode(["success" => false, "message" => "Database error: $err"]);
    exit;
}

$ticket_id = $stmt->insert_id;
$stmt->close();
$conn->close();

echo json_encode([
    "success" => true,
    "message" => "Inquiry submitted successfully!",
    "ticket_id" => $ticket_id,
    "category" => $category,
    "priority" => $priority
]);
exit;
?>
