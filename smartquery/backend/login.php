<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($username === '' || $password === '') {
    die("Please provide username and password. <a href='/smartquery/login.html'>Go back</a>");
  }

  $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $stmt->store_result();

  if ($stmt->num_rows === 0) {
    $stmt->close();
    $conn->close();
    die("User not found. <a href='/smartquery/register.html'>Register</a>");
  }

  $stmt->bind_result($id, $hashed);
  $stmt->fetch();

  if (password_verify($password, $hashed)) {
    // Success: set session and redirect to protected dashboard
    $_SESSION['user_id'] = $id;
    $_SESSION['username'] = $username;
    $stmt->close();
    $conn->close();
    header("Location: /smartquery/dashboard.php");
    exit;
  } else {
    $stmt->close();
    $conn->close();
    die("Invalid credentials. <a href='/smartquery/login.html'>Try again</a>");
  }
}
?>
