<?php
// src/auth/logout.php
session_start();

// Destroy all session data
$_SESSION = array();
session_destroy();

// Redirect back to login
header("Location: login.html");
exit;
?>
