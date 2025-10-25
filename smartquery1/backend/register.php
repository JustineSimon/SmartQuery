<?php
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $fullname = trim($_POST['fullname'] ?? ''); // new field
  $email = trim($_POST['email'] ?? ''); // new field
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';

  // Basic validation for username and password
  if ($username === '' || $password === '') {
    die("Please provide username and password. <a href='/smartquery/register.html'>Go back</a>");
  }

  // Check if username already exists in the database
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

  // Hash the password for storage
  $hash = password_hash($password, PASSWORD_DEFAULT);

  // Set the user role as 'user' by default
  $role = 'user';

  // Insert new user into the database
  $stmt = $conn->prepare("INSERT INTO users (fullname, email, username, password, role) VALUES (?, ?, ?, ?, ?)");
  $stmt->bind_param("sssss", $fullname, $email, $username, $hash, $role);

  if ($stmt->execute()) {
    $stmt->close();
    $conn->close();

    // Redirect to login page after successful registration
    header("Location: /smartquery/login.html?registered=1");
    exit;
  } else {
    $stmt->close();
    $conn->close();
    die("Error saving user. <a href='/smartquery/register.html'>Try again</a>");
  }
}
?>
