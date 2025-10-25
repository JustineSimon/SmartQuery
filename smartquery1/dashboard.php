<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: /smartquery/login.html");
  exit;
}
// user is logged in — include the existing dashboard.html content
include __DIR__ . '/dashboard.html';
?>
