<?php
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($username === '' || $password === '') {
    die("Please provide username and password. <a href='/smartquery/register.html'>Go back</a>");
  }

  // Check existing user
  $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $stmt->store_result();

  if ($stmt->num_rows > 0) {
    $stmt->close();
    $conn->close();
    die("Username already exists. <a href='/smartquery/register.html'>Try another</a>");
  }
  $stmt->close();

  // Insert new user (hashed password)
  $hash = password_hash($password, PASSWORD_DEFAULT);
  $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
  $stmt->bind_param("ss", $username, $hash);
  if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    // redirect to login (clean)
    header("Location: /smartquery/login.html?registered=1");
    exit;
  } else {
    $stmt->close();
    $conn->close();
    die("Error saving user. <a href='/smartquery/register.html'>Try again</a>");
  }
}
?>
