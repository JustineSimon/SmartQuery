<?php
session_start();
$_SESSION = [];
session_destroy();
header("Location: /smartquery/login.html");
exit;
?>
