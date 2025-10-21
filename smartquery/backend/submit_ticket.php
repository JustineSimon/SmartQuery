<?php
include 'db_connect.php';
session_start();

header('Content-Type: application/json');

// Check login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "You must be logged in."]);
    exit;
}

$user_id = $_SESSION['user_id'];

// ✅ Handle regular form submission (FormData)
if (isset($_POST['subject']) && isset($_POST['message'])) {
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    $priority = "Normal"; // Default priority
    $attachment_path = null;

    // ✅ Optional: handle file upload
    if (!empty($_FILES['attachment']['name'])) {
        $upload_dir = "../uploads/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $file_name = time() . "_" . basename($_FILES["attachment"]["name"]);
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES["attachment"]["tmp_name"], $target_file)) {
            $attachment_path = $file_name;
        }
    }

    // ✅ Insert into tickets table
    $stmt = $conn->prepare("INSERT INTO tickets (user_id, subject, message, priority, status, created_at, attachment) VALUES (?, ?, ?, ?, 'open', NOW(), ?)");
    $stmt->bind_param("issss", $user_id, $subject, $message, $priority, $attachment_path);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Inquiry submitted successfully!"]);
    } else {
        echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
    }

    $stmt->close();
    $conn->close();
    exit;
}

// ❌ If no valid data
echo json_encode(["success" => false, "message" => "Invalid request."]);
?>