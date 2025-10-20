<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($username === '' || $password === '') {
    die("Please provide username and password. <a href='/smartquery/login.html'>Go back</a>");
  }

  $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username = ?");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $stmt->store_result();

  if ($stmt->num_rows === 0) {
    $stmt->close();
    $conn->close();
    die("User not found. <a href='/smartquery/register.html'>Register</a>");
  }

  $stmt->bind_result($id, $hashed, $role);
  $stmt->fetch();

  if (password_verify($password, $hashed)) {
    $_SESSION['user_id'] = $id;
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $role;
    $stmt->close();
    $conn->close();

    // ✅ Redirect based on user role
    if ($role === 'admin') {
  header("Location: /smartquery/dashboard.php");
} elseif ($role === 'user') {
  header("Location: /smartquery/user_home.html");
} else {
  // Default fallback
  header("Location: /smartquery/login.html?error=invalid_role");
}

    exit;

  } else {
    $stmt->close();
    $conn->close();
    die("Invalid credentials. <a href='/smartquery/login.html'>Try again</a>");
  }
}
?>
