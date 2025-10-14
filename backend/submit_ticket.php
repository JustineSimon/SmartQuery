<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

include "db_connect.php";

session_start();

// For now, use a placeholder user_id if not logged in
$user_id = $_SESSION['user_id'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    $attachment = null;

    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === 0) {
        $upload_dir = __DIR__ . "/uploads/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = time() . "_" . basename($_FILES['attachment']['name']);
        $target_path = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $target_path)) {
            $attachment = $file_name;
        }
    }

    if (!empty($subject) && !empty($message)) {
        $stmt = $conn->prepare("INSERT INTO tickets (user_id, subject, message, attachment, status, created_at) VALUES (?, ?, ?, ?, 'Open', NOW())");
        $stmt->bind_param("isss", $user_id, $subject, $message, $attachment);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Inquiry submitted successfully."]);
        } else {
            echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Subject and message are required."]);
    }

} else {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
}
?>
